# Church service

The Church's Staff pages (`/{church}/…`), for every Staff member, not only Admins. What each person may do is
decided by `AccessScope` against the access-matrix row the Feature declares (`#[AccessRow]`), never by the URL.

> What each Feature is for lives on it as `#[UserStory]`; tests name their `story#N`. This file holds only
> domain knowledge.

## Who may
- **Every refusal is a 404**, never a 403: a page does not confirm that something it will not show exists. Ids in
  the address are looked up within this Church only.
- There is no church-wide Role: every Role is at one Satellite or one class. A Satellite admin does everything for
  their Satellite and edits Families whose home is their Satellite. A Lifeshaper sees their Satellite's Families but
  gets no edit forms.
- **Registration is church-wide**: any Staff member searches the whole Church first, then adds a Child to a found
  Family (any Satellite) or registers a new one. The email stays unconfirmed until the parent taps the link; that
  never blocks check-in.
- Lists and the Excel download show only Satellites the viewer can see; filters only narrow. Emails are masked
  for anyone who may not edit; the download has full emails only for the Satellite's admins.

## Children
- Status (active, inactive, Graduated) is set by the home Satellite's admin.
- **Only the home Satellite's admin corrects a Level**, outside Moving-Up, and only to a Level on the chart. There is no gender
  rule: the Child's Squad follows from Level and gender.
- **Moving-Up is a Satellite admin's, for their Satellite** (DECISIONS 2026-11-06, 2026-11-08): every Active child of
  that Satellite with a Level goes up one Level, from the top Level to Graduated; the admin unticks anyone who stays. If any child changed since the page was opened,
  nobody moves. The last Moving-Up can be put back — one child, or everyone — except a child someone changed since.
- A Lifeshaper may record a parent's in-person yes to photos ("Are you sure the parent said yes?"), then take the
  photo. Consent sent with a photo does not count.
- Every change to status, Level, Consent or email is noted on the Child or Family, with who did it.

## Families
- The family email can be corrected only while the login is this Family's alone, unconfirmed and not Staff;
  otherwise a Satellite admin could take over a Staff account.
- Any staff member in the Church may re-send a Family's confirmation link, a visiting Family too — from the
  family page, an unconfirmed row of the families list, or the door search. A resend sends a claim link if a claim is pending, otherwise a fresh
  confirm link; the page says honestly when the per-address limit stopped it. (A parent gets a fresh link by
  registering again or asking for a sign-in link.)
- **Registering starts from the Families list** ("Register a family", search first) — there is no Register tab.
  Staff then land on the Family's page; back at the search if it is a Satellite they cannot open.
- A Family has one or more Guardians. The primary one's login is the Family's; whoever may edit the Family adds,
  changes and removes the others (no login) on "Edit family details", each change noted on its history, and a
  phone they bring is checked for possible duplicates.
- **Possible duplicates have no page of their own**: the Families list's "duplicates" filter finds them, and they
  are answered on either Family's page by someone who may edit the newer Family: not the same (clears a false
  flag), or the same (flagged for a later merge; nothing is merged). A pair is recorded once, whichever Family it
  was found from.

## People
- One login per person: adding someone who already has a login (often a parent) only gives the Role.
- A Satellite admin gives and removes any Role at their Satellite (Admin or Lifeshaper), and Admin at its classes;
  nowhere else. A Role is switched off, never deleted, and a Satellite is never left without an admin.

## Classes
- A class (GLOSSARY "Schedule") belongs to one Satellite: a name, and a day and start/end time only if it has them.
  A class with a day shows at the door only on that day; one with no day shows every day.
- Satellite admins manage their Satellite's classes, a class admin their class; a Lifeshaper has no Classes page.
- A class is retired, never deleted (Sessions will point at it). Two active classes at a Satellite never share a
  name; a retired class's name is free again.

## The door (check-in)
- A Session (one class on one date) is made when an admin of the satellite, or of that class, opens it — on its date
  only — and is open until closed or until midnight at the satellite; reopened the same day, it is the same Session.
  Children are checked in, undone, marked present and counted only while it is open (spec item 15). One Check-in per
  child per Session, whatever number of phones press at once (the database's unique keys decide; tested with two real
  connections). "Today" is the church's date (its timezone), not the server's.
- Staff see today's classes at the satellites their Roles cover; a class with a day only on that day. Any child of the church may be checked in, and joins THIS satellite's Squad for their Level
  and gender — a visiting child needs nothing.
- Graduated children are refused. An inactive child is made active again first (the door asks), and a child with no
  Level gets one picked at the door (their age's suggestion first) — both saved and noted on the child's history. The
  door never changes a Level a child has.
- Undo keeps the row (who and when it was undone); checking the child in again revives it. Only today's Check-ins.
- The search shows no photo or phone and only an allergy yes/no; health details only when a child's row is opened.

## Attendance
- Admins only, at the Satellite in the address: pick a class and date and see
  the children checked in to that Session — undone Check-ins left out — by Squad (Level order, then mixed, boys,
  girls), each with nickname, full name, check-in time (the church's) and who checked them in; a count per Squad and
  a total; "No one was checked in" when empty. It opens on today's class with a Session, else the latest Session.
- A retired class stays in the picker, marked "(retired)", while it has a Session — its history stays reportable.
- The Excel has the same rows in allowlisted columns, every cell plain text. Another Satellite's or Church's class is a
  404, and a Lifeshaper has no Attendance (no menu item, 404).
