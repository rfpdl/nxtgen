import { expect, type Page } from '@playwright/test'
import { checkLayout } from './test'
import { CARDS, CHURCH, DOOR, type DoorProject } from './world'

/**
 * Name cards: this browser project's own Ocampo family (E2ESeeder::CARD_CHILDREN), opened by staff from the families
 * list. Its boys were imported, so none has a Card request until a journey asks for one.
 */
export async function openCardFamily(page: Page, project: string): Promise<void> {
    const guardian = DOOR.guardians[project as DoorProject]
    await page.goto(`/${CHURCH}/families?q=${guardian} ${CARDS.surname}`)
    await page
        .getByTestId('family-row')
        .filter({ hasText: `${guardian} ${CARDS.surname}` })
        .getByRole('link')
        .click()
    await page.waitForURL(/\/families\/\d+$/)
    await checkLayout(page)
}

/**
 * On the open card family's page: the first boy still offering "New card…" — so a retried journey, with no reseed,
 * takes the next one. Returns his name.
 */
export async function freshCardChild(page: Page): Promise<string> {
    const fresh = page
        .getByTestId('family-child')
        .filter({ has: page.getByTestId('new-card') })
        .first()
    const name =
        (await fresh.getByRole('heading', { level: 3 }).first().textContent())?.trim() ?? ''
    expect(CARDS.children).toContain(name)
    return name
}

/** "New card…" under `name`: picks the reason, writes the note, asks — and waits for the answer. */
export async function askForNewCard(
    page: Page,
    name: string,
    reason: 'lost' | 'damaged',
    note: string,
): Promise<void> {
    const kid = page.getByTestId('family-child').filter({ hasText: name })
    await kid.getByTestId('new-card').click()
    await kid.getByTestId(`card-reason-${reason}`).click()
    await kid.getByTestId('card-note').fill(note)
    await checkLayout(page)
    await kid.getByTestId('card-request-submit').click()
    await expect(page.getByTestId('flash-success')).toContainText(
        `A new card for ${name} is asked for.`,
    )
}
