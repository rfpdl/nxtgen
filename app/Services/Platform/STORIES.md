# Platform service

The platform admin's area (`/platform`, spec item 12): the church's satellites (add one, rename it, change its
address), all its people and every Role anywhere, and its settings (time zone, card fee, Moving-Up date).

> What each Feature is for lives on it as `#[UserStory]`; tests name their `story#N`. This file holds only
> domain knowledge.

## Rules
- **Only the platform admin.** Set at the terminal (`php artisan nxtgen:platform-admin`), never from a screen. For
  anyone else `/platform` is an address nobody has (404).
- The platform admin holds no Role: they are on no satellite's People, yet open any satellite as its admin.
- **A satellite's code never changes**; its address may. An address never has a code's shape, nor a word the app
  uses itself (`SatelliteCode`, `RouteSatellite::RESERVED`).
- The app serves one church (CCF): the platform pages are about the first one in the data.
