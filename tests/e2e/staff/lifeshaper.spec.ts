import { expect, test } from '@playwright/test'
import { fillChild, fillFamily } from '../support/forms'
import { latestMail, mailCount } from '../support/mailbox'
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
 * gets the new child added; a family that is not gets registered for them; a parent whose confirmation email never
 * came gets it again — from the children list, or from the door search for a family visiting from another satellite.
 */
test.use({ storageState: sessionFile('lifeshaper') })

test('finds a registered family and adds a child to it @story#46 @story#48', async ({ page }) => {
    await page.goto(`/${CHURCH}/families/new`)
    await page.getByTestId('family-search').fill('Dela Cruz')
    await page.getByTestId('family-search').press('Enter')

    const hit = page.getByTestId('search-result').filter({ hasText: FAMILIES.dxb.guardian })
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
        page.getByTestId('search-result').filter({ hasText: FAMILIES.dxb.guardian }),
    ).toContainText('Isa')
})

test('registers a new family for a parent who is not found @story#46 @story#47', async ({
    page,
}) => {
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
    const row = page.getByTestId('family-row').filter({ hasText: `Rosa ${surname}` })
    await expect(row).toContainText('Email not confirmed')
    await expect(row.getByTestId('resend')).toBeVisible()
})

test('prints the registration poster, whose address opens the form @story#80', async ({ page }) => {
    await page.goto(`/${CHURCH}/families`)
    await page.getByRole('link', { name: 'QR poster' }).click()

    const qr = page.getByTestId('poster-qr')
    await expect(qr).toBeVisible()
    // The QR image really rendered (not a broken image).
    await expect
        .poll(() => qr.evaluate((img: HTMLImageElement) => img.complete && img.naturalWidth > 0))
        .toBe(true)

    const address = (await page.getByTestId('poster-url').textContent())?.trim() ?? ''
    expect(address).toMatch(new RegExp(`/${CHURCH}/register$`))
    await page.goto(new URL(address).pathname)
    await expect(page.getByRole('heading', { name: 'Register your family' })).toBeVisible()
})

test('re-sends the confirmation link from the children list @story#84', async ({ page }) => {
    const email = uniqueEmail('resend')
    const surname = uniqueSurname('Reyes')
    await page.goto(`/${CHURCH}/families/new`)
    await page.getByTestId('register-new').click()
    await fillFamily(
        page,
        { first: 'Joy', last: surname, email, phone: '0507776655', satellite: SATELLITES.dxb },
        [{ first: 'Nico', last: surname, nickname: 'Nico', gender: 'boy', dob: '2017-03-03' }],
    )
    await page.getByTestId('register-submit').click()
    await expect(page.getByTestId('flash-success')).toBeVisible()

    // The parent says the email never came: find their child on the children list and send it again.
    await page.goto(`/${CHURCH}/children`)
    await page.getByTestId('filter-q').fill(surname)
    await page.getByTestId('filter-q').press('Enter')
    await expect(page).toHaveURL(new RegExp(`q=${surname}`))
    const row = page.getByTestId('child-row').filter({ hasText: 'Nico' })
    await expect(row).toHaveCount(1)

    const before = mailCount(email)
    await row.getByTestId('resend').click()
    await expect(page.getByTestId('flash-success')).toContainText(
        'We sent the confirmation link to',
    )
    await latestMail(email, before)
})

test("re-sends a visiting family's confirmation link from the door search @story#84", async ({
    page,
}) => {
    // A House Church family; this Lifeshaper serves at CCF DXB, so they cannot open it — but they can help.
    const email = uniqueEmail('visiting')
    const surname = uniqueSurname('Bautista')
    await page.goto(`/${CHURCH}/families/new`)
    await page.getByTestId('register-new').click()
    await fillFamily(
        page,
        { first: 'Lyn', last: surname, email, phone: '0506665544', satellite: SATELLITES.house },
        [{ first: 'Gabo', last: surname, nickname: 'Gabo', gender: 'boy', dob: '2018-05-05' }],
    )
    await page.getByTestId('register-submit').click()
    await expect(page.getByTestId('flash-success')).toBeVisible()

    // Next Sunday the parent visits DXB and says the email never came: search at the door, send it again.
    await page.goto(`/${CHURCH}/families/new`)
    await page.getByTestId('family-search').fill(surname)
    await page.getByTestId('family-search').press('Enter')
    const hit = page.getByTestId('search-result').filter({ hasText: `Lyn ${surname}` })
    await expect(hit).toContainText(SATELLITES.house)
    await expect(hit.getByRole('link')).toHaveCount(0)

    const before = mailCount(email)
    await hit.getByTestId('resend').click()
    await expect(page.getByTestId('flash-success')).toContainText(
        'We sent the confirmation link to',
    )
    await latestMail(email, before)
})
