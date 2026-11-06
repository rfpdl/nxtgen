import SatelliteLinks from '@/components/organisms/SatelliteLinks.vue'
import type { SatelliteLinks as SatelliteLinksProp } from '@/types'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const routerPut = vi.fn()
const routerDelete = vi.fn()

vi.mock('@inertiajs/vue3', async () => {
    const { reactive } = await import('vue')
    return {
        usePage: () => ({ props: { church: { name: 'CCF', slug: 'ccf' } } }),
        useForm: (data: Record<string, unknown>) =>
            reactive({ ...data, errors: {}, processing: false, post: vi.fn(), put: vi.fn() }),
        router: {
            put: (...args: unknown[]) => routerPut(...args),
            delete: (...args: unknown[]) => routerDelete(...args),
        },
    }
})

vi.mock('ziggy-js', () => ({
    route: (name: string, params: unknown[]) => `${name}:${JSON.stringify(params)}`,
}))

const dxb: SatelliteLinksProp = {
    id: 3,
    name: 'CCF DXB',
    links: [
        { id: 7, label: 'Facebook group', url: 'https://facebook.com/groups/ccf' },
        { id: 8, label: 'WhatsApp group', url: 'https://chat.whatsapp.com/abc' },
    ],
}

describe('SatelliteLinks', () => {
    beforeEach(() => {
        routerPut.mockReset()
        routerDelete.mockReset()
    })

    it("lists the satellite's Links in order, the first not movable up and the last not down", () => {
        const wrapper = mount(SatelliteLinks, { props: { satellite: dxb, max: 6 } })

        expect(wrapper.findAll('[data-testid="link-name"]').map((n) => n.text())).toEqual([
            'Facebook group',
            'WhatsApp group',
        ])
        const ups = wrapper.findAll('[data-testid="link-up"]')
        expect(ups[0]?.attributes('disabled')).toBeDefined()
        expect(ups[1]?.attributes('disabled')).toBeUndefined()
    })

    it('moves a Link and removes one at its own address, keeping the scroll', async () => {
        const wrapper = mount(SatelliteLinks, { props: { satellite: dxb, max: 6 } })

        await wrapper.findAll('[data-testid="link-up"]')[1]?.trigger('click')
        expect(routerPut).toHaveBeenCalledWith(
            'nxtgen.admin.links.move:["ccf",8]',
            { direction: 'up' },
            { preserveScroll: true },
        )

        await wrapper.findAll('[data-testid="remove-link"]')[0]?.trigger('click')
        await wrapper.find('[data-testid="confirm-remove-link"]').trigger('click')
        expect(routerDelete).toHaveBeenCalledWith('nxtgen.admin.links.destroy:["ccf",7]', {
            preserveScroll: true,
        })
    })

    it('opens the add form until the poster is full, then says so', async () => {
        const wrapper = mount(SatelliteLinks, { props: { satellite: dxb, max: 6 } })
        await wrapper.find('[data-testid="open-add-link"]').trigger('click')
        expect(wrapper.find('[data-testid="add-link"]').exists()).toBe(true)

        const full = mount(SatelliteLinks, { props: { satellite: dxb, max: 2 } })
        expect(full.find('[data-testid="open-add-link"]').exists()).toBe(false)
        expect(full.find('[data-testid="links-full"]').text()).toContain('holds 2 links')
    })

    it('says when there are none yet', () => {
        const wrapper = mount(SatelliteLinks, {
            props: { satellite: { ...dxb, links: [] }, max: 6 },
        })

        expect(wrapper.text()).toContain('No links on this poster yet')
    })
})
