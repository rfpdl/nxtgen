# Batch C — classes and Sessions (TODO #35): the plan

Written 2026-11-10 after Batch B. Spec items 14–19 (`spec.md`), split into issues 10–15, built in order, one at a
time, test-first, the same way as Batch B: page checks, address rule, small rules, bar parts, one e2e journey each.

| # | Issue | In plain words |
|---|---|---|
| 10 | A class is weekly or one-time | Add "Christmas party" for one date; weekly classes stay weekly |
| 11 | Open and close check-in | An admin opens a class for today; check-in works only while it is open |
| 12 | The Session's own address | Each opened class gets a short code: `/<code>/checkin`, `/<code>/whos-in` |
| 13 | Serve: "Open now" and "Today" | Serve's first page lists what is open and what is on today |
| 14 | A helper for the day | Someone added for one class only; their access ends when it closes |
| 15 | Offline door | Later, its own planning round |

Note: the Session address (`/<code>/checkin`) is the first staff address without `{satellite}` in front — the spec
asks for it, so the address rule (`AddressRuleTest`) gets one named exception for it.

## Questions for Roy (the spec does not say) — with what Claude would do

1. **The old door page** (`/dubai/serve/door`, where you pick today's class): does it go, now that each open class has
   its own address and Serve lists them? *Claude: yes — Serve's "Open now" replaces it.*
2. **What a helper for the day can do**: only check children in and use Who's in at their class? Or also edit a
   family's details? *Claude: check in, undo, Who's in and the head count — nothing else.*
3. **Reopening the same day**: same code (so a link already shared still works) or a new one? *Claude: the same code.*
4. **A one-time class after its date**: it moves to a "Past" list on Classes (kept for Attendance), or stays in the
   main list? *Claude: a "Past" list.*
5. **"Today" for a Lifeshaper**: do they see today's classes that are not open yet (without the Open button)?
   *Claude: yes, marked "Not open yet", so they know what is coming.*
6. **The bar's dropdown on a class's page**: it lists the satellite's open classes (to jump between doors) — and on
   other pages, satellites as now? *Claude: yes.*
