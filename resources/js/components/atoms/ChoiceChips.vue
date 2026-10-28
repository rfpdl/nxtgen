<script setup lang="ts">
import { RadioGroupItem, RadioGroupRoot } from 'reka-ui'

/**
 * Pick one of a few, as big pressable chips (the Bold look) — the door's class picker, instead of a dropdown that
 * hides the choice. A radio group to a screen reader and the keyboard (reka-ui: one Tab stop, arrow keys move),
 * named by `label`; the model is the option's own value. A long row scrolls sideways inside itself.
 */
type Value = string | number

defineProps<{
    label: string
    options: { value: Value; label: string; sub?: string | null }[]
}>()
const model = defineModel<Value | null>({ required: true })
</script>

<template>
    <RadioGroupRoot
        v-model="model"
        :aria-label="label"
        orientation="horizontal"
        loop
        class="flex gap-2 overflow-x-auto pb-2"
    >
        <RadioGroupItem
            v-for="option in options"
            :key="option.value"
            :value="option.value"
            :data-value="option.value"
            class="rounded-control flex min-h-14 flex-none flex-col justify-center border-2 border-b-4 px-4 text-left text-lg leading-tight font-bold"
            :class="
                option.value === model
                    ? 'border-primary bg-primary-soft text-primary'
                    : 'border-border-strong bg-surface text-text hover:bg-surface-sunken'
            "
        >
            {{ option.label }}
            <span v-if="option.sub" class="text-base font-medium">{{ option.sub }}</span>
        </RadioGroupItem>
    </RadioGroupRoot>
</template>
