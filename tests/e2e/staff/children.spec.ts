import { expect, test } from '@playwright/test'
import { CHURCH, FAMILIES, sessionFile } from '../support/world'

/*
 * The children list (plan §6) and a church admin correcting a child's Level (plan §3.2; DECISIONS 2026-10-17 —
 * Levels replaced groups). The child's age suggested Level 3 at registration; the correction is noted on the
 * history, and the list agrees.
 */
test.use({ storageState: sessionFile('churchAdmin') })

test('an admin finds a child by Level on the children list and corrects their Level @story#58 @story#64', async ({
    page,
}) => {
    await page.goto(`/${CHURCH}/families`)
    await page.getByRole('link', { name: 'Children', exact: true }).click()
    await expect(page.getByRole('heading', { name: /^Children/ })).toBeVisible()
    await page.getByTestId('filter-level').selectOption({ label: 'Level 3' })
    await expect(page).toHaveURL(/level=3/)
    await page.getByTestId('filter-q').fill(FAMILIES.dxb.child)
    await page.getByTestId('filter-q').press('Enter')
    const row = page.getByTestId('child-row').filter({ hasText: FAMILIES.dxb.child })
    await expect(row).toContainText('Level 3')

    await row.getByRole('link', { name: FAMILIES.dxb.child }).click()
    // The Level control sits inside the child's card.
    const levelPicker = page
        .getByTestId('family-child')
        .filter({ hasText: FAMILIES.dxb.child })
        .locator('[data-testid^="level-"]')
    await expect(levelPicker).toContainText('Level 3')

    await levelPicker.selectOption({ label: 'Level 4' })
    await expect(page.getByTestId('flash-success')).toContainText('is now in Level 4')
    await expect(page.getByTestId('history')).toContainText('Level changed from Level 3 to Level 4')

    // The children list agrees.
    await page.goto(`/${CHURCH}/children?q=${FAMILIES.dxb.child}`)
    await expect(
        page.getByTestId('child-row').filter({ hasText: FAMILIES.dxb.child }),
    ).toContainText('Level 4')
})
