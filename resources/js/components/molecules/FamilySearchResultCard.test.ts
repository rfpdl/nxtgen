import FamilySearchResultCard from '@/components/molecules/FamilySearchResultCard.vue'
import type { FamilySearchResult } from '@/types'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

const result = (over: Partial<FamilySearchResult> = {}): FamilySearchResult => ({
    id: 3,
    primaryName: 'Janice Ramos',
    maskedEmail: 'ja•••@example.com',
    emailConfirmed: false,
    satellite: 'House Church',
    children: ['Amara', 'Aaron'],
    canOpen: false,
    ...over,
})

describe('FamilySearchResultCard', () => {
    it('shows who the family is and which email they used, masked', () => {
        const text = mount(FamilySearchResultCard, {
            props: { result: result(), href: '/x' },
        }).text()

        expect(text).toContain('Janice Ramos')
        expect(text).toContain('ja•••@example.com')
        expect(text).toContain('House Church')
        expect(text).toContain('Amara, Aaron')
    })

    it('flags an email that is not confirmed yet', () => {
        expect(
            mount(FamilySearchResultCard, { props: { result: result(), href: '/x' } }).text(),
        ).toContain('Email not confirmed')
        expect(
            mount(FamilySearchResultCard, {
                props: { result: result({ emailConfirmed: true }), href: '/x' },
            }).text(),
        ).not.toContain('Email not confirmed')
    })

    it('links to the family only when the volunteer may open it', () => {
        expect(
            mount(FamilySearchResultCard, { props: { result: result(), href: '/x' } })
                .find('a')
                .exists(),
        ).toBe(false)
        expect(
            mount(FamilySearchResultCard, {
                props: { result: result({ canOpen: true }), href: '/x' },
            })
                .find('a')
                .attributes('href'),
        ).toBe('/x')
    })

    it('emits add-child for this family', async () => {
        const wrapper = mount(FamilySearchResultCard, { props: { result: result(), href: '/x' } })

        await wrapper.find('[data-testid="search-add-child"]').trigger('click')

        expect(wrapper.emitted('addChild')).toHaveLength(1)
    })

    it('can leave out "Add a child" where it does not belong', () => {
        const wrapper = mount(FamilySearchResultCard, {
            props: { result: result(), href: '/f/1', canAddChild: false },
        })

        expect(wrapper.find('[data-testid="search-add-child"]').exists()).toBe(false)
    })
})
