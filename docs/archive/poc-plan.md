> **SUPERSEDED 2026-10-14.** This was the first plan, which assumed building on the PoC. Roy chose a fresh build on the aegis stack. See `.planning/tasks/2026-10-14-nxtgen-v1/plan.md`.


# NXTGEN — 2-week plan

**Written:** 2026-10-14 · **Target:** 2026-10-28 (two Sundays inside the window: Oct 18 and Oct 25)
**Source of truth for requirements:** `project.md`
**PoC reviewed:** `~/projects/personal/poc/nxtgen-attendance` (Laravel 12 + Lucid + Inertia/Vue + MySQL, deployed at `nxtgen.poncethelion.com`, steps 1–7 of 9 done, `make check` green, 215 e2e passing)

---

## 1. What the PoC already covers from `project.md`

| `project.md` item | In the PoC? | Notes |
|---|---|---|
| Family that groups several kids | ✅ partly | `families` + `guardians` + `kids`. Family has only `name`, `notes`. Guardian has `name`, `phone`, `relationship` |
| Child: first/last name, nickname, gender, birthdate, allergies, notes | ✅ | `kids` table. Nickname drives search and card. Allergy shows as a badge everywhere |
| Check in to an event | ✅ | "Services" (`church_sessions`) opened/closed by admin; the door screen is search + one-tap check-in, family check-in, undo |
| Check in by scanning | ✅ QR only | jsQR camera scanner. **No barcode support** |
| Check in by searching the name | ✅ | Fuzzy, offline-capable name search (nickname, misspellings, diacritics) |
| Manage children | ✅ partly | Admin roster: list, filter by level, archive/unarchive. **No add/edit screen.** `CreateKidJob` exists but no screen uses it |
| Manage ID cards: lost → print → done | ✅ mostly | Mint cards, print queue (A4 QR sheet), mark printed, replace lost card (old QR stops working). No "review a reported loss" step |
| Manage attendance / events | ✅ partly | Open/close a service, head counts. **No recurrence, no attendance history/report screen** |

## 2. In the PoC but missing from `project.md` (the things you might forget)

Decide keep or drop for each. My default is **keep**, since they are built and tested:

