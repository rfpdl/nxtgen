# Issue tracker: Local Markdown

Issues and specs for this repo live as markdown files in `.planning/tasks/`, next to the existing plans and reviews.
The numbered backlog is `.planning/TODO.md`.

## Conventions

- One feature per directory: `.planning/tasks/<YYYY-MM-DD>-<feature-slug>/` (e.g. `.planning/tasks/2026-10-14-nxtgen-v1/`)
- The spec is `.planning/tasks/<dir>/spec.md`
- Implementation issues are one file per ticket at `.planning/tasks/<dir>/issues/<NN>-<slug>.md`, numbered from `01`, never a single combined tickets file
- Triage state is recorded as a `Status:` line near the top of each issue file (see `triage-labels.md` for the role strings)
- Comments and conversation history append to the bottom of the file under a `## Comments` heading
- `.planning/TODO.md` numbers are stable — reviews and code comments refer to them. When an item lands there, give it the next free number; never renumber.

## When a skill says "publish to the issue tracker"

Create a new file under `.planning/tasks/<dir>/` (creating the directory if needed).

## When a skill says "fetch the relevant ticket"

Read the file at the referenced path. The user will normally pass the path, the issue number, or a TODO `#` directly.

## Wayfinding operations

Used by `/wayfinder`. The **map** is a file with one **child** file per ticket.

- **Map**: `.planning/tasks/<dir>/map.md` (the Notes / Decisions-so-far / Fog body).
- **Child ticket**: `.planning/tasks/<dir>/issues/NN-<slug>.md`, numbered from `01`, with the question in the body. A `Type:` line records the ticket type (`research`/`prototype`/`grilling`/`task`); a `Status:` line records `claimed`/`resolved`.
- **Blocking**: a `Blocked by: NN, NN` line near the top. A ticket is unblocked when every file it lists is `resolved`.
- **Frontier**: scan `.planning/tasks/<dir>/issues/` for files that are open, unblocked, and unclaimed; first by number wins.
- **Claim**: set `Status: claimed` and save before any work.
- **Resolve**: append the answer under an `## Answer` heading, set `Status: resolved`, then append a context pointer (gist + link) to the map's Decisions-so-far in `map.md`.
