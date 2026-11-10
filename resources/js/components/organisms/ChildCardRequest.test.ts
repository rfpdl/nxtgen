import ChildCardRequest from '@/components/organisms/ChildCardRequest.vue'
import { enums } from '@/tests/enumPage'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { reactive } from 'vue'

const post = vi.fn()
const form = reactive({
    reason: '' as string,
    note: '' as string | null,
    errors: {} as Record<string, string>,
    processing: false,
    post,
    reset: vi.fn(),
    clearErrors: vi.fn(),
})

vi.mock('@inertiajs/vue3', async (original) => ({
    ...(await import('@/tests/enumPage')).withEnums(await original<object>()),
    useForm: () => form,
    Link: {
        props: ['href', 'method', 'preserveScroll'],
        template:
            '<a :href="href" :data-method="method" :data-preserve-scroll="String(preserveScroll)"><slot /></a>',
    },
}))

vi.mock('ziggy-js', () => ({
    route: (name: string, params: unknown[]) => `/#${name}/${params.join('/')}`,
}))

const action = '/ccf/children/4/card-requests'

beforeEach(() => {
    post.mockReset()
    form.reset.mockClear()
    form.reason = ''
    form.note = ''
    form.errors = {}
})

describe('ChildCardRequest', () => {
    it('offers "New card…" when the child has no open request, and opens the picker on a tap', async () => {
        const wrapper = mount(ChildCardRequest, { props: { request: null, action } })

        expect(wrapper.find('[data-testid="card-request-form"]').exists()).toBe(false)
        expect(wrapper.find('[data-testid="new-card"]').text()).toBe('New card…')

        await wrapper.find('[data-testid="new-card"]').trigger('click')

        expect(wrapper.find('[data-testid="new-card"]').exists()).toBe(false)
        const chips = wrapper.find('[data-testid="card-reason"]').findAll('[role="radio"]')
        expect(chips.map((c) => c.text())).toEqual(['Lost', 'Damaged'])
        expect(wrapper.find('[data-testid="card-note"]').exists()).toBe(true)
    })

    it('posts the reason and note to the given action, keeping the scroll', async () => {
        const wrapper = mount(ChildCardRequest, { props: { request: null, action } })
        await wrapper.find('[data-testid="new-card"]').trigger('click')

        await wrapper.find('[data-testid="card-reason-lost"]').trigger('click')
        await wrapper.find('[data-testid="card-note"]').setValue('Lost at the park')
        await wrapper.find('form').trigger('submit')

        expect([form.reason, form.note]).toEqual(['lost', 'Lost at the park'])
        expect(post).toHaveBeenCalledWith(action, expect.objectContaining({ preserveScroll: true }))
    })

    it('closes the picker again on Cancel, sending nothing', async () => {
        const wrapper = mount(ChildCardRequest, { props: { request: null, action } })
        await wrapper.find('[data-testid="new-card"]').trigger('click')

        await wrapper.find('[data-testid="card-request-cancel"]').trigger('click')

        expect(wrapper.find('[data-testid="card-request-form"]').exists()).toBe(false)
        expect(post).not.toHaveBeenCalled()
    })

    it("shows the server's refusal on the reason", async () => {
        form.errors = { reason: 'Please choose Lost or Damaged.' }
        const wrapper = mount(ChildCardRequest, { props: { request: null, action } })
        await wrapper.find('[data-testid="new-card"]').trigger('click')

        expect(wrapper.find('[role="alert"]').text()).toBe('Please choose Lost or Damaged.')
    })

    it('closes the picker once the request is saved', async () => {
        post.mockImplementation((_url: string, options: { onSuccess?: () => void }) =>
            options.onSuccess?.(),
        )
        const wrapper = mount(ChildCardRequest, { props: { request: null, action } })
        await wrapper.find('[data-testid="new-card"]').trigger('click')
        await wrapper.find('[data-testid="card-reason-damaged"]').trigger('click')

        await wrapper.find('form').trigger('submit')

        expect(wrapper.find('[data-testid="card-request-form"]').exists()).toBe(false)
        expect(form.reset).toHaveBeenCalled()
    })

    it("shows the server's refusal on the note", async () => {
        form.errors = { note: 'Please keep the note to 200 letters.' }
        const wrapper = mount(ChildCardRequest, { props: { request: null, action } })
        await wrapper.find('[data-testid="new-card"]').trigger('click')

        expect(wrapper.find('[role="alert"]').text()).toBe('Please keep the note to 200 letters.')
        expect(wrapper.find('[data-testid="card-note"]').attributes('aria-invalid')).toBe('true')
    })

    it("shows an open request in the server's words and colour instead of the button", () => {
        const mountWith = (fee_color: 'warning' | 'info', summary: string) =>
            mount(ChildCardRequest, {
                props: {
                    request: {
                        id: 9,
                        child_id: 4,
                        step: 'asked',
                        fee: 'not_paid',
                        fee_color,
                        summary,
                        moves: [],
                    },
                    action,
                },
            })

        const notPaid = mountWith('warning', 'Asked · not paid')
        const pill = notPaid.find('[data-testid="card-request-step"]')
        expect(pill.text()).toBe('Card: Asked · not paid')
        expect(pill.classes()).toContain('bg-warning-soft')
        expect(notPaid.find('[data-testid="new-card"]').exists()).toBe(false)
        // The colour is the server's (CardFeeEnum::color), never worked out here from the fee.
        expect(
            mountWith('info', 'Asked · paid').find('[data-testid="card-request-step"]').classes(),
        ).toContain('bg-info-soft')
    })

    it("names the desk's move as the server words it (CardMoveEnum)", () => {
        const given = enums.card_move.find((o) => o.value === 'give')!
        const before = given.label
        given.label = 'Handed over'
        try {
            const button = mount(ChildCardRequest, {
                props: {
                    request: {
                        id: 9,
                        child_id: 4,
                        step: 'printed',
                        fee: 'paid',
                        fee_color: 'info',
                        summary: 'Printed · paid',
                        moves: ['give'],
                    },
                    action,
                },
            }).find('[data-testid="card-given"]')
            expect(button.text()).toBe('Handed over')
        } finally {
            given.label = before
        }
    })

    it('offers "Given" when the server allows it — a PUT for this request, keeping the scroll', () => {
        const printed = mount(ChildCardRequest, {
            props: {
                request: {
                    id: 9,
                    child_id: 4,
                    step: 'printed',
                    fee: 'paid',
                    fee_color: 'info',
                    summary: 'Printed · paid',
                    moves: ['give'],
                },
                action,
            },
        }).find('[data-testid="card-given"]')

        expect(printed.text()).toBe('Given')
        expect(printed.attributes('href')).toBe('/#serve.card-requests.give/dubai/9')
        expect(printed.attributes('data-method')).toBe('put')
        expect(printed.attributes('data-preserve-scroll')).toBe('true')

        const asked = mount(ChildCardRequest, {
            props: {
                request: {
                    id: 9,
                    child_id: 4,
                    step: 'approved',
                    fee: 'paid',
                    fee_color: 'info',
                    summary: 'Approved · paid',
                    moves: [],
                },
                action,
            },
        })
        expect(asked.find('[data-testid="card-given"]').exists()).toBe(false)
    })
})
