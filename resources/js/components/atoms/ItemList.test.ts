import ItemList from '@/components/atoms/ItemList.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

describe('ItemList', () => {
    it('shows each comma-separated item on its own, trimmed, and skips empty ones', () => {
        const items = mount(ItemList, { props: { text: ' asthma,eczema , ,hay fever,' } })
            .findAll('li')
            .map((li) => li.text())

        expect(items).toEqual(['asthma', 'eczema', 'hay fever'])
    })

    it('shows text with no comma as one item', () => {
        expect(mount(ItemList, { props: { text: 'Peanuts' } }).findAll('li')).toHaveLength(1)
    })
})
