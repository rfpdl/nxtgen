import { type Locator, type Page } from '@playwright/test'
import { expect, test } from '../support/test'
import { CHURCH, CLASSES, DOOR, ROLL_CALL, sessionFile, type DoorProject } from '../support/world'

/*
 * Journey — a Squad leader's roll call, on their phone (DECISIONS 2026-10-28): check two girls in at 1PM, switch to
 * Who's in, pick Level 2 Girls, tap them present (and one again to untick her), confirm a head count one short (a
 * warning names who is not marked) then the right one; back on Check in the class is still 1PM; a child undone at
 * the door leaves Who's in. The browser projects share today's 1PM Session and its Level 2 Girls Squad, so each ticks
 * its own Reyes girls (E2ESeeder) and reads "N of M present" before comparing after.
 */
test.use({ storageState: sessionFile('lifeshaper') })

function onePm(page: Page): Locator {
    return page.getByTestId('door-class').getByRole('radio', { name: CLASSES.onePm.name })
}

function child(page: Page, nickname: string): Locator {
    return page
        .getByTestId('whos-in-child')
        .filter({ has: page.getByTestId('child-name').getByText(nickname, { exact: true }) })
}

/** Tap a child's row, and wait until the server has the mark. */
async function tap(page: Page, nickname: string): Promise<void> {
    await Promise.all([
        page.waitForResponse((r) => r.request().method() === 'PUT' && r.url().endsWith('/present')),
        child(page, nickname).getByRole('checkbox').click(),
    ])
}

async function present(squad: Locator): Promise<[number, number]> {
    const text = (await squad.getByTestId('squad-present').textContent()) ?? ''
    const [, n, m] = text.match(/(\d+) of (\d+) present/) ?? []
    return [Number(n), Number(m)]
}

