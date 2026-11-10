import { type Locator, type Page } from '@playwright/test'
import { DOOR_URL, WHOS_IN_URL, goToDoor, serveClass } from '../support/door'
import { checkLayout, expect, test } from '../support/test'
import {
    DOOR,
    DUBAI,
    SATELLITES,
    doorSaturday,
    sessionFile,
    type DoorProject,
} from '../support/world'

/*
 * An admin opens and closes a class for the day (spec item 15; Roy 2026-11-10: the class, not "check-in"). On its
 * own one-time class for the door's Saturday — so the shared 1PM the other journeys use stays open — an admin opens
 * it, a Lifeshaper checks a child in, the admin closes it, and the Lifeshaper's next check-in is refused; the door
 * then shows the class closed, its list with no Undo. The class is retired at the end.
 */

/** This browser's own Cruz family at the door (E2ESeeder::doorFamilies). */
function family(page: Page, project: string): Locator {
    return page
        .getByTestId('door-family')
        .filter({ hasText: `${DOOR.guardians[project as DoorProject]} Cruz` })
}

function child(card: Locator, nickname: string): Locator {
    return card
        .getByTestId('door-child')
        .filter({ has: card.page().getByTestId('child-name').getByText(nickname, { exact: true }) })
}

test('an admin opens a class, a Lifeshaper checks a child in, the admin closes it and the next check-in is refused @story#202 @story#204 @story#106 @story#109', async ({
    browser,
}, info) => {
    // Two people, a class made, opened, closed and retired: late in a full run Safari takes over a minute.
    test.setTimeout(90_000)
    const admin = await browser.newContext({ storageState: sessionFile('admin') })
    const adminPage = await admin.newPage()

    // The admin's own class for today, late, so it never comes before 1PM at the door.
    const name = `Late ${Math.random().toString(36).slice(2, 6)}`
    await adminPage.goto(`/${DUBAI}/admin/classes`)
    await adminPage.getByTestId('open-add-schedule').click()
    const add = adminPage.getByTestId('add-schedule')
    await add.getByTestId('schedule-name').fill(name)
    await add.getByTestId('schedule-one-time').click()
    await add.getByTestId('schedule-date').fill(doorSaturday())
    await add.getByTestId('schedule-starts-at').fill('22:00')
    await add.getByTestId('schedule-ends-at').fill('23:00')
    await add.getByTestId('schedule-submit').click()
    await expect(adminPage.getByTestId('flash-success')).toContainText(`Added "${name}"`)
    const lifeshaper = await browser.newContext({ storageState: sessionFile('lifeshaper') })
    try {
        // Not open yet: on Serve a Lifeshaper sees the class, but no way to its door, and no Open button.
        const door = await lifeshaper.newPage()
        await door.goto(`/${DUBAI}/serve`)
        const waiting = serveClass(door, name)
        await expect(waiting.getByTestId('serve-class-state')).toHaveText('Not open yet')
        await expect(waiting.getByTestId('serve-door')).toHaveCount(0)
        await expect(waiting.getByTestId('serve-open')).toHaveCount(0)

        // The admin opens it from its card on Serve, and lands on its own door.
        await adminPage.goto(`/${DUBAI}/serve`)
        await checkLayout(adminPage)
        await serveClass(adminPage, name).getByTestId('serve-open').click()
        await expect(adminPage).toHaveURL(DOOR_URL)
        await expect(adminPage.getByTestId('flash-success')).toContainText(`${name} is open.`)
        await expect(adminPage.getByTestId('door-heading')).toHaveText(name)
        await expect(adminPage.getByTestId('session-state')).toHaveText('Open')

        // The Lifeshaper goes from Serve to its door and checks Amara in.
        await goToDoor(door, name)
        await door.getByTestId('door-search').fill('Cruz')
        await door.getByTestId('door-search').press('Enter')
        const card = family(door, info.project.name)
        await child(card, DOOR.cruz.allergic).getByTestId('tick-child').check()
        await card.getByTestId('check-in').click()
        await expect(door.getByTestId('flash-success')).toContainText(
            `Checked in ${DOOR.cruz.allergic}.`,
        )
        // Who's in, at the same code (spec item 16), lists her — on a second tab, so the door keeps its search.
        const roll = await lifeshaper.newPage()
        await roll.goto(door.url().replace(/\/checkin.*$/, '/whos-in'))
        await expect(roll).toHaveURL(WHOS_IN_URL)
        await expect(roll.getByTestId('child-name')).toHaveText([DOOR.cruz.allergic])

        // Ben is ticked — and meanwhile the admin closes the class, after a second tap.
        await child(card, DOOR.cruz.active).getByTestId('tick-child').check()
        await adminPage.reload()
        await adminPage.getByTestId('session-close').click()
        await expect(adminPage.getByTestId('session-close-confirm')).toContainText(
            `Close ${name}? Nothing can change after`,
        )
        await checkLayout(adminPage)
        await adminPage.getByTestId('confirm-close').click()
        await expect(adminPage.getByTestId('flash-success')).toContainText(`${name} is closed.`)
        await expect(adminPage.getByTestId('session-open')).toHaveText(`Open ${name} again`)

        // The Lifeshaper's check-in is refused, and the door now shows the class closed: no search, no Undo.
        await card.getByTestId('check-in').click()
        await expect(door.getByTestId('flash-error')).toContainText(`${name} is closed.`)
        await expect(door.getByTestId('session-controls')).toContainText(`${name} is closed.`)
        await expect(door.getByTestId('door-search')).toHaveCount(0)
        await expect(door.getByTestId('checked-in-row')).toHaveText([
            new RegExp(DOOR.cruz.allergic),
        ])
        await expect(door.getByTestId('checked-in-undo')).toHaveCount(0)
        await expect(door.getByTestId('bar-satellite')).toContainText(SATELLITES.dxb)
        // A closed class's Who's in stays readable — no taps (spec item 16).
        await roll.reload()
        await expect(roll.getByTestId('child-name')).toHaveText([DOOR.cruz.allergic])
        await expect(roll.getByTestId('present-toggle')).toBeDisabled()
    } finally {
        // Retired even when a step failed, so the door's Saturday is left to the other journeys.
        await adminPage.goto(`/${DUBAI}/admin/classes`)
        const mine = adminPage.getByTestId('schedule').filter({ hasText: name })
        await mine.getByTestId('retire-schedule').click()
        await mine.getByTestId('confirm-retire').click()
        await expect(adminPage.getByTestId('flash-success')).toContainText(`Retired "${name}".`)
    }

    await lifeshaper.close()
    await admin.close()
})
