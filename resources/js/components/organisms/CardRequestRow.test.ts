import CardRequestRow from '@/components/organisms/CardRequestRow.vue'
import type { CardRequestRow as Row } from '@/types'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { reactive } from 'vue'

const put = vi.fn()
const form = reactive({
    reason: '' as string,
    errors: {} as Record<string, string>,
    processing: false,
    put,
    reset: vi.fn(),
    clearErrors: vi.fn(),
})

vi.mock('@inertiajs/vue3', async (original) => ({
    ...(await import('@/tests/enumPage')).withEnums(await original<object>()),
    Link: {
        props: ['href', 'method', 'as', 'preserveScroll'],
        template:
            '<a :href="href" :data-method="method" :data-preserve-scroll="String(preserveScroll)"><slot /></a>',
    },
    useForm: () => form,
}))

vi.mock('ziggy-js', () => ({
    route: (name: string, params: unknown[]) => `/#${name}/${params.join('/')}`,
}))

const asked: Row = {
    id: 9,
    child_name: 'Amara Joy Reyes',
    family_id: 3,
    family_name: 'Ana Reyes',
    satellite: 'CCF DXB',
    reason_label: 'Lost',
    note: 'Lost at the park',
    step: 'asked',
    step_label: 'Asked',
    fee: 'not_paid',
    fee_label: 'AED 10 · not paid',
    refused_reason: null,
    moves: ['mark_paid', 'refuse'],
}

const actions = (row: Row) =>
    mount(CardRequestRow, { props: { request: row } })
        .findAll('[data-testid^="card-action-"]')
        .map((a) => a.attributes('data-testid'))

beforeEach(() => {
    put.mockReset()
    form.errors = {}
})

describe('CardRequestRow', () => {
    it("says whose card, which family, why and the fee — the family's page a tap away", () => {
        const wrapper = mount(CardRequestRow, { props: { request: asked } })

        expect(wrapper.find('[data-testid="card-child"]').text()).toBe('Amara Joy Reyes')
        expect(wrapper.find('[data-testid="card-family"]').text()).toBe('Ana Reyes · CCF DXB')
        expect(wrapper.find('[data-testid="card-family"]').attributes('href')).toBe(
            '/#church.families.show/ccf/3',
        )
        expect(wrapper.find('[data-testid="card-reason"]').text()).toBe('Lost — Lost at the park')
        expect(wrapper.find('[data-testid="card-fee"]').text()).toBe('AED 10 · not paid')
        expect(wrapper.find('[data-testid="card-fee"]').classes()).toContain('bg-warning-soft')
    })

    it('offers exactly the moves the server sends, in its order — and nothing for a closed one', () => {
        expect(actions(asked)).toEqual(['card-action-mark_paid', 'card-action-refuse'])
        expect(actions({ ...asked, moves: ['approve', 'refuse'] })).toEqual([
            'card-action-approve',
            'card-action-refuse',
        ])
        expect(actions({ ...asked, moves: ['refuse', 'mark_printed'] })).toEqual([
            'card-action-refuse',
            'card-action-mark_printed',
        ])
        expect(actions({ ...asked, moves: ['give'] })).toEqual(['card-action-give'])
        expect(actions({ ...asked, moves: [] })).toEqual([])
    })

    it('words each move as the server names it, and routes it to its own action', () => {
        const row = mount(CardRequestRow, {
            props: {
                request: { ...asked, moves: ['mark_paid', 'approve', 'mark_printed', 'give'] },
            },
        })
        const link = (move: string) => row.find(`[data-testid="card-action-${move}"]`)

        expect(
            ['mark_paid', 'approve', 'mark_printed', 'give'].map((m) => [
                link(m).text(),
                link(m).attributes('href'),
            ]),
        ).toEqual([
            ['Mark paid', '/#church.card-requests.mark-paid/ccf/9'],
            ['Approve', '/#church.card-requests.approve/ccf/9'],
            ['Mark printed', '/#church.card-requests.mark-printed/ccf/9'],
            ['Given', '/#church.card-requests.give/ccf/9'],
        ])
    })

    it('puts each move to its own route for this request, keeping the scroll', () => {
        const paid = mount(CardRequestRow, { props: { request: asked } }).find(
            '[data-testid="card-action-mark_paid"]',
        )

        expect(paid.text()).toBe('Mark paid')
        expect(paid.attributes('data-method')).toBe('put')
        expect(paid.attributes('data-preserve-scroll')).toBe('true')
    })

    it('asks why before refusing, and puts the reason', async () => {
        const wrapper = mount(CardRequestRow, { props: { request: asked } })

        await wrapper.find('[data-testid="card-action-refuse"]').trigger('click')
        await wrapper.find('[data-testid="refuse-reason"]').setValue('Found it at home')
        await wrapper.find('form').trigger('submit')

        expect(form.reason).toBe('Found it at home')
        expect(put).toHaveBeenCalledWith(
            '/#church.card-requests.refuse/ccf/9',
            expect.objectContaining({ preserveScroll: true }),
        )
    })

    it("shows the server's refusal on the reason", async () => {
        form.errors = { reason: 'Please say why, in a few words.' }
        const wrapper = mount(CardRequestRow, { props: { request: asked } })
        await wrapper.find('[data-testid="card-action-refuse"]').trigger('click')

        expect(wrapper.find('[role="alert"]').text()).toBe('Please say why, in a few words.')
    })

    it('says why a refused card was refused', () => {
        const wrapper = mount(CardRequestRow, {
            props: {
                request: {
                    ...asked,
                    step: 'refused',
                    step_label: 'Refused',
                    refused_reason: 'Found it',
                    moves: [],
                },
            },
        })

        expect(wrapper.find('[data-testid="card-step"]').text()).toBe('Refused: Found it')
    })
})
