import PrintListCard from '@/components/molecules/PrintListCard.vue'
import type { PrintList } from '@/types'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

vi.mock('@inertiajs/vue3', async (original) => ({
    ...(await import('@/tests/enumPage')).withEnums(await original<object>()),
    Link: {
        props: ['href', 'method', 'preserveScroll'],
        template:
            '<a :href="href" :data-method="method" :data-preserve-scroll="String(preserveScroll)"><slot /></a>',
    },
}))

vi.mock('ziggy-js', () => ({
    route: (name: string, params: unknown[]) => `/#${name}/${params.join('/')}`,
}))

const list: PrintList = {
    id: 7,
    made: 'Sat 31 Oct 10:31',
    made_by: 'Grace',
    cards: 12,
    to_print: 10,
}

describe('PrintListCard', () => {
    it('says what the list holds, when and by whom', () => {
        const card = mount(PrintListCard, { props: { list } })

        expect(card.find('[data-testid="print-list-what"]').text()).toBe(
            'Print list · 12 cards · made Sat 31 Oct 10:31 by Grace',
        )
        expect(
            mount(PrintListCard, {
                props: { list: { ...list, cards: 1, to_print: 1, made_by: null } },
            })
                .find('[data-testid="print-list-what"]')
                .text(),
        ).toBe('Print list · 1 card · made Sat 31 Oct 10:31')
    })

    it('downloads this list, and marks its cards still to print printed — a PUT keeping the scroll', () => {
        const card = mount(PrintListCard, { props: { list } })
        const download = card.find('[data-testid="print-list-download"]')
        const mark = card.find('[data-testid="print-list-mark"]')

        expect([download.text(), download.attributes('href')]).toEqual([
            'Download',
            '/#church.card-requests.print-lists.show/ccf/7',
        ])
        expect([mark.text(), mark.attributes('href'), mark.attributes('data-method')]).toEqual([
            'Mark these 10 printed',
            '/#church.card-requests.print-lists.mark-printed/ccf/7',
            'put',
        ])
        expect(
            mount(PrintListCard, { props: { list: { ...list, to_print: 1 } } })
                .find('[data-testid="print-list-mark"]')
                .text(),
        ).toBe('Mark it printed')
    })
})
