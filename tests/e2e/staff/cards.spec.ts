import { expect, test } from '@playwright/test'
import { registerAndConfirm } from '../support/forms'
import { CHURCH, SATELLITES, sessionFile, uniqueSurname } from '../support/world'

/*
 * Name cards, issue 01 (DECISIONS 2026-10-29): a parent registers Amara; she loses her card and the parent tells a
 * Lifeshaper, who opens the family, taps "New card…" under Amara, picks Lost with a note, and sees "Card: Asked · not
 * paid" in place of the button. The parent's own My family page shows nothing new.
 */
const PHONES: Record<string, string> = {
    'mobile-webkit': '0504442201',
    'mobile-chromium': '0504442202',
    'desktop-chromium': '0504442203',
}

test('a Lifeshaper asks for a new card for a child who lost hers; the parent sees nothing new @story#130 @story#132 @story#133', async ({
    page,
    browser,
}, info) => {
    const surname = uniqueSurname('Navarro')
    await registerAndConfirm(
        page,
        {
            first: 'Lorna',
            last: surname,
            phone: PHONES[info.project.name] ?? '0504442200',
            satellite: SATELLITES.dxb,
        },
        [{ first: 'Amara', last: surname, nickname: 'Amara', gender: 'girl', dob: '2019-05-05' }],
    )
    await expect(page).toHaveURL(new RegExp(`/${CHURCH}/family`))

    const staff = await browser.newContext({ storageState: sessionFile('lifeshaper') })
    try {
        const desk = await staff.newPage()
        await desk.goto(`/${CHURCH}/families?q=${surname}`)
        await desk.getByTestId('family-row').getByRole('link').click()
        const amara = desk.getByTestId('family-child').filter({ hasText: 'Amara' })

        await amara.getByTestId('new-card').click()
        await amara.getByTestId('card-reason-lost').click()
        await amara.getByTestId('card-note').fill('Lost at the park')
        await amara.getByTestId('card-request-submit').click()

        await expect(desk.getByTestId('flash-success')).toContainText(
            'A new card for Amara is asked for.',
        )
        await expect(amara.getByTestId('card-request-step')).toHaveText('Card: Asked · not paid')
        await expect(amara.getByTestId('new-card')).toHaveCount(0)

        // It is kept: opened again, the family page still shows it in place of the button.
        await desk.reload()
        await expect(amara.getByTestId('card-request-step')).toHaveText('Card: Asked · not paid')
        await expect(amara.getByTestId('new-card')).toHaveCount(0)
    } finally {
        await staff.close()
    }

    // The parent's own page: nothing about cards.
    await page.reload()
    await expect(page.getByTestId('family-child').filter({ hasText: 'Amara' })).toBeVisible()
    await expect(page.getByTestId('child-card-request')).toHaveCount(0)
    await expect(page.getByText('New card…')).toHaveCount(0)
})
