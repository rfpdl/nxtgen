# Family service

A parent's own family page (`/{church}/family`) and a child's photo link.

> What each Feature is for lives on it as `#[UserStory]`; tests name their `story#N`. This file holds only
> domain knowledge.

## Rules
- **The Family comes from the signed-in Guardian**, never from the address or the request. Anything missing is
  a 404.
- A parent sees every detail of their own Children, health included. Staff notes never show here.
- A parent edits their Family, their own details and their Children's details, but **never a Child's status or
  Level** (status is an Admin action; the Level changes at Moving-Up or by a church admin's correction).
- A new Child starts at the Level their age suggests.
- The parent who signs in is the primary Guardian. On "Edit family details" they also add, change and remove the
  Family's other Guardians, who have no login; each change is noted on the Family's history.

## Photos and Consent
- **No photo without Consent.** Consent is given by the parent here, or recorded by a Lifeshaper the parent told
  in person (Church service). Every change is noted on the Child, with who and when.
- A parent can agree and add a photo in one step, or agree without one so a Lifeshaper takes it at church.
- Taking Consent back **hides** the photo from everyone (no link, the route refuses) but keeps it; agreeing again
  brings it back.
- A photo opens by a signed, short-lived link, and only for a parent of that Family or Staff who can see the
  Family, so a forwarded link is useless. Never cached.
