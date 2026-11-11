# Component registry

Every component in `resources/js/components/` (except vendored `ui/`) is listed here. The discipline test
fails when one is missing. Reuse before you build: search this list first.

Props are typed from `@/types` (aliases over the generated, snake_case `generated.ts`); a form's model is a
`Draft<…Input>`. Enum options come from the page's `enums` prop through `useEnum()`, the page's church through
`useChurch()` — neither is passed down as a prop.

## The UI kit (the Bold look, DECISIONS 2026-10-28)

Pages, molecules, organisms and layouts never draw a raw `<button>`, `<input>`, `<select>` or `<textarea>`, and
never pick a colour: they use these atoms, which read the tokens in `app.css`. The discipline test enforces it;
every staff, parent and public page is on the kit (issues 02 and 03 of `.planning/tasks/2026-10-28-ui-theme`).

| Component      | Layer  | Purpose                                                                                                                                                                                 |
| -------------- | ------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AppButton      | atom   | THE button: `primary` (green, pressable) / `secondary` / `quiet` / `danger` / `link`; `md`/`lg`/`icon`; loading; `href` → Inertia Link, `external` → plain `<a>` (a download)           |
| TextField      | atom   | Labelled input (v-model, `type`), hint + error wired by `aria-describedby`, `#leading` icon, `hideLabel`                                                                                |
| DateField      | atom   | Labelled date to type (day / month / year) or pick (calendar button); no day after today; model `YYYY-MM-DD` or ''                                                                      |
| DateCalendar   | atom   | DateField's calendar: month and year selects to jump, then the days — none after `max`                                                                                                  |
| SelectField    | atom   | Labelled pick-one list; the model is the option's own value (`null` for the placeholder; `placeholderDisabled`: shown, not pickable); hint + error wired                                |
| TextArea       | atom   | Labelled box for a few lines; hint + error wired                                                                                                                                        |
| CheckRow       | atom   | Big tick (role checkbox): a whole row with a label slot, or a 64px square named by `label` (`stretch`: full row height); disabled                                                       |
| Disclosure     | atom   | Show more in place: one big button (`aria-expanded`/`aria-controls`, chevron) over a `panel` slot shown while open (v-model:open)                                                       |
| ChoiceChips    | atom   | Pick one of a few as pressable chips (a radio group) — every week or one date, Who's in's Squads, allergy yes/no; `labelledby`/`label`, `required`, `error` wired; an option's `testid` |
| ClassSwitch    | atom   | The bar on a class's page: the class, and a menu of its satellite's classes open now, each a link to its door                                                                           |
| FileButton     | atom   | A file picker that looks and presses like a secondary AppButton (the photo's take / choose); attrs on the hidden input                                                                  |
| Card           | atom   | White tile, 2px edge and a 5px bottom; `as`, `flush` for a card of rows                                                                                                                 |
| RollCallMark   | atom   | A child's roll call on Attendance: "Present ✓" (a success Pill) or "Not marked" in quiet words — one piece for phone rows and table                                                     |
| Notice         | atom   | A short message in the page: `danger` (an alert), `warning` (check this) or `info`, bold on a soft tint — e.g. a name card that matched no one                                          |
| Pill           | atom   | Small label: a `level` (Level: number + name) in the age-level chart's colours (tint + dot; says the name by default), or `danger` / status / neutral                                   |
| ItemList       | atom   | Typed text as its items, one chip each — a comma separates them (allergies, illnesses); stored whole                                                                                    |
| SectionHeading | atom   | A part of a page's heading: `h2` under the PageHeader, or a smaller `h3` (`level="3"`) inside a card                                                                                    |
| LinkTabs       | atom   | Tabs that are pages: a named `nav` of links, the current one marked and pressed in; two a row on a phone, one row of equal tabs wider — door, Cards                                     |
| RowList        | atom   | A card of padded rows with a line between them; `href` makes each whole row a link ending in ›, `#after` sits outside it; `plain`                                                       |
| EmailText      | atom   | An email that breaks only after `@` `.` `-` `_` (`<wbr>`), so it never splits mid-word on a phone                                                                                       |
| PageHeader     | atom   | A page's one `h1`, a `sub` line, its own buttons (`#actions`)                                                                                                                           |
| EmptyState     | atom   | What an empty list says: what to do next, optional title and `#action`                                                                                                                  |
| BrandMark      | atom   | The NXTGEN logo (SVG), for the app bar; `large` for the welcome page                                                                                                                    |
| AppShell       | layout | The Bold frame: amber bar (logo, page name, place), menu as a phone sheet (reka-ui Dialog) or a desktop row, flash; no `items` = no menu (public pages); `narrow` = one column          |
| FamilyShell    | layout | The parent's frame on AppShell, phone-first and `narrow`: "My family", the church; menu My family, Switch (when they also serve), Sign out                                              |
| PlatformShell  | layout | The platform admin's frame on AppShell (spec item 12): menu Satellites, People, Settings                                                                                                |

## Everything else

| Component               | Layer    | Purpose                                                                                                                                                             |
| ----------------------- | -------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| FlashBanner             | molecule | The server's success/error message, sticky at the top; errors loud, successes quiet                                                                                 |
| ChildFieldset           | molecule | The fields for one child (a `ChildInput` draft); boy/girl from the page's `enums.gender` (`useEnum`)                                                                |
| RegisterFamilyForm      | organism | The registration form (parents and staff): parent, other parents/guardians, family, children, consent                                                               |
| FamilyChildCard         | molecule | One of the parent's own children on the family page; nickname first, allergy warning                                                                                |
| AddChildForm            | organism | "Add a child" on the family page, reusing ChildFieldset                                                                                                             |
| ChurchShell             | layout   | Church staff frame on AppShell: Check in, Families, Children, QR poster, People/Classes/Attendance (admins), Password; names the page                               |
| FamilySearchResultCard  | molecule | A church-wide "already registered?" hit (and each side of a possible duplicate): masked email, add-a-child                                                          |
| StaffMemberCard         | molecule | One person who serves, with their roles; Remove only where the viewer may                                                                                           |
| AddStaffForm            | organism | "Add someone": name, email, one role from the server's allowed choices                                                                                              |
| SatelliteSwitch         | atom     | The bar's satellite (spec item 13): its name; a dropdown to another satellite's home for someone with two or more                                                   |
| SignOutButton           | atom     | "Sign out" (POST /logout) for every signed-in page: staff menu, family page, landing pages                                                                          |
| GuardianFieldset        | molecule | A parent's own fields (+ email on registration only), one ministry; `other` words it for another guardian                                                           |
| FamilyFieldset          | molecule | The family's fields (satellite, area, FB, DGroup, lifestage); shared by registration and editing                                                                    |
| FamilyDetailsForm       | organism | "Edit family details": GuardianFieldset (no email) + OtherGuardiansFieldset + FamilyFieldset, PUT                                                                   |
| EditChildForm           | organism | "Edit {child}'s details": ChildFieldset from what is saved, PUT to the given action; never status/Level                                                             |
| RegistrationListFilters | molecule | Families + children lists: search, satellite/status/Level (+ unconfirmed/duplicates with familyToggles); emits address params                                       |
| PlatformLink            | atom     | "Platform": the platform admin's shortcut to /platform, beside Switch; not on the platform's own pages                                                              |
| SwitchLink              | atom     | "Switch" between My family / Serve (or churches): only when there is more than one place to go                                                                      |
| PasswordLoginForm       | organism | The staff backup password on /loginp (never on /login): a Card of TextFields + Sign in                                                                              |
| ChildPhotoForm          | organism | A child's photo: take (camera) or choose; parent agrees/takes back consent, staff only after consent                                                                |
| ChildAdminControls      | molecule | Staff status (`enums.child_status`) + Level pickers inside a child's card; a refused Level shows on that child                                                      |
| ResendLinkButton        | atom     | "Send the confirmation link again" (a secondary AppButton that POSTs) for an unconfirmed family: family page, lists, door search                                    |
| CorrectEmailForm        | organism | "Typed the email wrong? Correct it" on the family page (PUT): TextField + "Save email"; cleared once saved                                                          |
| Pager                   | molecule | "Page 2 of 3" with Previous / Next for the registration lists; hidden when one page holds all                                                                       |
| OtherGuardiansFieldset  | organism | The family's other parents/guardians (no email): add / remove (saved ones reported), via GuardianFieldset                                                           |
| PossibleDuplicateCard   | organism | A possible duplicate on a family's page: newer and earlier side by side, "Not the same" / "Same family"                                                             |
| ScheduleCard            | molecule | One class (Schedule) on the Classes page: name, day or date, and times; Edit, and Retire after a second tap (none when `past`)                                      |
| ServeClassCard          | molecule | One of today's classes on Serve: name, times, state; Go to the door (its code), See who was in, or Open for its admin                                               |
| SessionControls         | molecule | Where today's class stands at its door: not open yet, open, closed; its admin opens, closes (second tap) and reopens it                                             |
| AttendanceSquadTable    | molecule | One Squad of a Session on Attendance: name, count, head count + warning; table (sm+) or stacked rows (phone): names, time, who, present                             |
| ScheduleForm            | organism | Add (POST, with `satelliteId`) or edit (PUT, from a saved `schedule`) a class: name, every week on a day or one date, times; `done` on save or cancel               |
| SatelliteSchedules      | organism | One satellite on the Classes page: its classes, one edited in place at a time, "+ Add a class" opening the add form                                                 |
| GuardianList            | molecule | A family's parents and guardians (both family pages): name in bold, relationship, phone if any                                                                      |
| LinkCard                | molecule | One Link on the Links page: label and address; Edit, Move up/down (not past either end), Remove after a second tap                                                  |
| LinkForm                | organism | Add (POST, with `satelliteId`, Facebook/WhatsApp starter labels) or change (PUT, from a saved `link`) a Link; `done` on save or cancel                              |
| SatelliteLinks          | organism | One satellite on the Links page: its Links in order, one edited in place, "+ Add a link" until the poster holds `max`                                               |
| DoorChildRow            | molecule | A door child: full-height tick zone; the rest opens (Disclosure) health, status, Level, family link; ✓ + Undo; make active; Level                                   |
| ChildHealthPanel        | molecule | A child's health details inside their opened door row; allergies first and loud, or "Allergies: none"                                                               |
| DoorFamilyCard          | organism | A family found at the door: its children (DoorChildRow), "Check in N children" (POST), Undo (PUT) at the Session's code; a scanned card's child pre-ticked          |
| DoorCheckedInList       | molecule | Who is in the chosen class today at the door, newest first: nickname, Squad, time, Undo (none when `readOnly`); empty state says how to start                       |
| DoorTabs                | molecule | The door's two tabs, Check in · Who's in, on both pages, at the Session's own code; the current one marked                                                          |
| WhosInSquadCard         | molecule | One Squad on Who's in: Level Pill, "N of M present"; each child a whole-row tick: nickname, allergy flag, first + last name; its head count; `readOnly` when closed |
| HeadCountForm           | molecule | A Squad's head count on Who's in: number (the count checked in at first) + Confirm; "✓ Confirmed 13:20 by Joy (4)"; mismatch + Not marked                           |
| ChildAttendanceList     | molecule | A child's attendance on the staff family page, newest first: date · class · satellite · Squad, present or not; last 12, "Show all"                                  |
| ScanCardSheet           | organism | "Scan card" at the door: a bottom sheet (reka-ui Dialog) with the back camera reading a name card's Code 39; refused → type instead                                 |
| ChildCardRequest        | organism | A child's name card on the staff family page: "Card: Asked · not paid" (Pill) for an open request, else "New card…" → Lost/Damaged + note                           |
| CardRequestRow          | organism | One Card request on Cards: child's full name, family (link) · satellite, reason — note, fee Pill; the moves the server sends (PUT), Refuse… asks why                |
| PrintListCard           | molecule | A print list still to print on Cards' Approved tab: "Print list · 12 cards · made … by …", Download (Excel), "Mark these N printed" (PUT)                           |
| MovingUpLastCard        | molecule | The last Moving-Up: when and by whom, each child "Level 1 → Level 2" (or Graduated), "Put back" each, "Put everyone back" (PUTs)                                    |
