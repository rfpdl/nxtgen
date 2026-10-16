declare namespace App {
    namespace Domains {
        namespace Church {
            namespace DataObjects {
                export type ChurchData = {
                    name: string
                    slug: string
                }
                export type GrantOptionData = {
                    label: string
                    role: App.Enums.RoleEnum
                    scope_type: App.Enums.ScopeTypeEnum
                    scope_id: number
                }
                export type LandingOptionData = {
                    church_name: string
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
                    can_remove: boolean
                }
            }
        }
        namespace Family {
            namespace DataObjects {
                export type ChildListRowData = {
                    id: number
                    nickname: string
                    full_name: string
                    age: number
                    date_of_birth: string
                    level: string | null
                    status_label: string
                    has_allergies: boolean
                    satellite: string
                    family_id: number
                }
                export type ChildPortalData = {
                    id: number
                    first_name: string
                    middle_name: string | null
                    last_name: string
                    nickname: string
                    gender: App.Enums.GenderEnum
                    date_of_birth: string
                    allergies: string | null
                    illnesses: string | null
                    dislikes: string | null
                    fears: string | null
                    guardian_notes: string | null
                    level: string | null
                    status_label: string
                    photo_consent: boolean
                    photo_url: string | null
                }
                export type DuplicatePairData = {
                    id: number
                    reason_label: string
                    subject: App.Domains.Family.DataObjects.FamilySearchResultData
                    candidate: App.Domains.Family.DataObjects.FamilySearchResultData
                }
                export type FamilyAdminData = {
                    id: number
                    primary_name: string
                    email: string
                    email_confirmed: boolean
                    satellite: string
                    area: string | null
                    registered_by: string
                    guardians: App.Domains.Family.DataObjects.GuardianPortalData[]
                    children: App.Domains.Family.DataObjects.ChildPortalData[]
                    can_edit: boolean
                }
                export type FamilyDetailsData = {
                    home_satellite_id: number
                    area_text: string | null
                    fb_account: string | null
                    wants_fb_group: boolean
                    in_dgroup: boolean
                    dgroup_area_text: string | null
                    dgroup_leader_name: string | null
                    dgroup_leader_phone: string | null
                    wants_dgroup: boolean
                    lifestage: string | null
                    notes: string | null
                }
                export type FamilyListRowData = {
                    id: number
                    primary_name: string
                    email: string
                    email_confirmed: boolean
                    satellite: string
                    children: string[]
                    registered_by: string
                    registered_on: string
                }
                export type FamilyPortalData = {
                    home_satellite: string
                    area: string | null
                    guardians: App.Domains.Family.DataObjects.GuardianPortalData[]
                    children: App.Domains.Family.DataObjects.ChildPortalData[]
                }
                export type FamilySearchResultData = {
                    id: number
                    primary_name: string
                    masked_email: string
                    email_confirmed: boolean
                    satellite: string
                    children: string[]
                    can_open: boolean
                }
                export type GuardianEditData = {
                    first_name: string
                    last_name: string
                    relationship: App.Enums.RelationshipEnum
                    phone: string
                    ministries: string[]
                }
                export type GuardianPortalData = {
                    first_name: string
                    last_name: string
                    relationship_label: string
                    phone: string | null
                }
                export type ListFiltersData = {
                    q: string
                    satellite: number | null
                    status: App.Enums.ChildStatusEnum | null
                    level: number | null
                    unconfirmed: boolean
                    duplicates: boolean
                }
                export type NoteData = {
                    id: number
                    body: string
                    author: string
                    at: string
                }
            }
        }
        namespace Level {
            namespace DataObjects {
                export type LevelOptionData = {
                    level: number
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
            | 'set_child_level'
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
        export type MagicLinkRouteEnum = 'login' | 'verify' | 'claim'
        export type NoteKindEnum = 'system' | 'observation'
        export type NoteVisibilityEnum = 'staff' | 'family'
        export type RegistrationOutcomeEnum =
            'registered' | 'already_registered' | 'claim_sent' | 'claim_resent'
        export type RegistrationSourceEnum = 'self' | 'staff' | 'import'
        export type RelationshipEnum =
            'mother' | 'father' | 'guardian' | 'grandparent' | 'aunt_uncle' | 'sibling' | 'other'
        export type RoleEnum = 'admin' | 'lifeshaper'
        export type ScopeTypeEnum = 'church' | 'satellite' | 'schedule'
    }
    namespace Foundation {
        namespace DataObjects {
            export type PaginationData = {
                page: number
                last_page: number
                total: number
                per_page: number
            }
        }
        namespace Inputs {
            export type ChildInput = {
                first_name: string
                middle_name: string | null
                last_name: string
                nickname: string
                gender: App.Enums.GenderEnum
                date_of_birth: string
                has_allergies: boolean
                allergies: string | null
                illnesses: string | null
                dislikes: string | null
                fears: string | null
                guardian_notes: string | null
            }
            export type ChildPhotoInput = {
                photo: File
                consent: boolean
            }
            export type FamilyDetailsInput = {
                home_satellite_id: number
                area_text: string | null
                guardian: App.Foundation.Inputs.GuardianDetailsInput
                fb_account: string | null
                wants_fb_group: boolean
                in_dgroup: boolean
                dgroup_area_text: string | null
                dgroup_leader_name: string | null
                dgroup_leader_phone: string | null
                wants_dgroup: boolean
                lifestage: string | null
                notes: string | null
            }
            export type GuardianDetailsInput = {
                first_name: string
                last_name: string
                relationship: App.Enums.RelationshipEnum
                phone: string
                ministries: string[]
            }
            export type GuardianInput = {
                first_name: string
                last_name: string
                relationship: App.Enums.RelationshipEnum
                phone: string
                email: string
                ministries: string[]
            }
            export type RegisterFamilyInput = {
                home_satellite_id: number
                area_text: string | null
                privacy_consented: boolean
                guardian: App.Foundation.Inputs.GuardianInput
                children: App.Foundation.Inputs.ChildInput[]
                fb_account: string | null
                wants_fb_group: boolean
                in_dgroup: boolean
                dgroup_area_text: string | null
                dgroup_leader_name: string | null
                dgroup_leader_phone: string | null
                wants_dgroup: boolean
                lifestage: string | null
                notes: string | null
            }
        }
    }
    namespace Services {
        namespace Auth {
            namespace Data {
                export type PasswordLoginInput = {
                    email: string
                    password: string
                }
                export type RequestMagicLinkInput = {
                    email: string
                }
                export type UpdatePasswordInput = {
                    password: string
                }
            }
        }
        namespace Church {
            namespace Data {
                export type AddStaffInput = {
                    name: string
                    email: string
                    role: App.Enums.RoleEnum
                    scope_type: App.Enums.ScopeTypeEnum
                    scope_id: number
                }
                export type CorrectFamilyEmailInput = {
                    email: string
                }
                export type ResolveDuplicateInput = {
                    status: App.Enums.DuplicateStatusEnum
                }
                export type UpdateChildLevelInput = {
                    level: number
                }
                export type UpdateChildStatusInput = {
                    status: App.Enums.ChildStatusEnum
                }
            }
        }
    }
}
