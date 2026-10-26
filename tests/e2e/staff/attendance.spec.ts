import { expect, test, type Page } from '@playwright/test'
import { xlsxRows } from '../support/xlsx'
import { CHURCH, CLASSES, DOOR, SATELLITES, sessionFile } from '../support/world'

/*
 * Journey — Attendance (DECISIONS 2026-10-26: reports are admins only, with Excel). A church admin checks a child in
 * at the door first (so the journey never depends on another having run), then opens Attendance from the menu,
 * picks CCF DXB · 1PM · the door's Saturday, sees the children by Squad with a count per Squad and a total, and
 * downloads the Excel — which has the same rows. A Lifeshaper has no Attendance.
 */

/** The e2e server's door day (E2EServiceProvider::doorSaturday): today in Dubai if a Saturday, else the next. */
function doorSaturday(): string {
    const today = new Date(new Date().toLocaleString('en-US', { timeZone: 'Asia/Dubai' }))
    today.setDate(today.getDate() + ((6 - today.getDay() + 7) % 7))
    const pad = (n: number): string => String(n).padStart(2, '0')
    return `${today.getFullYear()}-${pad(today.getMonth() + 1)}-${pad(today.getDate())}`
}

/** Ben of this browser's own Cruz family is in at 1PM (checked in now, unless an earlier try already did). */
async function checkInBen(page: Page): Promise<void> {
    await page.goto(`/${CHURCH}/door`)
    const picker = page.getByTestId('door-class')
    const onePm = await picker
        .locator('option', { hasText: CLASSES.onePm.name })
        .getAttribute('value')
    await picker.selectOption(onePm ?? '')
    await expect(page).toHaveURL(new RegExp(`class=${onePm}`))
    await page.getByTestId('door-search').fill('Cruz')
    await page.getByTestId('door-search').press('Enter')
    const card = page
        .getByTestId('door-family')
        .filter({ hasText: `${DOOR.guardians['desktop-chromium']} Cruz` })
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
}) => {
    const admin = await browser.newContext({ storageState: sessionFile('churchAdmin') })
    const page = await admin.newPage()
    await checkInBen(page)

    await page.getByRole('link', { name: 'Attendance' }).click()
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
        for (const child of children) {
            const cells = await child.locator('td').allTextContents()
            rows.push([name, ...cells.map((c) => c.trim())].join('|'))
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
        'Squad|Child nickname|Child full name|Checked in at|Checked in by',
        ...rows,
    ])
    await admin.close()
})

test('a Lifeshaper has no Attendance @story#113', async ({ browser }) => {
    const lifeshaper = await browser.newContext({ storageState: sessionFile('lifeshaper') })
    const page = await lifeshaper.newPage()
    await page.goto(`/${CHURCH}/door`)
    await expect(page.getByTestId('nav-door')).toBeVisible()
    await expect(page.getByRole('link', { name: 'Attendance' })).toHaveCount(0)

    const response = await page.goto(`/${CHURCH}/attendance`)
    expect(response?.status()).toBe(404)
    await lifeshaper.close()
})
