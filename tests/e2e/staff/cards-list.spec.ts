import { type Page } from '@playwright/test'
import { expect, test } from '../support/test'
import { askForNewCard, freshCardChild, openCardFamily } from '../support/cards'
import { openMenu } from '../support/menu'
import { xlsxRows } from '../support/xlsx'
import { CARDS, CHURCH, DOOR, sessionFile, type DoorProject } from '../support/world'

/*
 * Cards (name cards 03, DECISIONS 2026-10-29). At the desk a Lifeshaper asks for new cards for three Ocampo boys (the
 * first still without one, so a retry takes others). The church admin opens Cards from the menu and finds each boy by
 * name: the first is paid, approved and marked printed, then given at the desk — the Pill gone, "New card…" back; the
 * second goes on a print list ("Make print list" on Approved; downloaded, its Barcode the code the door scans), is
 * printed with the list's "Mark these N printed" and marked Given from the Printed tab; the third is refused with a reason.
 * A moved row leaves its old tab.
 *
 * Retry-safe: each boy has at most one OPEN request, so on Asked, Approved and Printed his name finds one row; on the
 * closed tab (his history) the newest filed comes first.
 */
async function find(page: Page, tab: string, name: string): Promise<void> {
    await page.goto(`/${CHURCH}/card-requests?tab=${tab}`)
    await page.getByTestId('cards-search').fill(`${name} ${CARDS.surname}`)
    await page.getByTestId('cards-search-go').click()
    await expect(page).toHaveURL(new RegExp(`[?&]q=${name}`))
    await expect(page).toHaveURL(new RegExp(`[?&]tab=${tab}`))
    await expect(page.getByTestId(`card-tab-${tab}`)).toHaveAttribute('aria-current', 'page')
}

test('an admin finds, pays, approves, prints, gives and refuses new cards on Cards; staff give a printed one at the desk @story#134 @story#136 @story#138 @story#140 @story#142 @story#144 @story#146', async ({
    browser,
}, info) => {
    test.setTimeout(60_000)
    const family = `${DOOR.guardians[info.project.name as DoorProject]} ${CARDS.surname}`
    const lifeshaper = await browser.newContext({ storageState: sessionFile('lifeshaper') })
    const admin = await browser.newContext({ storageState: sessionFile('churchAdmin') })
    try {
        const desk = await lifeshaper.newPage()
        await openCardFamily(desk, info.project.name)
        const lost = await freshCardChild(desk)
        await askForNewCard(desk, lost, 'lost', 'Lost at the park')
        const torn = await freshCardChild(desk)
        await askForNewCard(desk, torn, 'damaged', 'Torn in half')
        const refused = await freshCardChild(desk)
        await askForNewCard(desk, refused, 'damaged', 'Chewed by the dog')
        const familyPage = desk.url()

        const office = await admin.newPage()
        await office.goto(`/${CHURCH}/families`)
        await openMenu(office)
        await office.getByRole('link', { name: 'Cards', exact: true }).first().click()
        await expect(office.getByTestId('card-tab-asked')).toHaveAttribute('aria-current', 'page')

        const row = (name: string) =>
            office
                .getByTestId('card-request')
                .filter({ hasText: `${name} ${CARDS.surname}` })
                .filter({ hasText: family })
        const flash = office.getByTestId('flash-success')

        // Asked: paid, then approved — and it leaves the Asked tab.
        for (const name of [lost, torn]) {
            await find(office, 'asked', name)
            await expect(row(name).getByTestId('card-fee')).toHaveText('AED 10 · not paid')
            await row(name).getByTestId('card-action-mark_paid').click()
            await expect(flash).toContainText(`${name}'s card is marked paid.`)
            await expect(row(name).getByTestId('card-fee')).toHaveText('AED 10 · paid')
            await row(name).getByTestId('card-action-approve').click()
            await expect(flash).toContainText(`${name}'s card is approved for printing.`)
            await expect(row(name)).toHaveCount(0)
        }

        // The third is refused, with a reason.
        await find(office, 'asked', refused)
        await expect(row(refused).getByTestId('card-reason')).toHaveText(
            'Damaged — Chewed by the dog',
        )
        await row(refused).getByTestId('card-action-refuse').click()
        await row(refused).getByTestId('refuse-reason').fill('Found it at home')
        await row(refused).getByTestId('refuse-submit').click()
        await expect(flash).toContainText(`${refused}'s card is refused.`)

        // Approved: one printed on its own, then the rest all at once.
        await find(office, 'approved', lost)
        await row(lost).getByTestId('card-action-mark_printed').click()
        await expect(flash).toContainText(`${lost}'s card is marked printed.`)
        await find(office, 'approved', torn)
        // As many as the tab shows for his name (a retry may show an earlier run's namesake still approved too).
        const shown = Number(
            /\((\d+)\)/.exec((await office.getByTestId('cards-title').textContent()) ?? '')?.[1],
        )
        expect(shown).toBeGreaterThan(0)
        await office.getByTestId('cards-make-print-list').click()
        await expect(flash).toHaveText(
            `Print list made: ${shown === 1 ? '1 card' : `${shown} cards`}.`,
        )
        const printList = office.getByTestId('print-list').first()
        const [download] = await Promise.all([
            office.waitForEvent('download'),
            printList.getByTestId('print-list-download').click(),
        ])
        expect(download.suggestedFilename()).toMatch(
            /^nxtgen-print-list-\d+-\d{4}-\d{2}-\d{2}\.xlsx$/,
        )
        const rows = xlsxRows((await download.path())!)
        expect(rows[0]).toBe('Full name|Nickname|Level|Squad|Barcode')
        expect(rows).toContainEqual(
            expect.stringMatching(
                new RegExp(
                    `^${torn} ${CARDS.surname}\\|${torn}\\|.*\\|${torn.toUpperCase()}${CARDS.surname.toUpperCase()}$`,
                ),
            ),
        )
        await printList.getByTestId('print-list-mark').click()
        await expect(flash).toContainText(`${shown} marked printed.`)

        // Printed: the torn one is given from Cards, and leaves the tab.
        await find(office, 'printed', torn)
        await row(torn).getByTestId('card-action-give').click()
        await expect(flash).toContainText(`${torn}'s new card is given.`)
        await expect(row(torn)).toHaveCount(0)

        // Given or refused: each one's newest row says how it ended.
        await find(office, 'closed', refused)
        await expect(row(refused).first().getByTestId('card-step')).toHaveText(
            'Refused: Found it at home',
        )
        await find(office, 'closed', torn)
        await expect(row(torn).first().getByTestId('card-step')).toHaveText('Given')

        // At the next class, at the desk: the lost one is Given.
        await desk.goto(familyPage)
        const kid = desk.getByTestId('family-child').filter({ hasText: lost })
        await expect(kid.getByTestId('card-request-step')).toHaveText('Card: Printed · paid')
        await kid.getByTestId('card-given').click()
        await expect(desk.getByTestId('flash-success')).toContainText(
            `${lost}'s new card is given.`,
        )
        await expect(kid.getByTestId('card-request-step')).toHaveCount(0)
        await expect(kid.getByTestId('new-card')).toBeVisible()
        for (const name of [torn, refused]) {
            await expect(
                desk.getByTestId('family-child').filter({ hasText: name }).getByTestId('new-card'),
            ).toBeVisible()
        }
    } finally {
        await lifeshaper.close()
        await admin.close()
    }
})
