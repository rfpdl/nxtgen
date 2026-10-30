import Index from '@/pages/Church/Cards/Index.vue'
import type { CardListTab, CardRequestRow } from '@/types'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const gets: { url: string; data: unknown }[] = []

vi.mock('@inertiajs/vue3', async (original) => ({
    ...(await import('@/tests/enumPage')).withEnums(await original<object>()),
    Head: { template: '<span />' },
    Link: {
        props: ['href', 'method'],
        template: '<a :href="href" :data-method="method"><slot /></a>',
    },
    router: { get: (url: string, data: unknown) => gets.push({ url, data }) },
}))

vi.mock('ziggy-js', () => ({
    route: (name: string, params: unknown) => `${name}:${JSON.stringify(params)}`,
}))

const row = { id: 9 } as CardRequestRow

function page(tab: CardListTab, requests: CardRequestRow[], q: string | null = null, lastPage = 1) {
    return mount(Index, {
        props: {
            requests,
            tab,
            q,
            pagination: { page: 1, last_page: lastPage, total: requests.length, per_page: 50 },
        },
        global: {
            stubs: {
                ChurchShell: { template: '<div><slot /></div>' },
                CardRequestRow: { props: ['request'], template: '<li data-testid="row" />' },
            },
        },
    })
}

beforeEach(() => {
    gets.length = 0
})

describe('Cards', () => {
    it('offers "Mark all printed" only on Approved, and only with cards to print', () => {
        expect(page('approved', [row]).find('[data-testid="cards-printed-all"]').exists()).toBe(
            true,
        )
        expect(page('approved', []).find('[data-testid="cards-printed-all"]').exists()).toBe(false)
        expect(page('asked', [row]).find('[data-testid="cards-printed-all"]').exists()).toBe(false)
        expect(
            page('approved', [row])
                .find('[data-testid="cards-printed-all"]')
                .attributes('data-method'),
        ).toBe('put')
    })

    it('links every tab, marking the one it is on, keeping the search', () => {
        const tabs = page('printed', [row], 'amara').findAll('nav a')

        expect(tabs.map((t) => t.text())).toEqual([
            'Asked',
            'Approved',
            'Printed',
            'Given or refused',
        ])
        expect(tabs[2]?.attributes('aria-current')).toBe('page')
        expect(tabs[0]?.attributes('href')).toBe(
            'church.card-requests.index:{"church":"ccf","tab":"asked","q":"amara"}',
        )
    })

    it('searches by name on the tab it is on', async () => {
        const wrapper = page('approved', [])

        await wrapper.find('[data-testid="cards-search"]').setValue('  Jon Ocampo ')
        await wrapper.find('form[role="search"]').trigger('submit')

        expect(gets).toEqual([
            { url: 'church.card-requests.index:"ccf"', data: { tab: 'approved', q: 'Jon Ocampo' } },
        ])
    })

    it('turns the page keeping the tab and the search', async () => {
        const wrapper = page('closed', [row], 'jon', 3)

        await wrapper.find('[data-testid="page-next"]').trigger('click')

        expect(gets).toEqual([
            { url: 'church.card-requests.index:"ccf"', data: { tab: 'closed', q: 'jon', page: 2 } },
        ])
    })

    it('says plainly when a search finds no one on this tab', () => {
        expect(page('asked', [], 'zed').find('[data-testid="cards-empty"]').text()).toContain(
            'No child called “zed” on this tab.',
        )
    })
})
