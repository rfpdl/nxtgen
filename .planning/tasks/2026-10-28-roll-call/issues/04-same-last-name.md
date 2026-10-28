# 04 — Same last name, different families

Status: resolved
Blocked by: 03
Raised: Roy 2026-10-28 — "there might be multiple same last name, like Reyes, it's very common, but different family".

## What
- **Search narrows with more words** (`Family::scopeMatching`, used by the door and the lists): split the typed text
  into words; a family matches when EVERY word matches something on it — a parent's first/last name or email, or a
  child's first/last/nickname. So "Ana Reyes", "Amara Reyes" and "Reyes Amara" find only the right family. One
  word works as today. A phone-shaped part of the search matches phone digits (four or more); name words around it
  narrow ("Reyes 050 444 5566") — after review.
- **Who's in shows the first + last name under the nickname** (small, muted; no middle name — after review), so two Amaras in one Squad differ. Still no
  photo, no phone.
- The door card already tells families apart (parent's name, satellite, children) — unchanged.

## Done when (what you see)
With two Reyes families (Ana's and Joy's), typing "Ana Reyes" at the door shows only Ana's family; "Reyes" shows
both. On Who's in, two Amaras read "Amara / Amara Reyes" and "Amara / Amara Santos".

## Test seams
Model/Feature: scopeMatching with several words (parent + last name, child + last name, word order, no cross-family
match where one word hits family A and the other family B — both words must hit the SAME family), phone still works,
the lists use the same rule. Allowlist for the full name on Who's in. E2E: the door search with two words.

## Comments

**2026-10-28 — built (agent), not committed.** Gates below. Looked at on a 390px phone and at 1280 (door search
"Reyes" / "Ana Reyes" / "Bela Reyes", Who's in with two Amaras): fine as built.

- **One word rule**: `App\Foundation\Support\SearchWords` — words split on spaces, each at least two letters, at most
  five; `phoneDigits()` for a phone-shaped term (digits, spaces, + - ( ) .) with four or more digits; `like()`.
  `Family::scopeMatching` / `scopeMatchingContact` now AND one group per word on the same family (a parent: names,
  email, login email, phone digits of the word; plus, for `matching`, a child's first/last/nickname), OR a parent's
  phone for a phone-shaped term. Callers: the door search and staff registration (SearchFamiliesJob), the Families
  list and its Excel (GetFamiliesJob), the Children list (GetChildrenJob: each word on the child's own names OR their
  family's parent — review 4's rule kept, tested).
- **Who's in**: WhosInChildData gains `full_name` (Child::fullName()), shown small and muted under the nickname inside
  the tick (row still 64px+); allowlist updated.
- **Tests**: SearchWords unit test; SearchFamiliesJob (two Reyes families: one word finds both, parent + last name,
  word order, child + last name, nickname, no cross-family match, one-letter word skipped, email + child, phone spaced
  / with + and dashes / name + digits, five-word cap); GetFamiliesJob and GetChildrenJob multi-word; Who's in feature
  + allowlist; Vitest for the full name; e2e: the door search with two words on all three browsers, and the full name
  on Who's in.
- **For Roy**: the door's own "Checked in" list (under the search) still shows two Amaras the same — the issue left the
  door unchanged; say if you want the full name there too.
Gates: `make check` green (Pest 1803 + arch 173, Vitest 393, PHPStan L10); `make e2e` green (82 passed, 1 skipped).

**2026-10-28 — review fixes (agent).** Eight items:
- **Name + spaced phone**: SearchWords pulls the first run of digit-ish words (digits, + - ( ) .) with four digits or
  more out as ONE phone number, AND'ed with the name words around it: "Reyes 050 444 5566", "050 444 5566" and "Reyes
  0504445566" all find Joy's family; "Ana 050 444 5566" finds none.
- **Any Unicode space** splits words (`(*UCP)[\s\p{Z}]`, a non-breaking space too). **Backslash** is escaped in LIKE
  with % and _. **Digits inside an email** (roy1985@…) are never compared with phones — only a digit-ish word is.
- **One rule**: SearchWords is a small value object (words + phone) with `apply($query, $perWord, $phone)`, used by
  Family::scopeMatching and GetChildrenJob; Family gains `withParentMatching($word)` and `withParentPhone($digits)`
  (scopeMatchingContact is gone). SearchFamiliesJob's own under-two-characters guard is gone (the rule decides).
- **Who's in** shows first + last name (`Child::shortName()`, `short_name` on WhosInChildData), no middle name;
  feature test and Vitest with two Amaras of different families in one Squad.
- DECISIONS records that a one-letter or blank search on the Children list now matches nothing.
Gates: `make check` green (Pest 1819 + arch 173, Vitest 393, PHPStan L10); `make e2e` green (82 passed, 1 skipped).
