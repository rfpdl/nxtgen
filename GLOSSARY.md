# NxtGen

The kids ministry app for CCF Dubai: families register their children, staff look after them, and children are
checked in and out of classes.

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
A regular time a Satellite runs its kids classes (e.g. Sunday 1pm). Shown to people as "class".
_Avoid_: Class (in code), event series, service, session

**Session**:
One Schedule on one date (e.g. Sunday 18 Oct, 1pm at CCF Dxb). Children are checked in and out of a Session.
_Avoid_: Event, occurrence, service

**Level**:
An age band of the Church (e.g. Level 2, ages 6–7). A Child's age suggests their Level; the Child stays at it
until Moving-Up.
_Avoid_: Grade, year, stage

**Squad**:
A Satellite's grouping of children within a Level, by gender from Level 2 up (e.g. "Level 2 Boys").
_Avoid_: Group, class, cohort

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
The Role that looks after children at a Schedule — checks them in and out and keeps their details right.
_Avoid_: Teacher, volunteer, servant
