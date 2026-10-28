import { expect, test } from '@playwright/test'
import { registerAndConfirm } from '../support/forms'
import { CHURCH, CLASSES, SATELLITES, sessionFile, uniqueSurname } from '../support/world'

/*
 * Child history (slice 3, DECISIONS 2026-10-28 night): a parent registers Amara; a Lifeshaper checks her in at 1PM and
 * ticks her present on Who's in; the family page shows staff the line "Sat … · 1PM · CCF DXB · Level 2 Girls ·
 * Present ✓"; undone at the door, the line is gone. The parent's own My family page never shows attendance.
 */
const PHONES: Record<string, string> = {
    'mobile-webkit': '0504441101',
    'mobile-chromium': '0504441102',
    'desktop-chromium': '0504441103',
}

test("staff see a child's attendance on the family page; the parent never does @story#126 @story#127", async ({
    page,
    browser,
}, info) => {
    const surname = uniqueSurname('Tan')
    await registerAndConfirm(
        page,
        {
            first: 'Ana',
            last: surname,
            phone: PHONES[info.project.name] ?? '0504441100',
            satellite: SATELLITES.dxb,
        },
        [{ first: 'Amara', last: surname, nickname: 'Amara', gender: 'girl', dob: '2019-05-05' }],
    )
    await expect(page).toHaveURL(new RegExp(`/${CHURCH}/family`))

    // A Lifeshaper checks Amara in at 1PM and ticks her present on Who's in.
    const staff = await browser.newContext({ storageState: sessionFile('lifeshaper') })
    const door = await staff.newPage()
    await door.goto(`/${CHURCH}/door`)
    await door.getByTestId('door-class').getByRole('radio', { name: CLASSES.onePm.name }).click()
    await door.getByTestId('door-search').fill(surname)
    await door.getByTestId('door-search').press('Enter')
    const card = door.getByTestId('door-family').filter({ hasText: `Ana ${surname}` })
    await card.getByTestId('tick-child').check()
    await card.getByTestId('check-in').click()
    await expect(door.getByTestId('flash-success')).toContainText('Checked in Amara.')
    await door.getByTestId('door-tab-whos-in').click()
    const amara = door.getByTestId('whos-in-child').filter({
        has: door.getByTestId('child-full-name').getByText(`Amara ${surname}`, { exact: true }),
    })
    await Promise.all([
        door.waitForResponse((r) => r.request().method() === 'PUT' && r.url().endsWith('/present')),
        amara.getByRole('checkbox').click(),
    ])

    // Her family page shows the line, under Amara.
    await door.goto(`/${CHURCH}/families?q=${surname}`)
    await door.getByTestId('family-row').getByRole('link').click()
    const history = door
        .getByTestId('family-child')
        .filter({ hasText: 'Amara' })
        .getByTestId('child-attendance')
    const line = history.getByTestId('child-attendance-line')
    await expect(line).toHaveCount(1)
    await expect(line.getByTestId('child-attendance-where')).toHaveText(
        new RegExp(
            `^\\w{3} \\d{1,2} \\w{3} · ${CLASSES.onePm.name} · ${SATELLITES.dxb} · Level 2 Girls$`,
        ),
    )
    await expect(line.getByTestId('child-roll-call')).toHaveText('Present ✓')

    // Undone at the door: the line is gone.
    const familyPage = door.url()
    await door.goto(`/${CHURCH}/door`)
    await door.getByTestId('door-class').getByRole('radio', { name: CLASSES.onePm.name }).click()
    await door.getByTestId('door-search').fill(surname)
    await door.getByTestId('door-search').press('Enter')
    await card.getByTestId('door-child').getByTestId('undo').click()
    await expect(door.getByTestId('flash-success')).toContainText("Undid Amara's check-in.")
    await door.goto(familyPage)
    await expect(history.getByTestId('child-attendance-line')).toHaveCount(0)
    await expect(history.getByTestId('child-attendance-empty')).toHaveText('Not checked in yet.')
    await staff.close()

    // The parent's own page: no attendance.
    await page.reload()
    await expect(page.getByTestId('family-child').filter({ hasText: 'Amara' })).toBeVisible()
    await expect(page.getByTestId('child-attendance')).toHaveCount(0)
})
