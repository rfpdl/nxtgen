# Church service

The Church's Staff pages (`/{church}/…`), for every Staff member, not only Admins. What each person may do is
decided by `AccessScope` against the access-matrix row the Feature declares (`#[AccessRow]`), never by the URL.

> What each Feature is for lives on it as `#[UserStory]`; tests name their `story#N`. This file holds only
> domain knowledge.

## Who may
- **Every refusal is a 404**, never a 403: a page does not confirm that something it will not show exists. Ids in
  the address are looked up within this Church only.
- A church admin sees and edits the whole Church. A Satellite admin edits Families whose home is their Satellite.
  A Lifeshaper sees their Satellite's Families but gets no edit forms.
- **Registration is church-wide**: any Staff member searches the whole Church first, then adds a Child to a found
  Family (any Satellite) or registers a new one. The email stays unconfirmed until the parent taps the link; that
  never blocks check-in.
- Lists and the Excel download show only Satellites the viewer can see; filters only narrow. Emails are masked
  for anyone who may not edit; the download has full emails only for a church admin.

## Children
- Status (active, inactive, Graduated) is set by a church admin or the home Satellite's admin.
- **Only a church admin corrects a Level**, outside Moving-Up, and only to a Level on the chart. There is no gender
  rule: the Child's Squad follows from Level and gender.
- A Lifeshaper may record a parent's in-person yes to photos ("Are you sure the parent said yes?"), then take the
  photo. Consent sent with a photo does not count.
- Every change to status, Level, Consent or email is noted on the Child or Family, with who did it.

## Families
- The family email can be corrected only while the login is this Family's alone, unconfirmed and not Staff;
  otherwise a Satellite admin could take over a Staff account.
- A resend sends a claim link if a claim is pending, otherwise a fresh confirm link; the page says honestly when
  the per-address limit stopped it.
- A possible duplicate is answered by someone who may edit the newer Family: not the same, or the same (flagged
  for a later merge; nothing is merged).

## People
- One login per person: adding someone who already has a login (often a parent) only gives the Role.
- A Satellite admin gives only Lifeshaper, in their own Satellite. A Role is switched off, never deleted, and the
  Church is never left without a church admin.
