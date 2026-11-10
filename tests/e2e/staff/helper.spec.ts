import { type Locator, type Page } from '@playwright/test'
import { addTodayClass, DOOR_URL, goToDoor, retireClass, serveClass } from '../support/door'
import { mailCount } from '../support/mailbox'
import { openEmailedLink } from '../support/sign-in'
import { checkLayout, expect, test } from '../support/test'
import { DOOR, DUBAI, sessionFile, uniqueEmail, type DoorProject } from '../support/world'

/*
 * A helper for the day (spec item 18; Roy 2026-11-10): an admin opens their own one-time class for the door's
 * Saturday and adds a helper on People, for that class; the helper signs in from the email, checks a child in at its
 * door; the admin closes the class, and the helper finds nothing. The class is retired at the end.
 */

function child(card: Locator, nickname: string): Locator {
    return card
        .getByTestId('door-child')
        .filter({ has: card.page().getByTestId('child-name').getByText(nickname, { exact: true }) })
}

async function cruz(page: Page, project: string): Promise<Locator> {
    await page.getByTestId('door-search').fill('Cruz')
    await page.getByTestId('door-search').press('Enter')
    const card = page
        .getByTestId('door-family')
        .filter({ hasText: `${DOOR.guardians[project as DoorProject]} Cruz` })
    await expect(card).toBeVisible()
    return card
}

test('an admin adds a helper for the day, who checks a child in from the emailed link — and finds nothing once the class closes @story#211 @story#213 @story#214', async ({
    page,
    browser,
}, info) => {
    test.setTimeout(90_000)
    const admin = await browser.newContext({ storageState: sessionFile('admin') })
    const adminPage = await admin.newPage()
    const name = `Helped ${Math.random().toString(36).slice(2, 6)}`
    const email = uniqueEmail('helper')
    const helperName = `Hana ${name}`
    let door: string | null = null
    await addTodayClass(adminPage, name)
    try {
        await adminPage.goto(`/${DUBAI}/serve`)
        await serveClass(adminPage, name).getByTestId('serve-open').click()
        await expect(adminPage).toHaveURL(DOOR_URL)
        door = adminPage.url()

        // On People, "Helper for the day" at the class open now.
        await adminPage.goto(`/${DUBAI}/admin/people`)
        await adminPage.getByTestId('staff-name').fill(helperName)
        await adminPage.getByTestId('staff-email').fill(email)
        const role = `Helper for the day · CCF Dubai · ${name} today`
        await adminPage.getByTestId('staff-role').selectOption({ label: role })
        await checkLayout(adminPage)
        const before = mailCount(email)
        await adminPage.getByTestId('add-staff-submit').click()
        await expect(adminPage.getByTestId('flash-success')).toContainText(
            `${email} is now ${role}`,
        )

        // Hana, on her own phone: from the email to Serve, to the class's door; she checks Ben in.
        await openEmailedLink(page, email, before)
        await expect(page).toHaveURL(new RegExp(`/${DUBAI}/serve$`))
        await goToDoor(page, name)
        const card = await cruz(page, info.project.name)
        await child(card, DOOR.cruz.active).getByTestId('tick-child').check()
        await card.getByTestId('check-in').click()
        await expect(page.getByTestId('flash-success')).toContainText(
            `Checked in ${DOOR.cruz.active}.`,
        )
        // Never Admin: an Admin page sends her to Serve.
        await page.goto(`/${DUBAI}/admin/families`)
        await expect(page).toHaveURL(new RegExp(`/${DUBAI}/serve$`))

        // The admin closes the class; Hana's door and Serve are gone.
        await adminPage.goto(door)
        await adminPage.getByTestId('session-close').click()
        await adminPage.getByTestId('confirm-close').click()
        await expect(adminPage.getByTestId('flash-success')).toContainText(`${name} is closed.`)
        expect((await page.goto(door))?.status()).toBe(404)
        expect((await page.goto(`/${DUBAI}/serve`))?.status()).toBe(404)
    } finally {
        // Closed and retired even when a step failed, so the door's Saturday is left to the other journeys.
        if (
            door !== null &&
            (await adminPage.goto(door)) &&
            (await adminPage.getByTestId('session-close').count()) > 0
        ) {
            await adminPage.getByTestId('session-close').click()
            await adminPage.getByTestId('confirm-close').click()
        }
        await retireClass(adminPage, name)
        await admin.close()
    }
})
