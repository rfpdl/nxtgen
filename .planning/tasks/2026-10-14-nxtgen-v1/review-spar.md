# Review spar — NxtGen Release 1 (2026-10-15)

Codex as read-only critic. Requested model `gpt-6-astra` on every call; the runner accepted it but never reported
the model used (requested, not verified). Two subjects per round: **A** = `adversarial-review --scope working-tree`
(the diff), **B** = `task` thread (absences, docs vs code, coverage, duplication), given `review.md`'s ledgers.

## Round 1

**A — needs-attention.**
- [high] Import reruns skip mapped rows; plan §7 promised field updates + conflicts. → **Accepted → decision D-R1** (product: is the sheet still edited after loading?).
- [medium] Resend ignores the access matrix. → **Accepted, fixed** (`AccessScope::canResendLink`; revert-check R1).
- [medium] Import recalculates groups instead of keeping the reviewed one. → **Accepted, fixed** (`Group` column; R7).

**B — changes_requested, 9 blocking.**
- B1 groups + rerun updates → groups **fixed**; rerun → D-R1.
- B2 import accounting (short records vanish, success on partial apply, family overcount) → **fixed** (R5, R6).
- B3 "sent" when suppressed; no already-registered context → **fixed** (R2, R3). Unknown-account email should carry a registration link → **Rejected**: `/login` is church-agnostic, there is no church whose link to offer; the email already says to ask a Lifeshaper (`app/Notifications/NoAccountNotification.php`). Codex accepted the rejection in round 2.
- B4 no production church/staff provisioning → **Accepted → decision D-R2**.
- B5 family edit UI / guardian edit / health display, B7 duplicate review + filters + notes, B9 QR poster + password backup + remembered landing → **Accepted, remaining R1 build** (already MISSING in my ledger).
- B6 form parity + allergy y/n → remaining build; staff path dropping fields → **fixed** with one `RegisterFamilyInput::toRegistration()` (R4).
- B8 CI on PoC settings → **fixed**; Playwright journeys → remaining build.
- Non-blocking: consolidate family-contact derivation and church resolution → **fixed** (`Family::contact*`, `RouteOrganization`).

Ruling note: nearly everything was accepted. That reflects the build being incomplete against the plan (I had marked
most of these MISSING myself before Codex saw them), not a soft ruling — the one claim that did not hold up was rejected.

## Round 2

**A — needs-attention.** Approved rows with an empty family key / no Families sheet vanish silently → **fixed**.
Email correction ignores the send result → **fixed**.
**B — changes_requested, 1 blocking:** the same email-correction item → **fixed**. Non-blocking: assert the rendered
already-registered text → **added** (passes; now protects the line).

## Round 3

**A — needs-attention.** [high] Edited review rows are not re-validated: clearing parent emails merges households
under one blank login → **Accepted, fixed** (identity fields re-validated at load; two regression tests).
**B — agree, 0 blocking.**

## Round 4

**A — approve.** "No genuinely new material blocker found." Review converged.

## Revert verification

Every fix in this review was reverted in place and its test run: R1–R7 all fail when reverted. From the earlier
diff review, 8 of 9 fail when reverted; the claim recheck under lock (H) survives — it only guards a concurrent
interleaving a sequential test cannot reach. Logged with a trigger (R2 concurrency harness).
