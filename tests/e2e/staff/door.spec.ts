import { expect, test, type Locator, type Page } from '@playwright/test'
import {
    CHURCH,
    CLASSES,
    DOOR,
    SATELLITES,
    SCAN,
    sessionFile,
    type DoorProject,
} from '../support/world'

/*
 * Journey — a Lifeshaper at the door, on their phone (DECISIONS 2026-10-26): pick today's 1PM, search the church,
 * tick a family's children and check them in; undo a mistake. The e2e server's door day is a Saturday, when 1PM
 * meets. Each browser checks in its own Cruz family (E2ESeeder), but all three share today's 1PM Session — so the
 * count is read before and compared after (the browsers run one after another, never at once).
 */
test.use({ storageState: sessionFile('lifeshaper') })

function guardian(project: string): string {
    return DOOR.guardians[project as DoorProject]
}

async function openDoor(page: Page): Promise<void> {
    await page.goto(`/${CHURCH}/door`)
    // The class picker is a row of chips, a radio group (the Bold look): pick 1PM.
    const chip = page.getByTestId('door-class').getByRole('radio', { name: CLASSES.onePm.name })
    await chip.click()
    await expect(chip).toHaveAttribute('aria-checked', 'true')
    await expect(page.getByTestId('door-heading')).toContainText(`${SATELLITES.dxb} · Sat `)
    // Phone first: nothing pushes the page sideways.
    expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(
        page.viewportSize()?.width ?? 0,
    )
}

async function count(page: Page): Promise<number> {
    const text = (await page.getByTestId('checked-in-count').textContent()) ?? ''
    return Number(text.match(/\d+/)?.[0] ?? NaN)
}

async function findFamily(page: Page, search: string, primaryName: string): Promise<Locator> {
    await page.getByTestId('door-search').fill(search)
    await page.getByTestId('door-search').press('Enter')
    const card = page.getByTestId('door-family').filter({ hasText: primaryName })
    await expect(card).toBeVisible()
    await expectInsideCard(card)
    return card
}

/** Nothing on a family card pokes out past its edges (a phone screen has none to spare). */
async function expectInsideCard(card: Locator): Promise<void> {
    const outside = await card.evaluate((el) => {
        const box = el.getBoundingClientRect()
        return [...el.querySelectorAll('*')]
            .map((e) => ({ tag: e.tagName, r: e.getBoundingClientRect() }))
            .filter(
                ({ r }) => r.width > 0 && (r.left < box.left - 0.5 || r.right > box.right + 0.5),
            )
            .map(({ tag }) => tag)
    })
    expect(outside).toEqual([])
}

function child(card: Locator, nickname: string): Locator {
    return card
        .getByTestId('door-child')
        .filter({ has: card.page().getByTestId('child-name').getByText(nickname, { exact: true }) })
}

test('checks in two children of a family at 1PM — one made active again — sees them in the list and undoes one there @story#103 @story#106 @story#107 @story#110 @story#116', async ({
    page,
}, info) => {
    await openDoor(page)
    const before = await count(page)
    const card = await findFamily(page, 'Cruz', `${guardian(info.project.name)} Cruz`)

    // The allergy is a flag, never the detail.
    await expect(child(card, DOOR.cruz.allergic).getByTestId('allergy-flag')).toBeVisible()
    await expect(card).not.toContainText(DOOR.allergy)

    await child(card, DOOR.cruz.allergic).getByTestId('tick-child').check()
    await child(card, DOOR.cruz.inactive).getByTestId('tick-child').check()
    await expect(card.getByTestId('make-active')).toContainText(
        `${DOOR.cruz.inactive} is inactive. Make them active again?`,
    )
    await expect(card.getByTestId('check-in')).toBeDisabled()
    await expectInsideCard(card)
    await card.getByTestId('make-active-yes').click()
    await card.getByTestId('check-in').click()

    await expect(page.getByTestId('flash-success')).toContainText(
        `Checked in ${DOOR.cruz.allergic} and ${DOOR.cruz.inactive}.`,
    )
    await expect(child(card, DOOR.cruz.allergic).getByTestId('checked-in')).toBeVisible()
    await expect(child(card, DOOR.cruz.inactive).getByTestId('checked-in')).toBeVisible()
    await expect(child(card, DOOR.cruz.inactive)).not.toContainText(/inactive/i)
    await expect(page.getByTestId('checked-in-count')).toHaveText(`${before + 2} checked in`)

    // Below the family, who is in: newest first, so this browser's two are on top; the count is the list's length.
    const rows = page.getByTestId('checked-in-row')
    await expect(rows).toHaveCount(before + 2)
    await expect(page.getByTestId('checked-in-list').locator('h2')).toHaveText('Checked in')
    await expect(rows.nth(0).getByTestId('checked-in-name')).toHaveText(DOOR.cruz.inactive)
    await expect(rows.nth(1).getByTestId('checked-in-name')).toHaveText(DOOR.cruz.allergic)
    await expect(rows.nth(1)).toContainText('Level 2 Girls')
    await expect(rows.nth(1)).toContainText(/\d{2}:\d{2}/)
    await expect(rows.nth(1)).not.toContainText(DOOR.allergy)

    // A mistake: undo Noah from the list. He leaves it, can be ticked again, and the count goes down.
    await rows.nth(0).getByTestId('checked-in-undo').click()
    await expect(page.getByTestId('flash-success')).toContainText(
        `Undid ${DOOR.cruz.inactive}'s check-in.`,
    )
    await expect(child(card, DOOR.cruz.inactive).getByTestId('tick-child')).toBeEnabled()
    await expect(page.getByTestId('checked-in-count')).toHaveText(`${before + 1} checked in`)
    await expect(rows).toHaveCount(before + 1)
    await expect(rows.nth(0).getByTestId('checked-in-name')).toHaveText(DOOR.cruz.allergic)
})

