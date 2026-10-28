import RollCallMark from '@/components/atoms/RollCallMark.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

describe('RollCallMark', () => {
    it('says "Present ✓" in a success pill for a child marked present', () => {
        const wrapper = mount(RollCallMark, { props: { present: true } })

        expect(wrapper.text()).toBe('Present ✓')
        expect(wrapper.find('span').classes()).toContain('bg-success-soft')
    })

    it('says "Not marked" in quiet words otherwise', () => {
        const wrapper = mount(RollCallMark, { props: { present: false } })

        expect(wrapper.text()).toBe('Not marked')
        expect(wrapper.find('span').classes()).toContain('text-text-muted')
    })
})
