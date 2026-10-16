import Pager from '@/components/molecules/Pager.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

describe('Pager', () => {
    it('says where you are and moves one page at a time', async () => {
        const wrapper = mount(Pager, { props: { page: 2, lastPage: 3, total: 120 } })

        expect(wrapper.text()).toContain('Page 2 of 3')
        await wrapper.find('[data-testid="page-next"]').trigger('click')
        await wrapper.find('[data-testid="page-prev"]').trigger('click')

        expect(wrapper.emitted('go')).toEqual([[3], [1]])
    })

    it('has no Previous on the first page and no Next on the last', () => {
        const first = mount(Pager, { props: { page: 1, lastPage: 3, total: 120 } })
        const last = mount(Pager, { props: { page: 3, lastPage: 3, total: 120 } })

        expect(first.find('[data-testid="page-prev"]').exists()).toBe(false)
        expect(last.find('[data-testid="page-next"]').exists()).toBe(false)
    })

    it('stays out of the way when everything fits on one page', () => {
        expect(
            mount(Pager, { props: { page: 1, lastPage: 1, total: 12 } })
                .find('nav')
                .exists(),
        ).toBe(false)
    })
})
