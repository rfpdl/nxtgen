import { navTo } from '../support/menu'
import { expect, test } from '../support/test'
import { DUBAI, SATELLITES, SHARJAH, sessionFile } from '../support/world'

/*
 * Links (GLOSSARY; Roy 2026-11-06): an admin puts a satellite's own addresses — its WhatsApp group, its Facebook
 * group — on its QR poster, each as a QR code. Every browser shares CCF Dubai's poster, so each adds a Link of its own
 * name and removes it before it ends (the journeys run one at a time; a retry first removes what a failed try left).
 */
test.use({ storageState: sessionFile('churchAdmin') })

test("an admin adds a WhatsApp group to CCF Dubai's poster, sees its QR there, then removes it @story#164 @story#166 @story#172 @story#174", async ({
    page,
}, info) => {
    const label = `WhatsApp group ${info.project.name}`
    const dxb = page.getByTestId('link-name').filter({ hasText: label })

    await page.goto(`/${DUBAI}/admin/families`)
    await navTo(page, 'Links')
    await expect(page.getByRole('heading', { name: 'Links', level: 1 })).toBeVisible()
    const section = page
        .locator('section')
        .filter({ has: page.getByRole('heading', { name: SATELLITES.dxb }) })

    for (const leftOver of await section.getByTestId('link').filter({ hasText: label }).all()) {
        await leftOver.getByTestId('remove-link').click()
        await leftOver.getByTestId('confirm-remove-link').click()
        await expect(page.getByTestId('flash-success')).toContainText('Removed')
    }

    // Add: "WhatsApp group" is a tap away; then the browser's own name, and the address.
    await section.getByTestId('open-add-link').click()
    await section.getByTestId('link-starter').filter({ hasText: 'WhatsApp group' }).click()
    await expect(section.getByTestId('link-label')).toHaveValue('WhatsApp group')
    await section.getByTestId('link-label').fill(label)
    await section.getByTestId('link-url').fill('http://chat.whatsapp.com/not-secure')
    await section.getByTestId('link-submit').click()
    await expect(
        section.getByText('Please paste the whole link, starting with https://'),
    ).toBeVisible()
    await section.getByTestId('link-url').fill(`https://chat.whatsapp.com/${info.project.name}`)
    await section.getByTestId('link-submit').click()
    await expect(page.getByTestId('flash-success')).toHaveText(
        `Added "${label}" to ${SATELLITES.dxb}'s poster.`,
    )
    await expect(dxb).toBeVisible()

    // The poster: CCF Dubai's, the Link's QR code with its label under it.
    await page.getByTestId('open-poster').click()
    await expect(page.getByTestId('poster-qr')).toBeVisible()
    await expect(page).toHaveURL(new RegExp(`/${DUBAI}/serve/poster$`))
    const onPoster = page.getByTestId('poster-link').filter({ hasText: label })
    await expect(onPoster).toBeVisible()
    await expect
        .poll(() =>
            onPoster
                .locator('img')
                .evaluate((img: HTMLImageElement) => img.complete && img.naturalWidth > 0),
        )
        .toBe(true)

    // Back on Links: remove it, after a second tap.
    await page.goto(`/${DUBAI}/admin/links`)
    await expect(dxb).toBeVisible()
    const card = section.getByTestId('link').filter({ hasText: label })
    await card.getByTestId('remove-link').click()
    await card.getByTestId('confirm-remove-link').click()
    await expect(page.getByTestId('flash-success')).toHaveText(
        `Removed "${label}" from ${SATELLITES.dxb}'s poster.`,
    )
    await expect(dxb).toHaveCount(0)
})

test('a poster with six Links — the most there can be, long labels too — prints on one A4 or Letter page @story#174', async ({
    page,
    browserName,
}, info) => {
    test.skip(browserName !== 'chromium', 'Only Chromium prints a page to PDF')
    const label = (n: number): string =>
        `Print check ${n} — the parents' group for ${info.project.name}`.slice(0, 60)
    // At CCF Sharjah's own address: a page shows only the satellite in it (DECISIONS 2026-11-08).
    await page.goto(`/${SHARJAH}/admin/links`)
    const section = page
        .locator('section')
        .filter({ has: page.getByRole('heading', { name: SATELLITES.house }) })

    const printChecks = section.getByTestId('link').filter({ hasText: 'Print check' })
    /** Removes every "Print check" Link, the first each time (the list shrinks as it goes). */
    const removePrintChecks = async (): Promise<void> => {
        for (let left = await printChecks.count(); left > 0; left--) {
            const first = printChecks.first()
            await first.getByTestId('remove-link').click()
            await first.getByTestId('confirm-remove-link').click()
            // The flash says "Removed" from the last one already: wait for the list itself.
            await expect(printChecks).toHaveCount(left - 1)
        }
    }

    // CCF Sharjah's poster gets six Links (any a failed try left are removed first, then refilled).
    await removePrintChecks()
    for (let n = (await section.getByTestId('link').count()) + 1; n <= 6; n++) {
        await section.getByTestId('open-add-link').click()
        await section.getByTestId('link-label').fill(label(n))
        await section.getByTestId('link-url').fill(`https://example.com/${info.project.name}/${n}`)
        await section.getByTestId('link-submit').click()
        await expect(page.getByTestId('flash-success')).toContainText(`Added "${label(n)}"`)
    }
    await expect(section.getByTestId('links-full')).toBeVisible()

    try {
        await page.goto(`/${SHARJAH}/serve/poster`)
        await expect(page.getByTestId('poster-link')).toHaveCount(6)
        await page.emulateMedia({ media: 'print' })
        for (const format of ['A4', 'Letter']) {
            const pdf = (await page.pdf({ format })).toString('latin1')
            expect(pdf.match(/\/Type\s*\/Page(?!s)/g) ?? [], format).toHaveLength(1)
        }
    } finally {
        await page.emulateMedia({ media: 'screen' })
        await page.goto(`/${SHARJAH}/admin/links`)
        await removePrintChecks()
    }
})
