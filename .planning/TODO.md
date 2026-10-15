# TODO

| # | Item | Why | When |
|---|---|---|---|
| 1 | Moving-Up for a **mixed** group (Level 1 → Level 2 Boys/Girls): `next_group_id` can't express a per-gender next group | design gap found while seeding | before Feb Moving-Up |
| 2 | E2E harness (Playwright projects, `APP_ENV=e2e`, auth setup) | plan §10 | before Release 1 deploy |
| 3 | Laravel Cloud project + env (D2) | Roy owns the account | before Release 1 deploy |
| 4 | ~~D-R1~~ decided: import once, app is the source of truth (plan §7.6) | Roy 2026-10-15 | done |
| 5 | ~~D-R2~~ decided: People screens (built). Still needed: production seeder for the church, satellites and first church admin | Roy 2026-10-15 | before deploy |
| 6 | Remaining R1 build list (review.md "Remaining Release 1 build") | review-spar | before R1 release |
| 7 | Claim-acceptance concurrency test (two DB connections) | review-spar | with R2 concurrency harness |
| 8 | A church slug must never be a reserved path (`RouteOrganization::RESERVED`) — enforce when a create-church screen/command exists | Org rename | with Platform |
| 9 | Class-admin roles (event series) in People — `canGrant` refuses them until series exist | People | R2 |
