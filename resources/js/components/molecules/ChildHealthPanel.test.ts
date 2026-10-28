import ChildHealthPanel from '@/components/molecules/ChildHealthPanel.vue'
import type { ChildHealth } from '@/types'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

const amara: ChildHealth = {
    id: 1,
    nickname: 'Amara',
    allergies: 'Peanuts',
    illnesses: 'Asthma',
    dislikes: null,
    fears: '',
    guardian_notes: null,
}

describe('ChildHealthPanel', () => {
    it('shows the allergy loud and the other health details given', () => {
        const wrapper = mount(ChildHealthPanel, { props: { health: amara } })

        const allergies = wrapper.find('[data-testid="health-allergies"]')
        expect(allergies.text()).toBe('⚠ Allergies: Peanuts')
        expect(allergies.classes()).toContain('bg-danger-soft')
        expect(wrapper.text()).toContain('Asthma')
        expect(wrapper.text()).not.toContain('Afraid of')
    })

    it('says "Allergies: none" plainly, and when nothing else was given', () => {
        const wrapper = mount(ChildHealthPanel, {
            props: { health: { ...amara, allergies: null, illnesses: null } },
        })

        const allergies = wrapper.find('[data-testid="health-allergies"]')
        expect(allergies.text()).toBe('Allergies: none')
        expect(allergies.classes()).not.toContain('bg-danger-soft')
        expect(wrapper.text()).toContain('No other health details given.')
    })

    it("sits inside the child's row — no heading of its own and no Close", () => {
        const wrapper = mount(ChildHealthPanel, { props: { health: amara } })

        expect(wrapper.find('h2').exists()).toBe(false)
        expect(wrapper.find('[data-testid="close-health"]').exists()).toBe(false)
    })
})
