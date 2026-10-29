import ChildCardRequest from '@/components/organisms/ChildCardRequest.vue'
import type { CardFee } from '@/types'
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

    it("shows an open request in the server's words instead of the button, amber only while not paid", () => {
        const mountWith = (fee: CardFee, summary: string) =>
            mount(ChildCardRequest, {
                props: { request: { id: 9, child_id: 4, step: 'asked', fee, summary }, action },
            })

        const notPaid = mountWith('not_paid', 'Asked · not paid')
        const pill = notPaid.find('[data-testid="card-request-step"]')
        expect(pill.text()).toBe('Card: Asked · not paid')
        expect(pill.classes()).toContain('bg-warning-soft')
        expect(notPaid.find('[data-testid="new-card"]').exists()).toBe(false)
        for (const fee of ['paid', 'free'] as const) {
            expect(
                mountWith(fee, `Asked · ${fee}`)
                    .find('[data-testid="card-request-step"]')
                    .classes(),
            ).toContain('bg-info-soft')
        }
    })
})
