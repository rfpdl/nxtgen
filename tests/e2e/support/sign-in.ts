import { expect, type Page } from '@playwright/test'
import { latestMail, magicPath, mailCount } from './mailbox'

/**
 * Open an emailed link the way a person does: the link shows a "Continue" page (so mail scanners that
 * pre-open links cannot use it up), and pressing it signs in.
 */
export async function openEmailedLink(page: Page, email: string, before: number): Promise<void> {
    const mail = await latestMail(email, before)
    await page.goto(magicPath(mail))
    await page.getByTestId('confirm-link').click()
    await expect(page).not.toHaveURL(/\/magic\//)
}

/** Sign in through the real form: type the email, open the link from the mailbox. */
export async function signInByEmail(page: Page, email: string): Promise<void> {
    const before = mailCount(email)
    await page.goto('/login')
    await page.getByTestId('login-email').fill(email)
    await page.getByTestId('login-submit').click()
    await expect(page.getByTestId('flash-success')).toBeVisible()
    await openEmailedLink(page, email, before)
}
