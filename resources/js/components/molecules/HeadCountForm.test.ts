import HeadCountForm from '@/components/molecules/HeadCountForm.vue'
import type { WhosInSquad } from '@/types'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

/** Level 2 Girls: Amara, Sofia and Isabel checked in. */
function girls(more: Partial<WhosInSquad> = {}): WhosInSquad {
    return {
        id: 5,
        name: 'Level 2 Girls',
        level: { number: 2, name: 'Level 2' },
        present: 2,
        count: 3,
        head_count: null,
        children: [
            {
                id: 31,
                revision: 0,
                nickname: 'Amara',
                short_name: 'Amara Reyes',
                has_allergies: false,
                present: true,
            },
            {
                id: 32,
                revision: 0,
                nickname: 'Isabel',
                short_name: 'Isabel Reyes',
                has_allergies: false,
                present: false,
            },
            {
                id: 33,
                revision: 0,
                nickname: 'Sofia',
                short_name: 'Sofia Reyes',
                has_allergies: false,
                present: true,
            },
        ],
        ...more,
    }
}

const input = (wrapper: ReturnType<typeof mount>) =>
    wrapper.find('[data-testid="head-count-input"]')

describe('HeadCountForm', () => {
    it('starts with the number checked in, a numeric field and "Confirm head count", and no line yet', () => {
        const wrapper = mount(HeadCountForm, { props: { squad: girls() } })

        expect((input(wrapper).element as HTMLInputElement).value).toBe('3')
        expect(input(wrapper).attributes('inputmode')).toBe('numeric')
        expect(wrapper.find('label').text()).toBe('Head count')
        expect(wrapper.find('[data-testid="head-count-confirm"]').text()).toBe('Confirm head count')
        expect(wrapper.find('[data-testid="head-count-confirmed"]').exists()).toBe(false)
        expect(wrapper.find('[data-testid="head-count-mismatch"]').exists()).toBe(false)
    })

    it('starts at the last confirmed count when there is one', () => {
        const wrapper = mount(HeadCountForm, {
            props: {
                squad: girls({ head_count: { count: 2, counted_at: '13:20', counted_by: 'Joy' } }),
            },
        })

        expect((input(wrapper).element as HTMLInputElement).value).toBe('2')
    })

    it('goes back to following the server once a count is sent — the confirmed number, then any later one', async () => {
        const wrapper = mount(HeadCountForm, { props: { squad: girls() } })

        await input(wrapper).setValue('2')
        await wrapper.find('form').trigger('submit')
        await wrapper.setProps({ confirming: true })
        await wrapper.setProps({
            confirming: false,
            squad: girls({ head_count: { count: 2, counted_at: '13:20', counted_by: 'Joy' } }),
        })
        expect((input(wrapper).element as HTMLInputElement).value).toBe('2')

        // Following again: a later count from the server (another leader, a refresh) shows.
        await wrapper.setProps({
            squad: girls({ head_count: { count: 4, counted_at: '13:30', counted_by: 'Ben' } }),
        })
        expect((input(wrapper).element as HTMLInputElement).value).toBe('4')
    })

    it('never overwrites a number typed while the last confirm was still on its way', async () => {
        const wrapper = mount(HeadCountForm, { props: { squad: girls() } })

        await input(wrapper).setValue('2')
        await wrapper.find('form').trigger('submit')
        await wrapper.setProps({
            confirming: true,
            squad: girls({ head_count: { count: 2, counted_at: '13:20', counted_by: 'Joy' } }),
        })
        await input(wrapper).setValue('3')
        await wrapper.setProps({ confirming: false })

        expect((input(wrapper).element as HTMLInputElement).value).toBe('3')
    })

    it('shows what was typed again when it is refused, even after the server sent a new number meanwhile', async () => {
        const wrapper = mount(HeadCountForm, { props: { squad: girls() } })

        await input(wrapper).setValue('250')
        await wrapper.find('form').trigger('submit')
        await input(wrapper).setValue('2')
        await wrapper.find('form').trigger('submit')
        // Sent; before the answer, a refresh brings another leader's count…
        await wrapper.setProps({
            confirming: true,
            squad: girls({ head_count: { count: 4, counted_at: '13:30', counted_by: 'Ben' } }),
        })
        expect((input(wrapper).element as HTMLInputElement).value).toBe('4')
        // …then this count is refused: the field shows what the leader sent, to fix.
        await wrapper.setProps({ confirming: false, error: 'Please type a number from 0 to 200.' })

        expect((input(wrapper).element as HTMLInputElement).value).toBe('2')
    })

    it('keeps what the leader typed when the server refuses it', async () => {
        const wrapper = mount(HeadCountForm, { props: { squad: girls() } })

        await input(wrapper).setValue('2')
        await wrapper.find('form').trigger('submit')
        await wrapper.setProps({ confirming: true })
        await wrapper.setProps({ confirming: false, error: 'Please type a number from 0 to 200.' })
        await wrapper.setProps({ squad: girls({ count: 5 }) })

        expect((input(wrapper).element as HTMLInputElement).value).toBe('2')
    })

    it('follows the number checked in until the leader types', async () => {
        const wrapper = mount(HeadCountForm, { props: { squad: girls() } })

        await wrapper.setProps({ squad: girls({ count: 4 }) })
        expect((input(wrapper).element as HTMLInputElement).value).toBe('4')

        await input(wrapper).setValue('2')
        await wrapper.setProps({ squad: girls({ count: 5 }) })
        expect((input(wrapper).element as HTMLInputElement).value).toBe('2')
    })

    it('asks the page to confirm the number typed', async () => {
        const wrapper = mount(HeadCountForm, { props: { squad: girls() } })

        await input(wrapper).setValue(' 2 ')
        await wrapper.find('form').trigger('submit')

        expect(wrapper.emitted('confirm')).toEqual([[2]])
    })

    it('catches a count that is not a whole number from 0 to 200, before sending', async () => {
        const wrapper = mount(HeadCountForm, { props: { squad: girls() } })

        for (const bad of ['', '-1', '201', '2.5', 'four']) {
            await input(wrapper).setValue(bad)
            await wrapper.find('form').trigger('submit')
            expect(wrapper.find('[role="alert"]').text()).toBe(
                'Please type a number from 0 to 200.',
            )
        }
        await input(wrapper).setValue('0')
        expect(wrapper.find('[role="alert"]').exists()).toBe(false)
        await wrapper.find('form').trigger('submit')

        expect(wrapper.emitted('confirm')).toEqual([[0]])
    })

    it("shows the server's refusal under the field", () => {
        const wrapper = mount(HeadCountForm, {
            props: { squad: girls(), error: 'Please type a number from 0 to 200.' },
        })

        expect(wrapper.find('[role="alert"]').text()).toBe('Please type a number from 0 to 200.')
    })

    it('says when and by whom it was confirmed, and warns with who is not marked when it does not match', () => {
        const wrapper = mount(HeadCountForm, {
            props: {
                squad: girls({ head_count: { count: 2, counted_at: '13:20', counted_by: 'Joy' } }),
            },
        })

        expect(wrapper.find('[data-testid="head-count-confirmed"]').text()).toBe(
            '✓ Confirmed 13:20 by Joy (2)',
        )
        const warning = wrapper.find('[data-testid="head-count-mismatch"]')
        expect(warning.attributes('role')).toBe('status')
        expect(warning.text()).toBe('⚠ Checked in 3, counted 2. Not marked: Isabel')
    })

    it('names no one when no child is marked yet, and leaves the name out when the login has gone', () => {
        const wrapper = mount(HeadCountForm, {
            props: {
                squad: girls({
                    present: 0,
                    children: girls().children.map((c) => ({ ...c, present: false })),
                    head_count: { count: 4, counted_at: '13:20', counted_by: '' },
                }),
            },
        })

        expect(wrapper.find('[data-testid="head-count-confirmed"]').text()).toBe(
            '✓ Confirmed 13:20 (4)',
        )
        expect(wrapper.find('[data-testid="head-count-mismatch"]').text()).toBe(
            '⚠ Checked in 3, counted 4.',
        )
    })

    it('gives no warning when the count matches', () => {
        const wrapper = mount(HeadCountForm, {
            props: {
                squad: girls({ head_count: { count: 3, counted_at: '13:25', counted_by: 'Joy' } }),
            },
        })

        expect(wrapper.find('[data-testid="head-count-mismatch"]').exists()).toBe(false)
    })

    it('shows the button busy while the count is on its way', () => {
        const wrapper = mount(HeadCountForm, { props: { squad: girls(), confirming: true } })

        expect(
            wrapper.find('[data-testid="head-count-confirm"]').attributes('disabled'),
        ).toBeDefined()
    })
})
