import { expect, test as setup } from '@playwright/test'
import { signInByEmail } from './support/sign-in'
import { DUBAI, SHARJAH, STAFF, sessionFile, type StaffRole } from './support/world'

/*
 * Each staff member signs in once, through the real login form and the real email link, and the session is
 * saved for the staff journeys. This is also the login journey's own proof for staff.
 */
for (const role of Object.keys(STAFF) as StaffRole[]) {
    setup(`sign in as ${role}`, async ({ page }) => {
        await signInByEmail(page, STAFF[role])

        // Staff land straight in their area at their satellite (Roy 2026-11-06, 2026-11-07): an admin on Admin's
        // families list, a Lifeshaper at Serve's door; the Sharjah admin at Sharjah, everyone else at Dubai.
        const satellite = role === 'houseAdmin' ? SHARJAH : DUBAI
        await expect(page).toHaveURL(
            new RegExp(`/${satellite}/${role === 'lifeshaper' ? 'serve/door' : 'admin/families'}`),
        )
        await page.context().storageState({ path: sessionFile(role) })
    })
}
