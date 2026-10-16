# Security list

Roy, 2026-10-15: *"we can make a list of security issues but i dont want it to stop us."*
Items here are known and tracked; none blocks a release unless marked **must**. Open **must** items: #4, #5
(both = TODO #18). Updated 2026-10-16.

| # | Item | Status | Where |
|---|---|---|---|
| 1 | Family email correction on a login that is also staff / multi-org: re-point the family + claim link instead of editing the account | v1 refuses with a message; full flow later | plan §4.4 |
| 2 | Magic links bound to email + version, single use (atomic), revoked on email change | done (R1) | plan §4.3 |
| 3 | Pending family claims: one per email per org, revision-bound links | done (R1) | plan §4.2 |
| 4 | Access matrix tests for every Feature (anonymous, parent, staff, other satellite, other church, revoked) | **must** — rows tested and every Feature declares its row (`AccessRowTest`); the full 7-actor grid is TODO #18, before R1 ships | plan §3.2 |
| 5 | Field allowlists per projection + response tests; no child data in Sentry | **must** — Sentry scrubbed (#18); projections built; response-allowlist tests are TODO #18, before R1 ships | plan §3.3 |
| 6 | Photo URLs short-lived + authorized; consent withdrawal removes the photo from view | done — signed 30-minute links + viewer check + consent check; withdrawal **hides** (Roy's choice, see #19) | plan §3.3 |
| 7 | Rate limits: login link per email + IP, registration per IP; backup password 5/min per email | done (R1) | plan §4.3 |
| 8 | Name barcodes cannot be revoked (legacy cards) | accepted until QR in Feb | plan §5 |
| 9 | Admin role-grant ceiling | done — `AccessScope::canGrant`, revert-checked | plan §3.2 |
| 10 | Emailed links: GET shows Continue, POST uses the link (scanner-safe) | done | diff review r3 |
| 11 | Spreadsheet exports write text only (no formula injection) | done | diff review r5 |
| 12 | Public registration gives one answer for every outcome (no membership oracle) | done | diff review r6 |
| 13 | Recipient-level limit on link emails across all paths | done | diff review r4 |
| 14 | Staff search shows masked emails and no phones/health to Lifeshapers outside their satellite | done | plan §3.3 |
| 15 | Codex diff review not signed off after 6 rounds — another pass before production | done — review-spar round A4 approves (`review-spar.md`) | spar.md |
| 16 | People "Add someone" shows the existing account's NAME for a known email (typed name ignored) — tells an admin who owns an email. Admin-only, low. Fixed: replies name the typed email. (Once added, the person appears on People with their name — that is the point of the list.) | done | review 2 |
| 17 | Photo upload racing a consent withdrawal could leave a photo with no consent (review 3, Codex round A) | done — save locks the child and re-checks consent before writing; the photo route and links refuse without consent. Two-connection interleaving test still TODO #20 | review 3 |
| 18 | Sentry: exception messages carried SQL values (names, emails) — "no child data" was documented-only (review 3) | done — `SentryScrubber::clean` | review 3 |
| 19 | Withdrawn photo consent HIDES the photo but keeps the file (Roy's choice over delete). The photo is served to nobody without consent (route + links refuse, tested); the stored file remains in the private bucket. If a parent asks for erasure, it must be deleted by hand until a delete action exists | accepted by Roy | review 4 |
