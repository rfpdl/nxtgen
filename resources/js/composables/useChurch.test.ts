import { useChurch } from '@/composables/useChurch'
import { describe, expect, it, vi } from 'vitest'

const page: { props: { church: { name: string; slug: string } | null } } = {
    props: { church: { name: 'CCF', slug: 'ccf' } },
}

vi.mock('@inertiajs/vue3', () => ({ usePage: () => page }))

describe('useChurch', () => {
    it('gives the church the page is about', () => {
        expect(useChurch().value).toEqual({ name: 'CCF', slug: 'ccf' })
    })

    it('fails loudly on a page outside a church', () => {
        page.props.church = null

        expect(() => useChurch().value).toThrow(/not under a church address/)

        page.props.church = { name: 'CCF', slug: 'ccf' }
    })
})
