# NxtGen

The kids ministry app for CCF's NxtGen, at several satellites: families register their children, staff look after
them, and children are checked in to classes.

## Who and where

**Church**:
The body that runs the app for its people (CCF). Everything in the app belongs to one Church. It is kept in the data
and never in an address.
_Avoid_: Organization, tenant, account

**Satellite**:
One place where a Church meets (e.g. CCF Dubai). Each Satellite keeps its own Squads and Schedules, and has its own
address: `/dubai/…`. A staff page shows the Satellite in its address and nothing else.
_Avoid_: Branch, campus, site

**Ministry**:
A part of the Church with its own people and classes (e.g. NxtGen kids). Today the app is NxtGen's only, so the
ministry is not in the address.
_Avoid_: Department, program

**Registration code**:
A Satellite's 8 letters and digits (e.g. `k7m2q9xa`) that never change, carried by its printed QR poster:
`/k7m2q9xa/register` opens the Satellite's registration form at its address, whatever the address is now.
_Avoid_: short link, poster id, slug (the slug is the address, which may change)

**Link**:
One of a Satellite's own addresses on its QR poster — its parents' Facebook group, its WhatsApp group — with what
the poster says under its QR code. Up to 6 a Satellite; https only.
_Avoid_: URL (in copy), social link, bookmark

**Serve** (area):
The everyday pages for everyone who serves — the door, Who's in, Families, the QR poster (`/dubai/serve/…`).
Where anyone without an Admin Role lands.
_Avoid_: client, front desk, app

**Admin** (area):
The pages for managing — families, children, classes, Attendance, Cards, Moving-Up, Links, People
(`/dubai/admin/…`). Only for someone with an Admin Role at that Satellite; someone who only serves there is sent to
its Serve.
_Avoid_: back office, dashboard

## Families

**Family**:
The household a child is registered under; one or more Guardians, one or more Children.
_Avoid_: Household, account

**Guardian**:
An adult on a Family who may act for its children (parent, grandparent, carer). Shown to people as "parent".
_Avoid_: Parent (in code), carer, contact

**Child**:
A young person registered on a Family, at one Level.
_Avoid_: Kid, student, member

**Lifestage**:
A Family's stage of life, from the registration form: Single, Married or KSP — or none given. The form starts on
Married when the parent registering is a mother or father.
_Avoid_: Civil status, marital status

**Consent**:
A Guardian's recorded yes or no to a specific use of a Child's data (today: the Child's photo).
_Avoid_: Permission, agreement

## Classes

**Schedule**:
A Satellite's named kids class, with a start and an end time; a Satellite may have several. **Recurring** (a weekday,
every week — "1PM" on Saturdays) or **one-time** (one date — "True Life Day 1"). Shown to people as "class".
_Avoid_: Class (in code), event series, service, session

**Session**:
One Schedule on one date (e.g. Saturday 31 Oct, 1PM at CCF Dubai). An admin opens its check-in (which gives it its
code) and closes it, or it closes at midnight; a Child is checked in only while it is open.
_Avoid_: Event, occurrence, service

**Check-in**:
Recording that a Child is at a Session, made by Staff at the door. A Check-in can be undone.
_Avoid_: Attendance (for the act), sign-in, registration

**Name card**:
The printed NXTGEN card a Child carries, with their name and a barcode (Code 39) of their full name in capitals with no
spaces, e.g. ADAMMATTHIASABERGOS. "Scan card" at the door reads it — or a desk scanner types it into the search.
_Avoid_: ID card, badge, tag

**Card request**:
One ask for a new Name card for one Child, moving Asked → Approved → Printed → Given (or Refused). Reason: Lost,
Damaged, or First card (free; a lost or damaged card is AED 10, marked paid at the desk). A Child has at most one open
request. Staff file it; parents tell a Lifeshaper.
_Avoid_: ID request, ticket, order

**Print list**:
The approved Card requests an admin put on one list to print — exactly those, kept as they were when the list was made
(nothing approved later joins it). Downloaded as Excel in the shape of the sheet's Barcode tab, and marked printed
together. In code: `PrintList`.
_Avoid_: batch, print run, export

**Attendance**:
The admins' report of a Session: the Children checked in, by Squad, with counts — on screen and as Excel.
_Avoid_: Session report, register (and "attendance" for the act of checking in — that is a Check-in)

**Roll call**:
During a Session, marking which checked-in Children are present in their Squad, or confirming the Squad's head
count.
_Avoid_: Attendance check, register

**Level**:
An age band of the Church (e.g. Level 2, ages 6–7). A Child's age suggests their Level; the Child stays at it
until Moving-Up.
_Avoid_: Grade, year, stage

**Squad**:
A Satellite's grouping of children within a Level, by gender from Level 2 up (e.g. "Level 2 Boys").
_Avoid_: Group, class, cohort

**Squad leader**:
The Lifeshaper looking after a Squad during a Session. Usually the same person, but anyone may step in; it is not a
Role.
_Avoid_: Teacher, group leader

**Moving-Up**:
The yearly step, done by people (never automatically), where Children go to the next Level; from the top Level they
leave NxtGen. Also said as "level up". The last one can be **put back** — for one Child, or everyone.
_Avoid_: Promotion, graduation; undo, revert (say "put back")

**Graduated**:
A Child who has moved up out of the top Level and is no longer part of NxtGen.
_Avoid_: Elevated, alumni, left

## Staff

**Staff**:
Anyone given a Role in the Church.
_Avoid_: Volunteer (in code), user, worker

**Role**:
What a Staff member may do, and where: one Satellite, or one Schedule. There is no church-wide Role; one person may
hold Roles at several Satellites.
_Avoid_: Permission, access level

**Admin**:
The Role that manages its Satellite — people (any Role there), families, children, classes, Cards, Links,
Moving-Up — or one class (a **class admin**: that class's door, Who's in, Attendance, and editing it). A Satellite
has any number of admins and always at least one.
_Platform admin_: set by a command (`nxtgen:platform-admin`), not a Role — opens any Satellite, and at `/platform`
manages the Satellites themselves, every Role anywhere and the church's settings (time zone, card fee, Moving-Up date);
the menu's "Platform" link takes them there.
_Avoid_: Super user, manager

**Lifeshaper**:
The Role that looks after children at a Schedule — checks them in and keeps their details right.
_Avoid_: Teacher, volunteer, servant
