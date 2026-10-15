import { expect, test } from '@playwright/test'
import { fillChild, fillFamily } from '../support/forms'
import { mailCount } from '../support/mailbox'
import { openEmailedLink, signInByEmail } from '../support/sign-in'
import { CHURCH, SATELLITES, uniqueEmail } from '../support/world'

/*
 * Journey 1 — the one the stakeholders care about most: a parent registers their family on their phone,
 * confirms their email from the link, sees their family page, and adds another child later.
 */
test('a parent registers, confirms their email and sees their family', async ({ page }) => {
    const email = uniqueEmail('parent')
    const before = mailCount(email)

    await page.goto(`/${CHURCH}/register`)
    await fillFamily(
        page,
        { first: 'Joy', last: 'Reyes', email, phone: '0501234567', satellite: SATELLITES.dxb },
        [
            {
                first: 'Gabriel',
                last: 'Reyes',
                nickname: 'Gabby',
                gender: 'boy',
                dob: '2017-05-10',
                allergies: 'Peanuts',
            },
            { first: 'Sofia', last: 'Reyes', nickname: 'Sofi', gender: 'girl', dob: '2019-11-02' },
        ],
    )
    await page.getByTestId('register-submit').click()

    await expect(page).toHaveURL(new RegExp(`/${CHURCH}/register/thanks`))
    await expect(page.getByRole('heading', { name: 'Thank you' })).toBeVisible()

    // The confirmation email → Continue → their family page.
    await openEmailedLink(page, email, before)
    await expect(page).toHaveURL(new RegExp(`/${CHURCH}/family`))
    await expect(page.getByRole('heading', { name: 'My family' })).toBeVisible()
    await expect(page.getByTestId('guardian')).toContainText('Joy Reyes')
    await expect(page.getByTestId('family-child')).toHaveCount(2)
    await expect(
        page.getByTestId('family-child').filter({ hasText: 'Gabby' }).getByTestId('allergy'),
    ).toBeVisible()

    // Later: a new baby brother.
    await page.getByTestId('open-add-child').click()
    await fillChild(page, 0, {
        first: 'Lucas',
        last: 'Reyes',
        nickname: 'Luke',
        gender: 'boy',
        dob: '2021-01-20',
    })
    await page.getByTestId('add-child-submit').click()
    await expect(page.getByTestId('family-child')).toHaveCount(3)
    await expect(page.getByTestId('family-child').filter({ hasText: 'Luke' })).toBeVisible()

    // A shared family phone: sign out, and the family page is closed again.
    await page.getByTestId('sign-out').click()
    await expect(page.getByRole('heading', { name: 'NxtGen' })).toBeVisible()
    await page.goto(`/${CHURCH}/family`)
    await expect(page).toHaveURL(/\/login/)
})

test('a registered parent signs in again with just their email', async ({ page }) => {
    const email = uniqueEmail('returning')
    const before = mailCount(email)
    await page.goto(`/${CHURCH}/register`)
    await fillFamily(
        page,
        { first: 'Ana', last: 'Lim', email, phone: '0507654321', satellite: SATELLITES.house },
        [{ first: 'Noah', last: 'Lim', nickname: 'Noah', gender: 'boy', dob: '2018-02-02' }],
    )
    await page.getByTestId('register-submit').click()
    await openEmailedLink(page, email, before)
    await page.context().clearCookies()

    await signInByEmail(page, email)

    await expect(page).toHaveURL(new RegExp(`/${CHURCH}/family`))
    await expect(page.getByTestId('family-child')).toContainText('Noah')
})

test('the form says what is missing, in plain words', async ({ page }) => {
    await page.goto(`/${CHURCH}/register`)
    await page.getByTestId('register-submit').click()

    await expect(page).toHaveURL(new RegExp(`/${CHURCH}/register$`))
    await expect(page.getByRole('alert').first()).toBeVisible()
    // The allergy question must be answered, and a "yes" needs the details.
    await expect(page.getByText('Please answer: does your child have any allergies?')).toBeVisible()
    await expect(page.getByText('Please enter the name your child goes by.')).toBeVisible()

    await page.getByTestId('child-0-has-allergies-yes').check()
    await page.getByTestId('register-submit').click()
    await expect(page.getByText('Please tell us what your child is allergic to.')).toBeVisible()
})
