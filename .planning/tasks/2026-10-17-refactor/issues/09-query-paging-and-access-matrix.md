# 09 — Paging in the query + access-matrix grid

Status: resolved
Blocked by: 08

## What
- TODO #24: families and children lists page in the database, not in memory (`GetFamiliesJob`, `GetChildrenJob`,
  `ListPage`). Excel export still gets every filtered row.
- TODO #18: the access-matrix grid — every Feature × anonymous, guardian-only, staff-only, guardian+staff, revoked
  staff, other-satellite staff, other-church user — plus response allowlists per actor. The last safety net over
  the whole refactor; closes SECURITY #4, #5.

## Comments

**2026-10-17 — done (Claude).** Not committed; awaiting Roy.

**Paging in the query (TODO #24).** `ListPage::of(Builder, page)` runs one `count`, clamps the page to 1..last
(same out-of-range behaviour as before), then fetches only that page with `forPage` (limit/offset) — so eager loads
(`primaryGuardian.user`, `homeSatellite`, `children`; `family.homeSatellite`) run for 50 rows, not the whole church.
`GetFamiliesJob` / `GetChildrenJob` take `?int $page` and return a `ListPage`; `page: null` = `ListPage::all()`, every
filtered row, which `ExportFamiliesFeature` uses (`CreateFamiliesSpreadsheetJob` now takes `iterable<Family>`). Same
ordering, filters and `PaginationData` props. Tests: `tests/Feature/Support/ListPageTest.php` (moved from Unit: it needs
the database; asserts exactly two queries, the second with `limit 50 offset 50`), a page-2 test in each Job test, and a
feature test that the download holds all 55 families while the screen shows 50.

**Access-matrix grid (TODO #18, SECURITY #4).** `tests/Feature/AccessMatrixTest.php`: 30 routes (every route served by
the Church and Family services, read from the router) × 12 actors (`tests/Support/Actor.php`) = 360 cells, plus 3 guards:
the router's route list must equal the grid's rows; each route's Feature (read from its controller action) must declare
the `AccessRowEnum` the grid names; 12 actors per route. The world is `tests/Support/AccessWorld.php`. Every cell sends a
valid payload, so a 404 is the access rule, not validation; `ok` for a change needs a success flash. Each describe/it
carries the `@story#N` tokens its cells prove. Revert-checked: making `canSeeFamily` always true fails 11 cells.

**Response allowlists (SECURITY #5).** `tests/Feature/ResponseAllowlistTest.php` (37 cases): the exact prop key paths
per actor for the door search, the staff family view (editor vs Lifeshaper — no `details`/`primary`/`satellites`, masked
email), the families and children lists, duplicates, People and the parent's page; list and search pages must carry no
photo, health, phone or notes field. A new field on any of these pages fails until added to its list.

**Bugs found:** none — every cell matched plan §3.2 / AccessScope on the first run. Two observations, not bugs:
(1) Input validation runs before a Feature's scope check, so a refused actor sending an INVALID payload gets a redirect
with validation errors instead of a 404 — the same answer for an id that does not exist (checked), so nothing is
revealed; the grid sends valid payloads. (2) `RequireAssignment` shares `churchNav`
through `Inertia::share`, which is process-wide — harmless per request today, but would leak between requests under a
long-lived worker (Octane).

**2026-10-17 — code-review fixes (Claude).**
- Auth + Public routes: `tests/Feature/OpenRouteAccessTest.php`, all 16 (from the router) × anonymous / parent / church
  admin / other-church admin = 64 cells, real emailed links. None excluded.
- Cross-church id guessing: the other church's admin (also a parent there) tries all 16 id-carrying ccf routes from
  `/other/...` — all 404.
- Thicker actors (now 15, 450 cells): a Lifeshaper whose own family is at their satellite; a revoked satellite admin; a
  revoked Lifeshaper who is still a parent (parent pages work, staff pages 404).
- Allowlists: away staff see only Away rows on the children list and in the Excel file; the Excel header is pinned.
- `churchNav` moved from `Inertia::share` in RequireAssignment (process-wide) to a lazy prop in HandleInertiaRequests
  read from the request's AccessScope — null on non-staff pages. `SharedPropsTest` proves a parent request after a
  staff request in the same app gets `churchNav: null` (it failed first).
- Kept on purpose: the 30 literal `it` titles (StoryTokenTest counts a story only in a literal title; the closure is
  now one shared `cellTest()`); `featureOf()` reads the controller method, because Lucid routes name the method and
  only its `serve()` names the Feature; `ListPage::all()` for the export, so each list Job has one return type.
- **For Roy (TODO #28):** resend follows plan §3.2 literally — an away Lifeshaper may resend, an away satellite admin
  gets 404. Confirm with Roy.
