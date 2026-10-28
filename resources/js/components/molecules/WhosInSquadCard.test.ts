import WhosInSquadCard from '@/components/molecules/WhosInSquadCard.vue'
import type { WhosInSquad } from '@/types'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

const squad: WhosInSquad = {
    id: 5,
    name: 'Level 2 Girls',
    level: { number: 2, name: 'Level 2' },
    present: 1,
    count: 3,
    head_count: null,
    children: [
        { id: 31, nickname: 'Amara', has_allergies: true, present: false },
        { id: 32, nickname: 'Isabel', has_allergies: false, present: false },
        { id: 33, nickname: 'Sofia', has_allergies: false, present: true },
    ],
}

describe('WhosInSquadCard', () => {
    it('heads the Squad with its Level colour and "N of M present"', () => {
        const wrapper = mount(WhosInSquadCard, { props: { squad } })

        expect(wrapper.find('h2 [data-testid="squad-name"]').text()).toBe('Level 2 Girls')
        expect(wrapper.find('[data-testid="level-dot"]').classes()).toContain('bg-level-2')
        expect(wrapper.find('[data-testid="squad-present"]').text()).toBe('1 of 3 present')
    })

    it('lists each child as one big tick — ticked when present — with the allergy flag only', () => {
        const wrapper = mount(WhosInSquadCard, { props: { squad } })

        const rows = wrapper.findAll('[data-testid="whos-in-child"]')
        expect(rows.map((r) => r.find('[data-testid="child-name"]').text())).toEqual([
            'Amara',
            'Isabel',
            'Sofia',
        ])
        const ticks = rows.map((r) => r.find('[role="checkbox"]'))
        expect(ticks.map((t) => t.attributes('aria-checked'))).toEqual(['false', 'false', 'true'])
        // The whole row is the tick: the name is inside it.
        expect(ticks[0]?.text()).toContain('Amara')
        expect(rows[0]?.find('[data-testid="allergy-flag"]').text()).toBe('⚠ Allergies')
        expect(rows[1]?.find('[data-testid="allergy-flag"]').exists()).toBe(false)
    })

    it('asks to mark a tapped child present, or to unmark one already present', async () => {
        const wrapper = mount(WhosInSquadCard, { props: { squad } })

        const ticks = wrapper.findAll('[role="checkbox"]')
        await ticks[0]?.trigger('click')
        await ticks[2]?.trigger('click')

        expect(wrapper.emitted('present')).toEqual([
            [31, true],
            [33, false],
        ])
    })

    it('carries the head count under the list, passing a confirm up', async () => {
        const wrapper = mount(WhosInSquadCard, { props: { squad, countError: 'Nope.' } })

        expect(wrapper.find('[data-testid="head-count"]').exists()).toBe(true)
        expect(wrapper.find('[role="alert"]').text()).toBe('Nope.')
        await wrapper.find('form').trigger('submit')

        expect(wrapper.emitted('confirm')).toEqual([[3]])
    })
})
