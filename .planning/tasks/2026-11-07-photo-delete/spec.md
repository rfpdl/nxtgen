# Delete a photo for good (TODO #25, SECURITY #19)

Status: done
Decided: Roy 2026-11-06 (overnight list): build "delete this photo for good" — for a parent asking for erasure.
Withdrawing consent only hides a photo (Roy 2026-10-16, SECURITY #19); this removes it.

## Who (Claude, low-stakes, for Roy to confirm)
- **The parent**, on their own family page: "Delete the photo for good", then a second tap. Their own child only.
- **An admin who may edit the family**, on the family page in Admin — for a parent who asks in person.
- **Not a Lifeshaper**: it cannot be undone, so it stays with the parent and admins.

## What happens
- The stored file is deleted and the child no longer points at it; the child's history says who deleted it and
  when ("Amara: photo deleted for good by Janice."). Consent is left as it was: a parent who still agrees can add a
  new photo; one who took consent back is still asked again first.
- The page shows the button whenever a photo is on file — hidden (consent taken back) or shown — so a parent can
  erase a hidden photo too (`has_photo` on the child).
- If the file cannot be deleted right then, nothing points at it any more and the nightly photo sweep (#22) removes
  it.
- The child is locked while it happens, as an upload locks it: an upload and a delete at the same moment run one
  after the other.

## Stories
- story#176 A parent deletes their child's photo for good — hidden or shown — and it is noted (happy).
- story#177 Only their own child's; nothing to delete is said plainly (unhappy).
- story#178 An admin who may edit the family deletes a child's photo for good, in Admin (happy).
- story#179 Not a Lifeshaper, not another satellite's admin, not from Serve (unhappy).
