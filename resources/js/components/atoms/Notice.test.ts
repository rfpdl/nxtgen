import Notice from '@/components/atoms/Notice.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

describe('Notice', () => {
    it('says something went wrong loudly — an alert, in the danger colours', () => {
        const wrapper = mount(Notice, {
            props: { tone: 'danger' },
            slots: { default: 'No child matches this card: X' },
        })

        expect(wrapper.attributes('role')).toBe('alert')
        expect(wrapper.classes()).toEqual(expect.arrayContaining(['bg-danger-soft', 'text-danger']))
        expect(wrapper.text()).toBe('No child matches this card: X')
    })

    it('asks staff to check, politely — a status, in the warning colours', () => {
        const wrapper = mount(Notice, {
            props: { tone: 'warning' },
            slots: { default: 'Check this is the right child.' },
        })

        expect(wrapper.attributes('role')).toBe('status')
        expect(wrapper.classes()).toEqual(
            expect.arrayContaining(['bg-warning-soft', 'text-warning']),
        )
    })

    it('mentions something by the way — a status, in the info colours', () => {
        const wrapper = mount(Notice, { props: { tone: 'info' }, slots: { default: 'FYI' } })

        expect(wrapper.attributes('role')).toBe('status')
        expect(wrapper.classes()).toContain('bg-info-soft')
    })
})
