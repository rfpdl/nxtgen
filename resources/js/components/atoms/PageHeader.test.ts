import PageHeader from '@/components/atoms/PageHeader.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

describe('PageHeader', () => {
    it('is the page’s one heading, with a line under it and its own buttons', () => {
        const wrapper = mount(PageHeader, {
            props: { title: 'Families', sub: '42 registered', titleTestid: 'heading' },
            slots: { actions: '<button>Register</button>' },
        })

        expect(wrapper.find('h1').text()).toBe('Families')
        expect(wrapper.find('h1').attributes('data-testid')).toBe('heading')
        expect(wrapper.text()).toContain('42 registered')
        expect(wrapper.find('button').text()).toBe('Register')
    })

    it('leaves out what it is not given', () => {
        const wrapper = mount(PageHeader, { props: { title: 'Families' } })

        expect(wrapper.findAll('p')).toHaveLength(0)
        expect(wrapper.find('h1').attributes('data-testid')).toBeUndefined()
    })
})
