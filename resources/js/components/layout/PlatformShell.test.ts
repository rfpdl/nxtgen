import PlatformShell from '@/components/layout/PlatformShell.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

const page = {
    url: '/platform/people',
    props: { flash: {}, auth: { user: { id: 1, name: 'Pat' }, can_switch: false } },
}

vi.mock('@inertiajs/vue3', () => ({
    Link: { props: ['href', 'method', 'as'], template: '<a :href="href"><slot /></a>' },
    usePage: () => page,
}))

const paths: Record<string, string> = {
    'platform.satellites.index': '/platform',
    'platform.people.index': '/platform/people',
    'platform.settings.show': '/platform/settings',
}

vi.mock('ziggy-js', () => ({ route: (name: string) => `http://app.test${paths[name] ?? '/'}` }))

/* The platform admin's frame (spec item 12): the bar and the platform's menu — Satellites, People, Settings. */
describe('PlatformShell', () => {
    it("has the platform's menu, marking the page it is on", () => {
        const row = mount(PlatformShell, { props: { title: 'People' } }).find(
            '[data-testid="menu-row"]',
        )

        const links = row
            .findAll('a')
            .filter((a) => a.attributes('data-testid')?.startsWith('nav-platform'))
        expect(links.map((a) => [a.text(), a.attributes('href')])).toEqual([
            ['Satellites', 'http://app.test/platform'],
            ['People', 'http://app.test/platform/people'],
            ['Settings', 'http://app.test/platform/settings'],
        ])
        expect(links[1]?.attributes('aria-current')).toBe('page')
        expect(links[0]?.attributes('aria-current')).toBeUndefined()
    })
})
