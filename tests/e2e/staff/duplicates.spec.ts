import { expect, test } from '@playwright/test'
import { fillFamily } from '../support/forms'
import {
    CHURCH,
    FAMILIES,
    SATELLITES,
    sessionFile,
    uniqueEmail,
    uniqueSurname,
} from '../support/world'

/*
 * A family registered again under a new email, with the same phone, shows up as a possible duplicate; the
 * admin looks at both side by side and answers. Nothing is merged; the answer is on the family's history.
 */
test.use({ storageState: sessionFile('churchAdmin') })

test('an admin reviews a possible duplicate @story#70 @story#72', async ({ page }) => {
    const surname = uniqueSurname('DelaCruz')
    await page.goto(`/${CHURCH}/families/new`)
    await page.getByTestId('register-new').click()
    await fillFamily(
        page,
        {
            first: 'Maria',
            last: surname,
            email: uniqueEmail('maria2'),
            phone: FAMILIES.dxb.phone,
            satellite: SATELLITES.dxb,
        },
        [{ first: 'Miguel', last: surname, nickname: 'Migs', gender: 'boy', dob: '2017-03-14' }],
    )
    await page.getByTestId('register-submit').click()
    await expect(page.getByTestId('flash-success')).toBeVisible()

    await page.getByRole('link', { name: 'Duplicates' }).click()
    const pair = page.getByTestId('duplicate-pair').filter({ hasText: `Maria ${surname}` })
    await expect(pair).toContainText('Same phone')
    await expect(pair).toContainText(FAMILIES.dxb.guardian)
    // Side by side in the limited view: masked emails only.
    await expect(pair).not.toContainText(FAMILIES.dxb.email)

    await pair.getByTestId('dismiss-duplicate').click()
    await expect(page.getByTestId('flash-success')).toContainText('not the same family')
    await expect(
        page.getByTestId('duplicate-pair').filter({ hasText: `Maria ${surname}` }),
    ).toHaveCount(0)

    // The answer is on the new family's history.
    await page.goto(`/${CHURCH}/families`)
    await page
        .getByTestId('family-row')
        .filter({ hasText: `Maria ${surname}` })
        .getByRole('link')
        .click()
    await expect(page.getByTestId('history')).toContainText(
        `Possible duplicate of ${FAMILIES.dxb.guardian} (same phone): not the same family`,
    )
})
