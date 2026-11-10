# 14 — A helper for the day (TODO #35)
Status: ready-for-agent (Roy answered 2026-11-10, `../batch-c-plan.md`)
Spec item 18. An admin adds a person (name, email) for one Session only; they sign in with the emailed link; they
may do anything a Lifeshaper may in Serve at that satellite (Roy 2026-11-10: "no need to do RBAC now") until the
Session closes. Done when: the helper checks children in at that Session and, once it closes,
finds nothing.

Roy 2026-11-10 (DECISIONS "before building"): the helper may do anything in Serve at that satellite — every open
Session's door, Families, registering — never Admin; the admin adds them **on People** (a choice beside Admin and
Lifeshaper, for one of today's classes).

Seams:
- Small rules (unit): a helper's access is Serve at that satellite while their Session is open, and nothing else.
- Page checks: the helper at their Session's door 200, Serve's Families 200; Admin → 404 (or Serve); another
  satellite → 404; after close → 404.
- e2e: an admin adds a helper, the helper signs in from the email and checks a child in; after close, 404.
