# Public service

What anyone can open without signing in: the welcome page and a church's registration form (`/{church}/register`).
The registration link and the poster's QR code point here, so these addresses must stay stable.

> What each Feature is for lives on it as `#[UserStory]`; tests name their `story#N`. This file holds only
> domain knowledge.

## Rules
- **One registration path.** A parent's own registration, staff registering for a parent and the registry import
  all go through `RegisterFamilyOperation`.
- **No membership oracle.** Every registration outcome gets the same public answer, so the form never tells anyone
  whether an email is already registered. The inbox owner learns the truth from the email.
- A Family's home Satellite must be one of this church's.
- Allergies are an explicit yes or no; "no" keeps no details, whatever was typed.
- A Child's nickname is required: it is the name on their card.
