import { navTo } from '../support/menu'
import { expect, test } from '../support/test'
import { CHURCH, sessionFile } from '../support/world'

/*
 * The staff areas (Roy 2026-11-06, tasks/2026-11-06-admin-serve): NxtGen Serve for everyone who serves — the door,
 * Families, the QR poster — and NxtGen Admin for admins. The church's address opens each person's own.
 */

test("a Lifeshaper's church address opens Serve: the door, Families without Excel, and no Admin @story#42", async ({
    browser,
}) => {
    const lifeshaper = await browser.newContext({ storageState: sessionFile('lifeshaper') })
    const page = await lifeshaper.newPage()

    await page.goto(`/${CHURCH}`)
    await expect(page).toHaveURL(new RegExp(`/${CHURCH}/nxtgen/serve/door`))
    await navTo(page, 'Families')
    await expect(page).toHaveURL(new RegExp(`/${CHURCH}/nxtgen/serve/families`))
    await expect(page.getByTestId('register-family')).toBeVisible()
    await expect(page.getByTestId('export')).toHaveCount(0)
    await expect(page.getByTestId('nav-admin')).toHaveCount(0)

    const admin = await page.goto(`/${CHURCH}/nxtgen/admin/families`)
    expect(admin?.status()).toBe(404)
    await lifeshaper.close()
})

test("an admin's church address opens Admin, and the menu goes to Serve's door and back @story#42", async ({
    browser,
}) => {
    const churchAdmin = await browser.newContext({ storageState: sessionFile('churchAdmin') })
    const page = await churchAdmin.newPage()

    await page.goto(`/${CHURCH}`)
    await expect(page).toHaveURL(new RegExp(`/${CHURCH}/nxtgen/admin/families`))
    await expect(page.getByTestId('export')).toBeVisible()

    await navTo(page, 'Serve')
    await expect(page).toHaveURL(new RegExp(`/${CHURCH}/nxtgen/serve/door`))
    await navTo(page, 'Admin')
    await expect(page).toHaveURL(new RegExp(`/${CHURCH}/nxtgen/admin/families`))
    await churchAdmin.close()
})
