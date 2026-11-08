import type { SharedProps } from '@/types'
import { usePage } from '@inertiajs/vue3'
import { computed, type ComputedRef } from 'vue'

type Satellite = App.Domains.Church.DataObjects.SatelliteData

/**
 * The satellite the page is about — the shared `satellite` prop (HandleInertiaRequests): the one its address names
 * (`/{satellite}/…`, Roy 2026-11-07). Staff pages and registration build their links from its slug. Throws on a page
 * outside a satellite address: a component that needs one there is in the wrong place.
 */
export function useSatellite(): ComputedRef<Satellite> {
    const page = usePage<SharedProps>()

    return computed(() => {
        const satellite = page.props.satellite
        if (satellite === null || satellite === undefined) {
            throw new Error(
                '[useSatellite] this page is not under a satellite address, so there is no `satellite` prop.',
            )
        }
        return satellite
    })
}
