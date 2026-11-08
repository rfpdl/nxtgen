import ResendLinkButton from '@/components/atoms/ResendLinkButton.vue'
import { makeSatellite } from '@/tests/satellite'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

vi.mock('@inertiajs/vue3', () => ({
    Link: {
        props: ['href', 'method', 'as', 'preserveScroll'],
        template:
            '<a :href="href" :data-method="method" :data-as="as" :data-preserve-scroll="String(preserveScroll)"><slot /></a>',
    },
    usePage: () => ({ props: { satellite: makeSatellite() } }),
}))

vi.mock('ziggy-js', () => ({
    route: (name: string, params: unknown[]) => `/#${name}/${params.join('/')}`,
}))

describe('ResendLinkButton', () => {
    it("posts to the family's resend route at this satellite, keeping the scroll", () => {
        const button = mount(ResendLinkButton, { props: { familyId: 7 } }).find(
            '[data-testid="resend"]',
        )

        expect(button.text()).toBe('Send the confirmation link again')
        expect(button.attributes('href')).toBe('/#serve.families.resend/dubai/7')
        expect(button.attributes('data-method')).toBe('post')
        expect(button.attributes('data-as')).toBe('button')
        expect(button.attributes('data-preserve-scroll')).toBe('true')
    })
})
