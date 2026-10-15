declare namespace App {
    namespace Domains {
        namespace Family {
            namespace DataObjects {
                export type ChildPortalData = {
                    id: number
                    firstName: string
                    middleName: string | null
                    lastName: string
                    nickname: string
                    gender: string
                    dateOfBirth: string
                    allergies: string | null
                    illnesses: string | null
                    dislikes: string | null
                    fears: string | null
                    parentNotes: string | null
                    group: string | null
                    status: string
                }
                export type FamilyAdminData = {
                    id: number
                    primaryName: string
                    email: string
                    emailConfirmed: boolean
                    satellite: string
                    area: string | null
                    registeredBy: string
                    guardians: App.Domains.Family.DataObjects.GuardianPortalData[]
                    children: App.Domains.Family.DataObjects.ChildPortalData[]
                    canEdit: boolean
                }
                export type FamilyListRowData = {
                    id: number
                    primaryName: string
                    email: string
                    emailConfirmed: boolean
                    satellite: string
                    children: string[]
                    registeredBy: string
                    registeredOn: string
                }
                export type FamilyPortalData = {
                    homeSatellite: string
                    area: string | null
                    guardians: App.Domains.Family.DataObjects.GuardianPortalData[]
                    children: App.Domains.Family.DataObjects.ChildPortalData[]
                }
                export type FamilySearchResultData = {
                    id: number
                    primaryName: string
                    maskedEmail: string
                    emailConfirmed: boolean
                    satellite: string
                    children: string[]
                    canOpen: boolean
                }
                export type GuardianPortalData = {
                    firstName: string
                    lastName: string
                    relationship: string
                    phone: string | null
                }
            }
        }
        namespace Organization {
            namespace DataObjects {
                export type BranchOptionData = {
                    id: number
                    name: string
                }
                export type GrantOptionData = {
                    label: string
                    role: App.Enums.RoleEnum
                    scopeType: App.Enums.ScopeTypeEnum
                    scopeId: number
                }
                export type LandingOptionData = {
                    churchName: string
                    label: string
                    url: string
                }
                export type StaffMemberData = {
                    id: number
                    name: string
                    email: string
                    roles: App.Domains.Organization.DataObjects.StaffRoleData[]
                }
                export type StaffRoleData = {
                    id: number
                    label: string
                    canRemove: boolean
                }
            }
        }
    }
    namespace Enums {
        export type ChildStatusEnum = 'active' | 'inactive' | 'graduated'
        export type DuplicateReasonEnum = 'same_phone' | 'same_name_dob' | 'same_email'
        export type DuplicateStatusEnum = 'open' | 'dismissed' | 'confirmed'
        export type FamilyClaimStatusEnum = 'pending' | 'accepted' | 'revoked'
        export type GenderEnum = 'boy' | 'girl'
        export type MagicLinkPurposeEnum = 'login' | 'verify_email' | 'claim_family'
        export type NoteKindEnum = 'system' | 'observation'
        export type NoteVisibilityEnum = 'staff' | 'family'
        export type RegistrationOutcomeEnum =
            'registered' | 'already_registered' | 'claim_sent' | 'claim_resent'
        export type RegistrationSourceEnum = 'self' | 'staff' | 'import'
        export type RelationshipEnum =
            'mother' | 'father' | 'guardian' | 'grandparent' | 'aunt_uncle' | 'sibling' | 'other'
        export type RoleEnum = 'admin' | 'lifeshaper'
        export type ScopeTypeEnum = 'organization' | 'branch' | 'event_series'
    }
}
