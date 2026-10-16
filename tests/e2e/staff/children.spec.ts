import { expect, test } from '@playwright/test'
import { CHURCH, FAMILIES, sessionFile } from '../support/world'

/*
 * The children list (plan §6) and a church admin correcting a child's group (plan §3.2) — refused when the group
 * does not fit the child, and noted on the history when it does.
 */
test.use({ storageState: sessionFile('churchAdmin') })

test('an admin finds a child on the children list and corrects their group', async ({ page }) => {
    await page.goto(`/${CHURCH}/families`)
    await page.getByRole('link', { name: 'Children', exact: true }).click()
    await expect(page.getByRole('heading', { name: /^Children/ })).toBeVisible()
    await page.getByTestId('filter-q').fill(FAMILIES.dxb.child)
    await page.getByTestId('filter-q').press('Enter')
    const row = page.getByTestId('child-row').filter({ hasText: FAMILIES.dxb.child })
    await expect(row).toContainText('Level 3 Boys')

    await row.getByRole('link', { name: FAMILIES.dxb.child }).click()
    const groupPicker = page.locator('[data-testid^="group-"]').first()

    // A girls' group does not fit a boy.
    await groupPicker.selectOption({ label: 'Level 3 Girls' })
    await expect(page.getByText('Level 3 Girls is for girls.')).toBeVisible()

    await groupPicker.selectOption({ label: 'Level 4 Boys' })
    await expect(page.getByTestId('flash-success')).toContainText('is now in Level 4 Boys')
    await expect(page.getByTestId('history')).toContainText(
        'group changed from Level 3 Boys to Level 4 Boys',
    )

    // The children list agrees.
    await page.goto(`/${CHURCH}/children?q=${FAMILIES.dxb.child}`)
    await expect(
        page.getByTestId('child-row').filter({ hasText: FAMILIES.dxb.child }),
    ).toContainText('Level 4 Boys')
})
