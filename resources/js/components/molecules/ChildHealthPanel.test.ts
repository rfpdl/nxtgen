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

        expect(wrapper.find('h2').text()).toBe('Amara — health')
        expect(wrapper.find('[data-testid="health-allergies"]').text()).toContain('Peanuts')
        expect(wrapper.text()).toContain('Asthma')
        expect(wrapper.text()).not.toContain('Afraid of')
    })

    it('says plainly when nothing was given', () => {
        const wrapper = mount(ChildHealthPanel, {
            props: { health: { ...amara, allergies: null, illnesses: null } },
        })

        expect(wrapper.text()).toContain('No allergies or health details given.')
    })

    it('closes', async () => {
        const wrapper = mount(ChildHealthPanel, { props: { health: amara } })

        await wrapper.find('[data-testid="close-health"]').trigger('click')
        expect(wrapper.emitted('close')).toHaveLength(1)
    })
})
