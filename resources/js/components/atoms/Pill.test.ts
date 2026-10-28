import Pill from '@/components/atoms/Pill.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

describe('Pill', () => {
    it.each([
        [1, 'bg-level-1-soft', 'bg-level-1'],
        [2, 'bg-level-2-soft', 'bg-level-2'],
        [3, 'bg-level-3-soft', 'bg-level-3'],
        [4, 'bg-level-4-soft', 'bg-level-4'],
    ])('colours Level %i from the age-level chart, with a dot', (level, tint, dot) => {
        const pill = mount(Pill, { props: { level }, slots: { default: 'Level 2 Girls' } })

        expect(pill.classes()).toContain(tint)
        expect(pill.find('[data-testid="level-dot"]').classes()).toContain(dot)
        expect(pill.text()).toBe('Level 2 Girls')
    })

    it.each([
        ['danger', 'bg-danger-soft'],
        ['success', 'bg-success-soft'],
        ['warning', 'bg-warning-soft'],
        ['info', 'bg-info-soft'],
        ['neutral', 'bg-surface-sunken'],
    ] as const)('draws the %s variant from tokens, with no dot', (variant, tint) => {
        const pill = mount(Pill, { props: { variant } })

        expect(pill.classes()).toContain(tint)
        expect(pill.find('[data-testid="level-dot"]').exists()).toBe(false)
    })

    it('is neutral for no Level, or one the chart does not have', () => {
        expect(mount(Pill, { props: { level: null } }).classes()).toContain('bg-surface-sunken')
        expect(mount(Pill, { props: { level: 9 } }).classes()).toContain('bg-surface-sunken')
    })
})
