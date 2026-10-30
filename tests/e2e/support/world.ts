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
 * The browser projects (playwright.config.ts), by layout band of the app (Tailwind's sm 640px, md 768px, lg 1024px):
 * phones upright (a small one, chromium, and Safari for parents' iPhones), a tablet upright (sm), the md edge and a
 * phone on its side (md), and a tablet on its side at the lg edge and a desktop (lg, the row menu). Every
 * journey runs on each, one after another, against one database — so each has its own seeded families below.
 */
export const PROJECTS = [
    'phone-portrait',
    'phone-portrait-safari',
    'desktop',
    'phone-landscape',
    'tablet-portrait',
    'tablet-landscape',
    'phone-small',
    'tablet-edge',
] as const

export type Project = (typeof PROJECTS)[number]

/** A phone number of this project's own, for a journey that registers a family: `base` with its last digit swapped. */
export const projectPhone = (base: string, project: string): string =>
    `${base.slice(0, -1)}${Math.max(0, PROJECTS.indexOf(project as Project))}`

/**
 * The door journeys' own families, one set per browser project (E2ESeeder::DOOR_GUARDIANS), so the browsers in
 * one database never check in the same child. At CCF DXB: the Cruz family — Amara (allergic: Peanuts), Ben, Noah
 * (inactive), Lola (no Level yet; her age suggests Level 1), Gabe (Graduated). Visiting from House Church: the Villa
 * family's Pia.
 */
export const DOOR = {
    guardians: {
        'phone-portrait': 'Ana',
        'phone-portrait-safari': 'Bela',
        desktop: 'Carla',
        'phone-landscape': 'Dina',
        'tablet-portrait': 'Elsa',
        'tablet-landscape': 'Fara',
        'phone-small': 'Gina',
        'tablet-edge': 'Iva',
    },
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
 * This project's imported Cruz family as a parent knows it (E2ESeeder::doorFamilies): the parent's name, email and
 * phone — the phone ends in the project's place in PROJECTS (1-based), as the seeder numbers them.
 */
export const doorFamily = (project: string): { guardian: string; email: string; phone: string } => {
    const first = DOOR.guardians[project as DoorProject]
    return {
        guardian: `${first} Cruz`,
        email: `cruz-${first.toLowerCase()}@nxtgen.test`,
        phone: `050300000${PROJECTS.indexOf(project as Project) + 1}`,
    }
}

/**
 * Who's in (roll call): each browser project's Reyes family at CCF DXB (E2ESeeder::ROLL_CALL_GIRLS) — Level 2 girls
 * named apart, because the browsers share today's 1PM Session and its Level 2 Girls Squad. The first two are the
 * Who's in journey's; the last two, the Attendance journey's (one ticked present, one not).
 */
export const ROLL_CALL = {
    girls: {
        'phone-portrait': ['Sofia', 'Isabel', 'Lia', 'Gia'],
        'phone-portrait-safari': ['Mia', 'Elena', 'Nina', 'Ivy'],
        desktop: ['Ruth', 'Hope', 'Tala', 'Faith'],
        'phone-landscape': ['Jade', 'Kaye', 'Lea', 'Mae'],
        'tablet-portrait': ['Nia', 'Ora', 'Pam', 'Rae'],
        'tablet-landscape': ['Sia', 'Tia', 'Una', 'Vea'],
        'phone-small': ['Wyn', 'Xia', 'Yna', 'Zoe'],
        'tablet-edge': ['Ada', 'Cora', 'Dora', 'Eda'],
    },
    squad: 'Level 2 Girls',
} as const

/**
 * Scan a name card (slice 3): each browser's Reyes family has an Amara with its own middle name
 * (E2ESeeder::SCAN_MIDDLE_NAMES), so the code its card spells finds only that child. She is never checked in.
 */
export const SCAN = {
    codes: {
        'phone-portrait': 'AMARAJOYREYES',
        'phone-portrait-safari': 'AMARAKAYREYES',
        desktop: 'AMARALEEREYES',
        'phone-landscape': 'AMARAROSEREYES',
        'tablet-portrait': 'AMARAFAYEREYES',
        'tablet-landscape': 'AMARAGWENREYES',
        'phone-small': 'AMARAIRISREYES',
        'tablet-edge': 'AMARAJUNEREYES',
    },
    child: 'Amara',
} as const

/**
 * Name cards: each browser project's Ocampo family at CCF DXB (E2ESeeder::CARD_CHILDREN), parent DOOR.guardians' name —
 * imported, so none has a Card request. Each card journey takes the first still offering "New card…" (support/cards.ts),
 * so a retry uses the next child without a reseed.
 */
export const CARDS = {
    surname: 'Ocampo',
    children: ['Jon', 'Kit', 'Ned', 'Rex', 'Sam', 'Tom', 'Ugo', 'Vic', 'Wes'],
} as const

/**
 * The Level journey: each browser project's Torres family at CCF DXB (E2ESeeder::LEVEL_CHILDREN), parent
 * DOOR.guardians' name — girls whose age suggests Level 3. The journey moves the first still at Level 3.
 */
export const LEVELS = {
    surname: 'Torres',
    children: ['Lexi', 'Lara', 'Luz'],
    suggested: 'Level 3',
} as const
