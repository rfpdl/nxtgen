import type { Page } from '@playwright/test'
import { expect, test } from '../support/test'
import { fillFamily } from '../support/forms'
import { latestMail, magicPath, mailCount } from '../support/mailbox'
import { CHURCH, SATELLITES, projectPhone, uniqueEmail, uniqueSurname } from '../support/world'

/*
 * Links expire (TODO #13): a confirm link after 7 days, a sign-in link after 2 hours (plan §4.3). A journey cannot
 * wait that long, so its requests tell the e2e server how far ahead its clock is (MoveE2EClock) — only this
 * browser's, only while it says so. An expired link gets the same plain message as a used one, and since it was
 * never used, the person's next fresh link (or this one, in time) still works.
 */

/** Every request this page makes from now on runs `minutes` later on the server (0 = the real clock). */
async function clockAhead(page: Page, minutes: number): Promise<void> {
    await page.setExtraHTTPHeaders(minutes > 0 ? { 'X-E2E-Clock-Ahead': String(minutes) } : {})
}

async function pressEmailedLink(page: Page, path: string): Promise<void> {
    await page.goto(path)
    await page.getByTestId('confirm-link').click()
}

test('a confirm link a week old and a sign-in link over two hours old say so, and a link in time still works @story#12 @story#13', async ({
    page,
}, info) => {
    const email = uniqueEmail('late')
    const surname = uniqueSurname('Lim')
    const before = mailCount(email)

    await page.goto(`/${CHURCH}/register`)
    await fillFamily(
        page,
        {
            first: 'Rina',
            last: surname,
            email,
            phone: projectPhone('0504443300', info.project.name),
            satellite: SATELLITES.dxb,
        },
        [{ first: 'Toby', last: surname, nickname: 'Toby', gender: 'boy', dob: '2018-04-12' }],
    )
    await page.getByTestId('register-submit').click()
    await expect(page.getByRole('heading', { name: 'Thank you' })).toBeVisible()
    const confirm = await latestMail(email, before)

    // The confirm link, opened a week and a minute later: refused, plainly.
    await clockAhead(page, 7 * 24 * 60 + 1)
    await pressEmailedLink(page, magicPath(confirm))
    await expect(page).toHaveURL(/\/login/)
    await expect(page.getByTestId('flash-error')).toHaveText(/expired or was already used/)

    // A sign-in link asked for now (the real clock), opened two hours and a minute later: refused too.
    await clockAhead(page, 0)
    const beforeLogin = mailCount(email)
    await page.getByTestId('login-email').fill(email)
    await page.getByTestId('login-submit').click()
    await expect(page.getByTestId('flash-success')).toBeVisible()
    const login = await latestMail(email, beforeLogin)

    await clockAhead(page, 2 * 60 + 1)
    await pressEmailedLink(page, magicPath(login))
    await expect(page).toHaveURL(/\/login/)
    await expect(page.getByTestId('flash-error')).toHaveText(/expired or was already used/)

    // The same sign-in link, an hour and a half in: still in time, and the late try did not use it up.
    await clockAhead(page, 90)
    await pressEmailedLink(page, magicPath(login))
    await expect(page).toHaveURL(new RegExp(`/${CHURCH}/family`))
    await expect(page.getByRole('heading', { name: `The ${surname} family` })).toBeVisible()
    await clockAhead(page, 0)
})
