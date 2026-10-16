import { expect, test } from '@playwright/test'
import { latestMail, mailCount } from '../support/mailbox'
import { fillFamily } from '../support/forms'
import { openEmailedLink, signInByEmail } from '../support/sign-in'
import { CHURCH, SATELLITES, sessionFile, uniqueEmail, uniqueSurname } from '../support/world'

/*
 * Journey 3 — a church admin adds a new Lifeshaper on People; the Lifeshaper gets an email, signs in from
 * it, and can work in the church straight away. Then the admin removes the role again.
 */
test('an admin adds a Lifeshaper who then signs in from the email @story#76', async ({
    page,
    browser,
}) => {
    const email = uniqueEmail('newlifeshaper')
    const before = mailCount(email)

    const admin = await browser.newContext({ storageState: sessionFile('churchAdmin') })
    const adminPage = await admin.newPage()
    await adminPage.goto(`/${CHURCH}/people`)
    await adminPage.getByTestId('staff-name').fill('Nina New')
    await adminPage.getByTestId('staff-email').fill(email)
    await adminPage.getByTestId('staff-role').selectOption({ label: 'Lifeshaper · House Church' })
    await adminPage.getByTestId('add-staff-submit').click()
    await expect(adminPage.getByTestId('flash-success')).toContainText(
        `${email} is now Lifeshaper · House Church`,
    )
    await expect(
        adminPage.getByTestId('staff-member').filter({ hasText: 'Nina New' }),
    ).toBeVisible()

    // The invite says what they were added as.
    const mail = await latestMail(email, before)
    expect(mail.subject).toContain('You have been added')

    // Nina, on her own device.
    await openEmailedLink(page, email, before)
    await expect(page).toHaveURL(new RegExp(`/${CHURCH}/families`))

    // The admin takes the role away; Nina loses access on her next page.
    await adminPage
        .getByTestId('staff-member')
        .filter({ hasText: 'Nina New' })
        .getByTestId('remove-role')
        .click()
    await expect(adminPage.getByTestId('flash-success')).toContainText('Role removed')

    const gone = await page.goto(`/${CHURCH}/families`)
    expect(gone?.status()).toBe(404)

    // Signing in again, with no family and no role left, she is told plainly there is nothing here yet.
    await page.context().clearCookies()
    await signInByEmail(page, email)
    await expect(page.getByRole('heading', { name: 'Nothing here yet' })).toBeVisible()
    await admin.close()
})

/*
 * A Lifeshaper registers their OWN family with the public form, using the email they already sign in with.
 * The family is not attached until they prove the email (claim link); then one login reaches both places,
 * so the landing asks where to go (plan §4.2, §4.5).
 */
test('a Lifeshaper who registers their own family can reach both, from one login @story#24 @story#26', async ({
    page,
    browser,
}) => {
    const email = uniqueEmail('servingparent')
    const surname = uniqueSurname('Tan')

    const admin = await browser.newContext({ storageState: sessionFile('churchAdmin') })
    const adminPage = await admin.newPage()
    await adminPage.goto(`/${CHURCH}/people`)
    await adminPage.getByTestId('staff-name').fill(`Ben ${surname}`)
    await adminPage.getByTestId('staff-email').fill(email)
    await adminPage.getByTestId('staff-role').selectOption({ label: 'Lifeshaper · CCF DXB' })
    await adminPage.getByTestId('add-staff-submit').click()
    await expect(adminPage.getByTestId('flash-success')).toBeVisible()
    await admin.close()

    const beforeClaim = mailCount(email)
    await page.goto(`/${CHURCH}/register`)
    await fillFamily(
        page,
        { first: 'Ben', last: surname, email, phone: '0503334444', satellite: SATELLITES.dxb },
        [{ first: 'Kai', last: surname, nickname: 'Kai', gender: 'boy', dob: '2018-09-09' }],
    )
    await page.getByTestId('register-submit').click()
    await expect(page.getByRole('heading', { name: 'Thank you' })).toBeVisible()

    const claim = await latestMail(email, beforeClaim)
    expect(claim.body).toContain('/magic/claim/')
    await openEmailedLink(page, email, beforeClaim)

    // One login, two places: the landing asks.
    await page.goto('/home')
    await expect(page.getByRole('heading', { name: 'Where would you like to go?' })).toBeVisible()
    await expect(page.getByTestId('landing-option')).toHaveCount(2)
    await page.getByTestId('landing-option').filter({ hasText: 'My family' }).click()
    await expect(page.getByTestId('family-child')).toContainText('Kai')

    // The choice is remembered: signing in again goes straight to the family page…
    await page.goto('/home')
    await expect(page).toHaveURL(new RegExp(`/${CHURCH}/family`))

    // …and "Switch" asks again.
    await page.getByTestId('switch-place').click()
    await page.getByTestId('landing-option').filter({ hasText: 'Serve' }).click()
    await expect(page).toHaveURL(new RegExp(`/${CHURCH}/families`))
    await page.goto('/home')
    await expect(page).toHaveURL(new RegExp(`/${CHURCH}/families`))
})
