import type { SatelliteData } from '@/types/generated'
import { useSatellite } from '@/composables/useSatellite'
import { makeSatellite } from '@/tests/satellite'
import { describe, expect, it, vi } from 'vitest'

const page: { props: { satellite: SatelliteData | null } } = {
    props: { satellite: makeSatellite() },
}

vi.mock('@inertiajs/vue3', () => ({ usePage: () => page }))

describe('useSatellite', () => {
    it('gives the satellite the address names', () => {
        expect(useSatellite().value).toEqual({ id: 1, name: 'CCF Dubai', slug: 'dubai' })
    })

    it('fails loudly on a page outside a satellite address', () => {
        page.props.satellite = null

        expect(() => useSatellite().value).toThrow(/not under a satellite address/)

        page.props.satellite = makeSatellite()
    })
})
