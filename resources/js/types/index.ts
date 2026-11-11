/*
 * Frontend types. Server shapes come from `generated.ts` (`php artisan typescript:transform`) — response DTOs,
 * Inputs and enums, all snake_case (DECISIONS 2026-10-17). They are aliased here so components import from one
 * place; nothing here redefines a shape the server owns.
 */

import type {
    AttendanceChildData,
    AttendanceClassData,
    AttendancePageData,
    AttendanceSquadData,
    CardFeeEnum,
    CardListTabEnum,
    CardMoveEnum,
    CardReasonEnum,
    CardRequestData,
    CardRequestRowData,
    CardRequestsPageData,
    ChildAttendanceData,
    ChildCheckInData,
    ChildHealthData,
    ChildInput,
    ChildListRowData,
    ChildPortalData,
    ChildStatusEnum,
    ChildrenPageData,
    ChoosePageData,
    ChurchData,
    ChurchPeoplePageData,
    ChurchSettingsData,
    ConfirmLinkPageData,
    DoorCheckInData,
    DoorChildData,
    DoorFamilyData,
    DoorPageData,
    DoorScanData,
    DoorSessionData,
    DuplicatePairData,
    DuplicateStatusEnum,
    FamiliesPageData,
    FamilyAdminData,
    FamilyDetailsData,
    FamilyDetailsInput,
    FamilyHomePageData,
    FamilyListRowData,
    FamilyPortalData,
    FamilySearchResultData,
    GenderEnum,
    GrantOptionData,
    GuardianDetailsInput,
    GuardianEditData,
    GuardianPortalData,
    LandingOptionData,
    LevelData,
    LevelOptionData,
    LifestageEnum,
    LinksPageData,
    ListChildData,
    ListFiltersData,
    MagicLinkRouteEnum,
    MovingUpCandidateData,
    MovingUpChildData,
    MovingUpData,
    MovingUpPageData,
    NoteData,
    OtherGuardianInput,
    PaginationData,
    PasswordPageData,
    PickSatellitePageData,
    PlatformPeoplePageData,
    PlatformSatelliteData,
    PlatformSatellitesPageData,
    PlatformSettingsPageData,
    PosterLinkData,
    PosterPageData,
    PrintListData,
    RegisterPageData,
    RelationshipEnum,
    SatelliteChoiceData,
    SatelliteLinkData,
    SatelliteLinksData,
    SatelliteOptionData,
    SatelliteSchedulesData,
    ScheduleData,
    SchedulesPageData,
    ServeClassData,
    ServePageData,
    SessionChoiceData,
    SessionStateEnum,
    SharedPropsData,
    SquadHeadCountData,
    StaffFamilyPageData,
    StaffMemberData,
    StaffRegisterPageData,
    WeekdayEnum,
    WhosInChildData,
    WhosInPageData,
    WhosInSquadData,
} from './generated'

// ─── What every page receives (HandleInertiaRequests::share, RequireAssignment) ─────────────────────────

export type Church = ChurchData
export type SatelliteChoice = SatelliteChoiceData
export type PlatformSatellite = PlatformSatelliteData
export type ChurchSettings = ChurchSettingsData

/** What every page receives: HandleInertiaRequests::share, generated from SharedPropsData (TODO #43). */
export type SharedProps = SharedPropsData

// ─── Enums ──────────────────────────────────────────────────────────────────────────────────────────────

/** The semantic colour an enum case carries (HasEnumMetadata::color). */
export type EnumColor = 'success' | 'info' | 'warning' | 'danger' | 'muted'

/**
 * THE option type: one case of a backend enum as ProvidesEnumMetadata::toFrontend() sends it. A page gets
 * these under its `enums` prop and reads them with `useEnum()`.
 */
export type Option<T extends string = string> = { value: T; label: string; color: EnumColor }

/** Every enum a page may be given under `enums`, by the name the Feature shares it as. */
export type EnumMap = {
    gender: GenderEnum
    relationship: RelationshipEnum
    lifestage: LifestageEnum
    child_status: ChildStatusEnum
    weekday: WeekdayEnum
    /** The reasons staff may pick for a new name card: Lost or Damaged (never First card). */
    card_reason: CardReasonEnum
    /** The Cards page's tabs: Asked, Approved, Printed, Given or refused. */
    card_tab: CardListTabEnum
    /** A Card request's moves, named for their buttons (Cards). */
    card_move: CardMoveEnum
}

