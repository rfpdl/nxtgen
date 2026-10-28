<script setup lang="ts">
import { ChevronDown } from 'lucide-vue-next'
import { computed, useAttrs, useId } from 'vue'

/**
 * A labelled pick-one list (the Bold look), the same frame as TextField. The model is the option's own value — a
 * number stays a number — and the empty `placeholder` choice is `null`. Hint and error are tied to it
 * (`aria-describedby`). Attributes (`data-testid`…) land on the select; `class` on the field.
 */
defineOptions({ inheritAttrs: false })

type Value = string | number

const props = withDefaults(
    defineProps<{
        label: string
        options: { value: Value; label: string }[]
        placeholder?: string
        hint?: string
        error?: string
        required?: boolean
        hideLabel?: boolean
    }>(),
    { placeholder: undefined, hint: undefined, error: undefined },
)
const model = defineModel<Value | null>({ default: null })

const id = useId()
const attrs = useAttrs()
/** `class` sizes the field (its frame); everything else is the control's. */
const controlAttrs = computed(() =>
    Object.fromEntries(Object.entries(attrs).filter(([key]) => key !== 'class')),
)
const describedBy = computed(
    () =>
        [props.hint ? `${id}-hint` : null, props.error ? `${id}-error` : null]
            .filter(Boolean)
            .join(' ') || undefined,
)

function onChange(event: Event): void {
    const raw = (event.target as HTMLSelectElement).value
    model.value =
        raw === '' ? null : (props.options.find((o) => String(o.value) === raw)?.value ?? raw)
}
</script>

<template>
    <div class="flex min-w-0 flex-col gap-1.5" :class="attrs.class">
        <label :for="id" class="text-text text-lg font-semibold" :class="{ 'sr-only': hideLabel }"
            >{{ label
            }}<span v-if="required" class="text-danger" aria-hidden="true"> *</span></label
        >
        <p v-if="hint" :id="`${id}-hint`" class="text-text-muted text-base">{{ hint }}</p>
        <div class="relative flex">
            <select
                :id="id"
                v-bind="controlAttrs"
                :value="model ?? ''"
                :required="required"
                :aria-invalid="error ? 'true' : undefined"
                :aria-describedby="describedBy"
                class="min-h-touch-door rounded-control bg-surface text-text focus:border-primary w-full min-w-0 appearance-none border-2 border-b-4 pr-12 pl-4 text-lg"
                :class="error ? 'border-danger' : 'border-border-strong'"
                @change="onChange"
            >
                <option v-if="placeholder !== undefined" value="">{{ placeholder }}</option>
                <option v-for="option in options" :key="option.value" :value="option.value">
                    {{ option.label }}
                </option>
            </select>
            <ChevronDown
                class="text-text pointer-events-none absolute top-1/2 right-4 size-5 -translate-y-1/2"
                aria-hidden="true"
            />
        </div>
        <p
            v-if="error"
            :id="`${id}-error`"
            role="alert"
            class="text-danger text-base font-semibold"
        >
            {{ error }}
        </p>
    </div>
</template>
