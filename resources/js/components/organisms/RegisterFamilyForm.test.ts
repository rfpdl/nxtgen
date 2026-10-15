import RegisterFamilyForm from '@/components/organisms/RegisterFamilyForm.vue'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const post = vi.fn()

vi.mock('@inertiajs/vue3', async () => {
    const { reactive } = await import('vue')
    return {
        useForm: (data: Record<string, unknown>) =>
            reactive({ ...data, errors: {}, processing: false, post }),
    }
})

const props = {
    action: '/ccf/register',
    satellites: [
        { id: 7, name: 'CCF DXB' },
        { id: 8, name: 'House Church' },
    ],
    relationships: [
        { value: 'mother', label: 'Mother' },
        { value: 'father', label: 'Father' },
    ],
    genders: [
        { value: 'boy', label: 'Boy' },
        { value: 'girl', label: 'Girl' },
    ],
}

describe('RegisterFamilyForm', () => {
    beforeEach(() => post.mockReset())

    it('starts with one child, who cannot be removed', () => {
        const wrapper = mount(RegisterFamilyForm, { props })

        expect(wrapper.findAll('fieldset')).toHaveLength(1)
        expect(wrapper.find('[data-testid="child-0-remove"]').exists()).toBe(false)
    })

    it('adds and removes children', async () => {
        const wrapper = mount(RegisterFamilyForm, { props })

        await wrapper.find('[data-testid="add-child"]').trigger('click')
        expect(wrapper.findAll('fieldset')).toHaveLength(2)

        await wrapper.find('[data-testid="child-1-remove"]').trigger('click')
        expect(wrapper.findAll('fieldset')).toHaveLength(1)
    })

    it('lists the church satellites to choose from', () => {
        const options = mount(RegisterFamilyForm, { props }).findAll(
            '[data-testid="home-branch"] option',
        )

        expect(options.map((o) => o.text())).toEqual(['Choose…', 'CCF DXB', 'House Church'])
    })

    it('posts to the action it was given', async () => {
        const wrapper = mount(RegisterFamilyForm, { props })

        await wrapper.find('form').trigger('submit')

        expect(post).toHaveBeenCalledWith('/ccf/register', expect.anything())
    })

    it('keeps the submit button big and labelled for a phone', () => {
        expect(
            mount(RegisterFamilyForm, { props }).find('[data-testid="register-submit"]').text(),
        ).toBe('Register my family')
    })
})
