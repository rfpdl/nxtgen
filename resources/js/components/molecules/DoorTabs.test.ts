import DoorTabs from '@/components/molecules/DoorTabs.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

vi.mock('@inertiajs/vue3', () => ({
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
}))

vi.mock('ziggy-js', () => ({
    route: (name: string, code: string) => `${name}:${code}`,
}))

describe('DoorTabs', () => {
    it("links Check in and Who's in, both at the Session's own code (spec item 16)", () => {
        const wrapper = mount(DoorTabs, { props: { current: 'check-in', code: 'abcd2345' } })

        expect(wrapper.find('nav').attributes('aria-label')).toBe('Door')
        const tabs = wrapper.findAll('a')
        expect(tabs.map((t) => t.text())).toEqual(['Check in', "Who's in"])
        expect(tabs.map((t) => t.attributes('href'))).toEqual([
            'session.checkin:abcd2345',
            'session.whos-in:abcd2345',
        ])
    })

    it('marks the page it is on, and only that one', () => {
        const wrapper = mount(DoorTabs, { props: { current: 'whos-in', code: 'abcd2345' } })

        const [checkIn, whosIn] = wrapper.findAll('a')
        expect(whosIn?.attributes('aria-current')).toBe('page')
        expect(whosIn?.classes()).toContain('bg-primary-soft')
        expect(checkIn?.attributes('aria-current')).toBeUndefined()
        expect(checkIn?.classes()).toContain('bg-surface')
    })
})
