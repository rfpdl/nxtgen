# 04 — Same last name, different families

Status: ready-for-agent
Blocked by: 03
Raised: Roy 2026-10-28 — "there might be multiple same last name, like Reyes, it's very common, but different family".

## What
- **Search narrows with more words** (`Family::scopeMatching`, used by the door and the lists): split the typed text
  into words; a family matches when EVERY word matches something on it — a parent's first/last name or email, or a
  child's first/last/nickname. So "Ana Reyes", "Amara Reyes" and "Reyes Amara" find only the right family. One
  word works as today; phone digits keep working (digits ≥ 4 compared as before, on the whole term).
- **Who's in shows the full name under the nickname** (small, muted), so two Amaras in one Squad differ. Still no
  photo, no phone.
- The door card already tells families apart (parent's name, satellite, children) — unchanged.

## Done when (what you see)
With two Reyes families (Ana's and Joy's), typing "Ana Reyes" at the door shows only Ana's family; "Reyes" shows
both. On Who's in, two Amaras read "Amara / Amara Reyes" and "Amara / Amara Santos".

## Test seams
Model/Feature: scopeMatching with several words (parent + last name, child + last name, word order, no cross-family
match where one word hits family A and the other family B — both words must hit the SAME family), phone still works,
the lists use the same rule. Allowlist for the full name on Who's in. E2E: the door search with two words.
