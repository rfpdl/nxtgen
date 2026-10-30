import { expect, test } from '../support/test'
import { fillFamily } from '../support/forms'
import { latestMail, magicPath, mailCount } from '../support/mailbox'
import { openEmailedLink } from '../support/sign-in'
import { CHURCH, DOOR, SATELLITES, doorFamily } from '../support/world'

/*
 * R1 exit criterion (plan §12): a returning IMPORTED parent who fills in the form again is told they are
 * already registered — by email, never on screen (the page says the same thing to everyone) — and the email
 * signs them straight into the family that was imported for them. The link works once.
 */
test('an imported parent who registers again is signed in to their existing family @story#14', async ({
    page,
}, info) => {
    // This project's own imported Cruz family: one address per project, under the 5-emails-per-address limit.
    const { email } = doorFamily(info.project.name)
    const child = DOOR.cruz.allergic
    const satellite = SATELLITES.dxb
    const before = mailCount(email)

    await page.goto(`/${CHURCH}/register`)
    await fillFamily(
        page,
        { first: 'Maria', last: 'Cruz', email, phone: '0500000000', satellite },
        [{ first: 'Amara', last: 'Cruz', nickname: 'Amara', gender: 'girl', dob: '2019-03-01' }],
    )
    await page.getByTestId('register-submit').click()

    // The same thank-you as everyone: the page never says whether an email is known.
    await expect(page.getByRole('heading', { name: 'Thank you' })).toBeVisible()

    const mail = await latestMail(email, before)
    expect(mail.body).toContain('already registered')

    await openEmailedLink(page, email, before)
    await expect(page).toHaveURL(new RegExp(`/${CHURCH}/family`))
    // Nothing was created twice: the imported child appears once. (Other journeys may add siblings.)
    await expect(page.getByTestId('family-child').filter({ hasText: child })).toHaveCount(1)

    // The same link a second time does nothing but say so.
    await page.context().clearCookies()
    await page.goto(magicPath(mail))
    await page.getByTestId('confirm-link').click()
    await expect(page).toHaveURL(/\/login/)
    await expect(page.getByTestId('flash-error')).toBeVisible()
})
