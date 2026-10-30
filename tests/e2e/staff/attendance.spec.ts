import { type Locator, type Page } from '@playwright/test'
import { expect, test } from '../support/test'
import { xlsxRows } from '../support/xlsx'
import { menuOf, navTo } from '../support/menu'
import {
    CHURCH,
    CLASSES,
    DOOR,
    ROLL_CALL,
    SATELLITES,
    sessionFile,
    type DoorProject,
} from '../support/world'

/*
 * Journey — Attendance (DECISIONS 2026-10-26: reports are admins only, with Excel). A church admin checks a child in
 * at the door first (so the journey never depends on another having run), then opens Attendance from the menu,
 * picks CCF DXB · 1PM · the door's Saturday, sees the children by Squad with a count per Squad and a total, and
 * downloads the Excel — which has the same rows. The roll call (DECISIONS 2026-10-28): after ticking a child present on
 * Who's in and counting her Squad one too many, Attendance shows her present ✓, the head count and the warning, and
 * the Excel's Present and Squad head count columns say the same. A Lifeshaper has no Attendance.
 */

/** The e2e server's door day (E2EServiceProvider::doorSaturday): today in Dubai if a Saturday, else the next. */
function doorSaturday(): string {
    const today = new Date(new Date().toLocaleString('en-US', { timeZone: 'Asia/Dubai' }))
    today.setDate(today.getDate() + ((6 - today.getDay() + 7) % 7))
    const pad = (n: number): string => String(n).padStart(2, '0')
    return `${today.getFullYear()}-${pad(today.getMonth() + 1)}-${pad(today.getDate())}`
}

/** Ben of this browser's own Cruz family is in at 1PM (checked in now, unless an earlier try already did). */
async function checkInBen(page: Page, project: string): Promise<void> {
    await page.goto(`/${CHURCH}/door`)
    // The class picker is a row of chips, a radio group (the Bold look): pick 1PM.
    const chip = page.getByTestId('door-class').getByRole('radio', { name: CLASSES.onePm.name })
    await chip.click()
    await expect(chip).toHaveAttribute('aria-checked', 'true')
    await page.getByTestId('door-search').fill('Cruz')
    await page.getByTestId('door-search').press('Enter')
    const card = page
        .getByTestId('door-family')
        .filter({ hasText: `${DOOR.guardians[project as DoorProject]} Cruz` })
    const ben = card.getByTestId('door-child').filter({
        has: page.getByTestId('child-name').getByText(DOOR.cruz.active, { exact: true }),
    })
    if ((await ben.getByTestId('checked-in').count()) === 0) {
        await ben.getByTestId('tick-child').check()
        await card.getByTestId('check-in').click()
        await expect(page.getByTestId('flash-success')).toContainText(
            `Checked in ${DOOR.cruz.active}.`,
        )
    }
    await expect(ben.getByTestId('checked-in')).toBeVisible()
}

