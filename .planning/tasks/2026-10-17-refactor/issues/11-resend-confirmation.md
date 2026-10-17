# 11 — Re-send the confirmation email: any staff member, a visiting family too

Status: resolved
Blocked by: 10

DECISIONS 2026-10-17 (later — Roy's answers), replaced by 2026-10-17 (later still — re-send for a visiting
family); closes TODO #28.

## What
- `AccessScope::canResendLink(family)` = family in this church AND an active staff role here (`canWorkHere`) —
  other satellites' Lifeshapers and admins included; revoked roles, parents and other churches refused. Update the
  access grid row and the allowlists.
- A "Send the confirmation link again" action on unconfirmed rows of the families list, the children list and the
  door search results (same endpoint as the family page's button), with the same success message.
- Parents: no change — registering again or asking for a sign-in link already sends a fresh link (prove it with a
  test if none exists).

## Done when
Grid row updated (every active staff actor `ok`, the other satellite's admin and Lifeshaper included; revoked,
parents-only and other church 404); e2e journeys press it from a list and, for a visiting family, from the door
search; `make check` + `make e2e` green.

## Comments

**2026-10-17 — built (test-first).**
- `AccessScope::canResendLink` is now `canSeeFamily` (AccessScopeTest: six roles × home/away, and never another
  church). Grid row `church.families.resend` = `Actor::seeingFamily()`: the other satellite's Lifeshaper is now
  404, the same answer as the other satellite's admin (before, any Lifeshaper anywhere was `ok`).
  `AccessRowEnum::ResendLink` label and plan §3.2's table row match.
- Lists: `FamilyListRowData` and `ChildListRowData` gain `can_resend` (unconfirmed AND `canResendLink`) — added to
  ResponseAllowlistTest deliberately. `GetChildrenJob` eager-loads `family.primaryGuardian.user`; a Pest test
  proves the children list's query count does not grow with rows.
- New atom `ResendLinkButton` (REGISTRY + Vitest) replaces the family page's inline button and is used on
  unconfirmed rows of both lists; same route, same flash text.
- Parents: Pest proves registering again with an unconfirmed email (story#5) and asking at /login (story#9/#12)
  each send a fresh link that confirms the email. No app change.
- New criterion `story#84` on `ResendConfirmationFeature`; e2e `staff/lifeshaper.spec.ts` "re-sends the
  confirmation link from the children list" runs on iPhone, Android and desktop.
- Gates: `make check` green (Pest 1328 + arch 173, Vitest 115); `make e2e` 46 passed.

**2026-10-17 — Roy widened the rule (DECISIONS "later still — re-send for a visiting family").** The first pass
above (`canSeeFamily`) is superseded:
- `canResendLink` = same church AND `canWorkHere()`. AccessScopeTest: church/home/away admin and Lifeshaper and a
  class (schedule) admin all may; a revoked role and another church's family may not. Grid row
  `church.families.resend` = `Actor::staff()`. ChurchFamiliesTest: the other satellite's admin re-sends for a
  visiting family; a revoked Lifeshaper gets 404 and no link is issued (the old "class admin refused" test went —
  the new rule allows it). Enum label, plan §3.2, STORIES.md, STATE match.
- Door search: `FamilySearchResultData` gains `can_resend` (unconfirmed AND `canResendLink`; a duplicate pair
  always says false) — added to `LITE_FAMILY` in ResponseAllowlistTest deliberately; no photo/health field.
  `FamilySearchResultCard` shows `ResendLinkButton` when it is true (Vitest).
- e2e: "re-sends a visiting family's confirmation link from the door search" — the DXB Lifeshaper registers a
  House Church family, finds it at the door (no link to open it), re-sends, sees the message, the email arrives.
