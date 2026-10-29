import type { EnumProps } from '@/types'

/**
 * A page's `enums` prop as the server sends it (ProvidesEnumMetadata::toFrontend), for tests that mount a
 * component reading `useEnum()`:
 *
 *     vi.mock('@inertiajs/vue3', async (original) =>
 *         (await import('@/tests/enumPage')).withEnums(await original()),
 *     )
 */
export const enums: Required<EnumProps> = {
    gender: [
        { value: 'boy', label: 'Boy', color: 'muted' },
        { value: 'girl', label: 'Girl', color: 'muted' },
    ],
    relationship: [
        { value: 'mother', label: 'Mother', color: 'muted' },
        { value: 'father', label: 'Father', color: 'muted' },
        { value: 'guardian', label: 'Guardian', color: 'muted' },
        { value: 'grandparent', label: 'Grandparent', color: 'muted' },
        { value: 'aunt_uncle', label: 'Aunt / uncle', color: 'muted' },
        { value: 'sibling', label: 'Sibling', color: 'muted' },
        { value: 'other', label: 'Other', color: 'muted' },
    ],
    child_status: [
        { value: 'active', label: 'Active', color: 'success' },
        { value: 'inactive', label: 'Inactive', color: 'muted' },
        { value: 'graduated', label: 'Graduated', color: 'info' },
    ],
    weekday: [
        { value: 'monday', label: 'Monday', color: 'muted' },
        { value: 'tuesday', label: 'Tuesday', color: 'muted' },
        { value: 'wednesday', label: 'Wednesday', color: 'muted' },
        { value: 'thursday', label: 'Thursday', color: 'muted' },
        { value: 'friday', label: 'Friday', color: 'muted' },
        { value: 'saturday', label: 'Saturday', color: 'muted' },
        { value: 'sunday', label: 'Sunday', color: 'muted' },
    ],
    card_reason: [
        { value: 'lost', label: 'Lost', color: 'muted' },
        { value: 'damaged', label: 'Damaged', color: 'muted' },
    ],
}

/** Inertia as the real module, except that its page carries `enums`. */
export function withEnums<T extends object>(
    inertia: T,
): T & { usePage: () => { props: { enums: Required<EnumProps> } } } {
    return { ...inertia, usePage: () => ({ props: { enums } }) }
}
