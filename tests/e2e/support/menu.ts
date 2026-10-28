import { expect, type Page } from '@playwright/test'

/**
 * Opens the staff menu where it is hidden: on a phone the menu is a sheet behind the Menu button (the Bold look,
 * DECISIONS 2026-10-28); on a wider screen it is already a row in the bar. Waits for the bar first, so the choice is
 * made on the painted page — which one shows is CSS, settled by then.
 */
export async function openMenu(page: Page): Promise<void> {
    await expect(page.getByTestId('page-name')).toBeVisible()
    const button = page.getByTestId('menu-open')
    if (await button.isVisible()) {
        await button.click()
        await expect(page.getByTestId('menu-sheet')).toBeVisible()
    }
}
