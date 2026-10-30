import { expect, test } from '../support/test'
import { CHURCH, FAMILIES, sessionFile } from '../support/world'

/*
 * Journey 4 — scope. A House Church admin sees House Church families and not CCF DXB ones, cannot open a
 * DXB family by guessing its address, and can only give the Lifeshaper role in House Church.
 */
test.use({ storageState: sessionFile('houseAdmin') })

test('a satellite admin sees only their own satellite @story#43 @story#58', async ({
    page,
    browser,
}) => {
    // Searched, not read off the first page: the list pages once the run has registered enough families.
    await page.goto(`/${CHURCH}/families?q=${FAMILIES.house.guardian}`)
    await expect(
        page.getByTestId('family-row').filter({ hasText: FAMILIES.house.guardian }),
    ).toBeVisible()
    await page.goto(`/${CHURCH}/families?q=${FAMILIES.dxb.guardian}`)
    await expect(
        page.getByTestId('family-row').filter({ hasText: FAMILIES.dxb.guardian }),
    ).toHaveCount(0)

    // The church admin can see the DXB family's address; the satellite admin typing it gets nothing.
    const admin = await browser.newContext({ storageState: sessionFile('churchAdmin') })
    const adminPage = await admin.newPage()
    await adminPage.goto(`/${CHURCH}/families?q=${FAMILIES.dxb.guardian}`)
    const href = await adminPage
        .getByTestId('family-row')
        .filter({ hasText: FAMILIES.dxb.guardian })
        .getByRole('link')
        .getAttribute('href')
    await admin.close()
    expect(href).toMatch(/\/families\/\d+$/)

    const response = await page.goto(href!)
    expect(response?.status()).toBe(404)
})

test('a satellite admin can only add Lifeshapers to their own satellite @story#77', async ({
    page,
}) => {
    await page.goto(`/${CHURCH}/people`)

    // A retrying assertion: allTextContents() reads once, before the page may have rendered its options.
    await expect(page.getByTestId('staff-role').locator('option')).toHaveText([
        'Lifeshaper · House Church',
    ])
})

test('a Lifeshaper can see a family but gets no edit forms @story#50 @story#53', async ({
    browser,
}) => {
    const lifeshaper = await browser.newContext({ storageState: sessionFile('lifeshaper') })
    const page = await lifeshaper.newPage()
    await page.goto(`/${CHURCH}/families?q=${FAMILIES.dxb.guardian}`)
    await page
        .getByTestId('family-row')
        .filter({ hasText: FAMILIES.dxb.guardian })
        .getByRole('link')
        .click()

    await expect(page.getByTestId('family-child').first()).toBeVisible()
    await expect(page.getByTestId('edit-family')).toHaveCount(0)
    await expect(page.getByTestId('edit-child')).toHaveCount(0)
    await lifeshaper.close()
})

test('a Lifeshaper does not get the People page @story#75', async ({ browser }) => {
    const lifeshaper = await browser.newContext({ storageState: sessionFile('lifeshaper') })
    const page = await lifeshaper.newPage()
    await page.goto(`/${CHURCH}/families`)
    await expect(page.getByRole('link', { name: 'People' })).toHaveCount(0)
    const response = await page.goto(`/${CHURCH}/people`)
    expect(response?.status()).toBe(404)
    await lifeshaper.close()
})
