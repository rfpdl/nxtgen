import DoorTabs from '@/components/molecules/DoorTabs.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: { church: { name: 'CCF', slug: 'ccf' } } }),
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
}))

vi.mock('ziggy-js', () => ({
    route: (name: string, params: Record<string, unknown>) => `${name}:${JSON.stringify(params)}`,
}))

describe('DoorTabs', () => {
    it("links Check in and Who's in, each keeping the class picked", () => {
        const wrapper = mount(DoorTabs, { props: { current: 'check-in', classId: 7 } })

        expect(wrapper.find('nav').attributes('aria-label')).toBe('Door')
        const tabs = wrapper.findAll('a')
        expect(tabs.map((t) => t.text())).toEqual(['Check in', "Who's in"])
        expect(tabs.map((t) => t.attributes('href'))).toEqual([
            'church.door.show:{"church":"ccf","class":7}',
            'church.door.whos-in:{"church":"ccf","class":7}',
        ])
    })

    it('marks the page it is on, and only that one', () => {
        const wrapper = mount(DoorTabs, { props: { current: 'whos-in', classId: 7 } })

        const [checkIn, whosIn] = wrapper.findAll('a')
        expect(whosIn?.attributes('aria-current')).toBe('page')
        expect(whosIn?.classes()).toContain('bg-primary-soft')
        expect(checkIn?.attributes('aria-current')).toBeUndefined()
        expect(checkIn?.classes()).toContain('bg-surface')
    })

    it('sends no class when none meets today', () => {
        const wrapper = mount(DoorTabs, { props: { current: 'whos-in', classId: null } })

        expect(wrapper.findAll('a')[0]?.attributes('href')).toBe(
            'church.door.show:{"church":"ccf"}',
        )
    })
})