export type EnumProps = { [K in keyof EnumMap]?: Option<EnumMap[K]>[] }

export type Gender = GenderEnum
export type Relationship = RelationshipEnum
export type Lifestage = LifestageEnum
export type ChildStatus = ChildStatusEnum
export type DuplicateStatus = DuplicateStatusEnum
export type MagicLinkRoute = MagicLinkRouteEnum
export type Weekday = WeekdayEnum
export type CardReason = CardReasonEnum
export type CardFee = CardFeeEnum
export type CardListTab = CardListTabEnum
export type CardMove = CardMoveEnum

// ─── Response DTOs ──────────────────────────────────────────────────────────────────────────────────────

/** THE pagination type: where a paged list is (ListPage::meta). */
export type Pagination = PaginationData

export type LandingOption = LandingOptionData
export type SatelliteOption = SatelliteOptionData
export type FamilyPortal = FamilyPortalData
export type ChildPortal = ChildPortalData
export type FamilyListRow = FamilyListRowData
export type FamilySearchResult = FamilySearchResultData
export type FamilyAdmin = FamilyAdminData
export type StaffMember = StaffMemberData
export type GrantOption = GrantOptionData
export type FamilyDetails = FamilyDetailsData
export type GuardianEdit = GuardianEditData
export type Note = NoteData
export type DuplicatePair = DuplicatePairData
export type ChildListRow = ChildListRowData
export type LevelOption = LevelOptionData
/** THE Level shape on every list, card and Squad: its number picks the pill's colour, its name is read. */
export type Level = LevelData
/** A child on a family's list row or search hit: nickname and Level. */
export type ListChild = ListChildData
/** A parent or guardian as a family page lists them: name, relationship, phone. */
export type GuardianPortal = GuardianPortalData
/** A class (GLOSSARY "Schedule") on the Classes page, and a satellite with its classes. */
export type Schedule = ScheduleData
export type SatelliteSchedules = SatelliteSchedulesData
/** Links (GLOSSARY): a satellite's own addresses on its QR poster — on the Links page, a satellite with them, and on the poster with its QR. */
export type SatelliteLink = SatelliteLinkData
export type SatelliteLinks = SatelliteLinksData
export type PosterLink = PosterLinkData
/** The door (DECISIONS 2026-10-26/27): a family found, one of its children, a child checked in, a child's health. */
/** The Session a door or Who's in page is at (spec item 16), and one of today's classes on Serve's first page. */
export type DoorSession = DoorSessionData
export type ServeClass = ServeClassData
/** One of the satellite's classes open now, in the bar's dropdown on a class's page (Roy 2026-11-10). */
export type SessionChoice = SessionChoiceData
/** Where today's Session of a class stands (spec item 15): not open yet, open, or closed. */
export type SessionState = SessionStateEnum
export type DoorFamily = DoorFamilyData
export type DoorChild = DoorChildData
export type DoorCheckIn = DoorCheckInData
export type ChildHealth = ChildHealthData
/** A name card at the door (slice 3): the code it spells and the children it names. */
export type DoorScan = DoorScanData
/** Who's in, the door's second tab (DECISIONS 2026-10-28 — roll call): a Squad of today's class, one child in it. */
export type WhosInSquad = WhosInSquadData
export type WhosInChild = WhosInChildData
/** A Squad's confirmed head count: how many, when (church time) and who (first name). */
export type SquadHeadCount = SquadHeadCountData
/** A child's attendance on the staff family page (slice 3): one per child, each Check-in a line. Staff only. */
export type ChildAttendance = ChildAttendanceData
/** One line of a child's attendance: date, class, satellite, Squad, present. */
export type ChildCheckIn = ChildCheckInData
/** Attendance (DECISIONS 2026-10-26): a Squad of a Session, one child checked in to it, a satellite in the picker. */
export type AttendanceSquad = AttendanceSquadData
export type AttendanceChild = AttendanceChildData
export type AttendanceClass = AttendanceClassData
/** A child's open Card request on the staff family page (name cards): its step, fee state and THE words for it ("Asked · not paid"). */
export type CardRequest = CardRequestData
/** One row of the Cards page (admins): whose card, family, why, the fee, and a refusal's reason. */
export type CardRequestRow = CardRequestRowData
/** A print list still to print (GLOSSARY "Print list"): when, by whom, its cards the viewer sees and those left. */
export type PrintList = PrintListData
/** Moving-Up (DECISIONS 2026-11-06): a child who will move, the last Moving-Up, and one child it moved. */
export type MovingUpCandidate = MovingUpCandidateData
export type MovingUp = MovingUpData
export type MovingUpChild = MovingUpChildData
/** A registration list's filters, named as the address parameters the filter bar sends back. */
export type ListFilters = ListFiltersData

