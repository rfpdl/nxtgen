# 14 — A helper for the day (TODO #35)
Status: needs-info — questions for Roy in `../batch-c-plan.md`
Spec item 18. An admin adds a person (name, email) for one Session only; they sign in with the emailed link; their
access ends when the Session closes. Done when: the helper checks children in at that Session and, once it closes,
finds nothing.

Seams:
- Small rules (unit): a helper's access is that Session while it is open, and nothing else.
- Page checks: the helper at their Session's door 200; another Session, Admin, Families → 404; after close → 404.
- e2e: an admin adds a helper, the helper signs in from the email and checks a child in; after close, 404.
