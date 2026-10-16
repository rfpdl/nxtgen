# Console service

Features served from an Artisan command, for the person at the terminal: no signed-in user, no church in the
address, no `AccessScope`. Today: loading the reviewed registry (`nxtgen:registry-apply`).

> What each Feature is for lives on it as `#[UserStory]`; tests name their `story#N`. This file holds only
> domain knowledge.

## Registry import
- Two steps: propose writes a review file (Families, Quarantine, Reconciliation that balances); a person
  approves rows in it; apply loads only the approved rows.
- Apply goes through the same registration path as everyone else, and **emails nobody**. A Family whose email
  belongs to an existing login waits for that login to confirm; a resend gets the parent in.
- It is safe to run twice: a Child whose source key is already mapped is skipped.
- The Level the reviewer wrote is kept, not recalculated.
- Rows that conflict with an earlier import, lost their family key, or fail validation are refused, and the
  command fails rather than dropping them.
