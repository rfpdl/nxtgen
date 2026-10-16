import type { Church, SharedProps } from '@/types'
import { usePage } from '@inertiajs/vue3'
import { computed, type ComputedRef } from 'vue'

/**
 * The church the page is about — the shared `church` prop (HandleInertiaRequests), which every page under a
 * church address receives, so no page declares it. Throws on a page outside a church: a component that needs
 * a church there is in the wrong place.
 */
export function useChurch(): ComputedRef<Church> {
    const page = usePage<SharedProps>()

    return computed(() => {
        const church = page.props.church
        if (church === null || church === undefined) {
            throw new Error(
                '[useChurch] this page is not under a church address, so there is no `church` prop.',
            )
        }
        return church
    })
}
