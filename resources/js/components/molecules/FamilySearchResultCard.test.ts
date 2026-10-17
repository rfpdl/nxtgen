import FamilySearchResultCard from '@/components/molecules/FamilySearchResultCard.vue'
import type { FamilySearchResult } from '@/types'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

const result = (over: Partial<FamilySearchResult> = {}): FamilySearchResult => ({
    id: 3,
    primary_name: 'Janice Ramos',
    masked_email: 'ja•••@example.com',
    email_confirmed: false,
    satellite: 'House Church',
    children: ['Amara', 'Aaron'],
    can_open: false,
    can_resend: false,
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
                props: { result: result({ email_confirmed: true }), href: '/x' },
            }).text(),
        ).not.toContain('Email not confirmed')
    })

    it('links to the family only when the staff member may open it', () => {
        expect(
            mount(FamilySearchResultCard, { props: { result: result(), href: '/x' } })
                .find('a')
                .exists(),
        ).toBe(false)
        expect(
            mount(FamilySearchResultCard, {
                props: { result: result({ can_open: true }), href: '/x' },
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

    it('offers to re-send the confirmation link only when the server says so', () => {
        const stubs = {
            ResendLinkButton: {
                props: ['familyId'],
                template: '<b data-testid="resend">{{ familyId }}</b>',
            },
        }
        const offered = mount(FamilySearchResultCard, {
            props: { result: result({ can_resend: true }), href: '/x' },
            global: { stubs },
        }).find('[data-testid="resend"]')

        expect(offered.text()).toBe('3')
        expect(
            mount(FamilySearchResultCard, {
                props: { result: result(), href: '/x' },
                global: { stubs },
            })
                .find('[data-testid="resend"]')
                .exists(),
        ).toBe(false)
    })

    it('can leave out "Add a child" where it does not belong', () => {
        const wrapper = mount(FamilySearchResultCard, {
            props: { result: result(), href: '/f/1', canAddChild: false },
        })

        expect(wrapper.find('[data-testid="search-add-child"]').exists()).toBe(false)
    })
})
