import SectionHeading from '@/components/atoms/SectionHeading.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

describe('SectionHeading', () => {
    it("is a page section's h2 by default", () => {
        const heading = mount(SectionHeading, { slots: { default: 'Children' } })

        expect(heading.element.tagName).toBe('H2')
        expect(heading.classes()).toEqual(expect.arrayContaining(['text-2xl', 'font-bold']))
        expect(heading.text()).toBe('Children')
    })

    it('is a smaller h3 inside a card', () => {
        const heading = mount(SectionHeading, { props: { level: '3' } })

        expect(heading.element.tagName).toBe('H3')
        expect(heading.classes()).toContain('text-xl')
    })
})
