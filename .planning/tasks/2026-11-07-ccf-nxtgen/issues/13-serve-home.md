# 13 — Serve: "Open now" and "Today" (TODO #35)
Status: ready-for-agent (Roy answered 2026-11-10, `../batch-c-plan.md`)
Spec item 17. `/{satellite}/serve` shows "Open now" (Sessions with check-in open, each a link to its door) and
"Today" (today's classes not yet open, each with "Open check-in" for an admin); none → "No class today". The bar's
dropdown switches between open Sessions. Done when: a Lifeshaper sees only open Sessions to go to, an admin can open
one from here.

Seams:
- Bar parts (Vitest): the Serve lists and their empty state; the bar dropdown with open Sessions.
- Page checks: Serve home for admin, Lifeshaper, staff of another satellite, stranger.
- e2e: an admin opens a class from Serve; it moves from "Today" to "Open now".
