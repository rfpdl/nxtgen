import RegisterFamilyForm from '@/components/organisms/RegisterFamilyForm.vue'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const post = vi.fn()

vi.mock('@inertiajs/vue3', async () => {
    const { reactive } = await import('vue')
    const { enums } = await import('@/tests/enumPage')
    return {
        usePage: () => ({ props: { enums } }),
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
            '[data-testid="home-satellite"] option',
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

    it('asks the DGroup area and leader phone only of a parent already in a DGroup', async () => {
        const wrapper = mount(RegisterFamilyForm, { props })
        expect(wrapper.find('[data-testid="dgroup-area"]').exists()).toBe(false)

        await wrapper.find('[data-testid="in-dgroup"]').setValue(true)

        expect(wrapper.find('[data-testid="dgroup-area"]').exists()).toBe(true)
        expect(wrapper.find('[data-testid="dgroup-leader-phone"]').exists()).toBe(true)
    })

    it('sends lifestage and the ministry the parent serves in', async () => {
        const wrapper = mount(RegisterFamilyForm, { props })

        await wrapper.find('[data-testid="lifestage"]').setValue('Married with kids')
        await wrapper.find('[data-testid="ministry"]').setValue('Music')
        await wrapper.find('form').trigger('submit')

        const sent = post.mock.contexts[0] as {
            lifestage: string
            guardian: { ministries: string[] }
        }
        expect(sent.lifestage).toBe('Married with kids')
        expect(sent.guardian.ministries).toEqual(['Music'])
    })
})
