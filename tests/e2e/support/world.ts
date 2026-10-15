/**
 * What `E2ESeeder` puts in the e2e database. `E2EWorldTest` (Pest, inside `make check`) asserts these
 * against the seeder, so the two cannot drift silently.
 */
export const CHURCH = 'ccf'

export const STAFF = {
    churchAdmin: 'admin@nxtgen.test',
    lifeshaper: 'lifeshaper@nxtgen.test',
    houseAdmin: 'house-admin@nxtgen.test',
} as const

export type StaffRole = keyof typeof STAFF

export const FAMILIES = {
    dxb: {
        parent: 'Maria Dela Cruz',
        email: 'delacruz@nxtgen.test',
        child: 'Migs',
        satellite: 'CCF DXB',
    },
    house: {
        parent: 'Grace Santos',
        email: 'santos@nxtgen.test',
        child: 'Bea',
        satellite: 'House Church',
    },
} as const

export const SATELLITES = { dxb: 'CCF DXB', house: 'House Church' } as const

/** Saved sign-in for a staff member, written by auth.setup.ts. */
export const sessionFile = (role: StaffRole): string => `tests/e2e/.auth/${role}.json`

/** A fresh address per run, so the per-address email limit never carries over between runs. */
export const uniqueEmail = (who: string): string =>
    `${who}-${Date.now()}-${Math.floor(Math.random() * 1e6)}@e2e.test`

/** A surname nobody else in the run has — each browser project registers its own families in one database. */
export const uniqueSurname = (base: string): string =>
    `${base}${Math.random().toString(36).slice(2, 7)}`
