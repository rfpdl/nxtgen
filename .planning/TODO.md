# TODO

| # | Item | Why | When |
|---|---|---|---|
| 1 | Moving-Up for a **mixed** group (Level 1 → Level 2 Boys/Girls): `next_group_id` can't express a per-gender next group | design gap found while seeding | before Feb Moving-Up |
| 2 | ~~E2E harness~~ built: `make e2e` / `make e2e-ui` / `make e2e-report`, 4 journeys × 2 browsers | plan §10 | done 2026-10-15 |
| 3 | Laravel Cloud project + env (D2) | Roy owns the account | before Release 1 deploy |
| 4 | ~~D-R1~~ decided: import once, app is the source of truth (plan §7.6) | Roy 2026-10-15 | done |
| 5 | ~~D-R2~~ decided: People screens (built) + `ChurchSeeder` from NXTGEN_* settings (built 2026-10-15). **Roy:** real satellite list + first admin email | Roy 2026-10-15 | before deploy |
| 6 | Remaining R1 build list (review.md "Remaining Release 1 build") | review-spar | before R1 release |
| 7 | Claim-acceptance concurrency test (two DB connections) | review-spar | with R2 concurrency harness |
| 8 | ~~Church slug never a reserved path~~ — ChurchSeeder refuses one; enforce again in any future create-church screen | Org rename | with Platform |
| 9 | Class-admin roles (event series) in People — `canGrant` refuses them until series exist | People | R2 |
| 10 | E2E "test map" like admin-v2 (`e2e/map/serve.mjs`): the access matrix (plan §3.2) as a page, each cell → its Pest + E2E tests, with Run buttons | Roy: admin-v2 has a good page per item | when the matrix grows (R2) |
| 11 | More journeys: family-page edit, duplicates screen, export download, resend/correct email, check-in (R2) | e2e | with each feature |
| 12 | E2E per breakpoint: a **tablet** project (iPad, both portrait/landscape) alongside the phones, and specs that assert layout per size (no sideways scroll, ≥44/56px targets, menu fits) — not just the same journeys at a different width | Roy 2026-10-15 | before R2 door screens |
| 13 | E2E for link EXPIRY (2 h login / 7 d verify) — needs a controllable clock in the e2e server; Pest covers it today | review 2 | with R2 harness work |
| 14 | ~~Sentry DSN~~ — Roy: keep off for now | Roy 2026-10-16 | later |
| 15 | Photo + consent built (Roy: parent or Lifeshaper, take or upload). **Roy to confirm:** consent wording; production private bucket (PHOTOS_DISK=s3) | Roy 2026-10-16 | before deploy |
| 16 | iPhone HEIC: now converted to JPEG on the phone before upload (`lib/photoForUpload`). Still try once on a real iPhone (camera + Photos + Files) | photo | before deploy |
| 17 | Missing arch guards from plan §10: DataObject, Enum, Migration, MorphMap, PhpStyle, ClassLength, TestCoverage | review 3 (B4) | before R2 freeze |
| 18 | **R1 exit criterion:** access-matrix grid — each R1 row × anonymous, parent-only, staff-only, parent+staff, revoked staff, other-branch staff, other-org user; and response-allowlist tests per actor (door lite view has no photo/health) | plan §3.2, §3.3, §12 | **before Release 1 ships** |
| 19 | TenancyArchTest blind to `DB::table()` / `->from()` / joins on tenant tables; ReachabilityTest matches basenames, not FQCNs | review 3 (B4) | before R2 (check-in adds builder queries) |
| 20 | Two-connection concurrency tests: photo upload vs consent withdrawal; last-admin removal; claim acceptance (with #7) | review 3 | with the R2 concurrency harness |
| 21 | Staging: real photo upload/read/delete against the private S3 bucket; registry import on staging, reconciliation balances | review 3 · plan §12 | before Release 1 ships |
| 22 | Photo housekeeping: a scheduled sweep deleting stored photo files no `children.photo_path` points to (a commit that fails after the file is written leaves one; withdrawal already removes every file of the child, so this is storage tidiness, not privacy) | review 3 (Codex final round, rejected as a defect) | after R1 |
| 23 | **Roy — to go live:** first admin's email (`NXTGEN_FIRST_ADMIN_EMAIL`); Laravel Cloud app + MySQL + PRIVATE bucket + domain; mail provider key + sending domain; the Registry tab exported as CSV, and the review file checked (with Ate Noemi) | go-live | before deploy |
