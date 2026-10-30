import { expect, test } from '../support/test'
import { askForNewCard, freshCardChild, openCardFamily } from '../support/cards'
import { registerAndConfirm } from '../support/forms'
import { CHURCH, SATELLITES, projectPhone, sessionFile, uniqueSurname } from '../support/world'

/*
 * Name cards (DECISIONS 2026-10-29). Issue 02: a parent registers Amara, and her family page shows staff her free first
 * card already asked for — "Card: Asked · free" — while the parent's own page shows nothing new. Issue 01: an Ocampo boy (seeded
 * through the import, so he has his card and no request) lost his; a Lifeshaper opens his family, taps "New card…",
 * picks Lost with a note, and sees "Card: Asked · not paid" in place of the button — still there after a reload.
 */
test('a newly registered child has a free first card asked for; staff ask for a new card for a child who lost his @story#130 @story#132 @story#133', async ({
    page,
    browser,
}, info) => {
    const surname = uniqueSurname('Navarro')
    await registerAndConfirm(
        page,
        {
            first: 'Lorna',
            last: surname,
            phone: projectPhone('0504442200', info.project.name),
            satellite: SATELLITES.dxb,
        },
        [{ first: 'Amara', last: surname, nickname: 'Amara', gender: 'girl', dob: '2019-05-05' }],
    )
    await expect(page).toHaveURL(new RegExp(`/${CHURCH}/family`))

    const staff = await browser.newContext({ storageState: sessionFile('lifeshaper') })
    try {
        const desk = await staff.newPage()

        // Issue 02: Amara's first card, asked for when she was registered.
        await desk.goto(`/${CHURCH}/families?q=${surname}`)
        await desk.getByTestId('family-row').getByRole('link').click()
        const amara = desk.getByTestId('family-child').filter({ hasText: 'Amara' })
        await expect(amara.getByTestId('card-request-step')).toHaveText('Card: Asked · free')
        await expect(amara.getByTestId('new-card')).toHaveCount(0)

        // Issue 01: an Ocampo boy lost his card — the first still without a request (support/cards.ts).
        await openCardFamily(desk, info.project.name)
        const name = await freshCardChild(desk)
        const kid = desk.getByTestId('family-child').filter({ hasText: name })
        await askForNewCard(desk, name, 'lost', 'Lost at the park')

        await expect(kid.getByTestId('card-request-step')).toHaveText('Card: Asked · not paid')
        await expect(kid.getByTestId('new-card')).toHaveCount(0)

        // It is kept: opened again, the family page still shows it in place of the button.
        await desk.reload()
        await expect(kid.getByTestId('card-request-step')).toHaveText('Card: Asked · not paid')
        await expect(kid.getByTestId('new-card')).toHaveCount(0)
    } finally {
        await staff.close()
    }

    // The parent's own page: nothing about cards.
    await page.reload()
    await expect(page.getByTestId('family-child').filter({ hasText: 'Amara' })).toBeVisible()
    await expect(page.getByTestId('child-card-request')).toHaveCount(0)
    await expect(page.getByText('New card…')).toHaveCount(0)
})
