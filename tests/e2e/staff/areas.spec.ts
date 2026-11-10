import { navTo } from '../support/menu'
import { expect, test } from '../support/test'
import { DUBAI, sessionFile } from '../support/world'

/*
 * The staff areas (Roy 2026-11-06, tasks/2026-11-06-admin-serve): NxtGen Serve for everyone who serves — the door,
 * Families, the QR poster — and NxtGen Admin for admins. The church's address opens each person's own.
 */

test("a Lifeshaper's satellite address opens Serve: today's classes, Families without Excel, and no Admin @story#42", async ({
    browser,
}) => {
    const lifeshaper = await browser.newContext({ storageState: sessionFile('lifeshaper') })
    const page = await lifeshaper.newPage()

    await page.goto(`/${DUBAI}`)
    await expect(page).toHaveURL(new RegExp(`/${DUBAI}/serve$`))
    await navTo(page, 'Families')
    await expect(page).toHaveURL(new RegExp(`/${DUBAI}/serve/families`))
    await expect(page.getByTestId('register-family')).toBeVisible()
    await expect(page.getByTestId('export')).toHaveCount(0)
    await expect(page.getByTestId('nav-admin')).toHaveCount(0)

    await page.goto(`/${DUBAI}/admin/families`)
    // Only serving here: an Admin page sends them to this satellite's Serve (spec item 10).
    await expect(page).toHaveURL(new RegExp(`/${DUBAI}/serve$`))
    await lifeshaper.close()
})

test("an admin's satellite address opens Admin, and the menu goes to Serve and back @story#42", async ({
    browser,
}) => {
    const admin = await browser.newContext({ storageState: sessionFile('admin') })
    const page = await admin.newPage()

    await page.goto(`/${DUBAI}`)
    await expect(page).toHaveURL(new RegExp(`/${DUBAI}/admin/families`))
    await expect(page.getByTestId('export')).toBeVisible()

    await navTo(page, 'Serve')
    await expect(page).toHaveURL(new RegExp(`/${DUBAI}/serve$`))
    await navTo(page, 'Admin')
    await expect(page).toHaveURL(new RegExp(`/${DUBAI}/admin/families`))
    await admin.close()
})
