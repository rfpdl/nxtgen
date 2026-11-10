import WhosIn from '@/pages/Church/Door/WhosIn.vue'
import type { DoorSession, SessionState, WhosInSquad } from '@/types'
import { makeSatellite } from '@/tests/satellite'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const polls: number[] = []
const gets: { url: string; data: unknown }[] = []

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: { satellite: makeSatellite() } }),
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

const onePm: DoorSession = {
    code: 'abcd2345',
    class_id: 1,
    name: '1PM',
    times: '13:00–15:00',
    date_label: 'Sat 31 Oct',
    state: 'open',
}
const girls: WhosInSquad = {
    id: 5,
    name: 'Level 2 Girls',
    level: { number: 2, name: 'Level 2' },
    present: 0,
    count: 1,
    head_count: null,
    children: [
        {
            id: 31,
            revision: 0,
            nickname: 'Amara',
            short_name: 'Amara Reyes',
            has_allergies: false,
            present: false,
        },
    ],
}

function page(squads: WhosInSquad[], sessionState: SessionState = 'open') {
    return mount(WhosIn, {
        props: {
            session: { ...onePm, state: sessionState },
            open_classes: [{ code: onePm.code, name: onePm.name }],
            squads,
            squad_id: null,
        },
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

    it('sends a Squad leader with no one in yet to Check in, at the same Session', () => {
        const wrapper = page([])

        expect(wrapper.find('[data-testid="whos-in-empty"]').text()).toContain(
            'No one checked in yet',
        )
        expect(wrapper.find('[data-testid="go-check-in"]').attributes('href')).toBe(
            'session.checkin:"abcd2345"',
        )
        expect(wrapper.find('[data-testid="squad-picker"]').exists()).toBe(false)
    })

    it("stays at the Session's code when a Squad chip is picked", async () => {
        const wrapper = page([girls])

        await wrapper.find('[data-testid="squad-picker"] [data-value="5"]').trigger('click')

        expect(gets).toEqual([{ url: 'session.whos-in:"abcd2345"', data: { squad: 5 } }])
    })

    it('shows a closed class read-only: it says so, and its Squads take no taps (Roy 2026-11-10)', () => {
        const wrapper = page([girls], 'closed')

        expect(wrapper.text()).toContain('1PM is closed. It can be looked at, not changed.')
        expect(wrapper.find('[data-testid="present-toggle"]').attributes('disabled')).toBeDefined()
        expect(wrapper.find('[data-testid="head-count"]').exists()).toBe(false)
        expect(wrapper.find('[data-testid="session-open"]').exists()).toBe(false)
    })
})
