import { expect, test } from '@playwright/test'
import { fillChild, fillFamily } from '../support/forms'
import {
    CHURCH,
    FAMILIES,
    SATELLITES,
    sessionFile,
    uniqueEmail,
    uniqueSurname,
} from '../support/world'

/*
 * Journey 2 — a Lifeshaper at the door, on their phone: search first; a family that is already registered
 * gets the new child added; a family that is not gets registered for them.
 */
test.use({ storageState: sessionFile('lifeshaper') })

test('finds a registered family and adds a child to it', async ({ page }) => {
    await page.goto(`/${CHURCH}/families/new`)
    await page.getByTestId('family-search').fill('Dela Cruz')
    await page.getByTestId('family-search').press('Enter')

    const hit = page.getByTestId('search-result').filter({ hasText: FAMILIES.dxb.parent })
    await expect(hit).toBeVisible()
    await hit.getByTestId('search-add-child').click()

    await fillChild(page, 0, {
        first: 'Isabel',
        last: 'Dela Cruz',
        nickname: 'Isa',
        gender: 'girl',
        dob: '2019-08-08',
    })
    await page.getByTestId('add-child-submit').click()

    await expect(page.getByTestId('flash-success')).toBeVisible()
    // The child is really on the family now: search again and the family lists Isa.
    await page.getByTestId('family-search').fill('Dela Cruz')
    await page.getByTestId('family-search').press('Enter')
    await expect(
        page.getByTestId('search-result').filter({ hasText: FAMILIES.dxb.parent }),
    ).toContainText('Isa')
})

test('registers a new family for a parent who is not found', async ({ page }) => {
    const email = uniqueEmail('walkup')
    const surname = uniqueSurname('Mendoza')
    await page.goto(`/${CHURCH}/families/new`)
    await page.getByTestId('family-search').fill(surname)
    await page.getByTestId('family-search').press('Enter')
    await expect(page.getByText('No family found.')).toBeVisible()

    await page.getByTestId('register-new').click()
    await fillFamily(
        page,
        { first: 'Rosa', last: surname, email, phone: '0509998877', satellite: SATELLITES.dxb },
        [{ first: 'Paolo', last: surname, nickname: 'Pao', gender: 'boy', dob: '2016-04-04' }],
    )
    await page.getByTestId('register-submit').click()

    await expect(page.getByTestId('flash-success')).toBeVisible()
    await page.goto(`/${CHURCH}/families`)
    await expect(
        page.getByTestId('family-row').filter({ hasText: `Rosa ${surname}` }),
    ).toContainText('Email not confirmed')
})
