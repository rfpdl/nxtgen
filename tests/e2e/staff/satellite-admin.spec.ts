import { expect, test } from '../support/test'
import { DUBAI, DOOR, FAMILIES, SHARJAH, sessionFile, type DoorProject } from '../support/world'

/*
 * Journey 4 — scope. A CCF Sharjah admin works at /sharjah: sees CCF Sharjah families and not CCF Dubai ones, cannot
 * open a Dubai family by guessing its address — at Dubai's address or their own (spec item 10) — and can only give the
 * Admin or Lifeshaper Role at CCF Sharjah (spec item 9).
 */
test.use({ storageState: sessionFile('houseAdmin') })

test('a satellite admin sees only their own satellite @story#43 @story#58', async ({
    page,
    browser,
}) => {
    // Searched, not read off the first page: the list pages once the run has registered enough families.
    await page.goto(`/${SHARJAH}/admin/families?q=${FAMILIES.house.guardian}`)
    await expect(
        page.getByTestId('family-row').filter({ hasText: FAMILIES.house.guardian }),
    ).toBeVisible()
    await page.goto(`/${SHARJAH}/admin/families?q=${FAMILIES.dxb.guardian}`)
    await expect(
        page.getByTestId('family-row').filter({ hasText: FAMILIES.dxb.guardian }),
    ).toHaveCount(0)

    // Dubai's admin can see the Dubai family's address; the Sharjah admin typing it gets nothing.
    const admin = await browser.newContext({ storageState: sessionFile('admin') })
    const adminPage = await admin.newPage()
    await adminPage.goto(`/${DUBAI}/admin/families?q=${FAMILIES.dxb.guardian}`)
    const href = await adminPage
        .getByTestId('family-row')
        .filter({ hasText: FAMILIES.dxb.guardian })
        .getByRole('link')
        .getAttribute('href')
    await admin.close()
    const path = new URL(href!, 'http://app').pathname
    expect(path).toMatch(new RegExp(`^/${DUBAI}/admin/families/\\d+$`))

    expect((await page.goto(path))?.status()).toBe(404)
    expect((await page.goto(path.replace(`/${DUBAI}/`, `/${SHARJAH}/`)))?.status()).toBe(404)
})

test("a satellite admin cannot open another satellite's Admin or Serve — not found @story#43", async ({
    page,
}) => {
    // Their own satellite opens; Dubai's address is not found for them (spec item 10).
    await page.goto(`/${SHARJAH}/admin/families`)
    await expect(page.getByRole('heading', { name: 'Families', level: 1 })).toBeVisible()
    for (const path of ['admin/families', 'admin/people', 'serve']) {
        expect((await page.goto(`/${DUBAI}/${path}`))?.status(), path).toBe(404)
    }
})

test('a satellite admin gives Roles — Admin or Lifeshaper — at their own satellite only @story#77', async ({
    page,
}) => {
    await page.goto(`/${SHARJAH}/admin/people`)

    // A retrying assertion: allTextContents() reads once, before the page may have rendered its options.
    await expect(page.getByTestId('staff-role').locator('option')).toHaveText([
        'Admin · CCF Sharjah',
        'Lifeshaper · CCF Sharjah',
    ])
})

test("a Lifeshaper edits their own satellite's family in Serve — not its email, status or Level @story#50 @story#52 @story#60", async ({
    browser,
}, info) => {
    const lifeshaper = await browser.newContext({ storageState: sessionFile('lifeshaper') })
    const page = await lifeshaper.newPage()
    const family = `${DOOR.guardians[info.project.name as DoorProject]} Cruz`
    await page.goto(`/${DUBAI}/serve/families?q=${encodeURIComponent(family)}`)
    await page.getByTestId('family-row').filter({ hasText: family }).getByRole('link').click()
    await expect(page).toHaveURL(new RegExp(`/${DUBAI}/serve/families/\\d+$`))

    // Roy 2026-11-06: a Lifeshaper of the satellite keeps the family's details right.
    const area = `Al Barsha ${info.project.name}`
    await page.getByTestId('edit-family').click()
    await page.getByTestId('area').fill(area)
    await page.getByTestId('edit-family-submit').click()
    await expect(page.getByTestId('flash-success')).toContainText('Family details saved')
    await expect(page.getByText(area)).toBeVisible()
    await expect(page.getByTestId('edit-child').first()).toBeVisible()

    // The admin's own controls stay in Admin.
    await expect(page.getByTestId('correct-email')).toHaveCount(0)
    await expect(page.getByRole('combobox', { name: 'Status', exact: true })).toHaveCount(0)
    await expect(page.getByRole('combobox', { name: 'Level', exact: true })).toHaveCount(0)
    await lifeshaper.close()
})

test('a Lifeshaper does not get the People page @story#75', async ({ browser }) => {
    const lifeshaper = await browser.newContext({ storageState: sessionFile('lifeshaper') })
    const page = await lifeshaper.newPage()
    await page.goto(`/${DUBAI}/serve/families`)
    await expect(page.getByRole('link', { name: 'People' })).toHaveCount(0)
    await page.goto(`/${DUBAI}/admin/people`)
    // Only serving here: an Admin page sends them to this satellite's Serve (spec item 10).
    await expect(page).toHaveURL(new RegExp(`/${DUBAI}/serve$`))
    await lifeshaper.close()
})
