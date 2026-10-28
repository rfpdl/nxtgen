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
        {
            id: 31,
            revision: 0,
            nickname: 'Amara',
            short_name: 'Amara Reyes',
            has_allergies: true,
            present: false,
        },
        {
            id: 32,
            revision: 0,
            nickname: 'Isabel',
            short_name: 'Isabel Reyes',
            has_allergies: false,
            present: false,
        },
        {
            id: 33,
            revision: 0,
            nickname: 'Sofia',
            short_name: 'Sofia Reyes',
            has_allergies: false,
            present: true,
        },
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

        // With the revision the card showed, sent back with the tap.
        expect(wrapper.emitted('present')).toEqual([
            [31, true, 0],
            [33, false, 0],
        ])
    })

    it('carries the head count under the list, passing a confirm up', async () => {
        const wrapper = mount(WhosInSquadCard, { props: { squad, countError: 'Nope.' } })

        expect(wrapper.find('[data-testid="head-count"]').exists()).toBe(true)
        expect(wrapper.find('[role="alert"]').text()).toBe('Nope.')
        await wrapper.find('form').trigger('submit')

        expect(wrapper.emitted('confirm')).toEqual([[3]])
    })

    it('tells two children of one nickname apart by first and last name, small under the nickname, inside the tick', () => {
        const amaras: WhosInSquad = {
            ...squad,
            children: [
                {
                    id: 41,
                    revision: 0,
                    nickname: 'Amara',
                    short_name: 'Amara Reyes',
                    has_allergies: false,
                    present: false,
                },
                {
                    id: 42,
                    revision: 0,
                    nickname: 'Amara',
                    short_name: 'Amara Santos',
                    has_allergies: false,
                    present: false,
                },
            ],
        }
        const wrapper = mount(WhosInSquadCard, { props: { squad: amaras } })

        const rows = wrapper.findAll('[data-testid="whos-in-child"]')
        expect(
            rows.map((r) => [
                r.find('[data-testid="child-name"]').text(),
                r.find('[data-testid="child-full-name"]').text(),
            ]),
        ).toEqual([
            ['Amara', 'Amara Reyes'],
            ['Amara', 'Amara Santos'],
        ])
        const small = rows[0]?.find('[data-testid="child-full-name"]')
        expect(small?.classes()).toEqual(expect.arrayContaining(['text-text-muted', 'text-base']))
        expect(rows[1]?.find('[role="checkbox"]').text()).toContain('Amara Santos')
    })
})
