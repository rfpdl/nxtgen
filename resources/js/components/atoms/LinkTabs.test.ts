import LinkTabs from '@/components/atoms/LinkTabs.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

vi.mock('@inertiajs/vue3', () => ({
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
}))

const tabs = [
    { key: 'asked', label: 'Asked', href: '/cards?step=asked' },
    { key: 'approved', label: 'Approved', href: '/cards?step=approved' },
    { key: 'printed', label: 'Printed', href: '/cards?step=printed' },
]

describe('LinkTabs', () => {
    it('links each tab, named for a screen reader, each with its testid', () => {
        const wrapper = mount(LinkTabs, {
            props: { label: 'Cards', tabs, current: 'asked', testidPrefix: 'card-tab' },
        })

        expect(wrapper.find('nav').attributes('aria-label')).toBe('Cards')
        const links = wrapper.findAll('a')
        expect(
            links.map((a) => [a.text(), a.attributes('href'), a.attributes('data-testid')]),
        ).toEqual([
            ['Asked', '/cards?step=asked', 'card-tab-asked'],
            ['Approved', '/cards?step=approved', 'card-tab-approved'],
            ['Printed', '/cards?step=printed', 'card-tab-printed'],
        ])
    })

    it('marks the tab it is on, and only that one, pressed in', () => {
        const links = mount(LinkTabs, {
            props: { label: 'Cards', tabs, current: 'approved', testidPrefix: 'card-tab' },
        }).findAll('a')

        expect(links.map((a) => a.attributes('aria-current'))).toEqual([
            undefined,
            'page',
            undefined,
        ])
        expect(links[1]?.classes()).toContain('bg-primary-soft')
        expect(links[0]?.classes()).toContain('bg-surface')
    })

    it('puts any number of tabs two a row on a phone, and all in one row of equal tabs wider', () => {
        for (const count of [2, 3, 5]) {
            const many = Array.from({ length: count }, (_, i) => ({
                key: `t${i}`,
                label: `Tab ${i}`,
                href: `/t${i}`,
            }))
            const nav = mount(LinkTabs, {
                props: { label: 'Tabs', tabs: many, current: 't0', testidPrefix: 't' },
            }).find('nav')

            expect(nav.findAll('a')).toHaveLength(count)
            expect(nav.classes()).toEqual(
                expect.arrayContaining(['grid-cols-2', 'sm:grid-flow-col', 'sm:auto-cols-fr']),
            )
        }
    })
})
