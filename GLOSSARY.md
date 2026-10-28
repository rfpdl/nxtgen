# NxtGen

The kids ministry app for CCF Dubai: families register their children, staff look after them, and children are
checked in to classes.

## Who and where

**Church**:
The body that runs the app for its people (e.g. CCF Dubai). Everything in the app belongs to one Church.
_Avoid_: Organization, tenant, account

**Satellite**:
One place where a Church meets (e.g. CCF Dxb). Each Satellite keeps its own Squads and Schedules.
_Avoid_: Branch, campus, site

**Ministry**:
A part of the Church with its own people and classes (e.g. NxtGen kids). Planned: today there is only one.
_Avoid_: Department, program

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

**Consent**:
A Guardian's recorded yes or no to a specific use of a Child's data (today: the Child's photo).
_Avoid_: Permission, agreement

## Classes

**Schedule**:
A Satellite's named, regular kids class (e.g. "1PM" on Saturdays at 1pm); a Satellite may have several. Shown to
people as "class".
_Avoid_: Class (in code), event series, service, session

**Session**:
One Schedule on one date (e.g. Saturday 31 Oct, 1PM at CCF Dxb). It begins with its first Check-in; it is never opened or closed.
_Avoid_: Event, occurrence, service

**Check-in**:
Recording that a Child is at a Session, made by Staff at the door. A Check-in can be undone.
_Avoid_: Attendance (for the act), sign-in, registration

**Name card**:
The printed NXTGEN card a Child carries, with their name and a barcode (Code 39) of their full name in capitals with no
spaces, e.g. ADAMMATTHIASABERGOS. "Scan card" at the door reads it — or a desk scanner types it into the search.
_Avoid_: ID card, badge, tag

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
leave NxtGen. Also said as "level up".
_Avoid_: Promotion, graduation

**Graduated**:
A Child who has moved up out of the top Level and is no longer part of NxtGen.
_Avoid_: Elevated, alumni, left

## Staff

**Staff**:
Anyone given a Role in the Church.
_Avoid_: Volunteer (in code), user, worker

**Role**:
What a Staff member may do, and where (the whole Church, one Satellite, or one Schedule).
_Avoid_: Permission, access level

**Admin**:
The Role that manages people, families and settings for its scope.
_Avoid_: Super user, manager

**Lifeshaper**:
The Role that looks after children at a Schedule — checks them in and keeps their details right.
_Avoid_: Teacher, volunteer, servant
