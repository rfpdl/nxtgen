import ClassSwitch from '@/components/atoms/ClassSwitch.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

vi.mock('@inertiajs/vue3', () => ({
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
}))

vi.mock('ziggy-js', () => ({
    route: (name: string, code: string) => `${name}:${code}`,
}))

const onePm = { code: 'abcd2345', name: '1PM' }
const sevenPm = { code: 'efgh6789', name: '7PM' }

/* The bar on a class's page (Roy 2026-11-10): the class, and a menu of the satellite's classes open now — to jump doors. */
describe('ClassSwitch', () => {
    it('shows the class as plain text when it is the only one open', () => {
        const wrapper = mount(ClassSwitch, { props: { current: onePm, classes: [onePm] } })

        expect(wrapper.find('button').exists()).toBe(false)
        expect(wrapper.find('[data-testid="bar-class"]').text()).toBe('1PM')
    })

    it('lists the open classes, each a link to its own door, this one marked', async () => {
        const wrapper = mount(ClassSwitch, {
            props: { current: sevenPm, classes: [onePm, sevenPm] },
        })
        const button = wrapper.find('button[data-testid="bar-class"]')
        expect(button.attributes('aria-label')).toBe('Class: 7PM')
        expect(button.attributes('aria-expanded')).toBe('false')

        await button.trigger('click')

        const links = wrapper.find('[data-testid="bar-class-menu"]').findAll('a')
        expect(links.map((l) => [l.text(), l.attributes('href')])).toEqual([
            ['1PM', 'session.checkin:abcd2345'],
            ['7PM', 'session.checkin:efgh6789'],
        ])
        expect(links[1]?.attributes('aria-current')).toBe('page')
        expect(button.attributes('aria-expanded')).toBe('true')
    })

    it('offers the jump from a closed class to the one open now, though only one is open (Astra)', async () => {
        const wrapper = mount(ClassSwitch, { props: { current: onePm, classes: [sevenPm] } })

        await wrapper.find('button[data-testid="bar-class"]').trigger('click')

        expect(
            wrapper
                .find('[data-testid="bar-class-menu"]')
                .findAll('a')
                .map((l) => l.text()),
        ).toEqual(['7PM'])
    })
})
