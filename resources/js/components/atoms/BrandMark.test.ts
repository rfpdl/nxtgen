import BrandMark from '@/components/atoms/BrandMark.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

describe('BrandMark', () => {
    it('is the NxtGen logo, named for a screen reader, in the brand colours', () => {
        const svg = mount(BrandMark).find('svg')

        expect(svg.attributes('role')).toBe('img')
        expect(svg.attributes('aria-label')).toBe('NxtGen')
        expect(svg.find('circle').classes()).toContain('fill-brand-amber')
        // No space inside NXT: a formatter splitting the orange X onto its own line would draw "N X T".
        expect(svg.findAll('text').map((t) => t.text())).toEqual(['NXT', 'GEN'])
    })
})
