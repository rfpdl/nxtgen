import Card from '@/components/atoms/Card.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

describe('Card', () => {
    it('is a padded tile with a thick bottom edge', () => {
        const card = mount(Card, { slots: { default: '<p>Cruz family</p>' } })

        expect(card.element.tagName).toBe('DIV')
        expect(card.classes()).toEqual(expect.arrayContaining(['border-b-[5px]', 'p-4']))
        expect(card.text()).toBe('Cruz family')
    })

    it('can be any element, and drops its padding for a card of rows', () => {
        const card = mount(Card, { props: { as: 'article', flush: true } })

        expect(card.element.tagName).toBe('ARTICLE')
        expect(card.classes()).not.toContain('p-4')
    })
})
