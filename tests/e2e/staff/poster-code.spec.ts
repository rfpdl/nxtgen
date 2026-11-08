import { expect, test } from '../support/test'
import { DUBAI, sessionFile } from '../support/world'

/*
 * A satellite's QR poster carries its code (spec item 7, Roy 2026-11-08), which never changes: a parent scanning it
 * lands on the satellite's registration form at its address — so a printed poster keeps working when the address
 * changes. The address printed under the QR is the one to type.
 */
test.use({ storageState: sessionFile('lifeshaper') })

test("the poster's QR code address forwards a parent to /dubai/register @story#80 @story#2", async ({
    page,
    browser,
}) => {
    await page.goto(`/${DUBAI}/serve/poster`)
    const qrUrl = await page.getByTestId('poster-qr').getAttribute('data-qr-url')
    expect(qrUrl).toMatch(/\/[2-9a-hjkmnp-z]{8}\/register$/)
    await expect(page.getByTestId('poster-url')).toHaveText(new RegExp(`/${DUBAI}/register$`))

    // A parent's phone, signed in to nothing, scans it.
    const phone = await browser.newContext({ storageState: { cookies: [], origins: [] } })
    const parent = await phone.newPage()
    await parent.goto(qrUrl!)
    await expect(parent).toHaveURL(new RegExp(`/${DUBAI}/register$`))
    await expect(parent.getByRole('heading', { name: 'Register your family' })).toBeVisible()
    await phone.close()
})