// ─── Inputs: what a form posts, typed from the class that validates it ──────────────────────────────────

/**
 * An Input while a person is still filling it in: every answer may still be blank (`''`), an unanswered
 * yes/no is `null`, and a nested Input or list of Inputs is a draft too. Posting a draft is the server's to
 * validate — the form never pretends a blank is an answer (review B6: no allergies ≠ unanswered).
 */
export type Draft<T> = {
    [K in keyof T]: T[K] extends (infer U)[]
        ? U extends object
            ? Draft<U>[]
            : T[K]
        : T[K] extends File
          ? T[K] | null
          : T[K] extends object
            ? Draft<T[K]>
            : T[K] extends boolean
              ? T[K] | null
              : T[K] | ''
}

/** The child fields every child form shares (ChildFieldset). */
export type ChildDraft = Draft<ChildInput>

/** A parent's own fields — with the email on registration, without it when editing (GuardianFieldset). */
export type GuardianDraft = Draft<GuardianDetailsInput> & { email?: string }

/** One of the family's OTHER parents and guardians (OtherGuardiansFieldset): no email; `id` once saved. */
export type OtherGuardianDraft = Draft<OtherGuardianInput>

/** The family's own fields, shared by registration and editing (FamilyFieldset). */
export type FamilyFieldsDraft = Omit<
    Draft<FamilyDetailsInput>,
    'guardian' | 'guardians' | 'removed_guardian_ids' | 'notes'
>

/** The family edit form (FamilyDetailsForm): the family, the parent's own details, the other guardians' changes. */
export type FamilyDetailsDraft = Draft<FamilyDetailsInput>

// ─── The UI kit ─────────────────────────────────────────────────────────────────────────────────────────

/** One item of the app shell's menu (AppShell): where it goes, and whether the page is under it. */
export type ShellItem = { label: string; href: string; testid?: string; current?: boolean }

// The server's own names, as they are (Inputs and DTOs named alike here and in PHP).
export type {
    AddStaffInput,
    CardRequestInput,
    CheckInChildInput,
    CheckInInput,
    ChildInput,
    ChildPhotoInput,
    ChurchSettingsInput,
    CorrectFamilyEmailInput,
    FamilyDetailsInput,
    GuardianDetailsInput,
    GuardianInput,
    HeadCountInput,
    MakePrintListInput,
    MarkPresentInput,
    MoveChildrenUpInput,
    MoveSatelliteLinkInput,
    OtherGuardianInput,
    PasswordLoginInput,
    RefuseCardRequestInput,
    RegisterFamilyInput,
    RequestMagicLinkInput,
    ResolveDuplicateInput,
    SatelliteInput,
    SatelliteLinkInput,
    ScheduleInput,
    UpdateChildLevelInput,
    UpdateChildStatusInput,
    UpdatePasswordInput,
} from './generated'

// ─── Page props: each page's …PageData, the ONE contract with its Feature (TODO #43) ───────────────────

export type ChoosePage = ChoosePageData
export type PickSatellitePage = PickSatellitePageData
export type PasswordPage = PasswordPageData
export type ConfirmLinkPage = ConfirmLinkPageData
export type PlatformSatellitesPage = PlatformSatellitesPageData
export type ChurchPeoplePage = ChurchPeoplePageData
export type PlatformPeoplePage = PlatformPeoplePageData
export type PlatformSettingsPage = PlatformSettingsPageData
export type AttendancePage = AttendancePageData
export type SchedulesPage = SchedulesPageData
export type CardRequestsPage = CardRequestsPageData
export type ChildrenPage = ChildrenPageData
export type LinksPage = LinksPageData
export type MovingUpPage = MovingUpPageData
export type FamiliesPage = FamiliesPageData
export type StaffRegisterPage = StaffRegisterPageData
export type ServePage = ServePageData
export type PosterPage = PosterPageData
export type FamilyHomePage = FamilyHomePageData
export type RegisterPage = RegisterPageData
export type StaffFamilyPage = StaffFamilyPageData
export type WhosInPage = WhosInPageData
export type DoorPage = DoorPageData
