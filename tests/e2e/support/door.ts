import { expect, type Locator, type Page } from '@playwright/test'
import { DUBAI, doorSaturday } from './world'

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

/** Adds a one-time class for the door's Saturday at CCF Dubai, late so it never comes before 1PM (Classes page). */
export async function addTodayClass(admin: Page, name: string): Promise<void> {
    await admin.goto(`/${DUBAI}/admin/classes`)
    await admin.getByTestId('open-add-schedule').click()
    const add = admin.getByTestId('add-schedule')
    await add.getByTestId('schedule-name').fill(name)
    await add.getByTestId('schedule-one-time').click()
    await add.getByTestId('schedule-date').fill(doorSaturday())
    await add.getByTestId('schedule-starts-at').fill('22:00')
    await add.getByTestId('schedule-ends-at').fill('23:00')
    await add.getByTestId('schedule-submit').click()
    await expect(admin.getByTestId('flash-success')).toContainText(`Added "${name}"`)
}

/** Retires a class a journey added, so the door's Saturday is left to the other journeys. */
export async function retireClass(admin: Page, name: string): Promise<void> {
    await admin.goto(`/${DUBAI}/admin/classes`)
    const mine = admin.getByTestId('schedule').filter({ hasText: name })
    await mine.getByTestId('retire-schedule').click()
    await mine.getByTestId('confirm-retire').click()
    await expect(admin.getByTestId('flash-success')).toContainText(`Retired "${name}".`)
}