test("marks children present by Squad on Who's in, keeps the class across the tabs, and drops a child undone at the door; confirms the Squad's head count @story#117 @story#119 @story#121 @story#122", async ({
    page,
}, info) => {
    const project = info.project.name as DoorProject
    const [sofia, isabel] = ROLL_CALL.girls[project]

    // At the door, 1PM: check this browser's two Reyes girls in.
    await page.goto(`/${CHURCH}/door`)
    await onePm(page).click()
    await expect(onePm(page)).toHaveAttribute('aria-checked', 'true')
    await page.getByTestId('door-search').fill('Reyes')
    await page.getByTestId('door-search').press('Enter')
    const card = page
        .getByTestId('door-family')
        .filter({ hasText: `${DOOR.guardians[project]} Reyes` })
    for (const name of [sofia, isabel]) {
        await card
            .getByTestId('door-child')
            .filter({ has: page.getByTestId('child-name').getByText(name, { exact: true }) })
            .getByTestId('tick-child')
            .check()
    }
    await card.getByTestId('check-in').click()
    await expect(page.getByTestId('flash-success')).toContainText(
        `Checked in ${sofia} and ${isabel}.`,
    )

    // Who's in, still 1PM; pick Level 2 Girls.
    await page.getByTestId('door-tab-whos-in').click()
    await expect(page.getByTestId('door-tab-whos-in')).toHaveAttribute('aria-current', 'page')
    await expect(onePm(page)).toHaveAttribute('aria-checked', 'true')
    const chip = page.getByTestId('squad-picker').getByRole('radio', { name: ROLL_CALL.squad })
    await chip.click()
    await expect(chip).toHaveAttribute('aria-checked', 'true')
    const squad = page.getByTestId('whos-in-squad')
    await expect(squad).toHaveCount(1)
    await expect(squad.getByTestId('squad-name')).toHaveText(ROLL_CALL.squad)
    const [before, all] = await present(squad)
    // First + last name under the nickname tells two children of the same nickname apart.
    await expect(child(page, sofia).getByTestId('child-full-name')).toHaveText(`${sofia} Reyes`)
    // Phone first: nothing pushes the page sideways, and each tick is a thumb-sized row.
    expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(
        page.viewportSize()?.width ?? 0,
    )
    expect(
        (await child(page, sofia).getByRole('checkbox').boundingBox())?.height,
    ).toBeGreaterThanOrEqual(48)

    // Tap both present; tap Sofia again to untick her.
    await tap(page, sofia)
    await tap(page, isabel)
    await expect(child(page, sofia).getByRole('checkbox')).toHaveAttribute('aria-checked', 'true')
    await expect(child(page, isabel).getByRole('checkbox')).toHaveAttribute('aria-checked', 'true')
    await expect(squad.getByTestId('squad-present')).toHaveText(`${before + 2} of ${all} present`)
    // No band at the top: it would push the list down under the next tap.
    await expect(page.getByTestId('flash-success')).toHaveCount(0)

    await tap(page, sofia)
    await expect(child(page, sofia).getByRole('checkbox')).toHaveAttribute('aria-checked', 'false')
    await expect(squad.getByTestId('squad-present')).toHaveText(`${before + 1} of ${all} present`)

    // The marks are the server's: they are there after a reload.
    await page.reload()
    await expect(child(page, isabel).getByRole('checkbox')).toHaveAttribute('aria-checked', 'true')
    await expect(child(page, sofia).getByRole('checkbox')).toHaveAttribute('aria-checked', 'false')

    // The head count. Counting one short warns, naming who is not marked (Sofia, not
    // Isabel); the right count clears the warning. No band at the top — the Squad's own line says it.
    const count = squad.getByTestId('head-count')
    const mismatch = count.getByTestId('head-count-mismatch')
    // It starts at the last count confirmed (the browsers share this Squad), else the number checked in.
    const confirmedBefore = count.getByTestId('head-count-confirmed')
    const start =
        (await confirmedBefore.count()) > 0
            ? ((await confirmedBefore.textContent())?.match(/\((\d+)\)/)?.[1] ?? '')
            : String(all)
    await expect(count.getByTestId('head-count-input')).toHaveValue(start)
    await count.getByTestId('head-count-input').fill(String(all - 1))
    await count.getByTestId('head-count-confirm').click()
    await expect(count.getByTestId('head-count-confirmed')).toHaveText(
        new RegExp(`✓ Confirmed \\d{2}:\\d{2} by \\S+ \\(${all - 1}\\)`),
    )
    await expect(mismatch).toContainText(`Checked in ${all}, counted ${all - 1}. Not marked:`)
    await expect(mismatch).toContainText(sofia)
    await expect(mismatch).not.toContainText(isabel)
    await expect(page.getByTestId('flash-success')).toHaveCount(0)

    await count.getByTestId('head-count-input').fill(String(all))
    await count.getByTestId('head-count-confirm').click()
    await expect(count.getByTestId('head-count-confirmed')).toContainText(`(${all})`)
    await expect(mismatch).toHaveCount(0)

    // Back on Check in, the class is still 1PM. Undo Isabel there: she leaves Who's in, and her mark with her.
    await page.getByTestId('door-tab-check-in').click()
    await expect(page.getByTestId('door-tab-check-in')).toHaveAttribute('aria-current', 'page')
    await expect(onePm(page)).toHaveAttribute('aria-checked', 'true')
    await page
        .getByTestId('checked-in-row')
        .filter({ has: page.getByTestId('checked-in-name').getByText(isabel, { exact: true }) })
        .getByTestId('checked-in-undo')
        .click()
    await expect(page.getByTestId('flash-success')).toContainText(`Undid ${isabel}'s check-in.`)

    await page.getByTestId('door-tab-whos-in').click()
    const again = page.getByTestId('squad-picker').getByRole('radio', { name: ROLL_CALL.squad })
    await again.click()
    await expect(again).toHaveAttribute('aria-checked', 'true')
    await expect(page.getByTestId('whos-in-squad')).toHaveCount(1)
    await expect(child(page, sofia)).toBeVisible()
    await expect(child(page, isabel)).toHaveCount(0)
    await expect(page.getByTestId('whos-in-squad').getByTestId('squad-present')).toHaveText(
        `${before} of ${all - 1} present`,
    )
    // The count was right before the Undo; worked out again now, it is one too many.
    await expect(page.getByTestId('head-count-mismatch')).toContainText(
        `Checked in ${all - 1}, counted ${all}.`,
    )
})
