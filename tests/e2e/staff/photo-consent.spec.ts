import { expect, test } from '@playwright/test'
import { CHURCH, FAMILIES, sessionFile } from '../support/world'

/*
 * Roy 2026-10-16: consent can be recorded by the parent OR by a Lifeshaper the parent told in person — saved with
 * the staff member's name and noted on the history. No photo until it is recorded.
 */
test.use({ storageState: sessionFile('lifeshaper') })

test("a Lifeshaper records the parent's yes, then takes the photo", async ({ page }) => {
    await page.goto(`/${CHURCH}/families?q=Dela%20Cruz`)
    await page
        .getByTestId('family-row')
        .filter({ hasText: FAMILIES.dxb.guardian })
        .getByRole('link')
        .click()
    const card = page.getByTestId('family-child').filter({ hasText: FAMILIES.dxb.child })

    await expect(card.getByTestId('take-photo')).toHaveCount(0)
    await card.getByTestId('record-photo-consent').click()
    await expect(card).toContainText('Are you sure the parent said yes')
    await card.getByTestId('confirm-photo-consent').click()
    await expect(page.getByTestId('flash-success')).toContainText('Consent recorded')

    await card.getByTestId('take-photo').setInputFiles('tests/Fixtures/phone-photo-rotated-gps.jpg')
    await expect(page.getByTestId('flash-success')).toContainText('photo is saved')
    await expect(card.getByTestId('child-photo-img')).toBeVisible()
    await expect(page.getByTestId('history')).toContainText(
        'photo consent recorded by Lifeshaper — the parent said yes in person',
    )
})