test('narrows the search with more words — every word on the same family @story#103', async ({
    page,
}, info) => {
    await openDoor(page)
    const parent = guardian(info.project.name)
    const families = page.getByTestId('door-family')

    // "Reyes" is every browser's Reyes family (E2ESeeder); with the parent's name too, only this one.
    await page.getByTestId('door-search').fill('Reyes')
    await page.getByTestId('door-search').press('Enter')
    await expect(families.filter({ hasText: `${parent} Reyes` })).toHaveCount(1)
    expect(await families.count()).toBeGreaterThan(1)

    for (const words of [`${parent} Reyes`, `reyes ${parent.toLowerCase()}`]) {
        await page.getByTestId('door-search').fill(words)
        await page.getByTestId('door-search').press('Enter')
        await expect(families).toHaveCount(1)
        await expect(families.first()).toContainText(`${parent} Reyes`)
    }
})

test('a name card typed in by a desk scanner shows the child ticked — one tap from checking in @story#128', async ({
    page,
}, info) => {
    await openDoor(page)
    const project = info.project.name as DoorProject
    // The family is on screen already (a surname search, nobody ticked); the card must still tick her.
    await page.getByTestId('door-search').fill('Reyes')
    await page.getByTestId('door-search').press('Enter')
    await expect(
        page.getByTestId('door-family').filter({ hasText: `${guardian(project)} Reyes` }),
    ).toBeVisible()

    await page.getByTestId('door-search').fill(SCAN.codes[project])
    await page.getByTestId('door-search').press('Enter')

    const card = page.getByTestId('door-family')
    await expect(card).toHaveCount(1)
    await expect(card).toContainText(`${guardian(project)} Reyes`)
    await expectInsideCard(card)
    const ticks = card.getByTestId('tick-child')
    const amara = child(card, SCAN.child)
    await expect(amara.getByTestId('tick-child')).toHaveAttribute('aria-checked', 'true')
    expect(
        await ticks.evaluateAll(
            (all) => all.filter((t) => t.getAttribute('aria-checked') === 'true').length,
        ),
    ).toBe(1)
    await expect(card.getByTestId('check-in')).toHaveText('Check in 1 child')
    await expect(card.getByTestId('check-in')).toBeEnabled()
})

test('"Scan card" opens the camera sheet; a refused camera says to type the name, and Cancel closes it @story#128', async ({
    page,
}) => {
    // No camera here: answer as a phone does when the camera is not allowed.
    await page.addInitScript(() => {
        const refuse = (): Promise<MediaStream> =>
            Promise.reject(new DOMException('Permission denied', 'NotAllowedError'))
        Object.defineProperty(navigator, 'mediaDevices', {
            value: { getUserMedia: refuse, enumerateDevices: () => Promise.resolve([]) },
            configurable: true,
        })
    })
    await openDoor(page)

    await page.getByTestId('scan-card').click()
    const sheet = page.getByTestId('scan-sheet')
    await expect(sheet).toBeVisible()
    await expect(sheet.getByTestId('scan-message')).toHaveText(
        'The camera is not allowed. Type the name instead.',
        { timeout: 10_000 },
    )
    await sheet.getByTestId('scan-cancel').click()
    await expect(sheet).toHaveCount(0)
    await expect(page.getByTestId('scan-card')).toBeFocused()
})

