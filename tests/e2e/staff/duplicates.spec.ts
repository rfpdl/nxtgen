import { allNavigation, closeMenu } from '../support/menu'
import { expect, test } from '../support/test'
import { fillFamily } from '../support/forms'
import {
    CHURCH,
    doorFamily,
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
}, info) => {
    // A family sharing the phone of this project's own imported Cruz family — each project its own pair.
    const seeded = doorFamily(info.project.name)
    const surname = uniqueSurname('DelaCruz')
    await page.goto(`/${CHURCH}/families`)
    // Duplicates has no menu item (DECISIONS 2026-10-17, after first look) — in no navigation on the page.
    for (const nav of await allNavigation(page)) await expect(nav).not.toContainText('Duplicates')
    await closeMenu(page)
    await page.getByTestId('register-family').click()
    await page.getByTestId('register-new').click()
    await fillFamily(
        page,
        {
            first: 'Maria',
            last: surname,
            email: uniqueEmail('maria2'),
            phone: seeded.phone,
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

    // One possible duplicate: the project's own Cruz family. (A retry also meets the family its first try made.)
    if (info.retry === 0) await expect(page.getByTestId('duplicate-pair')).toHaveCount(1)
    const pair = page.getByTestId('duplicate-pair').filter({ hasText: seeded.guardian })
    await expect(pair).toContainText('Same phone')
    await expect(pair).toContainText(`Maria ${surname}`)
    // Side by side in the limited view: masked emails only.
    await expect(pair).not.toContainText(seeded.email)

    await pair.getByTestId('dismiss-duplicate').click()
    await expect(page.getByTestId('flash-success')).toContainText('not the same family')
    if (info.retry === 0) await expect(page.getByTestId('duplicate-pair')).toHaveCount(0)
    await expect(pair).toHaveCount(0)
    await expect(page.getByTestId('history')).toContainText(
        `Possible duplicate of ${seeded.guardian} (same phone): not the same family`,
    )
    // On a retry, the first try's family shares the phone too: not the same family either.
    const others = page.getByTestId('duplicate-pair')
    while (info.retry > 0 && (await others.count()) > 0) {
        const left = await others.count()
        await others.first().getByTestId('dismiss-duplicate').click()
        await expect(others).toHaveCount(left - 1)
    }

    // Cleared: the filter no longer finds it.
    await page.goto(`/${CHURCH}/families?duplicates=1&q=${surname}`)
    await expect(page.getByTestId('family-row')).toHaveCount(0)
})
