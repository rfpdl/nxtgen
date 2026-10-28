import EmptyState from '@/components/atoms/EmptyState.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

describe('EmptyState', () => {
    it('says what to do next, with an optional title and button', () => {
        const wrapper = mount(EmptyState, {
            props: { title: 'No one yet' },
            slots: { default: 'Search a name to start.', action: '<a href="/x">Start</a>' },
            attrs: { 'data-testid': 'empty' },
        })

        expect(wrapper.attributes('data-testid')).toBe('empty')
        expect(wrapper.text()).toContain('No one yet')
        expect(wrapper.text()).toContain('Search a name to start.')
        expect(wrapper.find('a').text()).toBe('Start')
    })

    it('is just the sentence without a title', () => {
        const wrapper = mount(EmptyState, { slots: { default: 'Nothing here.' } })

        expect(wrapper.findAll('p')).toHaveLength(1)
    })
})
