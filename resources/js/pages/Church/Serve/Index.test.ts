import Serve from '@/pages/Church/Serve/Index.vue'
import type { ServeClass } from '@/types'
import { makeSatellite } from '@/tests/satellite'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

const posts: string[] = []

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: { satellite: makeSatellite() } }),
    Head: { template: '<span />' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    router: { post: (url: string) => posts.push(url) },
}))

vi.mock('ziggy-js', () => ({
    route: (name: string, params: unknown) => `${name}:${JSON.stringify(params)}`,
}))

const make = (name: string, state: ServeClass['state'], canOpen = false): ServeClass => ({
    id: name.length,
    name,
    times: '13:00–15:00',
    state,
    state_label: state,
    state_color: 'muted',
    code: state === 'not_open' ? null : `code${name}`,
    can_open: canOpen,
})

function page(classes: ServeClass[]) {
    return mount(Serve, {
        props: { today: 'Sat 31 Oct', classes },
        global: { stubs: { ChurchShell: { template: '<div><slot /></div>' } } },
    })
}

/* Serve's first page (spec item 17): "Open now" — the doors to go to — then "Today", the rest of today's classes. */
describe('Serve', () => {
    it('lists the open classes under "Open now" and the others under "Today"', () => {
        const wrapper = page([make('1PM', 'open'), make('7PM', 'not_open'), make('10AM', 'closed')])

        const openNow = wrapper.find('[data-testid="open-now"]')
        expect(openNow.find('h2').text()).toBe('Open now')
        expect(
            openNow.findAll('[data-testid="serve-class"]').map((c) => c.find('h3').text()),
        ).toEqual(['1PM'])
        const today = wrapper.find('[data-testid="today"]')
        expect(today.find('h2').text()).toBe('Today')
        expect(
            today.findAll('[data-testid="serve-class"]').map((c) => c.find('h3').text()),
        ).toEqual(['7PM', '10AM'])
        expect(wrapper.find('[data-testid="no-class"]').exists()).toBe(false)
    })

    it('shows only the parts that have classes', () => {
        expect(
            page([make('1PM', 'open')])
                .find('[data-testid="today"]')
                .exists(),
        ).toBe(false)
        expect(
            page([make('7PM', 'not_open')])
                .find('[data-testid="open-now"]')
                .exists(),
        ).toBe(false)
    })

    it('says "No class today" when none meets', () => {
        const wrapper = page([])

        expect(wrapper.find('[data-testid="no-class"]').text()).toContain('No class today')
        expect(wrapper.find('[data-testid="open-now"]').exists()).toBe(false)
        expect(wrapper.find('[data-testid="today"]').exists()).toBe(false)
    })

    it('opens a class for its admin from "Today"', async () => {
        posts.length = 0
        const wrapper = page([make('7PM', 'not_open', true)])

        await wrapper.find('[data-testid="serve-open"]').trigger('click')

        expect(posts).toEqual(['serve.sessions.open:["dubai",3]'])
    })
})
