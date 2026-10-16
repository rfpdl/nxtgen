# Domain Docs

How the engineering skills should consume this repo's domain documentation when exploring the codebase.

## Before exploring, read these

- **`GLOSSARY.md`** at the repo root.
- **`docs/DECISIONS.md`**: product and technical decisions, newest first, dated, each saying who decided and why. Read the entries that touch the area you're about to work in. This repo uses it **instead of `docs/adr/`** — don't create an ADR directory.
- For the design itself: `.planning/tasks/2026-10-14-nxtgen-v1/plan.md`; security items: `.planning/SECURITY.md`.

If `GLOSSARY.md` doesn't exist, **proceed silently**. Don't flag its absence; don't suggest creating it upfront. The `/domain-modeling` skill creates it lazily when terms actually get resolved.

## File structure

Single-context repo:

```
/
├── GLOSSARY.md            ← created lazily
├── docs/DECISIONS.md      ← decisions (in place of docs/adr/)
├── .planning/
└── app/
```

## Recording a decision

When a skill would write an ADR, add a new entry to `docs/DECISIONS.md` under today's date heading instead (create the heading if needed, newest first). Say what was decided, by whom, and why. A change of mind is a **new entry**, not a silent edit of an old one.

## Use the glossary's vocabulary

When your output names a domain concept (in an issue title, a refactor proposal, a hypothesis, a test name), use the term as defined in `GLOSSARY.md`. Don't drift to synonyms the glossary explicitly avoids.

If the concept you need isn't in the glossary yet, that's a signal: either you're inventing language the project doesn't use (reconsider) or there's a real gap (note it for `/domain-modeling`).

## Flag decision conflicts

If your output contradicts an entry in `docs/DECISIONS.md`, surface it explicitly rather than silently overriding:

> _Contradicts DECISIONS 2026-10-16 (withdrawing consent hides the photo), but worth reopening because…_
