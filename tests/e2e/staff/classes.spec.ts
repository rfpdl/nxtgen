import { type Page } from '@playwright/test'
import { navTo } from '../support/menu'
import { checkLayout, expect, test } from '../support/test'
import {
    DAY_AFTER,
    DUBAI,
    CLASSES,
    SATELLITES,
    SHARJAH,
    doorSaturday,
    sessionFile,
} from '../support/world'

/*
 * Classes (GLOSSARY "Schedule", DECISIONS 2026-10-26). An admin opens Classes from the menu, sees CCF Dubai's
 * 1PM and 7PM, adds a weekly class, changes its time, then retires it (so the seeded classes stay as they are for the
 * other journeys); adds a one-time class, which the door offers on its date and not the day after (spec item 14).
 * A satellite admin sees only their own satellite; a Lifeshaper has no Classes.
 */

/** One satellite's part of the Classes page. */
function satellite(page: Page, name: string) {
    return page.locator('section').filter({ has: page.getByRole('heading', { name, level: 2 }) })
}

test("an admin sees CCF Dubai's classes, adds a weekly one, edits its time and retires it @story#95 @story#97 @story#99 @story#101", async ({
    browser,
}) => {
    const admin = await browser.newContext({ storageState: sessionFile('admin') })
    const page = await admin.newPage()
    await page.goto(`/${DUBAI}/admin/families`)
    await navTo(page, 'Classes')
    await expect(page.getByRole('heading', { name: 'Classes', level: 1 })).toBeVisible()

    // The seeded weekly classes come first (one-time classes other journeys added follow, by date).
    const dxb = satellite(page, SATELLITES.dxb)
    await expect(dxb.getByTestId('schedule').nth(0)).toHaveText(
        new RegExp(`${CLASSES.onePm.name}\\s*${CLASSES.onePm.when}`),
    )
    await expect(dxb.getByTestId('schedule').nth(1)).toHaveText(
        new RegExp(`${CLASSES.sevenPm.name}\\s*${CLASSES.sevenPm.when}`),
    )
    // Only the satellite in the address (DECISIONS 2026-11-08).
    await expect(page.getByRole('heading', { level: 2 })).toHaveText([SATELLITES.dxb])

    // Add a weekly class on Mondays. The form opens from the button, and closes once added.
    // Named for this run, so the projects (and a retry) never meet each other's.
    const name = `Special class ${Math.random().toString(36).slice(2, 6)}`
    const classes = await dxb.getByTestId('schedule').count()
    await expect(dxb.getByTestId('add-schedule')).toHaveCount(0)
    await dxb.getByTestId('open-add-schedule').click()
    const add = dxb.getByTestId('add-schedule')
    await add.getByTestId('schedule-name').fill(name)
    await add.getByTestId('schedule-weekday').selectOption('monday')
    await add.getByTestId('schedule-starts-at').fill('10:00')
    await add.getByTestId('schedule-ends-at').fill('11:00')
    await checkLayout(page)
    await add.getByTestId('schedule-submit').click()
    await expect(page.getByTestId('flash-success')).toContainText(
        `Added "${name}" at ${SATELLITES.dxb}.`,
    )
    const special = dxb.getByTestId('schedule').filter({ hasText: name })
    await expect(special).toContainText('Mondays · 10:00–11:00')
    await expect(add).toHaveCount(0)
    await expect(dxb.getByTestId('open-add-schedule')).toBeVisible()

    // Change its time.
    await special.getByTestId('edit-schedule').click()
    const edit = dxb.getByTestId('edit-schedule-form')
    await edit.getByTestId('schedule-starts-at').fill('16:00')
    await edit.getByTestId('schedule-ends-at').fill('17:30')
    await edit.getByTestId('schedule-submit').click()
    await expect(page.getByTestId('flash-success')).toContainText(`Saved "${name}".`)
    await expect(special).toContainText('Mondays · 16:00–17:30')

    // An end before the start is refused, beside the field.
    await special.getByTestId('edit-schedule').click()
    await edit.getByTestId('schedule-ends-at').fill('15:00')
    await edit.getByTestId('schedule-submit').click()
    await expect(edit).toContainText('The class must end after it starts.')
    await edit.getByTestId('schedule-cancel').click()

    // Retire it: asked once more, then gone from the page.
    await special.getByTestId('retire-schedule').click()
    await expect(special).toContainText('It will no longer show at the door.')
    await special.getByTestId('confirm-retire').click()
    await expect(page.getByTestId('flash-success')).toContainText(`Retired "${name}".`)
    await expect(dxb.getByTestId('schedule')).toHaveCount(classes)
    await admin.close()
})

