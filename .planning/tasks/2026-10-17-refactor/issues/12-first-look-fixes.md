# 12 — Fixes from Roy's first look in the browser

Status: resolved
Blocked by: 11

DECISIONS 2026-10-17 (Roy, after first look in the browser). This batch changes screens on purpose.

## What
1. Remove the "Register a family" tab from the staff menu; a "Register a family" button on the Families list opens
   the same search-first registration page.
2. After staff register a family (or add a child to a found family), redirect to that family's page with the success
   message (not back to the form).
3. More than one guardian per family: the parent registration form, the staff registration form and the family page
   can add / edit / remove extra guardians (name, phone, relationship, can pick up …— whatever a guardian has today).
   The primary guardian keeps the one login (the family email); extra guardians have no login. Keep the duplicate
   detection (guardian phone) working for every guardian. Access: who may edit guardians = who may edit the family.
4. Family page: each child's Level and Status controls move inside that child's card.
5. Remove the Duplicates tab and its page/route/Feature if nothing else needs them; the Families list's
   "duplicates" filter stays. If the "answer a duplicate" action is only reachable from that page, decide: keep the
   answer action on the family page (flagged families) or drop it — prefer keeping a way to clear a false flag.

## Done when
Every change has Pest + e2e coverage (access grid and allowlists updated deliberately); stories updated;
`make check` + `make e2e` green.

## Comments

**2026-10-17 — built (Claude, tdd).** `make check` green (arch 173, Pest 1338, Vitest 125); `make e2e` 50 passed.

What changed, per item:

1. **No Register tab.** ChurchShell lost "Register a family"; the Families list has a "Register a family" button
   (`register-family`) to the same search-first page.
2. **Redirects.** Staff registering (`RegisterFamilyFeature`) and adding a child (`AddChildFeature`) land on the
   family's page with the same message (story#85, #86).
3. **Other guardians.** `OtherGuardianInput` (GuardianDetailsInput's rules, "their" wording, no email, optional
   `id`). `RegisterFamilyInput.guardians` → `FamilyRegistrationData.otherGuardians` → `RegisterFamilyOperation` makes
   them (no login, no email) BEFORE the duplicate check, so their phones count. `FamilyDetailsInput.guardians`
   (nullable) → `UpdateFamilyGuardiansJob` sets the family's non-primary guardians to the list (id = change, no id =
   add, left out = remove), checks every id first (primary / other family's → 404, nothing written) and notes each
   change on the family ("X (Grandparent) added to the family." / "…: details changed." / "… removed from the
   family."). Used by the staff edit (EditFamily row) and the parent's own edit (OwnFamily row); duplicate check
   re-runs after. Frontend: `OtherGuardiansFieldset` (add/remove; reuses `GuardianFieldset`, which gained
   `errorKey`/`testid`/`other`), in `RegisterFamilyForm` and `FamilyDetailsForm`. Pages get `other_guardians`
   (editors only on the staff page). Stories #87, #88, #89, #90, #91, #94.
4. **Level + Status inside the child's card** (`ChildAdminControls` in `FamilyChildCard`'s slot).
5. **Duplicates tab, page, route `church.duplicates.index`, `ShowDuplicatesFeature`, `Duplicates/Index.vue`,
   `GetOpenDuplicatesJob` and `church_nav.can_review_duplicates` removed.** The answer action is kept: the family
   page lists the open pairs the family is on (either side, family or child pairs — `GetFamilyOpenDuplicatesJob`)
   to an admin who may answer them, as `PossibleDuplicateCard` with "Not the same family" / "Same family" (same
   `church.duplicates.resolve` route and Feature). Stories #92, #93; #70/#71 retired with the page.

Decisions made (for Roy to confirm):

- **Other guardians have no email** (only phone, name, relationship, ministry): one login per family, so an email
  would be a field nobody uses. The edit form sends the whole list; `null` (not sent) leaves them untouched.
- **The primary guardian's own edit still writes no history note** (as before); only other-guardian changes do.
- **Staff who cannot open the family they just registered** (a Lifeshaper registering for another satellite) or
  added a child to go back to the search page with the message, instead of a 404. "Already registered" also stays
  on the search page.
- **A possible duplicate is shown on the family page only to someone who may answer it** (admin who may edit the
  newer family), on either family's page. Lifeshapers see no pairs.
- **A pair is recorded once, whichever family it is found from**: `CreateDuplicateCandidatesJob` now runs again when
  an older family changes (a guardian or child added), so it skips a pair already recorded the other way round —
  answered or not — instead of re-flagging a dismissed pair.
- `GuardianEditData` gained `id` (so `primary.id` / `me.id` are new on the allowlists); AccessWorld's family got
  another guardian (Jun) so the allowlist names `other_guardians.*` fields deliberately.

**2026-10-17 — after code review (Claude, tdd).** Fixed:

1. A refused save no longer wipes the guardian rows: `FamilyDetailsForm` starts again from what is saved only in
   `onSuccess` (Vitest + an e2e step: a guardian saved without a phone keeps her row and its error).
2. **Explicit removal**: the edit form sends the rows it shows plus `removed_guardian_ids`; a guardian mentioned in
   neither is left alone, so an older page cannot remove one added since (Pest, job + Feature). A removal of a
   guardian already gone is passed over; any other unknown id is a 404.
3. `UpdateFamilyGuardiansJob` only touches guardians with no login (`whereNull('user_id')`) other than the primary,
   and refuses (404, nothing written) a family with no primary — on the staff and the parent path.
4. A possible-duplicate pair now names the **newer record** (higher id: the family registered later, or the child
   added later) as its subject, whichever family it was found from — so the newer family's editor answers, as plan §6
   says. The card says "Added more recently" / "Registered earlier". Tested from registration, a guardian added on
   edit, and a child added to an older family.
5. On edit the duplicate check runs only when a phone came in or changed (an other guardian's, or the primary's).
6. `OtherGuardiansFieldset` and `PossibleDuplicateCard` are organisms (they compose molecules); REGISTRY updated.
7. The primary's `GuardianDetailsData.email` is required again. Other guardians travel as `GuardianEditData`
   (no email) and are made by their own `CreateOtherGuardianJob`; `GuardianEditData::fromForm` is the one clean-up
   for both inputs (no throwaway `GuardianDetailsInput`).
8. Deliberately unchanged: **"Can pick up" is not settable** — every guardian is created with `can_pick_up = true`,
   as before (it matters at check-out, Release 2). **At most 5 other guardians** per form (and 20 removals).

