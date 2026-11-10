# 11 — An admin opens and closes a Session's check-in (TODO #35)
Status: ready-for-agent (Roy answered 2026-11-10, `../batch-c-plan.md`)
Spec item 15 (reverses DECISIONS 2026-10-26 "never opened or closed"). A Session is one class on one date. An admin
(the satellite's, or that class's) opens its check-in any time on that date, never another day; closes it; reopens
it the same day. It is closed at midnight, satellite time, with no job needed (a past date is closed). A Child is
checked in only to an open Session. Attendance lists every Session that was opened, 0 children included. Done when:
check-in is refused before opening and after closing, and an opened Session with nobody shows 0 on Attendance.

Seams:
- Small rules (unit): open only on its date; closed after midnight satellite time; check-in refused unless open;
  reopen same day only.
- Page checks: open/close — satellite admin, class admin of that class, class admin of another class, Lifeshaper,
  stranger.
- e2e: an admin opens 1PM, a Lifeshaper checks a child in, the admin closes it, the next check-in is refused.
