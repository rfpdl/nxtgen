# 09 — Paging in the query + access-matrix grid

Status: ready-for-agent
Blocked by: 08

## What
- TODO #24: families and children lists page in the database, not in memory (`GetFamiliesJob`, `GetChildrenJob`,
  `ListPage`). Excel export still gets every filtered row.
- TODO #18: the access-matrix grid — every Feature × anonymous, guardian-only, staff-only, guardian+staff, revoked
  staff, other-satellite staff, other-church user — plus response allowlists per actor. The last safety net over
  the whole refactor; closes SECURITY #4, #5.
