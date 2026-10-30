import { openCardFamily } from '../support/cards'
import { expect, test } from '../support/test'
import { sessionFile } from '../support/world'

/*
 * Roy 2026-10-16: consent can be recorded by the parent OR by a Lifeshaper the parent told in person — saved with
 * the staff member's name and noted on the history. No photo until it is recorded.
 *
 * On this project's own Ocampo family (E2ESeeder::CARD_CHILDREN): the first boy with no consent yet, so every project
 * — and a retry, with no reseed — has a child of its own (TODO #12).
 */
test.use({ storageState: sessionFile('lifeshaper') })

test("a Lifeshaper records the parent's yes, then takes the photo @story#66 @story#68", async ({
    page,
}, info) => {
    await openCardFamily(page, info.project.name)
    const fresh = page
        .getByTestId('family-child')
        .filter({ has: page.getByTestId('record-photo-consent') })
        .first()
    const name =
        (await fresh.getByRole('heading', { level: 3 }).first().textContent())?.trim() ?? ''
    const card = page.getByTestId('family-child').filter({ hasText: name })

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
