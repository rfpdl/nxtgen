# 11 — Re-send the confirmation email: whoever can open the family

Status: ready-for-agent
Blocked by: 10

DECISIONS 2026-10-17 (later — Roy's answers); closes TODO #28.

## What
- `AccessScope::canResendLink(family)` = `canSeeFamily(family)` (church admin, the family's satellite admin, any
  Lifeshaper who can see it). Update the access grid row and the allowlists.
- A "Send the confirmation link again" action on unconfirmed rows of the families list and the children list
  (same endpoint as the family page's button), with the same success message.
- Parents: no change — registering again or asking for a sign-in link already sends a fresh link (prove it with a
  test if none exists).

## Done when
Grid row updated (another satellite's admin now ok where their Lifeshaper is); e2e journey presses it from a list;
`make check` + `make e2e` green.
