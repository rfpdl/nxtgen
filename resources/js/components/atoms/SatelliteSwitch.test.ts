import SatelliteSwitch from '@/components/atoms/SatelliteSwitch.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

vi.mock('@inertiajs/vue3', () => ({
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
}))

vi.mock('ziggy-js', () => ({
    route: (name: string, params: string) => `${name}:${params}`,
}))

const dubai = { name: 'CCF Dubai', slug: 'dubai' }
const sharjah = { name: 'CCF Sharjah', slug: 'sharjah' }

/*
 * The bar's satellite (spec item 13): the satellite of this page; a menu only for someone who may open two or more,
 * its satellites as links to their homes (Roy 2026-11-08). A menu button, not a select: an arrow key on a select moves
 * to the next satellite at once (review, issue 08) — here nothing moves until a satellite is chosen.
 */
describe('SatelliteSwitch', () => {
    it('shows the satellite as plain text for someone with one satellite — no menu', () => {
        const wrapper = mount(SatelliteSwitch, { props: { current: dubai, satellites: [dubai] } })

        expect(wrapper.find('button').exists()).toBe(false)
        expect(wrapper.find('[data-testid="bar-satellite"]').text()).toBe('CCF Dubai')
    })

    it('is a closed menu button on this satellite, with two or more', () => {
        const wrapper = mount(SatelliteSwitch, {
            props: { current: sharjah, satellites: [dubai, sharjah] },
        })

        const button = wrapper.find('button[data-testid="bar-satellite"]')
        expect(button.text()).toContain('CCF Sharjah')
        expect(button.attributes('aria-expanded')).toBe('false')
        expect(button.attributes('aria-label')).toBe('Satellite: CCF Sharjah')
        expect(wrapper.find('[data-testid="bar-satellite-menu"]').attributes('style')).toContain(
            'display: none',
        )
    })

    it('opens on a tap: each satellite a link to its home, this one marked — and keys move nobody', async () => {
        const wrapper = mount(SatelliteSwitch, {
            props: { current: sharjah, satellites: [dubai, sharjah] },
            attachTo: document.body,
        })
        const button = wrapper.find('button')
        await button.trigger('keydown', { key: 'ArrowDown' })
        expect(button.attributes('aria-expanded')).toBe('false')

        await button.trigger('click')
        expect(button.attributes('aria-expanded')).toBe('true')
        const links = wrapper.findAll('[data-testid="bar-satellite-menu"] a')
        expect(links.map((a) => [a.text(), a.attributes('href')])).toEqual([
            ['CCF Dubai', 'satellite.home:dubai'],
            ['CCF Sharjah', 'satellite.home:sharjah'],
        ])
        expect(links[1]?.attributes('aria-current')).toBe('page')

        await wrapper
            .find('[data-testid="bar-satellite-menu"]')
            .trigger('keydown', { key: 'Escape' })
        expect(button.attributes('aria-expanded')).toBe('false')
        wrapper.unmount()
    })
})
