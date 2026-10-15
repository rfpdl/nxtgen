# Component registry

Every component in `resources/js/components/` (except vendored `ui/`) is listed here. The discipline test
fails when one is missing. Reuse before you build: search this list first.

| Component              | Layer    | Purpose                                                                                        |
| ---------------------- | -------- | ---------------------------------------------------------------------------------------------- |
| FlashBanner            | molecule | The server's success/error message, sticky at the top; errors loud, successes quiet            |
| FormField              | atom     | A labelled form control with optional hint and its error                                       |
| ChildFieldset          | molecule | The fields for one child on the registration form                                              |
| RegisterFamilyForm     | organism | The parent registration form: parent, family, children, consent                                |
| FamilyChildCard        | molecule | One of the parent's own children on the family page; nickname first, allergy warning           |
| AddChildForm           | organism | "Add a child" on the family page, reusing ChildFieldset                                        |
| OrgShell               | layout   | Church staff frame: church name, Families, Register a family, People (admins), sign out, flash |
| FamilySearchResultCard | molecule | A church-wide "already registered?" hit: masked email, satellite, add-a-child                  |
| StaffMemberCard        | molecule | One person who serves, with their roles; Remove only where the viewer may                      |
| AddStaffForm           | organism | "Add someone": name, email, one role from the server's allowed choices                         |
| SignOutButton          | atom     | "Sign out" (POST /logout) for every signed-in page: staff menu, family page, landing pages     |