- **Levels and squads.** Level 1–4 by age band (4–5, 6–7, 8–9, 10–12). Level 1 is mixed; levels 2–4 split into boys and girls. Lifeshapers can correct a child's level at the door.
- **Undo check-in.** Fixes a wrong tap.
- **Roll call.** A per-squad headcount in the middle of class that names any child who was checked in but isn't in the room.
- **Release / pickup.** Records which guardian took the child home. An adult who isn't on the guardian list needs an admin to approve with their password. Once recorded, a release can't be changed.
- **Temporary cards.** Loaner cards for a child who forgot theirs, given out and returned at the desk.
- **Door filters + archive.** Filter by gender, level and status (checked in / not / went home / archived). Archived kids can still be found by search, and checking one in un-archives them.
- **Two roles:** Admin (does everything a teacher does, plus managing) and Teacher (= **Lifeshaper**).
- **Large-text, mobile-first teacher UI** (7:1 contrast, 56px buttons) for older volunteers.
- **Stars and rewards.** Built in the PoC. **You said to exclude these, so they get removed.**
- **Open TODOs from the PoC:** offline check-in (was marked HIGH), a text-size setting per user, kid photo (TODO #6), level promotion, and one flaky e2e test.

## 3. New work (not in the PoC)

| # | Item | Size |
|---|---|---|
| N1 | **Multi-tenancy under Organization.** `organizations` table; `organization_id` on every tenant table; a global scope plus a trait; users belong to an org; unique keys (card serial, QR token, one attendance per child per event) scoped per org; cross-tenant leak tests | **L** |
| N2 | **Expanded family fields:** email, area of residence, FB account, add-to-FB/parent-chat Y/N, DGroup member? / DGroup area / DGroup leader name / wants to join DGroup, attending 3pm or 7pm | M |
| N3 | **Parents as structured guardians:** father and mother each have first/last name, phone and ministries. Keep the `guardians` table (with a `relationship` of father/mother/other) so release and single-parent families still work | M |
| N4 | **Expanded child fields:** sickness/illness, dislikes, afraid of, parent notes, **picture** | M (S without the picture) |
| N5 | **Admin: manage families and children.** Create, edit, view a family and its kids | M |
| N6 | **Parent registration** (PoC step 9). A public form per org (reached by link or QR) that creates the family and kids, flags possible duplicates, and queues a card for printing | M |
| N7 | **Events with recurrence.** Recurring events (e.g. every Sunday at 3pm and 7pm) that generate occurrences; one-off events; open/close stays | M |
| N8 | **Attendance management.** Attendance per event, per-child history, edits, CSV export | M |
| N9 | **Barcode scanning.** Read the existing barcode cards (Code128/EAN, whatever they turn out to be) as well as QR, and import the existing barcode numbers onto cards | S–M |
| N10 | **Lost-card review flow.** Lifeshaper reports lost → admin reviews → marked as a print task → done | S (mostly built) |
| N11 | **Child observations.** A Lifeshaper records an observation about a child at an event; shown on the child's profile | S–M |
| N12 | **Manage users and Lifeshapers.** Create/invite, set role, deactivate, reset password. Lifeshaper schedule is **stretch** | M |
| N13 | **Real data import** from the current spreadsheet or form, plus a deploy | S–M |

## 4. Approach

**Start from the PoC code. Don't rebuild.** Copy the PoC repo into `current/nxtgen` as the starting point. The PoC shipped steps 1–7 in about four days, so its architecture and test suite are worth reusing.

- This is a **new deployment with a fresh database**, so the PoC's `create_*` migrations can be edited this one time to add `organization_id` and drop stars, instead of stacking `add_*` migrations. Once the new app is deployed, migrations become immutable again.
- **Rename only in the UI.** Teacher → Lifeshaper, Service → Event. Renaming classes too (`Teacher` service, `ChurchSession`) is a lot of churn for no benefit to users. Do it later or not at all.
- **Tenancy goes in second (Day 2–3), before any new tables.** Every table added afterwards gets `organization_id` from the start, and nothing has to be retrofitted.

## 5. Schedule (10 working days)

| Day | Date | Work | Done when |
|---|---|---|---|
| 1 | Wed 14 – Thu 15 | Copy the PoC, remove stars (migration, enum, domain, screens, tests, nav), rename labels to Lifeshaper/Event, `make check` green | App runs with no star traces; gate green |
| 2–3 | Thu 15 – Fri 16 | **N1 multi-tenancy.** Organizations, scoping, org-aware unique keys, org middleware, leak tests (user in org A cannot read or write org B by URL or id) | Two seeded orgs fully isolated, proven by tests |
| — | **Sun 18** | Use the deployed PoC at church as-is and collect feedback | |
| 4–5 | Mon 19 – Tue 20 | **N2, N3, N4 (without picture), N5.** Schema + admin family/child create/edit/view | Admin can register a whole family with both parents and 3 kids |
| 6 | Wed 21 | **N6 parent registration** (public form + duplicate check + card queued) | A parent registers on a phone and the card appears in the print queue |
| 7 | Thu 22 | **N7 recurring events + N8 attendance** history/export | Weekly 3pm/7pm events exist; attendance per event and per child is viewable and exportable |
| 8 | Fri 23 | **N9 barcode + N10 lost-card review**, **N13 import real data**, deploy to production (Lifeshaper accounts seeded by command for now) | An old barcode card checks a child in on the live URL with the real roster |
| — | **Sun 25** | **Dry run at church** on the new app, with paper as a fallback | Real check-ins recorded |
| 9 | Mon 26 | **N12 users/Lifeshapers + N11 observations** | Admin creates a Lifeshaper; an observation is saved and visible on the child |
| 10 | Tue 27 – Wed 28 | Buffer: fixes from the dry run, kid picture (N4) if there's time, docs | Handover |

**If it slips, cut in this order:** Lifeshaper schedule → kid picture → event recurrence (create events by hand instead) → CSV export → observations. **Don't cut:** tenancy, the family/child forms, barcode check-in. Those are the core of `project.md`.

**Out of scope for these two weeks:** offline check-in (PoC TODO #1), Lifeshaper scheduling, SMS/WhatsApp, a parent app.

## 6. Questions I need answered (some block Day 2)

1. **What is an Organization?** A church, a campus, or a ministry? Can one user belong to more than one org? Pick the org by subdomain or by a switcher after login? Who creates orgs (only you, as super-admin)? **Blocks Day 2.**
2. **Barcodes.** What format are the current cards (send a photo of one)? Is the number on the card the same number as in your records? Is there a spreadsheet of child ↔ barcode number? **Blocks Day 8.**
3. **Real data.** Where does the current roster live (Google Form / Sheet)? Its columns decide the import. **Blocks Day 9.**
4. **Keep the PoC extras?** Roll call, release/pickup, temp cards, levels/squads. Default: keep all.
5. **Observations.** Free text, or categories (behaviour, participation, spiritual, concern)? Who can see them: all Lifeshapers or admins only?
6. **Pictures and medical info.** Does registration need parent consent? Who can see the photo and the illness/fears fields?
7. **"Attending 3pm/7pm".** Is that the kids' event time (so check-in defaults to it) or the parents' adult service?
8. **Deploy.** Same Laravel Cloud and `nxtgen.poncethelion.com`, or a new domain per org?
