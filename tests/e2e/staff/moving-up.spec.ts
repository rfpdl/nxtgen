import { navTo } from '../support/menu'
import { expect, test } from '../support/test'
import { CHURCH, movingUpChild, sessionFile } from '../support/world'

/*
 * Moving-Up (TODO #27; DECISIONS 2026-11-06): a church admin moves children up a Level — from Level 4 they graduate —
 * then puts one back, then everyone. Every browser shares the church, so the journey unticks every Level and ticks
 * only its own Navarro children (E2ESeeder::MOVING_UP_CHILDREN), and puts them back before it ends.
 */
test.use({ storageState: sessionFile('churchAdmin') })

test('a church admin moves two children up — one graduates — then puts one back, then everyone @story#154 @story#156 @story#158 @story#160', async ({
    page,
}, info) => {
    const mika = movingUpChild('Mika', info.project.name)
    const theo = movingUpChild('Theo', info.project.name)
    const row = (name: string) => page.getByTestId('moving-up-child').filter({ hasText: name })
    const moved = (name: string) => page.getByTestId('moved-child').filter({ hasText: name })

    await page.goto(`/${CHURCH}/families`)
    await navTo(page, 'Children')
    await page.getByTestId('children-moving-up').click()
    await expect(page).toHaveURL(new RegExp(`/${CHURCH}/moving-up$`))

    // A retry after a failure part-way (CI retries once) first puts back what the failed try moved.
    for (const name of [mika, theo]) {
        const leftOver = moved(name).getByTestId('moved-child-put-back-button')
        if ((await leftOver.count()) > 0) {
            await leftOver.click()
            await expect(page.getByTestId('flash-success')).toContainText('is put back')
        }
    }

    // Everyone starts ticked; keep every Level back, then tick only this browser's two.
    for (const button of await page.getByTestId('moving-up-tick-all').all()) {
        if ((await button.textContent())?.includes('Untick all')) await button.click()
    }
    await expect(page.getByTestId('moving-up-submit')).toBeDisabled()
    await expect(
        page
            .getByTestId('moving-up-level')
            .filter({ has: row(mika) })
            .getByRole('heading'),
    ).toHaveText('Level 1 → Level 2')
    await expect(
        page
            .getByTestId('moving-up-level')
            .filter({ has: row(theo) })
            .getByRole('heading'),
    ).toHaveText('Level 4 → Graduated')
    await row(mika).click()
    await row(theo).click()

    await page.getByTestId('moving-up-submit').click()
    await page.getByTestId('moving-up-confirm').click()
    await expect(page.getByTestId('flash-success')).toHaveText('2 children moved up; 1 Graduated.')

    // The last Moving-Up: Mika to Level 2, Theo Graduated — and Mika is now listed at Level 2.
    await expect(page.getByTestId('moved-child')).toHaveCount(2)
    await expect(moved(mika).getByTestId('moved-child-step')).toHaveText('Level 1 → Level 2')
    await expect(moved(theo).getByTestId('moved-child-step')).toHaveText('Level 4 → Graduated')
    await expect(
        page
            .getByTestId('moving-up-level')
            .filter({ has: row(mika) })
            .getByRole('heading'),
    ).toHaveText('Level 2 → Level 3')
    await expect(row(theo)).toHaveCount(0)

    // Put Mika back on her own: back at Level 1, said by whom.
    await moved(mika).getByTestId('moved-child-put-back-button').click()
    await expect(page.getByTestId('flash-success')).toHaveText('Mika is put back: Level 1.')
    await expect(moved(mika).getByTestId('moved-child-put-back')).toContainText('Put back by')
    await expect(moved(mika).getByTestId('moved-child-put-back-button')).toHaveCount(0)

    // Then everyone left: Theo comes back from Graduated.
    await page.getByTestId('moving-up-put-back-everyone').click()
    await expect(page.getByTestId('flash-success')).toHaveText('1 child put back.')
    await expect(page.getByTestId('moving-up-put-back-everyone')).toHaveCount(0)
    await expect(
        page
            .getByTestId('moving-up-level')
            .filter({ has: row(theo) })
            .getByRole('heading'),
    ).toHaveText('Level 4 → Graduated')
})
