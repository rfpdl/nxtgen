/*
 * Hand-written frontend types. Server shapes come from `generated.d.ts` (`php artisan typescript:transform`);
 * alias them here so components import from one place.
 */
export type SharedProps = {
    auth: { user: { id: number; name: string } | null; canSwitch?: boolean }
    flash: { success?: string | null; error?: string | null }
    /** Shared on church staff pages only (RequireAssignment). */
    churchNav?: { canManagePeople: boolean; canReviewDuplicates: boolean }
}

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
export type UpdateChildLevelInput = App.Services.Church.Data.UpdateChildLevelInput
