<script setup lang="ts">
import { computed, useAttrs, useId, useSlots } from 'vue'

/**
 * A labelled text input (the Bold look): 56px tall, a 2px edge with a thicker bottom, an optional icon in front
 * (`#leading`). The hint and the error are tied to the input (`aria-describedby`) so a screen reader says them;
 * `hideLabel` keeps the label for a screen reader only. Attributes (`data-testid`, `placeholder`…) land on the
 * input; `class` on the field.
 */
defineOptions({ inheritAttrs: false })

const props = withDefaults(
    defineProps<{
        label: string
        type?: string
        hint?: string
        error?: string
        required?: boolean
        hideLabel?: boolean
    }>(),
    { type: 'text', hint: undefined, error: undefined },
)
const model = defineModel<string | number | null>({ default: '' })

const id = useId()
const attrs = useAttrs()
/** `class` sizes the field (its frame); everything else is the control's. */
const controlAttrs = computed(() =>
    Object.fromEntries(Object.entries(attrs).filter(([key]) => key !== 'class')),
)
const slots = useSlots()
const describedBy = computed(
    () =>
        [props.hint ? `${id}-hint` : null, props.error ? `${id}-error` : null]
            .filter(Boolean)
            .join(' ') || undefined,
)
</script>

<template>
    <div class="flex min-w-0 flex-col gap-1.5" :class="attrs.class">
        <label :for="id" class="text-text text-lg font-semibold" :class="{ 'sr-only': hideLabel }"
            >{{ label
            }}<span v-if="required" class="text-danger" aria-hidden="true"> *</span></label
        >
        <p v-if="hint" :id="`${id}-hint`" class="text-text-muted text-base">{{ hint }}</p>
        <div class="relative flex items-center">
            <span
                v-if="slots.leading"
                class="text-text-muted pointer-events-none absolute left-4 flex"
                aria-hidden="true"
                ><slot name="leading"
            /></span>
            <input
                :id="id"
                v-model="model"
                v-bind="controlAttrs"
                :type="type"
                :required="required"
                :aria-invalid="error ? 'true' : undefined"
                :aria-describedby="describedBy"
                class="min-h-touch-door rounded-control bg-surface text-text focus:border-primary w-full min-w-0 border-2 border-b-4 px-4 text-lg"
                :class="[
                    slots.leading ? 'pl-12' : null,
                    error ? 'border-danger' : 'border-border-strong',
                ]"
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