test('an admin sees 1PM by Squad with counts, and downloads the same rows as Excel @story#112 @story#114', async ({
    browser,
}, info) => {
    const admin = await browser.newContext({ storageState: sessionFile('churchAdmin') })
    const page = await admin.newPage()
    await checkInBen(page, info.project.name)

    await navTo(page, 'Attendance')
    await expect(page.getByRole('heading', { name: 'Attendance', level: 1 })).toBeVisible()
    await page.getByTestId('attendance-satellite').selectOption({ label: SATELLITES.dxb })
    await expect(page).toHaveURL(/satellite=\d+/)
    await page.getByTestId('attendance-class').selectOption({ label: CLASSES.onePm.name })
    await expect(page).toHaveURL(/class=\d+/)
    await expect(page.getByTestId('attendance-class').locator('option:checked')).toHaveText(
        CLASSES.onePm.name,
    )
    const day = doorSaturday()
    await page.getByTestId('attendance-date').fill(day)
    await expect(page).toHaveURL(new RegExp(`date=${day}`))
    await expect(page.getByTestId('attendance-heading')).toContainText(
        `${SATELLITES.dxb} · ${CLASSES.onePm.name} · Sat `,
    )

    // Ben is in, in his Squad; each Squad's count is its rows, and the total is all of them.
    const squads = page.getByTestId('attendance-squad')
    const bens = squads.filter({
        has: page.getByTestId('attendance-child').filter({ hasText: DOOR.cruz.active }),
    })
    await expect(bens.first()).toBeVisible()
    const rows: string[] = []
    let total = 0
    for (const squad of await squads.all()) {
        const name = (await squad.getByTestId('squad-name').textContent()) ?? ''
        const children = await squad.getByTestId('attendance-child').all()
        await expect(squad.getByTestId('squad-count')).toHaveText(`${children.length} in`)
        total += children.length
        // The Excel's last two columns: Present ("Yes" or empty) and the Squad's head count (or empty).
        const counted =
            ((await squad.getByTestId('squad-head-count').textContent()) ?? '').match(
                /Head count (\d+)/,
            )?.[1] ?? ''
        for (const child of children) {
            const [nickname, full, at, by, rollCall] = (
                await child.locator('td').allTextContents()
            ).map((c) => c.trim())
            const present = rollCall === 'Present ✓' ? 'Yes' : ''
            rows.push([name, nickname, full, at, by, present, counted].join('|'))
        }
    }
    await expect(page.getByTestId('attendance-total')).toHaveText(`${total} checked in`)
    expect(rows.some((r) => r.includes(`|${DOOR.cruz.active}|`))).toBe(true)

    const [download] = await Promise.all([
        page.waitForEvent('download'),
        page.getByTestId('attendance-export').click(),
    ])
    expect(download.suggestedFilename()).toBe(`nxtgen-attendance-ccf-dxb-1pm-${day}.xlsx`)
    expect(xlsxRows((await download.path())!)).toEqual([
        'Squad|Child nickname|Child full name|Checked in at|Checked in by|Present|Squad head count',
        ...rows,
    ])
    await admin.close()
})

/** Opens Attendance from the menu on CCF DXB · 1PM · the door's Saturday. */
async function openAttendance(page: Page): Promise<string> {
    await navTo(page, 'Attendance')
    await page.getByTestId('attendance-satellite').selectOption({ label: SATELLITES.dxb })
    await expect(page).toHaveURL(/satellite=\d+/)
    await page.getByTestId('attendance-class').selectOption({ label: CLASSES.onePm.name })
    await expect(page).toHaveURL(/class=\d+/)
    const day = doorSaturday()
    await page.getByTestId('attendance-date').fill(day)
    await expect(page).toHaveURL(new RegExp(`date=${day}`))
    return day
}

function squadNamed(page: Page, name: string): Locator {
    return page
        .getByTestId('attendance-squad')
        .filter({ has: page.getByTestId('squad-name').getByText(name, { exact: true }) })
}

