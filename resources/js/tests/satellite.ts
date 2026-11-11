import type { SatelliteData } from '@/types/generated'
type Satellite = SatelliteData

/**
 * The shared `satellite` prop (HandleInertiaRequests) for tests that mount a component reading `useSatellite()`:
 * the satellite the address names (`/{satellite}/…`). Links are built from its `slug`.
 *
 *     vi.mock('@inertiajs/vue3', () => ({ usePage: () => ({ props: { satellite: makeSatellite() } }) }))
 */
export function makeSatellite(overrides: Partial<Satellite> = {}): Satellite {
    return { id: 1, name: 'CCF Dubai', slug: 'dubai', ...overrides }
}
