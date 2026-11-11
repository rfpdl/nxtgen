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
    | 'manage_classes'
    | 'check_in'
    | 'attendance'
    | 'card_request'
    | 'cards'
    | 'moving_up'
    | 'manage_links'
    | 'delete_child_photo'
    | 'open_session'
export type AddStaffInput = {
    name: string
    email: string
    role: RoleEnum
    scope_type: ScopeTypeEnum
    scope_id: number
}
export type AttendanceChildData = {
    id: number
    nickname: string
    full_name: string
    checked_in_at: string
    checked_in_by: string
    present: boolean
}
export type AttendanceClassData = {
    id: number
    name: string
    retired: boolean
}
export type AttendancePageData = {
    classes: AttendanceClassData[]
    class_id: number | null
    date: string
    date_label: string
    squads: AttendanceSquadData[]
    total: number
}
export type AttendanceSquadData = {
    name: string
    level: LevelData
    count: number
    children: AttendanceChildData[]
    head_count: SquadHeadCountData | null
}
export type CardFeeEnum = 'free' | 'paid' | 'not_paid'
export type CardListTabEnum = 'asked' | 'approved' | 'printed' | 'closed'
export type CardMatchEnum = 'exact' | 'several' | 'close' | 'none'
export type CardMoveEnum = 'mark_paid' | 'approve' | 'refuse' | 'mark_printed' | 'give'
export type CardReasonEnum = 'lost' | 'damaged' | 'first_card'
export type CardRequestData = {
    id: number
    child_id: number
    step: CardStepEnum
    fee: CardFeeEnum
    fee_color: 'success' | 'info' | 'warning' | 'danger' | 'muted'
    summary: string
    moves: CardMoveEnum[]
}
export type CardRequestInput = {
    reason: CardReasonEnum
    note: string | null
}
export type CardRequestRowData = {
    id: number
    child_name: string
    family_id: number
    family_name: string
    satellite: string
    reason_label: string
    note: string | null
    step: CardStepEnum
    step_label: string
    fee: CardFeeEnum
    fee_label: string
    fee_color: 'success' | 'info' | 'warning' | 'danger' | 'muted'
    refused_reason: string | null
    moves: CardMoveEnum[]
}
export type CardRequestsPageData = {
    requests: CardRequestRowData[]
    pagination: PaginationData
    tab: CardListTabEnum
    q: string | null
    print_lists: PrintListData[]
}
export type CardStepEnum = 'asked' | 'approved' | 'printed' | 'given' | 'refused'
export type CheckInChildInput = {
    child_id: number
    level: number | null
    make_active: boolean
}
export type CheckInInput = {
    children: CheckInChildInput[]
}
export type CheckInResultEnum =
    'checked_in' | 'already_in' | 'graduated' | 'inactive' | 'needs_level' | 'closed'
