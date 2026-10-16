import type { EnumMap, EnumProps, Option } from '@/types'
import { usePage } from '@inertiajs/vue3'
import { computed, type ComputedRef } from 'vue'

/**
 * One backend enum, as the page's Feature shared it under `enums` (`'enums' => ['gender' =>
 * GenderEnum::toFrontend()]`) — aegis's `useEnum`. The options come from the enum itself, so a form never
 * keeps its own copy of the cases or their wording. (A label to SHOW for a saved value comes in the DTO as a
 * `…_label` field, so there is one way to name a value on screen.)
 *
 * Throws when the page was not given the enum: a missed Feature should fail loudly where it is used, not
 * render an empty select.
 */
export function useEnum<K extends keyof EnumMap>(
    name: K,
): {
    options: ComputedRef<Option<EnumMap[K]>[]>
} {
    const page = usePage<{ enums?: EnumProps }>()

    const options = computed<Option<EnumMap[K]>[]>(() => {
        const list = page.props.enums?.[name]
        if (list === undefined) {
            throw new Error(
                `[useEnum] '${name}' is not in page.props.enums — share it from the page's Feature (XEnum::toFrontend()).`,
            )
        }
        return list
    })

    return { options }
}
