import AddChildForm from '@/components/organisms/AddChildForm.vue'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const post = vi.fn()

vi.mock('@inertiajs/vue3', async () => {
    const { reactive } = await import('vue')
    const { enums } = await import('@/tests/enumPage')
    return {
        usePage: () => ({ props: { enums } }),
        useForm: (data: Record<string, unknown>) =>
            reactive({ ...data, errors: {}, processing: false, post, reset: vi.fn() }),
    }
})

describe('AddChildForm', () => {
    beforeEach(() => post.mockReset())

    it('stays closed until the parent asks to add a child', async () => {
        const wrapper = mount(AddChildForm, { props: { action: '/ccf/family/children' } })

        expect(wrapper.find('fieldset').exists()).toBe(false)

        await wrapper.find('[data-testid="open-add-child"]').trigger('click')

        expect(wrapper.find('fieldset').exists()).toBe(true)
    })

    it('posts the child to the action it was given', async () => {
        const wrapper = mount(AddChildForm, { props: { action: '/ccf/family/children' } })
        await wrapper.find('[data-testid="open-add-child"]').trigger('click')

        await wrapper.find('form').trigger('submit')

        expect(post).toHaveBeenCalledWith('/ccf/family/children', expect.anything())
    })
})
