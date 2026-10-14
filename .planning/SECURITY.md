# Security list

Roy, 2026-10-15: *"we can make a list of security issues but i dont want it to stop us."*
Items here are known and tracked; none blocks a release unless marked **must**.

| # | Item | Status | Where |
|---|---|---|---|
| 1 | Family email correction on a login that is also staff / multi-org: re-point the family + claim link instead of editing the account | v1 refuses with a message; full flow later | plan §4.4 |
| 2 | Magic links bound to email + version, single use (atomic), revoked on email change | **must** (cheap, built in R1) | plan §4.3 |
| 3 | Pending family claims: one per email per org, revision-bound links | built with registration | plan §4.2 |
| 4 | Access matrix tests for every Feature (anonymous, parent, staff, other branch, other org, revoked) | **must** | plan §3.2 |
| 5 | Field allowlists per projection + response tests; no child data in Sentry | **must** for health fields | plan §3.3 |
| 6 | Photo URLs short-lived + authorized; consent withdrawal deletes photo | with photo feature | plan §3.3 |
| 7 | Rate limits: login link per email + IP, registration per IP | R1 | plan §4.3 |
| 8 | Name barcodes cannot be revoked (legacy cards) | accepted until QR in Feb | plan §5 |
| 9 | Admin role-grant ceiling | R1 | plan §3.2 |
