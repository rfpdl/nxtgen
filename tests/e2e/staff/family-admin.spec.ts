import { readFileSync } from 'node:fs'
import { expect, test } from '@playwright/test'
import { fillFamily } from '../support/forms'
import { latestMail, mailCount } from '../support/mailbox'
import { openEmailedLink } from '../support/sign-in'
import { CHURCH, SATELLITES, sessionFile, uniqueEmail, uniqueSurname } from '../support/world'

/*
 * R1 exit criterion (plan §12): a staff member registers a family whose email is not confirmed, and resends.
 * Then the admin tools on the family: correct a mistyped email (the parent confirms from the new address),
 * set a child's status, the "not confirmed" filter, and the Excel download.
 */
test.use({ storageState: sessionFile('churchAdmin') })

test('register for a parent, resend, correct the email, set a status', async ({
    page,
    browser,
}) => {
    const typo = uniqueEmail('typo')
    const surname = uniqueSurname('Garcia')

    await page.goto(`/${CHURCH}/families/new`)
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
    await expect(page.getByTestId('flash-success')).toBeVisible()

    // The "not confirmed" filter finds them; open the family.
    await page.goto(`/${CHURCH}/families`)
    await page.getByTestId('filter-unconfirmed').check()
    await expect(page).toHaveURL(/unconfirmed=1/)
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

    // Set the child inactive (they moved away).
    await page.locator('[data-testid^="status-"]').first().selectOption('inactive')
    await expect(page.getByTestId('flash-success')).toBeVisible()
    // The status shows as the dropdown's current value.
    await expect(
        page.locator('[data-testid^="status-"]').first().locator('option').first(),
    ).toHaveText(/inactive/i)

    // The parent, on their own phone, confirms from the corrected address and sees their family.
    const parent = await browser.newContext({ storageState: { cookies: [], origins: [] } })
    const parentPage = await parent.newPage()
    await openEmailedLink(parentPage, real, beforeReal)
    await expect(parentPage).toHaveURL(new RegExp(`/${CHURCH}/family`))
    await expect(parentPage.getByTestId('family-child')).toContainText('Caloy')
    await parent.close()
})

test('downloads the families list as Excel', async ({ page }) => {
    await page.goto(`/${CHURCH}/families`)

    const [download] = await Promise.all([
        page.waitForEvent('download'),
        page.getByTestId('export').click(),
    ])

    expect(download.suggestedFilename()).toMatch(/\.xlsx$/)
    const bytes = readFileSync((await download.path())!)
    // An .xlsx is a zip: it starts with "PK".
    expect(bytes.subarray(0, 2).toString()).toBe('PK')
})

test('the welcome page is there', async ({ page }) => {
    await page.goto('/')
    await expect(page.getByRole('heading', { name: 'NxtGen' })).toBeVisible()
})
