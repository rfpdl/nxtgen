# 01 — Repo housekeeping

Status: resolved
Blocked by: —

## What
- Git tracks `claude.md` and `readme.md` lowercase (macOS hides it; Linux CI won't find `CLAUDE.md`/`README.md`). Rename in git.
- `CLAUDE.md`: keep it short (DECISIONS 2026-10-17) — the existing agent-skill pointers plus a few lines: read
  `GLOSSARY.md`, the definition of good (spec), the gate (`make check`, `make e2e`).
- Archive `plan.md` (root, marked SUPERSEDED, points at a missing `tech-spec.md`) and `project.md` (raw requirements)
  into `docs/archive/`; move the requirements PDF to `docs/`. Fix links (STATE, README, plan).
- Drop the PoC "client-side ranker" comment in `phpunit.xml`.
- TODO: add #26 Storybook (later); mark #1 superseded by issue 05.

## Done when
`git ls-files | grep -i -E 'claude.md|readme.md'` shows the upper-case names; no dangling links to moved files;
`make check` green.
