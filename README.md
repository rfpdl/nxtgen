# NxtGen

Kids church registration and check-in, built for CCF's NxtGen and for any church.

- Plan: `.planning/tasks/2026-10-14-nxtgen-v1/plan.md` (and `spar.md`, the Codex review that shaped it)
- **Start here:** `.planning/STATE.md` (where things stand, next step, where to look)
- Decisions (newest first): `docs/DECISIONS.md`
- Where things stand: `.planning/STATE.md` · Security list: `.planning/SECURITY.md` · TODO: `.planning/TODO.md`

## Run it locally

Needs PHP 8.4+, Composer, Node 22+, Docker (MySQL + Redis only).

```bash
docker compose up -d --wait mysql redis
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed          # CCF, two satellites, squads, admin@nxtgen.test, lifeshaper@nxtgen.test
make dev                            # http://localhost:3020
```

Sign in with a magic link: enter the email at `/login`; the email (with the link) is written to
`storage/logs/laravel.log`. For a real inbox: `docker compose --profile mail up -d`, `MAIL_MAILER=smtp`,
open http://localhost:8026.

## Check

```bash
make check    # pint, larastan level 10, architecture guards, pest, vitest, eslint, vue-tsc, prettier, build,
              # migrations from zero (test DB only — never touches your working data)
```

## Browser tests (E2E)

```bash
make e2e          # every journey at each layout band (sm 640, md 768, lg 1024): phones upright (360, 390 Chrome + Safari) and on their side, tablets 720/768/1024, desktop; layout-checked (own DB, nxtgen_e2e)
make e2e-ui       # watch each journey run, one by one
make e2e-report   # the last run's report: each journey, its steps; screenshot, video and trace for failures
```

They sign in the way people do — by opening the emailed link (copied to `storage/app/e2e/mailbox.jsonl` under
`APP_ENV=e2e` only). First time: `npx playwright install webkit chromium`.

## Starting production

1. In the server's `.env` (start from `.env.example`, which is set up for LOCAL use): `APP_ENV=production`,
   `APP_DEBUG=false`, a real mailer (`MAIL_MAILER` + its keys — the first admin signs in by email link), and the
   church: `NXTGEN_CHURCH_*`, `NXTGEN_SATELLITES` (spelled as the registry sheet spells them), and
   `NXTGEN_FIRST_ADMIN_EMAIL`. Photos: attach a PRIVATE bucket in Laravel Cloud (it fills in `AWS_*`) and set `PHOTOS_DISK=s3`.
2. `php artisan migrate --force && php artisan db:seed --force` — creates that church, its satellites, each
   satellite's squads and the first admin only (never the demo logins). It refuses to run with a log-only mailer. Running it
   again adds anything missing and changes nothing that exists.
3. The first admin signs in at `/login` and adds everyone else on **People** (`/{church}/people`).
4. Families: the registry import, once (`nxtgen:registry-propose` → review → `nxtgen:registry-apply`). It sends
   no emails. If it reports families "waiting for an existing login to confirm" (a volunteer who is also a parent
   in the sheet), open each and use **Send the confirmation link again**.
