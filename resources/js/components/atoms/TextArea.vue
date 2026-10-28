<script setup lang="ts">
import { computed, useAttrs, useId } from 'vue'

/**
 * A labelled box for a few lines of text (the Bold look), the same frame as TextField: hint and error tied to it
 * (`aria-describedby`). Attributes (`data-testid`, `placeholder`…) land on the textarea; `class` on the
 * field.
 */
defineOptions({ inheritAttrs: false })

const props = withDefaults(
    defineProps<{
        label: string
        hint?: string
        error?: string
        required?: boolean
        rows?: number
    }>(),
    { hint: undefined, error: undefined, rows: 4 },
)
const model = defineModel<string | null>({ default: '' })

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
</script>

<template>
    <div class="flex min-w-0 flex-col gap-1.5" :class="attrs.class">
        <label :for="id" class="text-text text-lg font-semibold"
            >{{ label
            }}<span v-if="required" class="text-danger" aria-hidden="true"> *</span></label
        >
        <p v-if="hint" :id="`${id}-hint`" class="text-text-muted text-base">{{ hint }}</p>
        <textarea
            :id="id"
            v-model="model"
            v-bind="controlAttrs"
            :rows="rows"
            :required="required"
            :aria-invalid="error ? 'true' : undefined"
            :aria-describedby="describedBy"
            class="rounded-control bg-surface text-text focus:border-primary w-full min-w-0 border-2 border-b-4 px-4 py-3 text-lg"
            :class="error ? 'border-danger' : 'border-border-strong'"
        />
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
