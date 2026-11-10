# 10 — A class is weekly or one-time (TODO #35)
Status: done 2026-11-10 (built test-first; reviews below)
Spec item 14. A class is either weekly (a weekday, start and end time) or one-time (one date, start and end time —
True Life, the Christmas party). The end is after the start. A several-day event is several one-time classes. The
demo "Any day" class goes. Each class is at one satellite. Done when: an admin adds "Christmas party" for one date;
it is a class of that date only, and a weekly class still meets every week.

Seams:
- Small rules (job/model unit tests): "does this class meet on this date?" for weekly and one-time; end after start.
- Page checks: Classes add/edit — admin there 200, admin elsewhere 404, Lifeshaper → Serve, stranger → sign in.
- e2e: an admin adds a one-time class; it is offered on its date and not the day after (e2e clock).
