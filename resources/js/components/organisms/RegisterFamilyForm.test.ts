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

        await wrapper.find('[data-testid="in-dgroup"]').trigger('click')

        expect(wrapper.find('[data-testid="dgroup-area"]').exists()).toBe(true)
        expect(wrapper.find('[data-testid="dgroup-leader-phone"]').exists()).toBe(true)
    })

    it('sends the lifestage picked and the ministry the parent serves in', async () => {
        const wrapper = mount(RegisterFamilyForm, { props })

        await wrapper.find('[data-testid="lifestage"]').setValue('ksp')
        await wrapper.find('[data-testid="guardian-ministry"]').setValue('Music')
        await wrapper.find('form').trigger('submit')

        const sent = post.mock.contexts[0] as {
            lifestage: string
            guardian: { ministries: string[] }
        }
        expect(sent.lifestage).toBe('ksp')
        expect(sent.guardian.ministries).toEqual(['Music'])
    })

    it('offers Single, Married and KSP, and starts on Married for a mother (Roy 2026-11-07)', () => {
        const select = mount(RegisterFamilyForm, { props }).find('[data-testid="lifestage"]')

        expect(select.findAll('option').map((o) => o.text())).toEqual([
            '—',
            'Single',
            'Married',
            'KSP',
        ])
        expect((select.element as HTMLSelectElement).value).toBe('married')
    })

    it('leaves the lifestage empty for anyone but a mother or father, until one is picked', async () => {
        const wrapper = mount(RegisterFamilyForm, { props })
        const lifestage = (): string =>
            (wrapper.find('[data-testid="lifestage"]').element as HTMLSelectElement).value

        await wrapper.find('[data-testid="guardian-relationship"]').setValue('aunt_uncle')
        expect(lifestage()).toBe('')

        await wrapper.find('[data-testid="guardian-relationship"]').setValue('father')
        expect(lifestage()).toBe('married')
    })

    it('keeps a lifestage the person picked when the relationship changes', async () => {
        const wrapper = mount(RegisterFamilyForm, { props })

        await wrapper.find('[data-testid="lifestage"]').setValue('single')
        await wrapper.find('[data-testid="guardian-relationship"]').setValue('aunt_uncle')

        expect((wrapper.find('[data-testid="lifestage"]').element as HTMLSelectElement).value).toBe(
            'single',
        )
    })

    // Found by Astra (2026-11-07): a pick that happens to equal the suggestion is still the person's own.
    it('keeps Married, or an empty lifestage, when the person picked it themselves', async () => {
        const wrapper = mount(RegisterFamilyForm, { props })
        const lifestage = wrapper.find('[data-testid="lifestage"]')
        const relationship = wrapper.find('[data-testid="guardian-relationship"]')

        await lifestage.setValue('single')
        await lifestage.setValue('married')
        await relationship.setValue('aunt_uncle')
        expect((lifestage.element as HTMLSelectElement).value).toBe('married')

        await lifestage.setValue('')
        await relationship.setValue('mother')
        expect((lifestage.element as HTMLSelectElement).value).toBe('')
    })

    it("sends the family's other parents and guardians, who are asked no email", async () => {
        const wrapper = mount(RegisterFamilyForm, { props })
        expect(wrapper.find('[data-testid="other-guardian-0-first-name"]').exists()).toBe(false)

        await wrapper.find('[data-testid="add-guardian"]').trigger('click')
        await wrapper.find('[data-testid="other-guardian-0-first-name"]').setValue('Jun')
        await wrapper.find('[data-testid="other-guardian-0-phone"]').setValue('0509990000')
        expect(wrapper.find('[data-testid="other-guardian-0-email"]').exists()).toBe(false)
        await wrapper.find('form').trigger('submit')

        const sent = post.mock.contexts[0] as { guardians: { first_name: string; phone: string }[] }
        expect(sent.guardians).toMatchObject([{ first_name: 'Jun', phone: '0509990000' }])
    })
})
