import FamilyChildCard from '@/components/molecules/FamilyChildCard.vue'
import type { ChildPortal } from '@/types'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

const child = (over: Partial<ChildPortal> = {}): ChildPortal => ({
    id: 1,
    firstName: 'Alexis',
    middleName: 'Amara',
    lastName: 'Ramos',
    nickname: 'Amara',
    gender: 'girl',
    dateOfBirth: '2019-05-10',
    allergies: null,
    illnesses: null,
    dislikes: null,
    fears: null,
    parentNotes: null,
    level: 'Level 2',
    status: 'Active',
    photoConsent: false,
    photoUrl: null,
    ...over,
})

describe('FamilyChildCard', () => {
    it('leads with the nickname and shows the full name and Level', () => {
        const text = mount(FamilyChildCard, { props: { child: child() } }).text()

        expect(text).toContain('Amara')
        expect(text).toContain('Alexis Amara Ramos')
        expect(text).toContain('Level 2')
    })

    it('warns about allergies, and only when there are some', () => {
        expect(
            mount(FamilyChildCard, { props: { child: child() } })
                .find('[data-testid="allergy"]')
                .exists(),
        ).toBe(false)
        expect(
            mount(FamilyChildCard, { props: { child: child({ allergies: 'Peanuts' }) } })
                .find('[data-testid="allergy"]')
                .text(),
        ).toContain('Peanuts')
    })

    it('says when the child has no Level yet', () => {
        expect(
            mount(FamilyChildCard, { props: { child: child({ level: null }) } }).text(),
        ).toContain('No Level yet')
    })

    it('shows every health detail the family gave, and nothing for what they left empty', () => {
        const wrapper = mount(FamilyChildCard, {
            props: {
                child: child({
                    illnesses: 'Asthma',
                    dislikes: 'Loud music',
                    fears: 'The dark',
                    parentNotes: 'Shy at first',
                }),
            },
        })

        expect(wrapper.find('[data-testid="health"]').text()).toContain('Asthma')
        expect(wrapper.text()).toContain('Loud music')
        expect(wrapper.text()).toContain('The dark')
        expect(wrapper.text()).toContain('Shy at first')
        expect(
            mount(FamilyChildCard, { props: { child: child() } })
                .find('[data-testid="health"]')
                .exists(),
        ).toBe(false)
    })

    it('has room for an action under the card', () => {
        const wrapper = mount(FamilyChildCard, {
            props: { child: child() },
            slots: { default: '<button>Edit</button>' },
        })

        expect(wrapper.find('button').text()).toBe('Edit')
    })

    it('shows the photo when there is one', () => {
        const wrapper = mount(FamilyChildCard, {
            props: { child: child({ photoUrl: '/photos/children/1?signature=x' }) },
        })

        expect(wrapper.find('[data-testid="child-photo-img"]').attributes('src')).toBe(
            '/photos/children/1?signature=x',
        )
        expect(
            mount(FamilyChildCard, { props: { child: child() } })
                .find('[data-testid="child-photo-img"]')
                .exists(),
        ).toBe(false)
    })
})
