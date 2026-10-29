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
        guardian: 'Maria Dela Cruz',
        email: 'delacruz@nxtgen.test',
        // Matches E2ESeeder; a new family with this phone is a "possible duplicate" of this one.
        phone: '0501000001',
        child: 'Migs',
        satellite: 'CCF DXB',
    },
    house: {
        guardian: 'Grace Santos',
        email: 'santos@nxtgen.test',
        child: 'Bea',
        satellite: 'House Church',
    },
} as const

export const SATELLITES = { dxb: 'CCF DXB', house: 'House Church' } as const

/** CCF DXB's Saturday classes (DemoChurchSeeder). House Church has none. */
export const CLASSES = {
    onePm: { name: '1PM', when: 'Saturdays · 13:00–15:00' },
    sevenPm: { name: '7PM', when: 'Saturdays · 19:00–21:00' },
} as const

/** Saved sign-in for a staff member, written by auth.setup.ts. */
export const sessionFile = (role: StaffRole): string => `tests/e2e/.auth/${role}.json`

/** A fresh address per run, so the per-address email limit never carries over between runs. */
export const uniqueEmail = (who: string): string =>
    `${who}-${Date.now()}-${Math.floor(Math.random() * 1e6)}@e2e.test`

/** A surname nobody else in the run has — each browser project registers its own families in one database. */
export const uniqueSurname = (base: string): string =>
    `${base}${Math.random().toString(36).slice(2, 7)}`

/**
 * The door journeys' own families, one set per browser project (E2ESeeder::DOOR_GUARDIANS), so three browsers in
 * one database never check in the same child. At CCF DXB: the Cruz family — Amara (allergic: Peanuts), Ben, Noah
 * (inactive), Lola (no Level yet; her age suggests Level 1), Gabe (Graduated). Visiting from House Church: the Villa
 * family's Pia.
 */
export const DOOR = {
    guardians: { 'mobile-webkit': 'Ana', 'mobile-chromium': 'Bela', 'desktop-chromium': 'Carla' },
    cruz: {
        allergic: 'Amara',
        active: 'Ben',
        inactive: 'Noah',
        noLevel: 'Lola',
        graduated: 'Gabe',
    },
    allergy: 'Peanuts',
    suggestedLevel: 'Level 1',
    visitor: 'Pia',
} as const

export type DoorProject = keyof typeof DOOR.guardians

/**
 * Who's in (roll call): each browser project's Reyes family at CCF DXB (E2ESeeder::ROLL_CALL_GIRLS) — Level 2 girls
 * named apart, because the three browsers share today's 1PM Session and its Level 2 Girls Squad. The first two are the
 * Who's in journey's; the last two, the Attendance journey's (one ticked present, one not).
 */
export const ROLL_CALL = {
    girls: {
        'mobile-webkit': ['Sofia', 'Isabel', 'Lia', 'Gia'],
        'mobile-chromium': ['Mia', 'Elena', 'Nina', 'Ivy'],
        'desktop-chromium': ['Ruth', 'Hope', 'Tala', 'Faith'],
    },
    squad: 'Level 2 Girls',
} as const

/**
 * Scan a name card (slice 3): each browser's Reyes family has an Amara with its own middle name
 * (E2ESeeder::SCAN_MIDDLE_NAMES), so the code its card spells finds only that child. She is never checked in.
 */
export const SCAN = {
    codes: {
        'mobile-webkit': 'AMARAJOYREYES',
        'mobile-chromium': 'AMARAKAYREYES',
        'desktop-chromium': 'AMARALEEREYES',
    },
    child: 'Amara',
} as const

/**
 * Name cards: each browser project's Ocampo family at CCF DXB (E2ESeeder::CARD_CHILDREN), parent DOOR.guardians' name —
 * imported, so none has a Card request. The card journey takes the first still offering "New card…", so a retry uses
 * the next child without a reseed.
 */
export const CARDS = {
    surname: 'Ocampo',
    children: ['Jon', 'Kit', 'Ned'],
} as const
