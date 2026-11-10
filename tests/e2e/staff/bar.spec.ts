import { type Page } from '@playwright/test'
import { expect, test } from '../support/test'
import { DUBAI, SATELLITES, SHARJAH, sessionFile } from '../support/world'

/*
 * The bar and switching satellite (spec items 11, 13; Roy 2026-11-08). The demo admin serves at all three satellites:
 * the bar's satellite is a menu, and picking another opens that satellite's home — which is then where they land
 * when they come back. A Lifeshaper of one satellite sees its name, no dropdown.
 */

/** Opens the bar's satellite menu and picks `name`. */
async function switchTo(page: Page, name: string): Promise<void> {
    const button = page.getByTestId('bar-satellite')
    await expect(button).toHaveAttribute('aria-expanded', 'false')
    await button.click()
    await page.getByTestId('bar-satellite-menu').getByRole('link', { name }).click()
}

test('an admin of several satellites switches from the bar, and lands there next time @story#186', async ({
    browser,
}) => {
    const admin = await browser.newContext({ storageState: sessionFile('admin') })
    const page = await admin.newPage()
    await page.goto(`/${DUBAI}/admin/families`)

    await switchTo(page, SATELLITES.house)
    await expect(page).toHaveURL(new RegExp(`/${SHARJAH}/admin/families$`))
    await expect(page.getByTestId('bar-satellite')).toContainText(SATELLITES.house)

    // The last satellite used is kept on the account: coming back lands at Sharjah.
    await page.goto('/home')
    await expect(page).toHaveURL(new RegExp(`/${SHARJAH}/admin/families$`))

    // Back to Dubai for the other journeys.
    await switchTo(page, SATELLITES.dxb)
    await expect(page).toHaveURL(new RegExp(`/${DUBAI}/admin/families$`))
    await admin.close()
})

test('a Lifeshaper of one satellite sees its name in the bar, and no dropdown', async ({
    browser,
}) => {
    const lifeshaper = await browser.newContext({ storageState: sessionFile('lifeshaper') })
    const page = await lifeshaper.newPage()
    await page.goto(`/${DUBAI}/serve`)

    await expect(page.getByTestId('bar-satellite')).toHaveText(SATELLITES.dxb)
    await expect(page.locator('button[data-testid="bar-satellite"]')).toHaveCount(0)
    await lifeshaper.close()
})

test('a person who serves at several satellites and used none yet picks one @story#186', async ({
    browser,
}) => {
    // The page sign-in sends them to when they used none yet (auth.setup.ts walks that path); here, opened directly.
    const admin = await browser.newContext({ storageState: sessionFile('admin') })
    const page = await admin.newPage()
    await page.goto('/pick')
    await expect(page.getByRole('heading', { name: 'Pick your satellite', level: 1 })).toBeVisible()
    await expect(page.getByTestId('pick-satellite')).toHaveText([
        SATELLITES.dxb,
        SATELLITES.house,
        'CCF Abu Dhabi',
    ])
    await page.getByTestId('pick-satellite').filter({ hasText: SATELLITES.dxb }).click()
    await expect(page).toHaveURL(new RegExp(`/${DUBAI}/admin/families$`))
    await admin.close()
})
