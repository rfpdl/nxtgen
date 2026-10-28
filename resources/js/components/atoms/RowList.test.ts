import RowList from '@/components/atoms/RowList.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import { h } from 'vue'

vi.mock('@inertiajs/vue3', () => ({
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
}))

type Row = { id: number; name: string }
const items: Row[] = [
    { id: 1, name: 'Ana' },
    { id: 2, name: 'Ben' },
]

function list(props: Record<string, unknown> = {}, after?: (row: Row) => unknown) {
    return mount(RowList<Row>, {
        props: { items, itemKey: (r: Row) => r.id, rowTestid: 'row', ...props },
        slots: {
            default: ({ item }: { item: Row }) => h('b', item.name),
            ...(after ? { after: ({ item }: { item: Row }) => after(item) } : {}),
        },
    })
}

describe('RowList', () => {
    it('is a card of padded rows with a line between them', () => {
        const wrapper = list()
        const rows = wrapper.findAll('[data-testid="row"]')

        expect(wrapper.element.tagName).toBe('UL')
        expect(wrapper.classes()).toEqual(expect.arrayContaining(['divide-y-2', 'border-b-[5px]']))
        expect(rows.map((r) => r.text())).toEqual(['Ana', 'Ben'])
        expect(rows[0]?.find('div').classes()).toContain('px-4')
        expect(wrapper.find('a').exists()).toBe(false)
    })

    it('makes the whole row one link ending in a chevron, with no underline', () => {
        const wrapper = list({ href: (r: Row) => `/families/${r.id}` })
        const link = wrapper.findAll('[data-testid="row"]')[1]!.find('a')

        expect(link.attributes('href')).toBe('/families/2')
        expect(link.text()).toBe('Ben')
        expect(link.classes()).not.toContain('underline')
        expect(link.find('[data-testid="row-chevron"]').exists()).toBe(true)
    })

    it('keeps what comes after (a button) outside the link', () => {
        const wrapper = list({ href: (r: Row) => `/f/${r.id}` }, (r) =>
            r.id === 1 ? h('button', 'Resend') : null,
        )
        const first = wrapper.findAll('[data-testid="row"]')[0]!

        expect(first.find('a').find('button').exists()).toBe(false)
        expect(first.find('button').text()).toBe('Resend')
    })

    it('drops the card when plain, for rows inside a card of their own', () => {
        const wrapper = list({ plain: true })

        expect(wrapper.element.tagName).toBe('UL')
        expect(wrapper.classes()).not.toContain('border-b-[5px]')
        expect(wrapper.attributes('flush')).toBeUndefined()
    })
})
