import Index from '@/pages/Church/MovingUp/Index.vue'
import type { MovingUp, MovingUpCandidate } from '@/types'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const posts: { url: string; data: unknown; options: unknown }[] = []

vi.mock('@inertiajs/vue3', async (original) => ({
    ...(await import('@/tests/enumPage')).withEnums(await original<object>()),
    Head: { template: '<span />' },
    router: {
        post: (url: string, data: unknown, options: unknown) => posts.push({ url, data, options }),
    },
}))

vi.mock('ziggy-js', () => ({
    route: (name: string, params: unknown) => `${name}:${JSON.stringify(params)}`,
}))

function child(id: number, nickname: string, level: number, movesTo: string): MovingUpCandidate {
    return {
        id,
        nickname,
        full_name: `${nickname} Reyes`,
        satellite: 'CCF DXB',
        level: { number: level, name: `Level ${level}` },
        moves_to: movesTo,
    }
}

const children = [
    child(1, 'Amara', 1, 'Level 2'),
    child(2, 'Ben', 1, 'Level 2'),
    child(3, 'Gabe', 4, 'Graduated'),
]

function page(list = children, withoutLevel = 0, last: MovingUp | null = null) {
    return mount(Index, {
        props: { children: list, without_level: withoutLevel, last },
        global: { stubs: { ChurchShell: { template: '<div><slot /></div>' } } },
    })
}

beforeEach(() => {
    posts.length = 0
})

describe('Moving-Up', () => {
    it('groups the children by Level, saying where each Level goes, everyone ticked', () => {
        const wrapper = page()

        expect(wrapper.findAll('h2').map((h) => h.text())).toEqual([
            'Level 1 → Level 2',
            'Level 4 → Graduated',
        ])
        expect(
            wrapper
                .findAll('[data-testid="moving-up-child"]')
                .map((r) => r.attributes('aria-checked')),
        ).toEqual(['true', 'true', 'true'])
        expect(wrapper.find('[data-testid="moving-up-submit"]').text()).toBe('Move up 3 children')
    })

    it('moves up only the children left ticked, each with the Level the page showed', async () => {
        const wrapper = page()
        await wrapper.findAll('[data-testid="moving-up-child"]')[1]?.trigger('click')

        expect(wrapper.find('[data-testid="moving-up-submit"]').text()).toBe('Move up 2 children')
        await wrapper.find('[data-testid="moving-up-submit"]').trigger('click')
        expect(posts).toHaveLength(0)
        expect(wrapper.find('[data-testid="moving-up-confirm"]').text()).toBe(
            'Yes, move up 2 children',
        )
        await wrapper.find('[data-testid="moving-up-confirm"]').trigger('click')

        expect(posts).toHaveLength(1)
        expect(posts[0]?.url).toBe('nxtgen.admin.moving-up.store:"ccf"')
        expect(posts[0]?.data).toEqual({
            children: [
                { child_id: 1, level: 1 },
                { child_id: 3, level: 4 },
            ],
        })
    })

    it('unticks a whole Level at once, and ticks it again', async () => {
        const wrapper = page()
        const levelOne = wrapper.findAll('[data-testid="moving-up-tick-all"]')[0]!

        await levelOne.trigger('click')
        expect(wrapper.find('[data-testid="moving-up-submit"]').text()).toBe('Move up 1 child')
        expect(levelOne.text()).toBe('Tick all')

        await levelOne.trigger('click')
        expect(wrapper.find('[data-testid="moving-up-submit"]').text()).toBe('Move up 3 children')
    })

    it('asks first, and Cancel moves nobody', async () => {
        const wrapper = page()
        await wrapper.find('[data-testid="moving-up-submit"]').trigger('click')
        await wrapper.find('[data-testid="moving-up-cancel"]').trigger('click')

        expect(posts).toHaveLength(0)
        expect(wrapper.find('[data-testid="moving-up-submit"]').exists()).toBe(true)
    })

    it('warns before another Moving-Up when the last one is recent', async () => {
        const last = (recent: boolean): MovingUp => ({
            id: 1,
            made: 'Sat 7 Feb 2026 10:00',
            made_by: 'Ana',
            is_recent: recent,
            children: [],
        })
        const recent = page(children, 0, last(true))
        await recent.find('[data-testid="moving-up-submit"]').trigger('click')
        expect(recent.find('[data-testid="moving-up-recent"]').text()).toContain(
            'A Moving-Up already ran on Sat 7 Feb 2026 10:00',
        )

        const old = page(children, 0, last(false))
        await old.find('[data-testid="moving-up-submit"]').trigger('click')
        expect(old.find('[data-testid="moving-up-recent"]').exists()).toBe(false)
    })

    it('cannot move up with nobody ticked', async () => {
        const wrapper = page([children[0]!])
        await wrapper.find('[data-testid="moving-up-child"]').trigger('click')

        expect(
            wrapper.find('[data-testid="moving-up-submit"]').attributes('disabled'),
        ).toBeDefined()
    })

    it('says how many children have no Level and will not move', () => {
        expect(page(children, 2).find('[data-testid="moving-up-without-level"]').text()).toContain(
            '2 children have no Level yet',
        )
        expect(page(children, 0).find('[data-testid="moving-up-without-level"]').exists()).toBe(
            false,
        )
    })

    it('says so when nobody can move up', () => {
        expect(page([]).find('[data-testid="moving-up-empty"]').exists()).toBe(true)
    })
})
