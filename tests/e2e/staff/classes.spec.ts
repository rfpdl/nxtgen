import { type Page } from '@playwright/test'
import { navTo } from '../support/menu'
import { checkLayout, expect, test } from '../support/test'
import { CHURCH, CLASSES, SATELLITES, sessionFile } from '../support/world'

/*
 * Classes (GLOSSARY "Schedule", DECISIONS 2026-10-26). A church admin opens Classes from the menu, sees CCF DXB's
 * 1PM and 7PM, adds "Special class" with no day, gives it a time, then retires it (so the seeded classes stay as
 * they are for the other journeys). A satellite admin sees only their own satellite; a Lifeshaper has no Classes.
 */

/** One satellite's part of the Classes page. */
function satellite(page: Page, name: string) {
    return page.locator('section').filter({ has: page.getByRole('heading', { name, level: 2 }) })
}

test("an admin sees CCF DXB's classes, adds one with no day, edits its time and retires it @story#95 @story#97 @story#99 @story#101", async ({
    browser,
}) => {
    const admin = await browser.newContext({ storageState: sessionFile('churchAdmin') })
    const page = await admin.newPage()
    await page.goto(`/${CHURCH}/families`)
    await navTo(page, 'Classes')
    await expect(page.getByRole('heading', { name: 'Classes', level: 1 })).toBeVisible()

    const dxb = satellite(page, SATELLITES.dxb)
    await expect(dxb.getByTestId('schedule')).toHaveText([
        new RegExp(`${CLASSES.onePm.name}\\s*${CLASSES.onePm.when}`),
        new RegExp(`${CLASSES.sevenPm.name}\\s*${CLASSES.sevenPm.when}`),
    ])
    await expect(satellite(page, SATELLITES.house)).toContainText('No classes yet.')

    // Add a special class with no day: it shows every day. The form opens from the button, and closes once added.
    // Named for this run, so the projects (and a retry) never meet each other's.
    const name = `Special class ${Math.random().toString(36).slice(2, 6)}`
    const classes = await dxb.getByTestId('schedule').count()
    await expect(dxb.getByTestId('add-schedule')).toHaveCount(0)
    await dxb.getByTestId('open-add-schedule').click()
    const add = dxb.getByTestId('add-schedule')
    await add.getByTestId('schedule-name').fill(name)
    await checkLayout(page)
    await add.getByTestId('schedule-submit').click()
    await expect(page.getByTestId('flash-success')).toContainText(
        `Added "${name}" at ${SATELLITES.dxb}.`,
    )
    const special = dxb.getByTestId('schedule').filter({ hasText: name })
    await expect(special).toContainText('Any day')
    await expect(add).toHaveCount(0)
    await expect(dxb.getByTestId('open-add-schedule')).toBeVisible()

    // Give it a time.
    await special.getByTestId('edit-schedule').click()
    const edit = dxb.getByTestId('edit-schedule-form')
    await edit.getByTestId('schedule-starts-at').fill('16:00')
    await edit.getByTestId('schedule-ends-at').fill('17:30')
    await edit.getByTestId('schedule-submit').click()
    await expect(page.getByTestId('flash-success')).toContainText(`Saved "${name}".`)
    await expect(special).toContainText('Any day · 16:00–17:30')

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

test("a satellite admin sees only their own satellite's classes @story#95", async ({ browser }) => {
    const houseAdmin = await browser.newContext({ storageState: sessionFile('houseAdmin') })
    const page = await houseAdmin.newPage()
    await page.goto(`/${CHURCH}/classes`)

    await expect(page.getByRole('heading', { level: 2 })).toHaveText([SATELLITES.house])
    await expect(page.getByText(CLASSES.onePm.name, { exact: true })).toHaveCount(0)
    await houseAdmin.close()
})

test('a Lifeshaper has no Classes menu and no Classes page @story#96', async ({ browser }) => {
    const lifeshaper = await browser.newContext({ storageState: sessionFile('lifeshaper') })
    const page = await lifeshaper.newPage()
    await page.goto(`/${CHURCH}/families`)
    await expect(page.getByRole('link', { name: 'Classes' })).toHaveCount(0)

    const response = await page.goto(`/${CHURCH}/classes`)
    expect(response?.status()).toBe(404)
    await lifeshaper.close()
})
