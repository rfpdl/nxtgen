import { areaRoute, inAdminArea } from '@/composables/useArea'
import { describe, expect, it, vi } from 'vitest'

const page: { props: { church_nav: { area: string | null } | null } } = {
    props: { church_nav: { area: 'admin' } },
}
const route = vi.fn((name: string, params?: unknown) => `${name}:${JSON.stringify(params)}`)

vi.mock('@inertiajs/vue3', () => ({ usePage: () => page }))
vi.mock('ziggy-js', () => ({ route: (name: string, params?: unknown) => route(name, params) }))

describe('areaRoute', () => {
    it('builds a family page in the staff area the page is in — Admin stays in Admin', () => {
        expect(areaRoute('families.show', ['dubai', 3])).toBe('admin.families.show:["dubai",3]')

        page.props.church_nav = { area: 'serve' }
        expect(areaRoute('families.show', ['dubai', 3])).toBe('serve.families.show:["dubai",3]')
    })

    it('uses Serve from a page with no area', () => {
        page.props.church_nav = { area: null }
        expect(areaRoute('families.index', 'dubai')).toBe('serve.families.index:"dubai"')

        page.props.church_nav = null
        expect(areaRoute('families.index', 'dubai')).toBe('serve.families.index:"dubai"')
    })

    it('says whether the page is in Admin', () => {
        page.props.church_nav = { area: 'admin' }
        expect(inAdminArea()).toBe(true)

        page.props.church_nav = { area: 'serve' }
        expect(inAdminArea()).toBe(false)
    })
})
