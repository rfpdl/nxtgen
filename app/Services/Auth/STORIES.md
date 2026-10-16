# Auth service

One sign-in for everyone (parents and Staff), by emailed link; Staff may also keep a backup password. After
sign-in, one place decides where the person lands.

> What each Feature is for lives on it as `#[UserStory]`; tests name their `story#N`. This file holds only
> domain knowledge.

## Links
- Links are emailed per purpose: sign in (2 hours), confirm email (7 days), claim an imported Family. The address
  says which (`/magic/login|verify|claim/{token}`).
- **Opening a link uses nothing.** It shows a Continue page; only the button (POST) uses the link, so an email
  scanner cannot spend it or accept a claim.
- A link is single use, and dies when the account email or credential version changes, or (claims) when the
  claim moves on or is revoked. Only a hash of the token is stored.
- **No account oracle.** A known email gets a sign-in link, an unknown one a "register here" note; the screen
  says the same thing for both. At most 5 link emails per address per 10 minutes.
- A claim is refused if the login already belongs to another Family in that Church.

## Passwords
- Only for someone who serves somewhere right now. A parent never signs in with a password, even one who
  once served and still has one. One answer for every failure.

## Landing
- No role picker: one destination goes there, several ask, none says so. Revoked Roles do not count.
- The choice is remembered on the device, and only one of the person's own options is ever accepted.
