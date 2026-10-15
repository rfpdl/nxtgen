import { expect, test } from '@playwright/test'
import { fillFamily } from '../support/forms'
import { mailCount } from '../support/mailbox'
import { openEmailedLink } from '../support/sign-in'
import { CHURCH, SATELLITES, uniqueEmail } from '../support/world'

/*
 * A parent keeps their own details right (plan §3.2 "own family, not status/group"; review B5): the family's
 * and their own details, and a child's — including health — from the family page, on their phone.
 */
test("a parent edits their family details and a child's health details", async ({ page }) => {
    const email = uniqueEmail('editor')
    const before = mailCount(email)
    await page.goto(`/${CHURCH}/register`)
    await fillFamily(
        page,
        { first: 'Mia', last: 'Cruz', email, phone: '0501110000', satellite: SATELLITES.dxb },
        [{ first: 'Leo', last: 'Cruz', nickname: 'Leo', gender: 'boy', dob: '2018-01-15' }],
    )
    await page.getByTestId('register-submit').click()
    await openEmailedLink(page, email, before)
    await expect(page).toHaveURL(new RegExp(`/${CHURCH}/family`))

    // Family + own details: moved house, new number, now in a DGroup.
    await page.getByTestId('edit-family').click()
    await page.getByTestId('guardian-phone').fill('0559990000')
    await page.getByTestId('area').fill('Deira')
    await page.getByTestId('in-dgroup').check()
    await page.getByTestId('dgroup-area').fill('Deira')
    await page.getByTestId('edit-family-submit').click()
    await expect(page.getByTestId('flash-success')).toContainText('saved')
    await expect(page.getByText('Lives in Deira')).toBeVisible()
    await expect(page.getByTestId('guardian')).toContainText('0559990000')

    // A child's health: an allergy found since registering, and a fear.
    const card = page.getByTestId('family-child').filter({ hasText: 'Leo' })
    await card.getByTestId('edit-child').click()
    await card.getByTestId('child-0-has-allergies-yes').check()
    await card.getByTestId('child-0-allergies').fill('Shrimp')
    await card.getByLabel('Things your child is afraid of').fill('Thunder')
    await card.getByTestId('edit-child-submit').click()
    await expect(page.getByTestId('flash-success')).toBeVisible()
    await expect(card.getByTestId('allergy')).toContainText('Shrimp')
    await expect(card.getByTestId('health')).toContainText('Thunder')
})
