import PossibleDuplicateCard from '@/components/organisms/PossibleDuplicateCard.vue'
import type { DuplicatePair, FamilySearchResult } from '@/types'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: { church: { name: 'CCF', slug: 'ccf' } } }),
}))

vi.mock('ziggy-js', () => ({
    route: (name: string, params: [string, number]) => `/${params[0]}/families/${params[1]}`,
}))

const side = (over: Partial<FamilySearchResult>): FamilySearchResult => ({
    id: 1,
    primary_name: 'Janice Ramos',
    masked_email: 'ja•••@example.com',
    email_confirmed: true,
    satellite: 'CCF DXB',
    children: ['Amara'],
    can_open: true,
    can_resend: false,
    ...over,
})

const pair: DuplicatePair = {
    id: 9,
    reason_label: 'Same phone',
    subject: side({ id: 2, primary_name: 'Mary Santos', masked_email: 'ma•••@new.com' }),
    candidate: side({ id: 1, primary_name: 'Maria Santos', can_open: false }),
}

describe('PossibleDuplicateCard', () => {
    it("shows why, and the newer record's family beside the earlier one, masked", () => {
        const wrapper = mount(PossibleDuplicateCard, { props: { pair } })

        expect(wrapper.text()).toContain('Added more recently')
        expect(wrapper.text()).toContain('Registered earlier')
        expect(wrapper.text()).not.toContain('Just registered')
        expect(wrapper.text()).toContain('Same phone')
        expect(wrapper.text()).toContain('Mary Santos')
        expect(wrapper.text()).toContain('ma•••@new.com')
        expect(wrapper.text()).toContain('Maria Santos')
        // Each side opens only when the viewer may open it.
        expect(wrapper.findAll('a').map((a) => a.attributes('href'))).toEqual(['/ccf/families/2'])
    })

    it('answers "not the same" to clear a false flag, or "the same" to flag it for merging', async () => {
        const wrapper = mount(PossibleDuplicateCard, { props: { pair } })

        await wrapper.find('[data-testid="dismiss-duplicate"]').trigger('click')
        await wrapper.find('[data-testid="confirm-duplicate"]').trigger('click')

        expect(wrapper.emitted('answer')).toEqual([
            [9, 'dismissed'],
            [9, 'confirmed'],
        ])
    })
})