test("an admin sees the roll call — present ✓, the Squad's head count and the warning — on screen and in Excel @story#124 @story#125", async ({
    browser,
}, info) => {
    const project = info.project.name as DoorProject
    const admin = await browser.newContext({ storageState: sessionFile('churchAdmin') })
    const page = await admin.newPage()
    const [, , tala, faith] = ROLL_CALL.girls[project]

    // At the door, 1PM: this browser's Tala and Faith.
    await page.goto(`/${CHURCH}/door`)
    await page.getByTestId('door-class').getByRole('radio', { name: CLASSES.onePm.name }).click()
    await page.getByTestId('door-search').fill('Reyes')
    await page.getByTestId('door-search').press('Enter')
    const card = page
        .getByTestId('door-family')
        .filter({ hasText: `${DOOR.guardians[project]} Reyes` })
    // Tala and Faith are in (checked in now, unless an earlier try already did).
    const girl = (name: string): Locator =>
        card
            .getByTestId('door-child')
            .filter({ has: page.getByTestId('child-name').getByText(name, { exact: true }) })
    const out: string[] = []
    for (const name of [tala, faith]) {
        if ((await girl(name).getByTestId('checked-in').count()) === 0) out.push(name)
    }
    if (out.length > 0) {
        for (const name of out) await girl(name).getByTestId('tick-child').check()
        await card.getByTestId('check-in').click()
    }
    await expect(girl(tala).getByTestId('checked-in')).toBeVisible()
    await expect(girl(faith).getByTestId('checked-in')).toBeVisible()

    // Who's in: Tala ticked present (Faith not), and Level 2 Girls counted one more than are in.
    await page.getByTestId('door-tab-whos-in').click()
    await page.getByTestId('squad-picker').getByRole('radio', { name: ROLL_CALL.squad }).click()
    await expect(page.getByTestId('whos-in-squad')).toHaveCount(1)
    const tick = page
        .getByTestId('whos-in-child')
        .filter({ has: page.getByTestId('child-name').getByText(tala, { exact: true }) })
        .getByRole('checkbox')
    if ((await tick.getAttribute('aria-checked')) !== 'true') {
        await Promise.all([
            page.waitForResponse(
                (r) => r.request().method() === 'PUT' && r.url().endsWith('/present'),
            ),
            tick.click(),
        ])
    }
    const inNow = Number(
        ((await page.getByTestId('squad-present').textContent()) ?? '').match(
            /of (\d+) present/,
        )?.[1],
    )
    const counted = inNow + 1
    await page.getByTestId('head-count-input').fill(String(counted))
    await page.getByTestId('head-count-confirm').click()
    await expect(page.getByTestId('head-count-confirmed')).toContainText(`(${counted})`)

    // Attendance: Tala present ✓, Faith not marked, the head count with when and who, and the warning (naming Faith).
    const day = await openAttendance(page)
    const girls = squadNamed(page, ROLL_CALL.squad)
    const row = girls.getByTestId('attendance-child').filter({ hasText: tala })
    await expect(row.getByTestId('child-roll-call')).toHaveText('Present ✓')
    await expect(
        girls
            .getByTestId('attendance-child')
            .filter({ hasText: faith })
            .getByTestId('child-roll-call'),
    ).toHaveText('Not marked')
    await expect(girls.getByTestId('squad-head-count')).toHaveText(
        new RegExp(`Head count ${counted} · \\d{2}:\\d{2} by \\S+`),
    )
    await expect(girls.getByTestId('squad-mismatch')).toContainText(
        `Checked in ${inNow}, counted ${counted}.`,
    )
    await expect(girls.getByTestId('squad-mismatch')).toContainText(faith)

    // The Excel: Tala's row ends Present "Yes" and the Squad's head count.
    const [download] = await Promise.all([
        page.waitForEvent('download'),
        page.getByTestId('attendance-export').click(),
    ])
    expect(download.suggestedFilename()).toBe(`nxtgen-attendance-ccf-dxb-1pm-${day}.xlsx`)
    const talaRows = xlsxRows((await download.path())!).filter((r) =>
        r.includes(`|${tala}|${tala} Reyes|`),
    )
    expect(talaRows).toHaveLength(1)
    expect(talaRows[0]).toMatch(new RegExp(`^${ROLL_CALL.squad}\\|.*\\|Yes\\|${counted}$`))
    await admin.close()
})

test('a Lifeshaper has no Attendance @story#113', async ({ browser }) => {
    const lifeshaper = await browser.newContext({ storageState: sessionFile('lifeshaper') })
    const page = await lifeshaper.newPage()
    await page.goto(`/${CHURCH}/door`)
    // The menu opened (a sheet on a narrow screen), then the whole page: no Attendance anywhere.
    await expect((await menuOf(page)).getByTestId('nav-door')).toBeVisible()
    await expect(page.getByRole('link', { name: 'Attendance' })).toHaveCount(0)

    const response = await page.goto(`/${CHURCH}/attendance`)
    expect(response?.status()).toBe(404)
    await lifeshaper.close()
})
