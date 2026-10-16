import { expect, test } from '@playwright/test'
import { CHURCH, STAFF, sessionFile } from '../support/world'

/*
 * The staff backup password (plan D7): a staff member sets one from the menu, then — the morning the email is
 * slow — signs in with it on another device. A wrong one gets the same plain answer as any failure.
 */
test.use({ storageState: sessionFile('lifeshaper') })

test('a staff member sets a backup password and signs in with it', async ({ page, browser }) => {
    const password = `church-morning-${Date.now()}`

    await page.goto(`/${CHURCH}/families`)
    await page.getByTestId('backup-password').click()
    await page.getByTestId('new-password').fill(password)
    await page.getByTestId('new-password-confirm').fill(password)
    await page.getByTestId('save-password').click()
    await expect(page.getByTestId('flash-success')).toContainText('Backup password saved')

    const other = await browser.newContext({ storageState: { cookies: [], origins: [] } })
    const phone = await other.newPage()
    await phone.goto('/login')
    await phone.getByTestId('use-password').click()
    await phone.getByTestId('password-email').fill(STAFF.lifeshaper)
    await phone.getByTestId('password-password').fill('not-the-password')
    await phone.getByTestId('password-submit').click()
    await expect(phone.getByText('That email and password do not match')).toBeVisible()

    await phone.getByTestId('password-password').fill(password)
    await phone.getByTestId('password-submit').click()
    await expect(phone).toHaveURL(new RegExp(`/${CHURCH}/families`))
    await other.close()
})
