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
    /** Shared on church staff pages only (RequireAssignment). */
    churchNav?: { can_manage_people: boolean; can_review_duplicates: boolean }
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
}

export type EnumProps = { [K in keyof EnumMap]?: Option<EnumMap[K]>[] }

export type Gender = App.Enums.GenderEnum
export type Relationship = App.Enums.RelationshipEnum
export type ChildStatus = App.Enums.ChildStatusEnum
export type DuplicateStatus = App.Enums.DuplicateStatusEnum
export type MagicLinkRoute = App.Enums.MagicLinkRouteEnum

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
/** A registration list's filters, named as the address parameters the filter bar sends back. */
export type ListFilters = App.Domains.Family.DataObjects.ListFiltersData

// ─── Inputs: what a form posts, typed from the class that validates it ──────────────────────────────────

export type ChildInput = App.Foundation.Inputs.ChildInput
export type GuardianInput = App.Foundation.Inputs.GuardianInput
export type GuardianDetailsInput = App.Foundation.Inputs.GuardianDetailsInput
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

/** The family's own fields, shared by registration and editing (FamilyFieldset). */
export type FamilyFieldsDraft = Omit<Draft<FamilyDetailsInput>, 'guardian' | 'notes'>
