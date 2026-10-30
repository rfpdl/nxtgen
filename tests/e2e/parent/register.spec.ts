import { expect, test } from '../support/test'
import { fillChild, fillFamily } from '../support/forms'
import { mailCount } from '../support/mailbox'
import { signOutFromMenu } from '../support/menu'
import { openEmailedLink, signInByEmail } from '../support/sign-in'
import { CHURCH, SATELLITES, uniqueEmail } from '../support/world'

/*
 * Journey 1 — the one the stakeholders care about most: a parent registers their family on their phone,
 * confirms their email from the link, sees their family page, and adds another child later.
 */
test('a parent registers, confirms their email and sees their family @story#4 @story#28 @story#87', async ({
    page,
}) => {
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
    // The father too: he will not sign in, so the form asks him for no email.
    await page.getByTestId('add-guardian').click()
    await page.getByTestId('other-guardian-0-first-name').fill('Ramon')
    await page.getByTestId('other-guardian-0-last-name').fill('Reyes')
    await page.getByTestId('other-guardian-0-phone').fill('0501234568')
    await expect(page.getByTestId('other-guardian-0-email')).toHaveCount(0)
    await page.getByTestId('register-submit').click()

    await expect(page).toHaveURL(new RegExp(`/${CHURCH}/register/thanks`))
    await expect(page.getByRole('heading', { name: 'Thank you' })).toBeVisible()

    // The confirmation email → Continue → their family page.
    await openEmailedLink(page, email, before)
    await expect(page).toHaveURL(new RegExp(`/${CHURCH}/family`))
    await expect(page.getByTestId('page-name')).toHaveText('My family')
    await expect(page.getByRole('heading', { name: 'The Reyes family' })).toBeVisible()
    await expect(page.getByTestId('guardian')).toHaveText([
        /Joy Reyes · Mother/,
        /Ramon Reyes · Father · 0501234568/,
    ])
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
    await signOutFromMenu(page)
    await expect(
        page.getByRole('heading', { name: 'Welcome to NxtGen', exact: true }),
    ).toBeVisible()
    await page.goto(`/${CHURCH}/family`)
    await expect(page).toHaveURL(/\/login/)
})

test('a registered parent signs in again with just their email @story#9 @story#12', async ({
    page,
}) => {
    const email = uniqueEmail('returning')
    const before = mailCount(email)
    await page.goto(`/${CHURCH}/register`)
    await fillFamily(
        page,
        { first: 'Rhea', last: 'Lim', email, phone: '0507654321', satellite: SATELLITES.house },
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
