import { expect, type Locator, type Page } from '@playwright/test'
import { checkLayout } from './test'

/**
 * Opens the menu (staff's or a parent's) where it is hidden: on a phone the menu is a sheet behind the Menu button (the Bold look,
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

/** Signs out from the menu — the sheet on a phone, the bar's row on a wider screen. */
export async function signOutFromMenu(page: Page): Promise<void> {
    await (await menuOf(page)).getByTestId('sign-out').click()
}

/** The open menu — the sheet on a narrow screen, the bar's row on a wider one — to find a menu item in. */
export async function menuOf(page: Page): Promise<Locator> {
    await openMenu(page)
    await checkLayout(page)
    const sheet = page.getByTestId('menu-sheet')
    return (await sheet.isVisible()) ? sheet : page.getByTestId('menu-row')
}

/** Goes to a page from the menu, by its label, whatever the width (a phone's sheet or the bar's row). */
export async function navTo(page: Page, label: string): Promise<void> {
    await (await menuOf(page)).getByRole('link', { name: label, exact: true }).click()
}

/** Closes the menu if it is a sheet (a phone or an upright tablet); the bar's row needs no closing. */
export async function closeMenu(page: Page): Promise<void> {
    const sheet = page.getByTestId('menu-sheet')
    if (await sheet.isVisible()) {
        await page.keyboard.press('Escape')
        await expect(sheet).toBeHidden()
    }
}

/**
 * Every navigation landmark on the page — the menu (opened, if it is a sheet), and any other (a pager) — for an
 * assertion about the whole page's navigation, whatever the width.
 */
export async function allNavigation(page: Page): Promise<Locator[]> {
    await menuOf(page)
    return page.getByRole('navigation').all()
}
