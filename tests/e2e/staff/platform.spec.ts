import { navTo } from '../support/menu'
import { openEmailedLink } from '../support/sign-in'
import { mailCount } from '../support/mailbox'
import { expect, test } from '../support/test'
import { DUBAI, sessionFile, uniqueEmail } from '../support/world'

/*
 * The platform (spec item 12): the platform admin (made one by `nxtgen:platform-admin`; their own login here, no Role)
 * adds a satellite and gives it its first admin, who signs in from the email and lands at the new satellite's Admin.
 * Anyone else finds no /platform at all.
 */
test('a platform admin adds a satellite and gives it its first admin @story#192 @story#198', async ({
    page,
    browser,
}) => {
    // A sign-in from an email and three pages of the platform: long on Safari.
    test.setTimeout(60_000)
    // Each browser adds its own satellite to the one e2e database.
    const slug = `al-ain-${Math.random().toString(36).slice(2, 7)}`
    const name = `CCF Al Ain ${slug.slice(-5)}`
    const email = uniqueEmail('alainadmin')
    const before = mailCount(email)

    const platform = await browser.newContext({ storageState: sessionFile('platform') })
    const admin = await platform.newPage()
    await admin.goto('/platform')
    await admin.getByTestId('satellite-name').fill(name)
    await admin.getByTestId('satellite-slug').fill(slug)
    await admin.getByTestId('add-satellite-submit').click()
    await expect(admin.getByTestId('flash-success')).toContainText(name)
    await expect(admin.getByTestId('platform-satellite').filter({ hasText: name })).toContainText(
        `/${slug} · QR code`,
    )
    await expect(admin.getByTestId('platform-satellite').filter({ hasText: name })).toContainText(
        '0 admins',
    )

    // Renamed straight away, with no reload: the new satellite's form is there too.
    const renamed = `${name} Oasis`
    const card = admin.getByTestId('platform-satellite').filter({ hasText: name })
    await card.getByTestId('edit-satellite').click()
    await card.getByLabel('Name').fill(renamed)
    await card.getByTestId('save-satellite').click()
    await expect(admin.getByTestId('flash-success')).toContainText(renamed)

    // Its first admin, from the platform's People.
    await navTo(admin, 'People')
    await admin.getByTestId('staff-name').fill('Aisha Admin')
    await admin.getByTestId('staff-email').fill(email)
    await admin.getByTestId('staff-role').selectOption({ label: `Admin · ${renamed}` })
    await admin.getByTestId('add-staff-submit').click()
    await expect(admin.getByTestId('flash-success')).toContainText(
        `${email} is now Admin · ${name}`,
    )

    // Aisha, on her own device: she lands at her satellite's Admin.
    await openEmailedLink(page, email, before)
    await expect(page).toHaveURL(new RegExp(`/${slug}/admin/families$`))
    // …and finds no other satellite, and no platform.
    expect((await page.goto(`/${DUBAI}/admin/families`))?.status()).toBe(404)
    expect((await page.goto('/platform'))?.status()).toBe(404)
    await platform.close()
})
