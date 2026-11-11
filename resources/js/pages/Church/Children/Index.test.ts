import Index from '@/pages/Church/Children/Index.vue'
import { makeSatellite } from '@/tests/satellite'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

const nav = { can_move_up: false }

vi.mock('@inertiajs/vue3', async (original) => ({
    ...(await import('@/tests/enumPage')).withEnums(await original<object>()),
    Head: { template: '<span />' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    usePage: () => ({ props: { satellite: makeSatellite(), church_nav: nav } }),
    router: { get: () => undefined },
}))

vi.mock('ziggy-js', () => ({
    route: (name: string, params: unknown) => `${name}:${JSON.stringify(params)}`,
}))

function page() {
    return mount(Index, {
        props: {
            children: [],
            filters: {} as never,
            pagination: { page: 1, last_page: 1, total: 0, per_page: 50 },
            options: { levels: [] },
        },
        global: {
            stubs: {
                ChurchShell: { template: '<div><slot /></div>' },
                RegistrationListFilters: { template: '<div />' },
            },
        },
    })
}

describe('Children', () => {
    it('offers Moving-Up to an admin only — once a year, so not in the menu', () => {
        nav.can_move_up = false
        expect(page().find('[data-testid="children-moving-up"]').exists()).toBe(false)

        nav.can_move_up = true
        expect(page().find('[data-testid="children-moving-up"]').attributes('href')).toBe(
            'admin.moving-up.index:"dubai"',
        )
    })
})
