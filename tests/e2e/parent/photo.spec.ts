import { expect, test } from '../support/test'
import { registerAndConfirm } from '../support/forms'
import { CHURCH, SATELLITES, sessionFile, uniqueSurname } from '../support/world'

/*
 * Photos (Roy 2026-10-16): a parent or a Lifeshaper can take or upload one, once the parent agrees. The parent
 * can take consent back, which deletes it. The photo shows only through a short-lived signed link.
 */
const PHOTO = 'tests/Fixtures/phone-photo-rotated-gps.jpg'

test('a parent agrees; a Lifeshaper takes the first photo; the parent replaces it, then takes consent back @story#36 @story#37 @story#38 @story#68', async ({
    page,
    browser,
}) => {
    const surname = uniqueSurname('Lim')
    await registerAndConfirm(
        page,
        { first: 'Rhea', last: surname, phone: '0504440000', satellite: SATELLITES.dxb },
        [{ first: 'Noah', last: surname, nickname: 'Noah', gender: 'boy', dob: '2018-02-02' }],
    )
    await expect(page).toHaveURL(new RegExp(`/${CHURCH}/family`))
    const card = page.getByTestId('family-child').filter({ hasText: 'Noah' })

    // A Lifeshaper cannot add one before the parent agrees.
    const staff = await browser.newContext({ storageState: sessionFile('lifeshaper') })
    const door = await staff.newPage()
    await door.goto(`/${CHURCH}/families?q=${surname}`)
    await door.getByTestId('family-row').getByRole('link').click()
    const staffCard = door.getByTestId('family-child').filter({ hasText: 'Noah' })
    await expect(staffCard).toContainText('The parent has not agreed to a photo yet')

    // The parent agrees — no photo yet.
    await card.getByTestId('give-photo-consent').click()
    await expect(page.getByTestId('flash-success')).toContainText('a volunteer can now add')

    // The Lifeshaper takes the FIRST photo at the door.
    await door.reload()
    await staffCard.getByTestId('take-photo').setInputFiles(PHOTO)
    await expect(door.getByTestId('flash-success')).toContainText("Noah's photo is saved")
    await expect(staffCard.getByTestId('child-photo-img')).toBeVisible()
    await staff.close()

    // The parent sees it, upright, and can replace it with their own.
    await page.reload()
    const img = card.getByTestId('child-photo-img')
    await expect
        .poll(() => img.evaluate((el: HTMLImageElement) => el.complete && el.naturalWidth > 0))
        .toBe(true)
    expect(await img.evaluate((el: HTMLImageElement) => el.naturalHeight > el.naturalWidth)).toBe(
        true,
    )
    await card.getByTestId('choose-photo').setInputFiles(PHOTO)
    await expect(page.getByTestId('flash-success')).toContainText("Noah's photo is saved")

    // The parent takes consent back: the photo is hidden.
    await card.getByTestId('withdraw-photo-consent').click()
    await expect(page.getByTestId('flash-success')).toContainText("Noah's photo is hidden")
    await expect(card.getByTestId('child-photo-img')).toHaveCount(0)
})
