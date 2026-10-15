import { expect, test as setup } from '@playwright/test'
import { signInByEmail } from './support/sign-in'
import { CHURCH, STAFF, sessionFile, type StaffRole } from './support/world'

/*
 * Each staff member signs in once, through the real login form and the real email link, and the session is
 * saved for the staff journeys. This is also the login journey's own proof for staff.
 */
for (const role of Object.keys(STAFF) as StaffRole[]) {
    setup(`sign in as ${role}`, async ({ page }) => {
        await signInByEmail(page, STAFF[role])

        // Staff with one church land straight on its families list.
        await expect(page).toHaveURL(new RegExp(`/${CHURCH}/families`))
        await page.context().storageState({ path: sessionFile(role) })
    })
}
