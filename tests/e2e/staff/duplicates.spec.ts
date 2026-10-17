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
 * A family registered again under a new email, with the same phone, shows up as a possible duplicate. There is no
 * Duplicates tab (DECISIONS 2026-10-17, after first look): the Families list's "duplicates" filter finds it, and the
 * admin answers on the family's page, looking at both families side by side. Nothing is merged; the answer is on
 * the family's history.
 */
test.use({ storageState: sessionFile('churchAdmin') })

test('an admin finds a possible duplicate with the list filter and clears it on the family page @story#72 @story#92', async ({
    page,
}) => {
    const surname = uniqueSurname('DelaCruz')
    await page.goto(`/${CHURCH}/families`)
    await expect(page.getByRole('navigation')).not.toContainText('Duplicates')
    await page.getByTestId('register-family').click()
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

    // The families list's filter finds it.
    await page.goto(`/${CHURCH}/families?duplicates=1&q=${surname}`)
    await expect(page.getByTestId('filter-duplicates')).toBeChecked()
    await page
        .getByTestId('family-row')
        .filter({ hasText: `Maria ${surname}` })
        .getByRole('link')
        .click()

    const pair = page.getByTestId('duplicate-pair')
    await expect(pair).toContainText('Same phone')
    await expect(pair).toContainText(`Maria ${surname}`)
    await expect(pair).toContainText(FAMILIES.dxb.guardian)
    // Side by side in the limited view: masked emails only.
    await expect(pair).not.toContainText(FAMILIES.dxb.email)

    await pair.getByTestId('dismiss-duplicate').click()
    await expect(page.getByTestId('flash-success')).toContainText('not the same family')
    await expect(page.getByTestId('duplicate-pair')).toHaveCount(0)
    await expect(page.getByTestId('history')).toContainText(
        `Possible duplicate of ${FAMILIES.dxb.guardian} (same phone): not the same family`,
    )

    // Cleared: the filter no longer finds it.
    await page.goto(`/${CHURCH}/families?duplicates=1&q=${surname}`)
    await expect(page.getByTestId('family-row')).toHaveCount(0)
})