test('says when the camera read a card no child matches, and shows no family @story#129', async ({
    page,
}) => {
    await openDoor(page)
    const url = new URL(page.url())
    url.searchParams.set('card', '1')
    url.searchParams.set('q', 'NOBODYHEREATALL')

    await page.goto(url.toString())

    await expect(page.getByTestId('scan-note')).toHaveText(
        'No child matches this card: NOBODYHEREATALL',
    )
    await expect(page.getByTestId('scan-note')).toHaveAttribute('role', 'alert')
    await expect(page.getByTestId('door-family')).toHaveCount(0)
})

test('shows who is in before any search @story#116', async ({ page }) => {
    await openDoor(page)
    const n = await count(page)

    await expect(page.getByTestId('door-family')).toHaveCount(0)
    await expect(page.getByTestId('checked-in-list')).toBeVisible()
    await expect(page.getByTestId('checked-in-row')).toHaveCount(n)
})

test('asks for a Level first for a child with none, the age suggestion picked @story#107', async ({
    page,
}, info) => {
    await openDoor(page)
    const card = await findFamily(page, 'Cruz', `${guardian(info.project.name)} Cruz`)
    const lola = child(card, DOOR.cruz.noLevel)
    await expect(lola).toContainText('No Level yet')

    await lola.getByTestId('tick-child').check()
    await expectInsideCard(card)
    await expect(lola.getByTestId('pick-level').locator('option:checked')).toHaveText(
        DOOR.suggestedLevel,
    )
    await card.getByTestId('check-in').click()

    await expect(page.getByTestId('flash-success')).toContainText(
        `Checked in ${DOOR.cruz.noLevel}.`,
    )
    await expect(lola.getByTestId('checked-in')).toBeVisible()
    await expect(lola).toContainText(`${DOOR.suggestedLevel}`)
})

test('never lets a Graduated child be ticked @story#108', async ({ page }, info) => {
    await openDoor(page)
    const card = await findFamily(page, 'Cruz', `${guardian(info.project.name)} Cruz`)
    const gabe = child(card, DOOR.cruz.graduated)

    await expect(gabe.getByTestId('tick-child')).toBeDisabled()
    await expect(gabe).toContainText('Graduated — cannot be checked in')
})

test('checks in a child visiting from House Church, in a CCF DXB Squad @story#106', async ({
    page,
}, info) => {
    await openDoor(page)
    const card = await findFamily(page, 'Villa', `${guardian(info.project.name)} Villa`)
    await expect(card).toContainText(SATELLITES.house)
    const pia = child(card, DOOR.visitor)

    await pia.getByTestId('tick-child').check()
    await card.getByTestId('check-in').click()

    await expect(page.getByTestId('flash-success')).toContainText(`Checked in ${DOOR.visitor}.`)
    await expect(pia.getByTestId('checked-in')).toBeVisible()
    await expect(pia).toContainText('Level 2 Girls')
})

test("opens a child's row for their health details, status and Level — the family page only where the Lifeshaper may @story#105", async ({
    page,
}, info) => {
    await openDoor(page)
    const card = await findFamily(page, 'Cruz', `${guardian(info.project.name)} Cruz`)
    const amara = child(card, DOOR.cruz.allergic)
    const expand = amara.getByTestId('child-expand')
    await expect(expand).toHaveAttribute('aria-expanded', 'false')
    await expect(card.getByTestId('child-health')).toHaveCount(0)

    await expand.click()
    await expect(expand).toHaveAttribute('aria-expanded', 'true')
    await expect(amara.getByTestId('health-allergies')).toContainText(DOOR.allergy)
    await expect(amara.getByTestId('child-status')).toHaveText('Active')
    await expect(amara.getByTestId('child-level')).toHaveText('Level 2')
    await expect(amara.getByTestId('open-family')).toBeVisible()
    await expectInsideCard(card)

    await expand.click()
    await expect(expand).toHaveAttribute('aria-expanded', 'false')
    await expect(card.getByTestId('child-health')).toHaveCount(0)

    // Opening a row never ticks it: the tick is its own zone. (Noah is out again after the first journey's Undo.)
    const noah = child(card, DOOR.cruz.inactive)
    await noah.getByTestId('child-expand').click()
    await expect(noah.getByTestId('health-allergies')).toHaveText('Allergies: none')
    await expect(noah.getByTestId('tick-child')).not.toBeChecked()
    await expect(card.getByTestId('check-in')).toHaveText('Tick to check in')

    // A visiting family from a satellite the Lifeshaper's Role does not cover: health yes, family page no.
    const villa = await findFamily(page, 'Villa', `${guardian(info.project.name)} Villa`)
    const pia = child(villa, DOOR.visitor)
    await pia.getByTestId('child-expand').click()
    await expect(pia.getByTestId('health-allergies')).toHaveText('Allergies: none')
    await expect(pia.getByTestId('open-family')).toHaveCount(0)
})
