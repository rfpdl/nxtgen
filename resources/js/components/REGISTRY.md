# Component registry

Every component in `resources/js/components/` (except vendored `ui/`) is listed here. The discipline test
fails when one is missing. Reuse before you build: search this list first.

Props are typed from `@/types` (aliases over the generated, snake_case `generated.d.ts`); a form's model is a
`Draft<…Input>`. Enum options come from the page's `enums` prop through `useEnum()`, the page's church through
`useChurch()` — neither is passed down as a prop.

| Component               | Layer    | Purpose                                                                                                                        |
| ----------------------- | -------- | ------------------------------------------------------------------------------------------------------------------------------ |
| FlashBanner             | molecule | The server's success/error message, sticky at the top; errors loud, successes quiet                                            |
| FormField               | atom     | A labelled form control with optional hint and its error                                                                       |
| ChildFieldset           | molecule | The fields for one child (a `ChildInput` draft); boy/girl from the page's `enums.gender` (`useEnum`)                           |
| RegisterFamilyForm      | organism | The registration form (parents and staff): parent, other parents/guardians, family, children, consent                          |
| FamilyChildCard         | molecule | One of the parent's own children on the family page; nickname first, allergy warning                                           |
| AddChildForm            | organism | "Add a child" on the family page, reusing ChildFieldset                                                                        |
| ChurchShell             | layout   | Church staff frame: the shared `church`'s name, Check in, Families, Children, QR poster, People and Classes (admins), sign out |
| FamilySearchResultCard  | molecule | A church-wide "already registered?" hit (and each side of a possible duplicate): masked email, add-a-child                     |
| StaffMemberCard         | molecule | One person who serves, with their roles; Remove only where the viewer may                                                      |
| AddStaffForm            | organism | "Add someone": name, email, one role from the server's allowed choices                                                         |
| SignOutButton           | atom     | "Sign out" (POST /logout) for every signed-in page: staff menu, family page, landing pages                                     |
| GuardianFieldset        | molecule | A parent's own fields (+ email on registration only), one ministry; `other` words it for another guardian                      |
| FamilyFieldset          | molecule | The family's fields (satellite, area, FB, DGroup, lifestage); shared by registration and editing                               |
| FamilyDetailsForm       | organism | "Edit family details": GuardianFieldset (no email) + OtherGuardiansFieldset + FamilyFieldset, PUT                              |
| EditChildForm           | organism | "Edit {child}'s details": ChildFieldset from what is saved, PUT to the given action; never status/Level                        |
| RegistrationListFilters | molecule | Families + children lists: search, satellite/status/Level (+ unconfirmed/duplicates with familyToggles); emits address params  |
| SwitchLink              | atom     | "Switch" between My family / Serve (or churches): only when there is more than one place to go                                 |
| PasswordLoginForm       | organism | "Use a password instead" on sign-in: the staff backup, collapsed by default                                                    |
| ChildPhotoForm          | organism | A child's photo: take (camera) or choose; parent agrees/takes back consent, staff only after consent                           |
| ChildAdminControls      | molecule | Staff status (`enums.child_status`) + Level pickers inside a child's card; a refused Level shows on that child                 |
| ResendLinkButton        | atom     | "Send the confirmation link again" (POST) for an unconfirmed family: family page, lists, door search                           |
| Pager                   | molecule | "Page 2 of 3" with Previous / Next for the registration lists; hidden when one page holds all                                  |
| OtherGuardiansFieldset  | organism | The family's other parents/guardians (no email): add / remove (saved ones reported), via GuardianFieldset                      |
| PossibleDuplicateCard   | organism | A possible duplicate on a family's page: newer and earlier side by side, "Not the same" / "Same family"                        |
| ScheduleCard            | molecule | One class (Schedule) on the Classes page: name, day and times; Edit, and Retire after a second tap                             |
| AttendanceSquadTable    | molecule | One Squad of a Session on Attendance: name, count; a table (sm+) or stacked rows (phone) of names, time, who                   |
| ScheduleForm            | organism | Add (POST, with `satelliteId`) or edit (PUT, from a saved `schedule`) a class: name, day, times; `done` on save or cancel      |
| SatelliteSchedules      | organism | One satellite on the Classes page: its classes, one edited in place at a time, "+ Add a class" opening the add form            |
| DoorChildRow            | molecule | A child on a door family card: tick, nickname (tap → health), Squad, ⚠ flag; ✓ in + Undo; "Make active again?"; pick a Level   |
| ChildHealthPanel        | molecule | A child's health details at the door, only after their name is tapped; allergies first and loud; Close                         |
| DoorFamilyCard          | organism | A family found at the door: its children (DoorChildRow), "Check in N children" into the chosen class (POST), Undo (PUT)        |
| DoorCheckedInList       | molecule | Who is in the chosen class today at the door, newest first: nickname, Squad, time, Undo; empty state says how to start         |
