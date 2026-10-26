# Retro — the Release 1 refactor (2026-10-17 → 19)

First run of the mattpocock chain on NxtGen: grill-with-docs → spec + 12 issues → tdd per issue → two-axis
code-review → commit. 14 commits, 562 files. Pest 310 → 1352, arch tests 31 → 173, e2e 43 → 50.

## What worked

- **Grilling before code.** The domain model came out of questions, not code: Church/Satellite, Level vs Squad,
  Schedule/Session, one login per family. The glossary was used in every later issue and commit.
- **Two-axis review after every batch found real problems every time**, not style nits:
  - guards that could never fail (regex lookbehinds, string literals counted as calls)
  - a MySQL unique key that allowed duplicate mixed Squads
  - a stale form that could delete another person's guardian
  - a form that wiped typed rows on a failed save
  - the phone-search bug
- **Splitting the axes mattered.** Spec reviews caught missing scope and wrong rules. Standards reviews caught weak
  guards and smells. Neither would have found the other's issues.
- **The known-gaps ratchet.** Guards went in first with a list of today's violations, and later batches emptied the
  lists. Every list ended at zero.
- **One agent per issue, one commit per issue.** Each agent started with a clean context, and each commit can be
  undone on its own.

## What didn't

- **Roy found in 5 minutes in the browser what 30+ reviews passed.** Wrong tab placement, no redirect after
  registering, only one guardian, Level/Status outside the card, a Duplicates tab nobody needed. Every review
  checked the code against the spec. Nobody looked at the product as a user, and the spec was the old plan.
- **The tdd skill's "agree the seams with the user" step** only happened for batch 02. After that, agents chose
  their own seams.
- **The orchestrator (Claude) wrote contradictions into issues:**
  - issue 07 asked for something the 03 guard forbids
  - issue 11's Done-when contradicted its What
  - batch 02's finish line was loosened mid-work without asking

  Reviews caught all three, but they should not have been written.
- **Decisions made overnight on Roy's behalf were reworked.** The prop-casing rule (batch 06) was replaced the next
  day (issue 10), and a 70-file change was redone.
- **Agents took risky git actions.** One used `git stash` and dropped the staging; others slipped on `git mv`.
  Nothing was lost, but the briefs had to forbid it.
- **The e2e harness loaded dev assets** whenever `make dev` was running, which made a journey flaky. Fixed in
  issue 10.
- **The chain has holes:** `/to-spec`, `/to-tickets`, `/implement` and `/retro` aren't installed, so those steps
  were improvised.

## Changes for check-in (Release 2)

1. **Look before building.** For new screens, run `/prototype` or a quick paper walk-through with Roy during the
   grilling, before the issues exist.
2. **"Done when" says what a person sees.** Every issue states its result as a user would experience it, not only
   as tests.
3. **A look step before each commit.** After the gates pass, open the screen (Claude drives Chrome and takes a
   screenshot, or Roy clicks through) and check it against step 2.
4. **Seams per issue, agreed up front.** The issue lists its test seams. Roy confirms them in the grilling, and the
   agent brief carries them.
5. **Ask before the night, not during it.** Batch the open decisions and ask them before a long autonomous run.
   Only reversible, low-stakes calls get made on Roy's behalf.
6. **Agent briefs always say:** no `git stash`/`reset`, don't touch the index, and the orchestrator stages and
   commits.
7. **Install or document the missing skills** (`to-spec`, `to-tickets`, `implement`, `retro`) so the chain matches
   the diagram.
