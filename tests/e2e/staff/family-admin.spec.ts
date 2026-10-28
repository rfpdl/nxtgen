import { readFileSync } from 'node:fs'
import { expect, test } from '@playwright/test'
import { fillFamily } from '../support/forms'
import { latestMail, mailCount } from '../support/mailbox'
import { openEmailedLink } from '../support/sign-in'
import { CHURCH, SATELLITES, sessionFile, uniqueEmail, uniqueSurname } from '../support/world'

/*
 * R1 exit criterion (plan §12): a staff member registers a family whose email is not confirmed, and resends.
 * Then the admin tools on the family: correct a mistyped email (the parent confirms from the new address),
 * set a child's status, the "not confirmed" filter, and the Excel download. Registering starts from the Families
 * list's button and ends on the new family's page (DECISIONS 2026-10-17, after first look).
 */
test.use({ storageState: sessionFile('churchAdmin') })

test('register for a parent, resend, correct the email, set a status @story#47 @story#54 @story#56 @story#62 @story#85', async ({
    page,
    browser,
}) => {
    const typo = uniqueEmail('typo')
    const surname = uniqueSurname('Garcia')

    // No Register tab: the Families list's button opens the search-first page.
    await page.goto(`/${CHURCH}/families`)
    await expect(page.getByRole('navigation')).not.toContainText('Register a family')
    await page.getByTestId('register-family').click()
    await expect(page.getByRole('heading', { name: 'Register a family' })).toBeVisible()
    await page.getByTestId('register-new').click()
    await fillFamily(
        page,
        {
            first: 'Lea',
            last: surname,
            email: typo,
            phone: '0501112222',
            satellite: SATELLITES.dxb,
        },
        [{ first: 'Carlo', last: surname, nickname: 'Caloy', gender: 'boy', dob: '2016-06-06' }],
    )
    await page.getByTestId('register-submit').click()
    // Straight to the new family's page, with the message.
    await expect(page).toHaveURL(new RegExp(`/${CHURCH}/families/\\d+$`))
    await expect(page.getByRole('heading', { name: `Lea ${surname}` })).toBeVisible()
    await expect(page.getByTestId('flash-success')).toContainText('Registered.')

    // The "not confirmed" filter finds them; open the family.
    await page.goto(`/${CHURCH}/families`)
    await page.getByTestId('filter-unconfirmed').check()
    await expect(page).toHaveURL(/unconfirmed=1/)
    // Search narrows further, and the filters stay in the address.
    await page.getByTestId('filter-q').fill(surname)
    await page.getByTestId('filter-q').press('Enter')
    await expect(page).toHaveURL(new RegExp(`q=${surname}`))
    await expect(page.getByTestId('family-row')).toHaveCount(1)
    const row = page.getByTestId('family-row').filter({ hasText: `Lea ${surname}` })
    await expect(row).toContainText('Email not confirmed')
    await row.getByRole('link').click()
    await expect(page.getByRole('heading', { name: `Lea ${surname}` })).toBeVisible()

    // Resend: another link goes to the (mistyped) address.
    const beforeResend = mailCount(typo)
    await page.getByTestId('resend').click()
    await expect(page.getByTestId('flash-success')).toBeVisible()
    await latestMail(typo, beforeResend)

    // The parent says "that's not my email": correct it; the new address gets the link.
    const real = uniqueEmail('real')
    const beforeReal = mailCount(real)
    await page.getByTestId('correct-email').fill(real)
    await page.getByRole('button', { name: 'Save email' }).click()
    await expect(page.getByTestId('flash-success')).toContainText('Email updated')

    // Set the child inactive (they moved away) — from the controls inside the child's card.
    const status = page
        .getByTestId('family-child')
        .filter({ hasText: 'Caloy' })
        .locator('[data-testid^="status-"]')
    await status.selectOption('inactive')
    await expect(page.getByTestId('flash-success')).toBeVisible()
    // The status shows as the dropdown's current value.
    await expect(status.locator('option').first()).toHaveText(/inactive/i)

    // Both changes are on the family's history, for staff.
    await expect(page.getByTestId('history')).toContainText(
        `Email corrected from ${typo} to ${real}`,
    )
    await expect(page.getByTestId('history')).toContainText(
        'status changed from Active to Inactive',
    )

    // Staff fix the family's details and a child's, the same forms the parent has.
    await page.getByTestId('edit-family').click()
    await page.getByTestId('area').fill('Karama')
    await page.getByTestId('edit-family-submit').click()
    await expect(page.getByTestId('flash-success')).toContainText('Family details saved')
    await expect(page.getByText('Karama')).toBeVisible()

    const card = page.getByTestId('family-child').filter({ hasText: 'Caloy' })
    await card.getByTestId('edit-child').click()
    await card.getByTestId('child-0-nickname').fill('Carlitos')
    await card.getByTestId('edit-child-submit').click()
    await expect(page.getByTestId('family-child').filter({ hasText: 'Carlitos' })).toBeVisible()

    // The parent, on their own phone, confirms from the corrected address and sees their family.
    const guardianContext = await browser.newContext({ storageState: { cookies: [], origins: [] } })
    const guardianPage = await guardianContext.newPage()
    await openEmailedLink(guardianPage, real, beforeReal)
    await expect(guardianPage).toHaveURL(new RegExp(`/${CHURCH}/family`))
    await expect(guardianPage.getByTestId('family-child')).toContainText('Carlitos')
    await guardianContext.close()
})

