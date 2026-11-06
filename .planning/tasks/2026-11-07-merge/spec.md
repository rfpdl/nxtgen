# Merge two families, or two children (TODO #29) — spec

Status: needs-info
Asked: Roy 2026-11-06 ("Merge families spec (#29)" — the spec only, no building). Plan §13/§12: merge was deferred;
"Registration proceeds; a possible duplicate is created for staff review — never auto-merged."

## Why
The same family or child gets into the app twice: a parent registers again (new email, typo), a Lifeshaper registers a
visitor who was already there, the Registry import brought a family in that later registered itself. Today an admin can
only answer a possible duplicate "Same / Not the same" — "Same" (Confirmed) just flags it "for the later merge".

## Words
- **Merge**: two records become one. The one kept is the **survivor**; the other is **merged into** it and is gone from
  every list, search and the door (kept in the database, marked merged, for the record and an undo).
- **Family merge**: everything of family B moves to family A. **Child merge**: child B (a second record of the same
  child) moves into child A — usually inside one family after a family merge, or across two families.

## Who (proposal)
- A **church admin** — anywhere in the church. A merge can touch two satellites, and it is hard to undo.
- **Not** a satellite admin, not a Lifeshaper, not a parent. (Question 1.)

## Where it starts
- From a **Confirmed** possible duplicate on the family page in Admin: "Merge these two" — and from the families list
  (tick two, "Merge"), for duplicates nobody flagged. (Question 2.)
- A **preview first**: side by side, what the survivor will look like, what moves, what clashes (below), then Merge.

## What moves — a family merge (B into A)
| Record | What happens |
|---|---|
| Children | All of B's move to A. Two that are the same child: offered for a child merge in the same preview (Q3) |
| Guardians | B's guardians join A. The same person on both (same login, or same name + phone): kept once |
| The main parent (primary guardian) | A's stays, unless the admin picks B's (Q4) |
| A parent's login (User) | Untouched: a login is a person. If B's parent signs in, they now see family A |
| Family details (area, DGroup, notes, Facebook…) | A's kept; where A's is empty, B's fills it; the admin can pick per field in the preview |
| Home satellite | A's, unless the admin picks B's |
| Notes / history | All of B's move to A, plus a system note: "Merged with the Cruz family (registered 2026-10-20) by Grace." |
| Possible duplicates | B's pairs move to A; a pair of A with B is closed as merged; a pair that becomes A–A is dropped |
| Pending claims (FamilyClaim) | A claim waiting on B is moved to A |
| Registry mappings | Re-pointed to A (and its children's), so a later import still finds them |

## What moves — a child merge (child B into child A)
| Record | What happens |
|---|---|
| Check-ins | B's move to A. **Clash**: both checked in to the same Session (unique session + child) — keep the earlier, record the other in the note (Q5) |
| Card requests | B's move to A. **Clash**: only one OPEN request per child (database rule) — keep the newer open one, close the other as Refused "merged" |
| Moving-Up rows | B's move to A; a put-back still finds them |
| Photo + consent | A's photo kept; if A has none and B has one with consent, B's comes over. The other file is deleted for good |
| Level, status, details (allergies, illnesses, fears…) | A's kept; empty fields filled from B; health fields **joined, never dropped** (both allergies kept) — Q6 |
| Name card | The survivor keeps its name card key; a card printed for B no longer scans (says "see the family") — Q7 |
| Notes | Moved, plus "Merged with a second record of Amara by Grace." |

## Safety
- One transaction; both families (and their children) **locked**, in id order, so nothing checks in or edits them
  half-way (the same lock rules as Moving-Up and photos).
- Another church's records can never be merged (ids looked up in this church only).
- **Undo**: the merged record is kept and marked merged (who, when, into whom), with the moves recorded, so the last
  merge of a family can be undone for a while (Q8).
- The access matrix gets the new rows; Astra reviews before it ships.

## Questions for Roy
1. Who may merge: **church admins only** (proposal), or a satellite admin for two families of their own satellite?
2. Start only from a "Same" possible duplicate, or also by picking any two families from the list?
3. In a family merge, offer the child merge for two children with the same name + birthday right there (proposal), or
   leave children to a separate step?
4. Which main parent: always the surviving family's, or the admin picks?
5. A child checked in twice for the same class that day (once on each record): keep one and note it (proposal)?
6. Health details: join both (proposal: "Peanuts" + "Shellfish" both kept), or the admin picks?
7. A name card printed for the record that goes away: stop it scanning and print a new one (a Card request, free), or
   keep both cards working?
8. Undo: needed? For how long — until the next merge, a week, always?

## Not now
Merging two logins (users) — a person with two emails. Merging across churches.
