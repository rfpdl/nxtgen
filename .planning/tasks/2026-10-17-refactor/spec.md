# Refactor Release 1 — spec

Status: ready-for-agent
Decided: 2026-10-17 grilling session (Roy) — `docs/DECISIONS.md` 2026-10-17, terms in `GLOSSARY.md`.

## Why

Release 1 works and is well guarded (PHPStan L10, 310 Pest, 106 Vitest, 43 e2e runs), but the code says
Organization/Branch/Group/parent where the church says Church/Satellite/Squad/Guardian, several aegis rules are
missing, test fixtures are copied ~10 times, and frontend types are partly hand-written. Fix that before check-in
builds on it and before any real data exists.

## Definition of good

1. Code uses the words in `GLOSSARY.md`.
2. Lucid layers each do one job (Routes → Features → Jobs/Operations → Models), no pass-throughs.
3. Architecture tests enforce the rules; each new guard starts with a known-gaps list that may only shrink.
4. Frontend types are generated from PHP (DTOs, Inputs, Enums), snake_case.
5. Files are small enough to read in one go.
6. Every Job and Enum has a unit test, every Feature a feature test (access included), every screen an e2e journey.
7. Matches aegis (`~/projects/work/aegis/aegis/core`) except where DECISIONS 2026-10-17 says otherwise.

## Rules for every batch

- Behaviour stays the same, except batch 05 (Levels/Squads), which may change screens.
- A bug found on the way gets its own failing test and its own small fix.
- Test-first (`/tdd`); `make check` + `make e2e` green; `/code-review` against this spec; commit when Roy says.
- Migrations may be edited in place (nothing is deployed); `migrate:fresh` after.

## Batches

| # | Issue | Blocked by |
|---|---|---|
| 01 | Repo housekeeping | — |
| 02 | Test layout + shared test World | 01 |
| 03 | Architecture guards from aegis | 02 |
| 04 | Rename to Church / Satellite / Guardian | 03 |
| 05 | Levels, Squads, Schedules | 04 |
| 06 | Enums, DTOs and generated TS | 05 |
| 07 | Feature clean-up | 06 |
| 08 | Empty the known-gaps lists + user stories | 07 |
| 09 | Paging in the query + access-matrix grid (TODO #24, #18) | 08 |
