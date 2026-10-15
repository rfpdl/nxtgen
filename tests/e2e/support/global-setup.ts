import { execSync } from 'node:child_process'
import { existsSync, rmSync } from 'node:fs'
import { MAILBOX } from './mailbox'

/**
 * Which database `--env=e2e` really reaches, asked of Laravel itself.
 *
 * Not trusted from `.env.e2e`: when that file is missing, Laravel quietly falls back to `.env` — the
 * WORKING database — and E2EServiceProvider's own check never runs, because APP_ENV is then not e2e.
 * So the wipe below is refused unless Laravel confirms an `*_e2e` database first.
 */
function e2eDatabase(): string {
    if (!existsSync('.env.e2e')) {
        throw new Error(
            '.env.e2e is missing — refusing to run: --env=e2e would fall back to .env (your working data).',
        )
    }
    const out = execSync('php artisan db:show --json --env=e2e', { encoding: 'utf8' })
    const name = (JSON.parse(out) as { platform: { config: { database: string } } }).platform.config
        .database
    if (!name.endsWith('_e2e')) {
        throw new Error(
            `--env=e2e reaches database "${name}", not an *_e2e one — refusing to wipe it.`,
        )
    }
    return name
}

/** A known church before every run: a fresh e2e database, seeded, an empty mailbox and no saved sessions. */
export default function globalSetup(): void {
    e2eDatabase()
    execSync('php artisan migrate:fresh --force --env=e2e', { stdio: 'inherit' })
    execSync('php artisan db:seed --class=E2ESeeder --force --env=e2e', { stdio: 'inherit' })
    rmSync(MAILBOX, { force: true })
    rmSync('tests/e2e/.auth', { recursive: true, force: true })
}
