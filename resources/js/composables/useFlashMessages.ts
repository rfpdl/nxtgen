import { usePage } from '@inertiajs/vue3'
import { readonly, ref, watch, type Ref } from 'vue'

export type FlashMessage = {
    /**
     * A number that changes on every message, even a repeated one.
     *
     * This is the load-bearing part. The banner is keyed on it, so an identical message announces
     * itself again — see the note below about why the text alone cannot be the key.
     */
    id: number
    tone: 'success' | 'error'
    text: string
}

/**
 * The one place `flash.success` and `flash.error` reach a screen.
 *
 * **Neither had ever been rendered.** Twenty Features flash one or the other and the middleware
 * shares both, so every layer of the contract looked right — but nothing in `resources/js` read
 * either key, and no test asked. What that cost, concretely: *"No temporary card is numbered
 * T-0042"* was the temp-card desk's only error and appeared nowhere; a star refused as already given
 * looked identical to one awarded; *"That check-in was undone — check the child in first"* was the
 * roll call's explanation for a button that then did nothing.
 *
 * It is the same failure as the `checkIn` key one layer further out. `FlashContractTest` guards
 * Feature → middleware in both directions and cannot see the third hop, so there is now a guard for
 * middleware → frontend too. This file is what that guard finds.
 *
 * **Watched by OBJECT IDENTITY, not by text**, and that is not a detail. Inertia replaces
 * `page.props` wholesale on every response, so the `flash` object is new each time even when the
 * string inside it is the same. Watching `flash.error` directly would miss the second of two
 * identical messages — check a child in, undo, check them in again, and the third message never
 * fires because "Igo checked in." has not changed. On this app that is the common case, not an edge
 * one: the same sentence about the same child is exactly what a teacher does twice.
 *
 * **A message is cleared by the next request, not by a timer.** Every action on these screens is a
 * request, so the flash from the last one is replaced or nulled by the next — which is the behaviour
 * a timer would be approximating anyway, without a timer's habit of removing an error mid-sentence.
 */
export function useFlashMessages(): {
    message: Readonly<Ref<FlashMessage | null>>
    dismiss: () => void
} {
    const page = usePage<{ flash?: { success?: string | null; error?: string | null } }>()

    const message = ref<FlashMessage | null>(null)
    let nextId = 0

    /*
     * `flash` is optional, and not merely for the sake of the test mocks that found this. An Inertia
     * partial reload sends only the props it was asked for, so a visit with `only: [...]` that omits
     * `flash` hands this an undefined — and the shell is mounted on every screen, so the crash would
     * take the whole page down rather than just losing a message.
     */
    function take(flash?: { success?: string | null; error?: string | null }): void {
        // Error first. A response can carry both — a family tap where one sibling was refused — and
        // the refusal is the one somebody has to act on.
        const tone = flash?.error ? 'error' : 'success'
        const text = flash?.error ?? flash?.success ?? null

        message.value = text === null || text === '' ? null : { id: ++nextId, tone, text }
    }

    take(page.props.flash)

    watch(() => page.props.flash, take)

    function dismiss(): void {
        message.value = null
    }

    return { message: readonly(message) as Readonly<Ref<FlashMessage | null>>, dismiss }
}