export type ChildAttendanceData = {
    child_id: number
    check_ins: ChildCheckInData[]
}
export type ChildCheckInData = {
    id: number
    date: string
    class: string
    satellite: string
    squad: string
    present: boolean
}
export type ChildHealthData = {
    id: number
    nickname: string
    allergies: string | null
    illnesses: string | null
    dislikes: string | null
    fears: string | null
    guardian_notes: string | null
}
export type ChildInput = {
    first_name: string
    middle_name: string | null
    last_name: string
    nickname: string
    gender: GenderEnum
    date_of_birth: string
    has_allergies: boolean
    allergies: string | null
    illnesses: string | null
    dislikes: string | null
    fears: string | null
    guardian_notes: string | null
}
export type ChildListRowData = {
    id: number
    nickname: string
    full_name: string
    age: number
    date_of_birth: string
    level: LevelData | null
    status_label: string
    has_allergies: boolean
    satellite: string
    family_id: number
}
export type ChildPhotoInput = {
    photo: File
    consent: boolean
}
export type ChildPortalData = {
    id: number
    first_name: string
    middle_name: string | null
    last_name: string
    nickname: string
    gender: GenderEnum
    date_of_birth: string
    allergies: string | null
    illnesses: string | null
    dislikes: string | null
    fears: string | null
    guardian_notes: string | null
    level: LevelData | null
    status_label: string
    photo_consent: boolean
    photo_url: string | null
    has_photo: boolean
}
export type ChildStatusEnum = 'active' | 'inactive' | 'graduated'
export type ChildrenPageData = {
    children: ChildListRowData[]
    pagination: PaginationData
    filters: ListFiltersData
    options: {
        levels: LevelOptionData[]
    }
}
export type ChoosePageData = {
    options: LandingOptionData[]
}
export type ChurchData = {
    name: string
    slug: string
}
export type ChurchNavData = {
    area: string | null
    can_use_admin: boolean
    can_use_full_admin: boolean
    can_manage_people: boolean
    can_manage_classes: boolean
    can_see_attendance: boolean
    can_manage_cards: boolean
    can_move_up: boolean
    can_manage_links: boolean
}
export type ChurchPeoplePageData = {
    people: StaffMemberData[]
    grant_options: GrantOptionData[]
}
export type ChurchSettingsData = {
    timezone: string
    card_fee: string
    moving_up_date: string | null
}
export type ChurchSettingsInput = {
    timezone: string
    card_fee: string
    moving_up_date: string
}
export type ConfirmLinkPageData = {
    purpose: MagicLinkRouteEnum
    title: string
    prompt: string
    button: string
    action: string
}
export type CorrectFamilyEmailInput = {
    email: string
}
export type DoorCheckInData = {
    id: number
    nickname: string
    squad: string
    level: LevelData
    checked_in_at: string
}
export type DoorChildData = {
    id: number
    nickname: string
    full_name: string
    level: LevelData | null
    squad: string | null
    status: ChildStatusEnum
    status_label: string
    has_allergies: boolean
    check_in_id: number | null
    suggested_level: number | null
}
export type DoorFamilyData = {
    id: number
    name: string
    primary_name: string
    satellite: string
    children: DoorChildData[]
    can_open: boolean
}
export type DoorPageData = {
    session: DoorSessionData
    open_classes: SessionChoiceData[]
    can_open: boolean
    checked_in: DoorCheckInData[]
    query: string
    scan: DoorScanData | null
    families: DoorFamilyData[]
    levels: LevelOptionData[]
    health?: undefined | ChildHealthData | null
}
export type DoorQueryInput = {
    squad: number | null
    card: boolean
}
export type DoorScanData = {
    code: string
    match: CardMatchEnum
    color: 'success' | 'info' | 'warning' | 'danger' | 'muted'
    child_ids: number[]
    note: string | null
}
export type DoorSessionData = {
    code: string
    class_id: number
    name: string
    times: string
    date_label: string
    state: SessionStateEnum
    state_label: string
    state_color: 'success' | 'info' | 'warning' | 'danger' | 'muted'
    notice: string | null
}
export type DuplicatePairData = {
    id: number
    reason_label: string
    subject: FamilySearchResultData
    candidate: FamilySearchResultData
}
export type DuplicateReasonEnum = 'same_phone' | 'same_name_dob'
export type DuplicateStatusEnum = 'open' | 'dismissed' | 'confirmed'
export type FamiliesPageData = {
    families: FamilyListRowData[]
    pagination: PaginationData
    filters: ListFiltersData
    options: {
        levels: LevelOptionData[]
    }
}
export type FamilyAdminData = {
    id: number
    primary_name: string
    email: string
    email_confirmed: boolean
    satellite: string
    area: string | null
    registered_by: string
    guardians: GuardianPortalData[]
    children: ChildPortalData[]
    can_edit: boolean
}
export type FamilyClaimStatusEnum = 'pending' | 'accepted' | 'revoked'
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
    lifestage: LifestageEnum | null
    notes: string | null
}
export type FamilyDetailsInput = {
    home_satellite_id: number
    area_text: string | null
    guardian: GuardianDetailsInput
    fb_account: string | null
    wants_fb_group: boolean
    in_dgroup: boolean
    dgroup_area_text: string | null
    dgroup_leader_name: string | null
    dgroup_leader_phone: string | null
    wants_dgroup: boolean
    lifestage: LifestageEnum | null
    notes: string | null
    guardians: OtherGuardianInput[]
    removed_guardian_ids: number[]
}
export type FamilyHomePageData = {
    family: FamilyPortalData
    details: FamilyDetailsData
    me: GuardianEditData
    other_guardians: GuardianEditData[]
    satellites: SatelliteOptionData[]
}
export type FamilyListRowData = {
    id: number
    primary_name: string
    email: string
    email_confirmed: boolean
    satellite: string
    children: ListChildData[]
    registered_by: string
    registered_on: string
    can_resend: boolean
}
export type FamilyPortalData = {
    home_satellite: string
    area: string | null
    guardians: GuardianPortalData[]
    children: ChildPortalData[]
}
export type FamilySearchResultData = {
    id: number
    primary_name: string
    masked_email: string
    email_confirmed: boolean
    satellite: string
    children: ListChildData[]
    can_open: boolean
    can_resend: boolean
}
export type GenderEnum = 'boy' | 'girl'
export type GiveRoleInput = {
    name: string
    email: string
    role: RoleEnum
    scope_type: ScopeTypeEnum
    scope_id: number
}
export type GrantOptionData = {
    label: string
    role: RoleEnum
    scope_type: ScopeTypeEnum
    scope_id: number
}
export type GuardianDetailsInput = {
    first_name: string
    last_name: string
    relationship: RelationshipEnum
    phone: string
    ministries: string[]
}
export type GuardianEditData = {
    first_name: string
    last_name: string
    relationship: RelationshipEnum
    phone: string
    ministries: string[]
    id: number | null
}
export type GuardianInput = {
    first_name: string
    last_name: string
    relationship: RelationshipEnum
    phone: string
    email: string
    ministries: string[]
}
export type GuardianPortalData = {
    first_name: string
    last_name: string
    relationship_label: string
    phone: string | null
}
export type HeadCountInput = {
    count: number
}
export type LandingOptionData = {
    church_name: string
    label: string
    url: string
    place: string
}
export type LevelData = {
    number: number
    name: string
}
export type LevelOptionData = {
    level: number
    name: string
}
export type LifestageEnum = 'single' | 'married' | 'ksp'
export type LinksPageData = {
    satellites: SatelliteLinksData[]
    max: number
}
export type ListChildData = {
    nickname: string
    level: LevelData | null
}
export type ListFiltersData = {
    q: string
    status: ChildStatusEnum | null
    level: number | null
    unconfirmed: boolean
    duplicates: boolean
}
export type MagicLinkPurposeEnum = 'login' | 'verify_email' | 'claim_family'
export type MagicLinkRouteEnum = 'login' | 'verify' | 'claim'
export type MakePrintListInput = {
    q: string | null
}
export type MarkPresentInput = {
    present: boolean
    revision: number
}
export type MoveChildUpInput = {
    child_id: number
    level: number
}
export type MoveChildrenUpInput = {
    children: MoveChildUpInput[]
}
export type MoveSatelliteLinkInput = {
    direction: string
}
export type MovingUpCandidateData = {
    id: number
    nickname: string
    full_name: string
    satellite: string
    level: LevelData
    moves_to: string
}
export type MovingUpChildData = {
    child_id: number
    nickname: string
    full_name: string
    from: string
    to: string
    put_back: string | null
}
export type MovingUpData = {
    id: number
    made: string
    made_by: string | null
    is_recent: boolean
    children: MovingUpChildData[]
}
export type MovingUpPageData = {
    children: MovingUpCandidateData[]
    without_level: number
    last: MovingUpData | null
}
export type NoteData = {
    id: number
    body: string
    author: string
    at: string
}
export type NoteKindEnum = 'system' | 'observation'
export type NoteVisibilityEnum = 'staff' | 'family'
export type OtherGuardianInput = {
    first_name: string
    last_name: string
    relationship: RelationshipEnum
    phone: string
    ministries: string[]
    id: number | null
}
export type PaginationData = {
    page: number
    last_page: number
    total: number
    per_page: number
}
export type PasswordLoginInput = {
    email: string
    password: string
}
export type PasswordPageData = {
    has_password: boolean
}
export type PickSatellitePageData = {
    satellites: SatelliteChoiceData[]
}
export type PlatformPeoplePageData = {
    people: StaffMemberData[]
    grant_options: GrantOptionData[]
}
export type PlatformSatelliteData = {
    id: number
    name: string
    slug: string
    code: string
    admins: number
}
export type PlatformSatellitesPageData = {
    satellites: PlatformSatelliteData[]
}
export type PlatformSettingsPageData = {
    settings: ChurchSettingsData
    timezones: string[]
}
export type PosterLinkData = {
    label: string
    url: string
    qr: string
}
export type PosterPageData = {
    register_url: string
    qr_url: string
    qr: string
    links: PosterLinkData[]
}
export type PrintListData = {
    id: number
    made: string
    made_by: string | null
    cards: number
    to_print: number
}
export type PutBackResultEnum = 'put_back' | 'already_put_back' | 'changed_since' | 'elsewhere'
export type RefuseCardRequestInput = {
    reason: string
}
export type RegisterFamilyInput = {
    home_satellite_id: number
    area_text: string | null
    privacy_consented: boolean
    guardian: GuardianInput
    children: ChildInput[]
    fb_account: string | null
    wants_fb_group: boolean
    in_dgroup: boolean
    dgroup_area_text: string | null
    dgroup_leader_name: string | null
    dgroup_leader_phone: string | null
    wants_dgroup: boolean
    lifestage: LifestageEnum | null
    notes: string | null
    guardians: OtherGuardianInput[]
}
export type RegisterPageData = {
    satellites: SatelliteOptionData[]
}
export type RegistrationOutcomeEnum =
    'registered' | 'already_registered' | 'claim_sent' | 'claim_resent'
