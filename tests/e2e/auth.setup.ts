import { expect, test as setup } from '@playwright/test'
import { signInByEmail } from './support/sign-in'
import { DUBAI, SATELLITES, SHARJAH, STAFF, sessionFile, type StaffRole } from './support/world'

/*
 * Each staff member signs in once, through the real login form and the real email link, and the session is
 * saved for the staff journeys. This is also the login journey's own proof for staff.
 */
for (const role of Object.keys(STAFF) as StaffRole[]) {
    setup(`sign in as ${role}`, async ({ page }) => {
        await signInByEmail(page, STAFF[role])

        // Staff land straight in their area at their satellite (Roy 2026-11-06, 2026-11-07): an admin on Admin's
        // families list, a Lifeshaper at Serve's door; the Sharjah admin at Sharjah, everyone else at Dubai.
        // The demo admin serves at all three satellites and used none yet: "Pick your satellite" (spec item 11).
        // The platform admin, who may open any satellite and used none yet, picks too — then saves the session as is.
        if (role === 'platform') {
            await expect(page).toHaveURL(/\/pick$/)
            await page.context().storageState({ path: sessionFile(role) })
            return
        }
        if (role === 'admin') {
            await expect(page).toHaveURL(/\/pick$/)
            await page.getByTestId('pick-satellite').filter({ hasText: SATELLITES.dxb }).click()
        }
        const satellite = role === 'houseAdmin' ? SHARJAH : DUBAI
        await expect(page).toHaveURL(
            new RegExp(`/${satellite}/${role === 'lifeshaper' ? 'serve/door' : 'admin/families'}`),
        )
        await page.context().storageState({ path: sessionFile(role) })
    })
}
