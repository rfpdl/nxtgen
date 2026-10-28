import { router, usePoll } from '@inertiajs/vue3'
import { onBeforeUnmount, reactive } from 'vue'
import { route } from 'ziggy-js'

/** How often Who's in asks for the list again, so children checked in at the door appear (DECISIONS 2026-10-28). */
export const ROLL_CALL_POLL_MS = 20_000

/** What Inertia hands a visit so it can be cancelled. */
type Cancellable = { cancel: () => void }

/**
 * Who's in's taps and refresh (roll call, DECISIONS 2026-10-28).
 *
 * - A tap shows at once (`pending`, by Check-in id) and goes to the server one after another, in the order made, so
 *   a quick tap on a second child neither cancels the first nor lets an older answer overwrite a newer one.
 * - The refresh (every 20 s, `squads` only) is skipped while a tap is waiting, and a refresh already on its way is
 *   cancelled when a tap starts: it read the list before the tap and would undo the tick until the next one.
 * - `stop()` drops the taps not yet sent — when the page is left, or before a chip moves to another class or Squad,
 *   so a queued tap never fires from the next page.
 */
export function useRollCall(slug: () => string) {
    const pending = reactive<Record<number, boolean>>({})
    const waiting = new Map<number, number>()
    let queue: Promise<void> = Promise.resolve()
    let round = 0
    let refreshing: Cancellable | null = null

    usePoll(ROLL_CALL_POLL_MS, () => ({
        only: ['squads', 'squad_id'],
        onBefore: () => waiting.size === 0,
        onCancelToken: (token: Cancellable) => {
            refreshing = token
        },
        onFinish: () => {
            refreshing = null
        },
    }))

    function settle(checkInId: number): void {
        const left = (waiting.get(checkInId) ?? 1) - 1
        if (left > 0) {
            waiting.set(checkInId, left)
            return
        }
        waiting.delete(checkInId)
        delete pending[checkInId]
    }

    function send(checkInId: number, present: boolean): Promise<void> {
        return new Promise<void>((resolve) => {
            router.put(
                route('church.door.present', [slug(), checkInId]),
                { present },
                {
                    preserveScroll: true,
                    onFinish: () => {
                        settle(checkInId)
                        resolve()
                    },
                },
            )
        })
    }

    function mark(checkInId: number, present: boolean): void {
        pending[checkInId] = present
        waiting.set(checkInId, (waiting.get(checkInId) ?? 0) + 1)
        refreshing?.cancel()
        const ours = round
        queue = queue.then(() => (ours === round ? send(checkInId, present) : undefined))
    }

    function stop(): void {
        round++
        waiting.clear()
        for (const id of Object.keys(pending)) {
            delete pending[Number(id)]
        }
    }

    onBeforeUnmount(stop)

    return { pending, mark, stop }
}
