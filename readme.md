# NxtGen

Kids church registration and check-in, built for CCF's NxtGen and for any church.

- Plan: `.planning/tasks/2026-10-14-nxtgen-v1/plan.md` (and `spar.md`, the Codex review that shaped it)
- Where things stand: `.planning/STATE.md` · Security list: `.planning/SECURITY.md` · TODO: `.planning/TODO.md`

## Run it locally

Needs PHP 8.4+, Composer, Node 22+, Docker (MySQL + Redis only).

```bash
docker compose up -d --wait mysql redis
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed          # CCF, two satellites, groups, admin@nxtgen.test, lifeshaper@nxtgen.test
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
