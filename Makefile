SHELL := /bin/bash

.PHONY: help up down fresh reseed seed migrate migrate-check \
        pint analyse test test-parallel assert-parallel-grant test-pure test-arch \
        test-fe lint format format-check typecheck discipline build \
        check e2e e2e-ui dev coverage coverage-fe

# MySQL runs in a container; PHP and node run on the host. There is no app container, unlike palo:
# production is Laravel Cloud, which supplies its own runtime, so a local PHP container would only be
# a second toolchain to keep in step with the one Cloud actually uses.
DC = docker compose

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-16s\033[0m %s\n", $$1, $$2}'

# ─── Containers ───────────────────────────────────────────────────────────────

# Waits on the CONTAINERS' OWN healthchecks rather than polling a client command.
#
# This target still said `pg_isready -U nxtgen` on a container called `nxtgen-pgsql` long after the
# project moved to MySQL — so it could only ever time out, and nobody noticed because `docker compose
# up -d` on the line above had already done the useful part. Asking compose to wait means this cannot
# go stale again when a service is added: Redis arrived and needed no change here.
up: ## Start MySQL and Redis, and wait for both to be healthy
	$(DC) up -d --wait
	@echo '  mysql and redis ready'

down: ## Stop MySQL and Redis
	$(DC) down

# Override the port with `make dev PORT=3020`. Keep APP_URL in .env in step with it — Ziggy and
# Inertia both build absolute URLs from it, so a mismatch shows up as a redirect to the wrong port
# after sign-in rather than as anything that looks like a config problem.
PORT ?= 3020

dev: up ## Start MySQL/Redis, Vite and artisan serve (make dev PORT=3020)
	@npx concurrently -k -n vite,php -c "cyan,magenta" \
		"npm run dev" \
		"php artisan serve --port=$(PORT)"

# ─── Database ─────────────────────────────────────────────────────────────────

migrate: ## Run migrations
	php artisan migrate

seed: ## Seed the demo church, satellites and sign-in accounts
	php artisan db:seed



# The everyday one. Rebuilds the DATA without touching the schema, so it does not sign anybody out
# and does not need the migrations to have changed. Reach for `fresh` only when a migration has.
reseed: ## Wipe and reseed the data, keeping the schema (and your login)
	php artisan db:wipe --quiet && php artisan migrate --quiet && php artisan db:seed

# DESTRUCTIVE, and deliberately NOT part of `make check`.
#
# It was part of the gate in palo, briefly, and it silently destroyed a session the operator had spent
# an afternoon building by hand — everything persisted correctly and the gate threw it away. A quality
# gate must never touch working data.
fresh: ## DESTRUCTIVE — drop the working database, migrate and reseed
	php artisan migrate:fresh --seed

# Proves migrations run clean from zero WITHOUT touching the working database. Once the first Laravel
# Cloud deploy has happened, a migration that has run can never be edited (plan §10b) — so a schema
# change is a dated `add_*_to_*_table` migration, and this is what catches one that does not apply.
migrate-check: ## Verify migrations run clean from zero, against the test database
	php artisan migrate:fresh --force --env=testing

# ─── PHP ──────────────────────────────────────────────────────────────────────

pint: ## Format PHP
	./vendor/bin/pint

analyse: ## Static analysis (larastan, level 10)
	./vendor/bin/phpstan analyse --memory-limit=512M

test: ## Run the full Pest suite
	./vendor/bin/pest

test-parallel: ## Run the Pest suite across all cores
	@$(MAKE) --no-print-directory assert-parallel-grant
	php artisan test --parallel

# See bin/assert-parallel-grant.php for why this exists and why it is PHP rather than a mysql client.
assert-parallel-grant:
	@php bin/assert-parallel-grant.php

# No database, no booted application — these should finish in milliseconds. If this suite ever slows
# down, something in it has reached for the framework.
test-pure: ## Run only the pure unit tests
	./vendor/bin/pest --testsuite=Pure

# Line coverage. Deliberately OUTSIDE `check`: it needs the pcov extension, which not every machine
# has, and a missing driver would fail the gate for a reason unrelated to the code.
#
# `pcov.enabled=1` per run rather than in php.ini, because pcov costs memory even idle — enabled
# globally it pushed a PHPStan worker past its limit, which is a confusing way to find out.
#
# `memory_limit=1G` because coverage holds a map of every executed line for the whole suite, and at
# 526 tests that passed PHP's 128M default. It failed as "Allowed memory size exhausted" inside
# Pest's own temp file, which looks like a Pest bug and is not one.
#
# Worth running when the question is "what has never been EXECUTED", which is different from "do the
# tests pass". Asked once, it found seven Jobs at 0%, three dead enum methods that read like a
# frontend contract, and a token column that was silently case-insensitive.
coverage: ## Report line coverage, PHP and frontend
	php -d pcov.enabled=1 -d memory_limit=1G ./vendor/bin/pest --coverage --min=90
	@$(MAKE) --no-print-directory coverage-fe

# The frontend half, added 2026-09-10, because `coverage` had only ever meant PHP — so 517 Vitest
# tests had never been asked the same question. The first answer: `useQrScanner`'s entire frame loop
# at 0%, dedupe included, which is the thing standing between one card and sixty check-in requests.
#
# **No `--min`.** Pages sit at 0% on purpose (Playwright owns them), so a floor here would either be
# permanently red or push unit tests onto the wrong layer. The thresholds and the reasoning live in
# `vitest.config.ts`. Read this one; gate on the PHP number above.
#
# **Do not install the provider with `npm i -D`, and do not reach for `--legacy-peer-deps`.**
#
# npm 11.19.0 on this machine crashes in its own tree builder on any ADD — "Cannot read properties of
# null (reading 'children')", from `@npmcli/arborist`'s PlaceDep. `--legacy-peer-deps` gets past it
# and then quietly prunes every peer dependency: it dropped `@vue/devtools-api`, which `pinia` needs
# as a peer, and `make check` began failing in `build` with a Rolldown resolve error naming pinia —
# nothing about the install that caused it.
#
# What works, because npm's RECONCILE path is fine and only the add path is broken:
#
#     edit package.json by hand, then `npm install`
#
# Pin the provider to vitest's own major (`^4` today) or it refuses to load.
coverage-fe: ## Report frontend line coverage
	npx vitest run --coverage

# Structural guards for the PHP side, the counterpart to the frontend's component-discipline test.
# The suite was EMPTY for five build steps while this target existed and `check` did not call it —
# a promise the project made and never kept. Both were fixed after a bug the first guard would have
# caught shipped and survived three steps (see FlashContractTest).
test-arch: ## Run only the architecture guards
	./vendor/bin/pest --testsuite=Architecture

# ─── Frontend ─────────────────────────────────────────────────────────────────

# `set -o pipefail` is not optional. A pipeline's exit status is its LAST command's, and `tee` always
# succeeds — so `npx vitest run | tee` reports success with tests failing, and the gate goes green
# over them. Palo shipped exactly that bug for an afternoon, as the fix for a different one.
# NODE_OPTIONS silences Node 26's "localStorage is not available" notice, which it prints once per
# worker and which is actively misleading here: resources/js/tests/setup.ts installs a real
# in-memory Storage, precisely because happy-dom on this Node does not.
test-fe: ## Run Vitest component tests, and prove every file was collected
	@set -o pipefail; NODE_OPTIONS=--disable-warning=ExperimentalWarning npx vitest run 2>&1 | tee /tmp/nxtgen-vitest.log
	@disk=$$(find resources/js -name '*.test.ts' | wc -l | tr -d ' '); \
	ran=$$(sed 's/\x1b\[[0-9;]*m//g' /tmp/nxtgen-vitest.log | grep -oE 'Test Files.*\(([0-9]+)\)' | grep -oE '\(([0-9]+)\)' | tr -d '()' | tail -1); \
	if [ "$$disk" != "$$ran" ]; then \
		echo ""; \
		echo "  Vitest collected $$ran test files; $$disk exist on disk."; \
		echo "  Nothing is failing — those files did not run at all, which is worse,"; \
		echo "  because a suite that goes quiet reports success."; \
		echo ""; \
		exit 1; \
	fi

lint: ## Lint the frontend
	npx eslint resources/js

format: ## Format the frontend
	npm run format

format-check: ## Check frontend formatting
	npm run format:check

typecheck: ## Typecheck the frontend
	npx vue-tsc --noEmit

discipline: ## Atomic-design structural gate only
	npx vitest run resources/js/tests/component-discipline.test.ts

build: ## Build production assets
	npm run build

# ─── The gate ─────────────────────────────────────────────────────────────────

check: pint analyse test-arch test-parallel test-fe lint format-check typecheck build migrate-check ## Everything. Must be green before any task is done. Never touches the working database.
	@echo ""
	@echo "  All green."
	@echo ""

# Deliberately outside `check`: it needs browsers and a server, and it runs against its own database
# on its own port so a run never touches the working data.
e2e: ## Playwright — mobile WebKit, mobile Chromium and desktop Chromium
	npx playwright test

e2e-ui: ## Playwright, interactive
	npx playwright test --ui
