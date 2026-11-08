import Poster from '@/pages/Church/Poster.vue'
import { makeSatellite } from '@/tests/satellite'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    usePage: () => ({ props: { satellite: makeSatellite({ name: 'CCF DXB' }) } }),
}))

vi.mock('ziggy-js', () => ({
    route: (name: string, params: unknown) => `${name}:${JSON.stringify(params)}`,
}))

const base = {
    register_url: 'https://app.test/dubai/register',
    qr: 'data:image/svg+xml;base64,AAA',
    links: [],
}

describe('Poster', () => {
    it('shows the registration QR and its address, named for the satellite', () => {
        const wrapper = mount(Poster, { props: base })

        expect(wrapper.find('[data-testid="poster-qr"]').attributes('src')).toBe(
            'data:image/svg+xml;base64,AAA',
        )
        expect(wrapper.find('[data-testid="poster-url"]').text()).toBe(
            'https://app.test/dubai/register',
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

    // DECISIONS 2026-11-08: the poster is the address satellite's — no satellite picker; Back stays at that satellite.
    it("is the address satellite's poster: no satellite picker, and Back to its Families", () => {
        const wrapper = mount(Poster, { props: base })

        expect(wrapper.find('[data-testid="poster-satellite"]').exists()).toBe(false)
        expect(wrapper.find('a').attributes('href')).toBe('serve.families.index:"dubai"')
    })
})
