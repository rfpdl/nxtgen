import WhosIn from '@/pages/Church/Door/WhosIn.vue'
import type { DoorClass, WhosInSquad } from '@/types'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const polls: number[] = []
const gets: { url: string; data: unknown }[] = []

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: { church: { name: 'CCF', slug: 'ccf' } } }),
    usePoll: (interval: number) => polls.push(interval),
    Head: { template: '<span />' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    router: {
        get: (url: string, data: unknown) => gets.push({ url, data }),
        put: vi.fn(),
    },
}))

vi.mock('ziggy-js', () => ({
    route: (name: string, params: unknown) => `${name}:${JSON.stringify(params)}`,
}))

const onePm: DoorClass = { id: 1, name: '1PM', satellite: 'CCF DXB', times: '13:00–15:00' }
const girls: WhosInSquad = {
    id: 5,
    name: 'Level 2 Girls',
    level: { number: 2, name: 'Level 2' },
    present: 0,
    count: 1,
    head_count: null,
    children: [{ id: 31, nickname: 'Amara', has_allergies: false, present: false }],
}

function page(squads: WhosInSquad[]) {
    return mount(WhosIn, {
        props: { today: 'Sat 31 Oct', classes: [onePm], class_id: 1, squads, squad_id: null },
        global: { stubs: { ChurchShell: { template: '<div><slot /></div>' } } },
    })
}

beforeEach(() => {
    polls.length = 0
    gets.length = 0
})

describe("Who's in", () => {
    it('asks for the list again every 20 seconds', () => {
        page([girls])

        expect(polls).toEqual([20_000])
    })

    it('sends a Squad leader with no one in yet to Check in, on the same class', () => {
        const wrapper = page([])

        expect(wrapper.find('[data-testid="whos-in-empty"]').text()).toContain(
            'No one checked in yet',
        )
        expect(wrapper.find('[data-testid="go-check-in"]').attributes('href')).toBe(
            'church.door.show:{"church":"ccf","class":1}',
        )
        expect(wrapper.find('[data-testid="squad-picker"]').exists()).toBe(false)
    })

    it('keeps the class when a Squad chip is picked', async () => {
        const wrapper = page([girls])

        await wrapper.find('[data-testid="squad-picker"] [data-value="5"]').trigger('click')

        expect(gets).toEqual([{ url: 'church.door.whos-in:"ccf"', data: { class: 1, squad: 5 } }])
    })
})
