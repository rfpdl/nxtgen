# NxtGen

Kids ministry app (Laravel 12 · Lucid · Inertia + Vue 3/TS), modelled on aegis. Start at `.planning/STATE.md`.

## Rules

- Use the words in `GLOSSARY.md` — in code, tests, issues and copy.
- Lucid: Route → Controller → one `serve()` → Feature → Jobs/Operations → Models. The arch tests in
  `tests/Architecture` are the rulebook; a new guard starts with a known-gaps list that may only shrink.
- Test-first. Every Job and Enum has a unit test, every Feature a feature test (who may and may not), every screen
  an e2e journey.
- Frontend types are generated from PHP (`#[TypeScript]`), snake_case — never hand-written.
- Decisions go in `docs/DECISIONS.md` (newest first, who and why). Security items go in `.planning/SECURITY.md`.
- Gate: `make check` and `make e2e` green before a commit. Commit only when Roy asks.

## Agent skills

### Issue tracker

Local markdown under `.planning/tasks/<feature>/`; the numbered backlog is `.planning/TODO.md`. See `docs/agents/issue-tracker.md`.

### Triage labels

The five default roles, recorded as a `Status:` line in each issue file. See `docs/agents/triage-labels.md`.

### Domain docs

Single-context: `GLOSSARY.md` at the root; decisions in `docs/DECISIONS.md`. See `docs/agents/domain.md`.