export type RegistrationSourceEnum = 'self' | 'staff' | 'import'
export type RelationshipEnum =
    'mother' | 'father' | 'guardian' | 'grandparent' | 'aunt_uncle' | 'sibling' | 'other'
export type RequestMagicLinkInput = {
    email: string
}
export type ResolveDuplicateInput = {
    status: DuplicateStatusEnum
}
export type RoleEnum = 'admin' | 'lifeshaper'
export type SatelliteChoiceData = {
    name: string
    slug: string
}
export type SatelliteData = {
    id: number
    name: string
    slug: string
}
export type SatelliteInput = {
    name: string
    slug: string
}
export type SatelliteLinkData = {
    id: number
    label: string
    url: string
}
export type SatelliteLinkInput = {
    label: string
    url: string
}
export type SatelliteLinksData = {
    id: number
    name: string
    links: SatelliteLinkData[]
}
export type SatelliteOptionData = {
    id: number
    name: string
}
export type SatelliteSchedulesData = {
    id: number
    name: string
    schedules: ScheduleData[]
    past: ScheduleData[]
    can_add: boolean
}
export type ScheduleData = {
    id: number
    name: string
    weekday: WeekdayEnum | null
    weekday_label: string | null
    date: string | null
    date_label: string | null
    starts_at: string
    ends_at: string
}
export type ScheduleInput = {
    name: string
    one_time: boolean
    weekday: WeekdayEnum | null
    date: string | null
    starts_at: string
    ends_at: string
}
export type SchedulesPageData = {
    satellites: SatelliteSchedulesData[]
}
export type ScopeTypeEnum = 'satellite' | 'schedule'
export type ServeClassData = {
    id: number
    name: string
    times: string
    state: SessionStateEnum
    state_label: string
    state_color: 'success' | 'info' | 'warning' | 'danger' | 'muted'
    code: string | null
    can_open: boolean
}
export type ServePageData = {
    today: string
    classes: ServeClassData[]
}
export type SessionChoiceData = {
    code: string
    name: string
}
export type SessionStateEnum = 'not_open' | 'open' | 'closed'
export type SharedAuthData = {
    user: {
        id: number
        name: string
    } | null
    can_switch: boolean
    is_platform_admin: boolean
}
export type SharedFlashData = {
    success: string | null
    error: string | null
}
export type SharedPropsData = {
    auth: SharedAuthData
    church: ChurchData | null
    open_satellites: SatelliteChoiceData[]
    satellite: SatelliteData | null
    church_nav: ChurchNavData | null
    flash: SharedFlashData
}
export type SquadHeadCountData = {
    count: number
    counted_at: string
    counted_by: string
}
export type StaffFamilyPageData = {
    family: FamilyAdminData
    attendance: ChildAttendanceData[]
    card_requests: CardRequestData[]
    notes: NoteData[]
    duplicates: DuplicatePairData[]
    details: FamilyDetailsData | null
    primary: GuardianEditData | null
    other_guardians: GuardianEditData[]
    satellites: SatelliteOptionData[]
    admin_controls: boolean
    can_set_level: boolean
    levels: LevelOptionData[]
}
export type StaffMemberData = {
    id: number
    name: string
    email: string
    roles: StaffRoleData[]
}
export type StaffRegisterPageData = {
    query: string
    results: FamilySearchResultData[]
    satellites: SatelliteOptionData[]
}
export type StaffRoleData = {
    id: number
    label: string
    can_remove: boolean
}
export type UpdateChildLevelInput = {
    level: number
}
export type UpdateChildStatusInput = {
    status: ChildStatusEnum
}
export type UpdatePasswordInput = {
    password: string
}
export type WeekdayEnum =
    'monday' | 'tuesday' | 'wednesday' | 'thursday' | 'friday' | 'saturday' | 'sunday'
export type WhosInChildData = {
    id: number
    revision: number
    nickname: string
    short_name: string
    has_allergies: boolean
    present: boolean
}
export type WhosInPageData = {
    session: DoorSessionData
    open_classes: SessionChoiceData[]
    squads: WhosInSquadData[]
    squad_id: number | null
}
export type WhosInSquadData = {
    id: number
    name: string
    level: LevelData
    present: number
    count: number
    children: WhosInChildData[]
    head_count: SquadHeadCountData | null
}
