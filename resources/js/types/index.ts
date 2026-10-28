/*
 * Frontend types. Server shapes come from `generated.d.ts` (`php artisan typescript:transform`) — response DTOs,
 * Inputs and enums, all snake_case (DECISIONS 2026-10-17). They are aliased here so components import from one
 * place; nothing here redefines a shape the server owns.
 */

// ─── What every page receives (HandleInertiaRequests::share, RequireAssignment) ─────────────────────────

export type Church = App.Domains.Church.DataObjects.ChurchData

export type SharedProps = {
    auth: { user: { id: number; name: string } | null; can_switch?: boolean }
    flash: { success?: string | null; error?: string | null }
    /** The church the address names; null on a page outside one. */
    church: Church | null
    /** The staff menu: set on church staff pages (from RequireAssignment's scope), null on every other page. */
    church_nav: App.Domains.Church.DataObjects.ChurchNavData | null
}

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
    gender: App.Enums.GenderEnum
    relationship: App.Enums.RelationshipEnum
    child_status: App.Enums.ChildStatusEnum
    weekday: App.Enums.WeekdayEnum
}

export type EnumProps = { [K in keyof EnumMap]?: Option<EnumMap[K]>[] }

export type Gender = App.Enums.GenderEnum
export type Relationship = App.Enums.RelationshipEnum
export type ChildStatus = App.Enums.ChildStatusEnum
export type DuplicateStatus = App.Enums.DuplicateStatusEnum
export type MagicLinkRoute = App.Enums.MagicLinkRouteEnum
export type Weekday = App.Enums.WeekdayEnum

// ─── Response DTOs ──────────────────────────────────────────────────────────────────────────────────────

/** THE pagination type: where a paged list is (ListPage::meta). */
export type Pagination = App.Foundation.DataObjects.PaginationData

export type LandingOption = App.Domains.Church.DataObjects.LandingOptionData
export type SatelliteOption = App.Domains.Church.DataObjects.SatelliteOptionData
export type FamilyPortal = App.Domains.Family.DataObjects.FamilyPortalData
export type ChildPortal = App.Domains.Family.DataObjects.ChildPortalData
export type FamilyListRow = App.Domains.Family.DataObjects.FamilyListRowData
export type FamilySearchResult = App.Domains.Family.DataObjects.FamilySearchResultData
export type FamilyAdmin = App.Domains.Family.DataObjects.FamilyAdminData
export type StaffMember = App.Domains.Church.DataObjects.StaffMemberData
export type GrantOption = App.Domains.Church.DataObjects.GrantOptionData
export type FamilyDetails = App.Domains.Family.DataObjects.FamilyDetailsData
export type GuardianEdit = App.Domains.Family.DataObjects.GuardianEditData
export type Note = App.Domains.Family.DataObjects.NoteData
export type DuplicatePair = App.Domains.Family.DataObjects.DuplicatePairData
export type ChildListRow = App.Domains.Family.DataObjects.ChildListRowData
export type LevelOption = App.Domains.Level.DataObjects.LevelOptionData
/** A class (GLOSSARY "Schedule") on the Classes page, and a satellite with its classes. */
export type Schedule = App.Domains.Schedule.DataObjects.ScheduleData
export type SatelliteSchedules = App.Domains.Schedule.DataObjects.SatelliteSchedulesData
/** The door (DECISIONS 2026-10-26/27): one of today's classes, a family found, one of its children, a child checked in, a child's health. */
export type DoorClass = App.Domains.CheckIn.DataObjects.DoorClassData
export type DoorFamily = App.Domains.CheckIn.DataObjects.DoorFamilyData
export type DoorChild = App.Domains.CheckIn.DataObjects.DoorChildData
export type DoorCheckIn = App.Domains.CheckIn.DataObjects.DoorCheckInData
export type ChildHealth = App.Domains.CheckIn.DataObjects.ChildHealthData
/** Attendance (DECISIONS 2026-10-26): a Squad of a Session, one child checked in to it, a satellite in the picker. */
export type AttendanceSquad = App.Domains.CheckIn.DataObjects.AttendanceSquadData
export type AttendanceChild = App.Domains.CheckIn.DataObjects.AttendanceChildData
export type AttendanceSatellite = App.Domains.CheckIn.DataObjects.AttendanceSatelliteData
/** A registration list's filters, named as the address parameters the filter bar sends back. */
export type ListFilters = App.Domains.Family.DataObjects.ListFiltersData

// ─── Inputs: what a form posts, typed from the class that validates it ──────────────────────────────────

export type ChildInput = App.Foundation.Inputs.ChildInput
export type GuardianInput = App.Foundation.Inputs.GuardianInput
export type GuardianDetailsInput = App.Foundation.Inputs.GuardianDetailsInput
export type OtherGuardianInput = App.Foundation.Inputs.OtherGuardianInput
export type FamilyDetailsInput = App.Foundation.Inputs.FamilyDetailsInput
export type RegisterFamilyInput = App.Foundation.Inputs.RegisterFamilyInput
export type ChildPhotoInput = App.Foundation.Inputs.ChildPhotoInput
export type PasswordLoginInput = App.Services.Auth.Data.PasswordLoginInput
export type RequestMagicLinkInput = App.Services.Auth.Data.RequestMagicLinkInput
export type UpdatePasswordInput = App.Services.Auth.Data.UpdatePasswordInput
export type AddStaffInput = App.Services.Church.Data.AddStaffInput
export type CorrectFamilyEmailInput = App.Services.Church.Data.CorrectFamilyEmailInput
export type ResolveDuplicateInput = App.Services.Church.Data.ResolveDuplicateInput
export type UpdateChildStatusInput = App.Services.Church.Data.UpdateChildStatusInput
export type UpdateChildLevelInput = App.Services.Church.Data.UpdateChildLevelInput
export type ScheduleInput = App.Services.Church.Data.ScheduleInput
export type AddScheduleInput = App.Services.Church.Data.AddScheduleInput
export type CheckInInput = App.Services.Church.Data.CheckInInput
export type CheckInChildInput = App.Services.Church.Data.CheckInChildInput

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
