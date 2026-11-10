# 12 — The Session's own address (TODO #35)
Status: done 2026-11-10 (DECISIONS 2026-11-10 "Issue 12 built")
Spec item 16. Opening a Session gives it a code (the same 8-letter kind as a satellite's registration code). The door
is `/<session code>/checkin`, Who's in `/<session code>/whos-in`. A Role at the Session's satellite is needed, else
404. A closed Session's Who's in stays readable. Done when: the door works at the Session's address for staff of
its satellite only.

Seams:
- Address rule (arch): Session addresses carry the code, never an id; every other staff address still starts with
  `{satellite}`.
- Small rules (unit): a code finds its Session; a satellite address is never code-shaped (already true).
- Page checks: door and Who's in — staff of that satellite 200, staff of another 404, stranger → sign in; closed
  Session: Who's in 200, check-in refused.
- e2e: open a Session, follow its address, check a child in, see them on Who's in.
