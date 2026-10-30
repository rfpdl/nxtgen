import { navTo } from '../support/menu'
import { expect, test } from '../support/test'
import { CHURCH, DOOR, LEVELS, sessionFile, type DoorProject } from '../support/world'

/*
 * The children list (plan §6) and a church admin correcting a child's Level (plan §3.2; DECISIONS 2026-10-17 —
 * Levels replaced groups). The child's age suggested Level 3 at registration; the correction is noted on the
 * history, and the list agrees.
 *
 * On this project's own Torres girls (E2ESeeder::LEVEL_CHILDREN, Level 3 by age): the first still at Level 3, found by
 * the Level filter and her family's names — so every project, and a retry, has a child of its own (TODO #12).
 */
test.use({ storageState: sessionFile('churchAdmin') })

test('an admin finds a child by Level on the children list and corrects their Level @story#58 @story#64', async ({
    page,
}, info) => {
    const family = `${LEVELS.surname} ${DOOR.guardians[info.project.name as DoorProject]}`
    await page.goto(`/${CHURCH}/families`)
    await navTo(page, 'Children')
    await expect(page.getByRole('heading', { name: /^Children/ })).toBeVisible()
    await page.getByTestId('filter-level').selectOption({ label: LEVELS.suggested })
    await expect(page).toHaveURL(/level=3/)
    await page.getByTestId('filter-q').fill(family)
    await page.getByTestId('filter-q').press('Enter')
    await expect(page).toHaveURL(/[?&]q=/)
    const first = page.getByTestId('child-row').first()
    await expect(first).toContainText(LEVELS.suggested)
    const child =
        (await first.textContent())?.match(new RegExp(LEVELS.children.join('|')))?.[0] ?? ''
    expect(LEVELS.children).toContain(child)
    const row = page.getByTestId('child-row').filter({ hasText: `${child} ${LEVELS.surname}` })
    await expect(row).toContainText(LEVELS.suggested)

    await row.getByRole('link', { name: child }).first().click()
    // The Level control sits inside the child's card.
    const levelPicker = page
        .getByTestId('family-child')
        .filter({ hasText: child })
        .locator('select[data-testid^="level-"]')
    await expect(levelPicker).toContainText(LEVELS.suggested)

    await levelPicker.selectOption({ label: 'Level 4' })
    await expect(page.getByTestId('flash-success')).toContainText('is now in Level 4')
    await expect(page.getByTestId('history')).toContainText('Level changed from Level 3 to Level 4')

    // The children list agrees.
    await page.goto(`/${CHURCH}/children?q=${encodeURIComponent(`${child} ${family}`)}`)
    await expect(page.getByTestId('child-row').filter({ hasText: child })).toContainText('Level 4')
})
