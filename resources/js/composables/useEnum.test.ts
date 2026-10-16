import { useEnum } from '@/composables/useEnum'
import { enums } from '@/tests/enumPage'
import { describe, expect, it, vi } from 'vitest'

const page: { props: { enums?: Partial<typeof enums> } } = { props: { enums } }

vi.mock('@inertiajs/vue3', () => ({ usePage: () => page }))

describe('useEnum', () => {
    it('gives the options the page was given, in the server order', () => {
        const { options } = useEnum('child_status')

        expect(options.value.map((o) => o.value)).toEqual(['active', 'inactive', 'graduated'])
    })

    it('fails loudly when the page was not given the enum', () => {
        page.props = { enums: {} }

        expect(() => useEnum('gender').options.value).toThrow(/'gender' is not in page.props.enums/)

        page.props = { enums }
    })
})
