import { existsSync, readFileSync } from 'node:fs'
import { expect } from '@playwright/test'

/** Written by App\Providers\E2EServiceProvider: one JSON line per email the app sent. */
export const MAILBOX = 'storage/app/e2e/mailbox.jsonl'

type Mail = { to: string[]; subject: string; body: string; sentAt: string }

function read(): Mail[] {
    if (!existsSync(MAILBOX)) return []
    return readFileSync(MAILBOX, 'utf8')
        .split('\n')
        .filter((line) => line.trim() !== '')
        .map((line) => JSON.parse(line) as Mail)
}

/**
 * The newest email to `to`, waiting for it to arrive. `after` = how many emails that address had before the
 * action, so a journey never picks up an older link.
 */
export async function latestMail(to: string, after = 0): Promise<Mail> {
    const address = to.toLowerCase()
    let found: Mail | undefined
    await expect
        .poll(
            () => {
                const mine = read().filter((m) => m.to.includes(address))
                found = mine.length > after ? mine[mine.length - 1] : undefined
                return found !== undefined
            },
            { message: `an email to ${address}`, timeout: 10_000 },
        )
        .toBe(true)
    return found!
}

export function mailCount(to: string): number {
    return read().filter((m) => m.to.includes(to.toLowerCase())).length
}

/** The `/magic/...` path inside an email — opened against the test server, whatever host the email names. */
export function magicPath(mail: Mail): string {
    const match = mail.body.match(/\/magic\/[a-z]+\/[A-Za-z0-9]+/)
    if (match === null) throw new Error(`No sign-in link in "${mail.subject}"`)
    return match[0]
}
