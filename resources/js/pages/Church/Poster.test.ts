import Poster from '@/pages/Church/Poster.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

const routerGet = vi.fn()

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    usePage: () => ({ props: { church: { name: 'CCF', slug: 'ccf' } } }),
    router: { get: (...args: unknown[]) => routerGet(...args) },
}))

vi.mock('ziggy-js', () => ({
    route: (name: string, params: unknown) => `${name}:${JSON.stringify(params)}`,
}))

const dxb = { id: 3, name: 'CCF DXB' }
const house = { id: 4, name: 'House Church' }
const base = {
    register_url: 'https://app.test/ccf/register',
    qr: 'data:image/svg+xml;base64,AAA',
    satellite: dxb,
    satellites: [dxb],
    links: [],
}

describe('Poster', () => {
    it('shows the registration QR and its address, named for the satellite', () => {
        const wrapper = mount(Poster, { props: base })

        expect(wrapper.find('[data-testid="poster-qr"]').attributes('src')).toBe(
            'data:image/svg+xml;base64,AAA',
        )
        expect(wrapper.find('[data-testid="poster-url"]').text()).toBe(
            'https://app.test/ccf/register',
        )
        expect(wrapper.text()).toContain('CCF DXB kids church')
    })

    it('shows each Link as a QR code with its label', () => {
        const wrapper = mount(Poster, {
            props: {
                ...base,
                links: [
                    {
                        label: 'WhatsApp group',
                        url: 'https://chat.whatsapp.com/abc',
                        qr: 'data:image/svg+xml;base64,BBB',
                    },
                ],
            },
        })

        const link = wrapper.find('[data-testid="poster-link"]')
        expect(link.find('img').attributes('src')).toBe('data:image/svg+xml;base64,BBB')
        expect(link.find('img').attributes('alt')).toBe('QR code for WhatsApp group')
        expect(link.text()).toBe('WhatsApp group')
    })

    it('picks a satellite only for someone in more than one', async () => {
        expect(
            mount(Poster, { props: base }).find('[data-testid="poster-satellite"]').exists(),
        ).toBe(false)

        const wrapper = mount(Poster, { props: { ...base, satellites: [dxb, house] } })
        await wrapper.find('[data-testid="poster-satellite"]').setValue('4')

        expect(routerGet).toHaveBeenCalledWith('nxtgen.serve.poster:"ccf"', { satellite: 4 })
    })
})
