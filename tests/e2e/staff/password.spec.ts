import { menuOf } from '../support/menu'
import { signInByEmail } from '../support/sign-in'
import { expect, test } from '../support/test'
import { DUBAI, volunteer } from '../support/world'

/*
 * The staff backup password (plan D7): a staff member sets one from the menu, then — the morning the email is
 * slow — signs in with it on another device. A wrong one gets the same plain answer as any failure. /loginp opens
 * the password straight away; /loginc clears the device's sign-in (Roy, 2026-11-06). Each browser uses its own
 * volunteer: passwords are per person, and five tries a minute per email is the guessing limit.
 */
test.use({ storageState: { cookies: [], origins: [] } })

test('a staff member sets a backup password, signs in with it on /loginp — never offered on /login — and clears the device on /loginc @story#8 @story#16 @story#21 @story#162 @story#163', async ({
    page,
    browser,
}, info) => {
    const email = volunteer(info.project.name)
    const password = `church-morning-${Date.now()}`

    await signInByEmail(page, email)
    await expect(page).toHaveURL(new RegExp(`/${DUBAI}/serve/door`))
    await (await menuOf(page)).getByTestId('backup-password').click()
    await page.getByTestId('new-password').fill(password)
    await page.getByTestId('new-password-confirm').fill(password)
    await page.getByTestId('save-password').click()
    await expect(page.getByTestId('flash-success')).toContainText('Backup password saved')

    const other = await browser.newContext({ storageState: { cookies: [], origins: [] } })
    const phone = await other.newPage()
    // /login is the email link alone (Roy 2026-11-06); the password is on /loginp.
    await phone.goto('/login')
    await expect(phone.getByTestId('login-email')).toBeVisible()
    await expect(phone.getByTestId('password-password')).toHaveCount(0)

    await phone.goto('/loginp')
    await phone.getByTestId('password-email').fill(email)
    await phone.getByTestId('password-password').fill('not-the-password')
    await phone.getByTestId('password-submit').click()
    await expect(phone.getByText('That email and password do not match')).toBeVisible()

    await phone.getByTestId('password-password').fill(password)
    await phone.getByTestId('password-submit').click()
    await expect(phone).toHaveURL(new RegExp(`/${DUBAI}/serve/door`))

    await phone.goto('/loginc')
    await expect(phone).toHaveURL(/\/login$/)
    await expect(phone.getByTestId('flash-success')).toHaveText(
        'Signed out. This device is cleared.',
    )
    await phone.goto(`/${DUBAI}/serve/door`)
    await expect(phone).toHaveURL(/\/login$/)
    await other.close()
})