test('an admin adds a one-time class: the door offers it on its date and not the day after @story#97 @story#95', async ({
    browser,
}) => {
    const admin = await browser.newContext({ storageState: sessionFile('admin') })
    const page = await admin.newPage()
    await page.goto(`/${DUBAI}/admin/classes`)
    const dxb = satellite(page, SATELLITES.dxb)

    // Late in the evening, so it never comes before 1PM at the door the other journeys use.
    const name = `Party ${Math.random().toString(36).slice(2, 6)}`
    await dxb.getByTestId('open-add-schedule').click()
    const add = dxb.getByTestId('add-schedule')
    await add.getByTestId('schedule-name').fill(name)
    await add.getByTestId('schedule-one-time').click()
    await expect(add.getByTestId('schedule-weekday')).toHaveCount(0)
    await add.getByTestId('schedule-date').fill(doorSaturday())
    await add.getByTestId('schedule-starts-at').fill('22:00')
    await add.getByTestId('schedule-ends-at').fill('23:00')
    await checkLayout(page)
    await add.getByTestId('schedule-submit').click()
    await expect(page.getByTestId('flash-success')).toContainText(`Added "${name}"`)
    await expect(dxb.getByTestId('schedule').filter({ hasText: name })).toContainText(
        /^.*Sat \d{1,2} \w{3} \d{4} · 22:00–23:00/,
    )

    // On its date the door offers it…
    await page.goto(`/${DUBAI}/serve/door`)
    await expect(page.getByTestId('door-class').getByRole('radio', { name })).toBeVisible()

    // …and the day after it does not; Classes lists it under "Past".
    const later = await browser.newContext({
        storageState: sessionFile('admin'),
        extraHTTPHeaders: DAY_AFTER,
    })
    const after = await later.newPage()
    await after.goto(`/${DUBAI}/serve/door`)
    await expect(after).toHaveURL(new RegExp(`/${DUBAI}/serve/door$`))
    await expect(after.getByTestId('no-class')).toBeVisible()
    await expect(
        after.getByTestId('door-class').getByRole('radio', { name: CLASSES.onePm.name }),
    ).toHaveCount(0)
    await expect(after.getByRole('radio', { name })).toHaveCount(0)
    await after.goto(`/${DUBAI}/admin/classes`)
    await expect(
        after.getByTestId('past-schedules').getByTestId('schedule').filter({ hasText: name }),
    ).toBeVisible()
    await expect(after.getByTestId('schedule').filter({ hasText: name })).toHaveCount(1)

    // Retired from "Past" — its name is free again, and it leaves the door's Saturday to the other journeys.
    const past = after
        .getByTestId('past-schedules')
        .getByTestId('schedule')
        .filter({ hasText: name })
    await past.getByTestId('retire-schedule').click()
    await past.getByTestId('confirm-retire').click()
    await expect(after.getByTestId('flash-success')).toContainText(`Retired "${name}".`)
    await expect(after.getByTestId('schedule').filter({ hasText: name })).toHaveCount(0)
    await later.close()
    await admin.close()
})

test("a satellite admin sees only their own satellite's classes @story#95", async ({ browser }) => {
    const houseAdmin = await browser.newContext({ storageState: sessionFile('houseAdmin') })
    const page = await houseAdmin.newPage()
    await page.goto(`/${SHARJAH}/admin/classes`)

    await expect(page.getByRole('heading', { level: 2 })).toHaveText([SATELLITES.house])
    await expect(page.getByText('No classes yet.')).toBeVisible()
    await expect(page.getByText(CLASSES.onePm.name, { exact: true })).toHaveCount(0)
    await houseAdmin.close()
})

test('a Lifeshaper has no Classes menu and no Classes page @story#96', async ({ browser }) => {
    const lifeshaper = await browser.newContext({ storageState: sessionFile('lifeshaper') })
    const page = await lifeshaper.newPage()
    await page.goto(`/${DUBAI}/serve/door`)
    await expect(page.getByRole('link', { name: 'Classes' })).toHaveCount(0)

    await page.goto(`/${DUBAI}/admin/classes`)
    // Only serving here: an Admin page sends them to this satellite's Serve (spec item 10).
    await expect(page).toHaveURL(new RegExp(`/${DUBAI}/serve/door$`))
    await lifeshaper.close()
})
