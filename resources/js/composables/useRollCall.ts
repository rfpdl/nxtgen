import { router, usePoll } from '@inertiajs/vue3'
import { onBeforeUnmount, reactive } from 'vue'
import { route } from 'ziggy-js'

/** How often Who's in asks for the list again, so children checked in at the door appear (DECISIONS 2026-10-28). */
export const ROLL_CALL_POLL_MS = 20_000

/** What Inertia hands a visit so it can be cancelled. */
type Cancellable = { cancel: () => void }

/**
 * Who's in's taps, head counts and refresh (roll call, DECISIONS 2026-10-28).
 *
 * - A tap shows at once (`pending`, by Check-in id); a head count shows "Confirming…" (`confirming`, by Squad id). Both
 *   go to the server one after another, in the order made, so a quick tap on a second child neither cancels the first
 *   nor lets an older answer overwrite a newer one. A refused count's message is kept by Squad id (`countErrors`).
 * - The refresh (every 20 s, `squads` only) is skipped while anything is waiting, and a refresh already on its way is
 *   cancelled when a tap or a count starts: it read the list before and would undo it until the next one.
 * - `stop()` drops what is not yet sent — when the page is left, or before a chip moves to another class or Squad,
 *   so nothing queued fires from the next page.
 */
export function useRollCall(slug: () => string) {
    const pending = reactive<Record<number, boolean>>({})
    const confirming = reactive<Record<number, boolean>>({})
    const countErrors = reactive<Record<number, string>>({})
    /** How many of each thing are still to be answered: taps by Check-in id, counts by Squad id. */
    const waiting = new Map<number, number>()
    const counting = new Map<number, number>()
    let queue: Promise<void> = Promise.resolve()
    let round = 0
    let refreshing: Cancellable | null = null

    usePoll(ROLL_CALL_POLL_MS, () => ({
        only: ['squads', 'squad_id'],
        onBefore: () => waiting.size === 0 && Object.keys(confirming).length === 0,
        onCancelToken: (token: Cancellable) => {
            refreshing = token
        },
        onFinish: () => {
            refreshing = null
        },
    }))

    /** Send a PUT after everything before it; `done` runs when it is answered (not when it is dropped by stop()). */
    function enqueue(
        url: string,
        data: Record<string, boolean | number>,
        done: () => void,
        more: { errorBag?: string; onError?: (errors: Record<string, string>) => void } = {},
    ): void {
        refreshing?.cancel()
        const ours = round
        queue = queue.then(() =>
            ours !== round
                ? undefined
                : new Promise<void>((resolve) => {
                      router.put(url, data, {
                          preserveScroll: true,
                          ...more,
                          onFinish: () => {
                              done()
                              resolve()
                          },
                      })
                  }),
        )
    }

    /** One more answered for `id`; true when none is left. */
    function answered(left: Map<number, number>, id: number): boolean {
        const now = (left.get(id) ?? 1) - 1
        if (now > 0) {
            left.set(id, now)
            return false
        }
        left.delete(id)
        return true
    }

    /** `revision`: the Check-in's as shown — the server refuses a tap on a Check-in revived since (roll call 05). */
    function mark(checkInId: number, present: boolean, revision: number): void {
        pending[checkInId] = present
        waiting.set(checkInId, (waiting.get(checkInId) ?? 0) + 1)
        enqueue(route('church.door.present', [slug(), checkInId]), { present, revision }, () => {
            if (answered(waiting, checkInId)) delete pending[checkInId]
        })
    }

    function confirm(classId: number, squadId: number, count: number): void {
        confirming[squadId] = true
        counting.set(squadId, (counting.get(squadId) ?? 0) + 1)
        delete countErrors[squadId]
        enqueue(
            route('church.door.head-count', [slug(), classId, squadId]),
            { count },
            () => {
                if (answered(counting, squadId)) delete confirming[squadId]
            },
            {
                errorBag: `head-count-${squadId}`,
                onError: (errors) => {
                    countErrors[squadId] = errors.count ?? 'The head count was not saved.'
                },
            },
        )
    }

    function stop(): void {
        round++
        waiting.clear()
        counting.clear()
        for (const store of [pending, confirming, countErrors]) {
            for (const id of Object.keys(store)) {
                delete store[Number(id)]
            }
        }
    }

    onBeforeUnmount(stop)

    return { pending, confirming, countErrors, mark, confirm, stop }
}
