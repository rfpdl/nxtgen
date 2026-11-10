import { expect, type Locator, type Page } from '@playwright/test'
import { DUBAI } from './world'

/** A class's own door: an opened class (Session) is at its 8-letter code (spec item 16). */
export const DOOR_URL = /\/[a-z0-9]{8}\/checkin$/

/** Who's in for an opened class, at the same code. */
export const WHOS_IN_URL = /\/[a-z0-9]{8}\/whos-in$/

/** Today's class on Serve's first page, by its name. */
export function serveClass(page: Page, name: string): Locator {
    return page
        .getByTestId('serve-class')
        .filter({ has: page.getByRole('heading', { name, exact: true }) })
}

/**
 * Goes to Serve's first page at CCF Dubai, and through an OPEN class's card to its door. The seeded 1PM and 7PM are
 * open for every browser (E2ESeeder) — a journey that closes a class closes its own one-time class, never these.
 */
export async function goToDoor(page: Page, name: string): Promise<void> {
    await page.goto(`/${DUBAI}/serve`)
    await expect(serveClass(page, name).getByTestId('serve-class-state')).toHaveText('Open')
    await serveClass(page, name).getByTestId('serve-door').click()
    await expect(page).toHaveURL(DOOR_URL)
}
