declare namespace App {
    namespace Domains {
        namespace Church {
            namespace DataObjects {
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
                export type SatelliteOptionData = {
                    id: number
                    name: string
                }
                export type StaffMemberData = {
                    id: number
                    name: string
                    email: string
                    roles: App.Domains.Church.DataObjects.StaffRoleData[]
                }
                export type StaffRoleData = {
                    id: number
                    label: string
                    canRemove: boolean
                }
            }
        }
        namespace Family {
            namespace DataObjects {
                export type ChildListRowData = {
                    id: number
                    nickname: string
                    fullName: string
                    age: number
                    dateOfBirth: string
                    group: string | null
                    status: string
                    hasAllergies: boolean
                    satellite: string
                    familyId: number
                }
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
                    photoConsent: boolean
                    photoUrl: string | null
                }
                export type DuplicatePairData = {
                    id: number
                    reason: string
                    subject: App.Domains.Family.DataObjects.FamilySearchResultData
                    candidate: App.Domains.Family.DataObjects.FamilySearchResultData
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
                export type FamilyDetailsData = {
                    homeSatelliteId: number
                    areaText: string | null
                    fbAccount: string | null
                    wantsFbGroup: boolean
                    inDgroup: boolean
                    dgroupAreaText: string | null
                    dgroupLeaderName: string | null
                    dgroupLeaderPhone: string | null
                    wantsDgroup: boolean
                    lifestage: string | null
                    notes: string | null
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
                export type GuardianEditData = {
                    firstName: string
                    lastName: string
                    relationship: App.Enums.RelationshipEnum
                    phone: string
                    ministries: string[]
                }
                export type GuardianPortalData = {
                    firstName: string
                    lastName: string
                    relationship: string
                    phone: string | null
                }
                export type NoteData = {
                    id: number
                    body: string
                    author: string
                    at: string
                }
            }
        }
        namespace Group {
            namespace DataObjects {
                export type GroupOptionData = {
                    id: number
                    name: string
                }
            }
        }
    }
    namespace Enums {
        export type AccessRowEnum =
            | 'staff_area'
            | 'door_search'
            | 'register_family'
            | 'add_child'
            | 'family_list'
            | 'view_family'
            | 'edit_family'
            | 'resend_link'
            | 'correct_contact'
            | 'set_child_status'
            | 'set_child_group'
            | 'review_duplicates'
            | 'grant_roles'
            | 'own_family'
            | 'child_photo'
            | 'see_child_photo'
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
        export type ScopeTypeEnum = 'church' | 'satellite' | 'event_series'
    }
}
