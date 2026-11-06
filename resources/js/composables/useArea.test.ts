import { areaRoute, inAdminArea } from '@/composables/useArea'
import { describe, expect, it, vi } from 'vitest'

const page: { props: { church_nav: { area: string | null } | null } } = {
    props: { church_nav: { area: 'nxtgen.admin' } },
}
const route = vi.fn((name: string, params?: unknown) => `${name}:${JSON.stringify(params)}`)

vi.mock('@inertiajs/vue3', () => ({ usePage: () => page }))
vi.mock('ziggy-js', () => ({ route: (name: string, params?: unknown) => route(name, params) }))

describe('areaRoute', () => {
    it('builds a family page in the NxtGen area the page is in — Admin stays in Admin', () => {
        expect(areaRoute('families.show', ['ccf', 3])).toBe('nxtgen.admin.families.show:["ccf",3]')

        page.props.church_nav = { area: 'nxtgen.serve' }
        expect(areaRoute('families.show', ['ccf', 3])).toBe('nxtgen.serve.families.show:["ccf",3]')
    })

    it('uses Serve from a church-wide Admin page, or a page with no area', () => {
        page.props.church_nav = { area: 'admin' }
        expect(areaRoute('families.index', 'ccf')).toBe('nxtgen.serve.families.index:"ccf"')

        page.props.church_nav = null
        expect(areaRoute('families.index', 'ccf')).toBe('nxtgen.serve.families.index:"ccf"')
    })

    it('says whether the page is in NxtGen Admin', () => {
        page.props.church_nav = { area: 'nxtgen.admin' }
        expect(inAdminArea()).toBe(true)

        page.props.church_nav = { area: 'nxtgen.serve' }
        expect(inAdminArea()).toBe(false)
    })
})