test("adds, changes and removes a family's other parents and guardians @story#88 @story#89", async ({
    page,
}) => {
    const surname = uniqueSurname('Aquino')
    await page.goto(`/${CHURCH}/families/new`)
    await page.getByTestId('register-new').click()
    await fillFamily(
        page,
        {
            first: 'Liza',
            last: surname,
            email: uniqueEmail('liza'),
            phone: '0502223333',
            satellite: SATELLITES.dxb,
        },
        [{ first: 'Ben', last: surname, nickname: 'Benjie', gender: 'boy', dob: '2017-07-07' }],
    )
    // The father, on the same form; he will not sign in, so there is no email to give.
    await page.getByTestId('add-guardian').click()
    await page.getByTestId('other-guardian-0-first-name').fill('Rey')
    await page.getByTestId('other-guardian-0-last-name').fill(surname)
    await page.getByTestId('other-guardian-0-phone').fill('0502224444')
    await expect(page.getByTestId('other-guardian-0-email')).toHaveCount(0)
    await page.getByTestId('register-submit').click()
    await expect(page.getByRole('heading', { name: `Liza ${surname}` })).toBeVisible()
    await expect(page.getByText(`Rey ${surname} · Father`)).toBeVisible()

    // Later: the grandmother collects on Sundays, and Rey's number changed.
    await page.getByTestId('edit-family').click()
    await page.getByTestId('other-guardian-0-phone').fill('0502225555')
    await page.getByTestId('add-guardian').click()
    await page.getByTestId('other-guardian-1-first-name').fill('Nena')
    await page.getByTestId('other-guardian-1-last-name').fill(surname)
    await page.getByTestId('other-guardian-1-relationship').selectOption('grandparent')
    // Saved without her number first: refused, and what was typed stays, the error on her row.
    await page.getByTestId('edit-family-submit').click()
    await expect(page.getByTestId('other-guardian-1-row')).toContainText(
        'Please enter their mobile number.',
    )
    await expect(page.getByTestId('other-guardian-1-first-name')).toHaveValue('Nena')
    await expect(page.getByTestId('other-guardian-0-phone')).toHaveValue('0502225555')
    await page.getByTestId('other-guardian-1-phone').fill('0502226666')
    await page.getByTestId('edit-family-submit').click()
    await expect(page.getByTestId('flash-success')).toContainText('Family details saved')
    await expect(page.getByText(`Rey ${surname} · Father · 0502225555`)).toBeVisible()
    await expect(page.getByText(`Nena ${surname} · Grandparent`)).toBeVisible()

    // Rey moved away: take him off.
    await page.getByTestId('edit-family').click()
    await page.getByTestId('other-guardian-0-remove').click()
    await page.getByTestId('edit-family-submit').click()
    await expect(page.getByTestId('flash-success')).toContainText('Family details saved')
    await expect(page.getByText(`Rey ${surname} · Father`)).toHaveCount(0)

    // Every change is on the family's history.
    const history = page.getByTestId('history')
    await expect(history).toContainText(`Rey ${surname} (Father): details changed.`)
    await expect(history).toContainText(`Nena ${surname} (Grandparent) added to the family.`)
    await expect(history).toContainText(`Rey ${surname} (Father) removed from the family.`)
})

test('downloads the families list as Excel — what the filters show @story#45', async ({ page }) => {
    await page.goto(`/${CHURCH}/families?unconfirmed=1`)
    await expect(page.getByTestId('export')).toHaveAttribute('href', /unconfirmed=1/)

    const [download] = await Promise.all([
        page.waitForEvent('download'),
        page.getByTestId('export').click(),
    ])

    expect(download.suggestedFilename()).toMatch(/\.xlsx$/)
    const bytes = readFileSync((await download.path())!)
    // An .xlsx is a zip: it starts with "PK".
    expect(bytes.subarray(0, 2).toString()).toBe('PK')
})

test('the welcome page is there, and "Register your family" opens the form @story#1', async ({
    page,
}) => {
    await page.goto('/')
    await expect(
        page.getByRole('heading', { name: 'Welcome to NxtGen', exact: true }),
    ).toBeVisible()
    await page.getByTestId('welcome-register').click()
    await expect(page).toHaveURL(new RegExp(`/${CHURCH}/register$`))
    await expect(page.getByRole('heading', { name: 'Register your family' })).toBeVisible()
})
