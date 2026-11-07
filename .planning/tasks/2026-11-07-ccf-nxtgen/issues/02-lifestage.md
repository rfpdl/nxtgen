# 02 — Lifestage: Single, Married, KSP
Status: done
Spec item 2. `LifestageEnum` (unit test), validated on every family form and the Registry import; no migration —
the app is not live (make fresh). The form defaults from the first parent's relationship until
picked. Done when: a parent registering as Mother sees Married chosen; as Aunt/Uncle sees nothing chosen; "Widowed"
is refused; the import reads "MARRIED" and leaves "n/a" empty. story#184, story#185.
