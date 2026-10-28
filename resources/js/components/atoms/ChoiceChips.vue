<script setup lang="ts">
import { RadioGroupItem, RadioGroupRoot } from 'reka-ui'
import { useId } from 'vue'

/**
 * Pick one of a few, as big pressable chips (the Bold look) — the door's class picker, instead of a dropdown that
 * hides the choice. A radio group to a screen reader and the keyboard (reka-ui: one Tab stop, arrow keys move),
 * named by `label` — or by a visible question elsewhere (`labelledby`, its id); the model is the option's own
 * value. Like TextField: `required`, and an `error` shown under the chips and tied to the group (`aria-describedby`,
 * `aria-invalid`). A long row scrolls sideways inside itself. An option's `testid` lands on its chip; other
 * attributes (`data-testid`…) on the group.
 */
defineOptions({ inheritAttrs: false })

type Value = string | number

withDefaults(
    defineProps<{
        label?: string
        labelledby?: string
        required?: boolean
        error?: string
        options: { value: Value; label: string; sub?: string | null; testid?: string }[]
    }>(),
    { label: undefined, labelledby: undefined, error: undefined },
)
const errorId = `${useId()}-error`
const model = defineModel<Value | null>({ required: true })
</script>

<template>
    <div class="flex min-w-0 flex-col gap-1.5">
        <RadioGroupRoot
            v-bind="$attrs"
            v-model="model"
            :aria-label="labelledby ? undefined : label"
            :aria-labelledby="labelledby"
            :required="required"
            :aria-invalid="error ? 'true' : undefined"
            :aria-describedby="error ? errorId : undefined"
            orientation="horizontal"
            loop
            class="flex gap-2 overflow-x-auto pb-2"
        >
            <RadioGroupItem
                v-for="option in options"
                :key="option.value"
                :value="option.value"
                :data-value="option.value"
                :data-testid="option.testid"
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
        <p v-if="error" :id="errorId" role="alert" class="text-danger text-base font-semibold">
            {{ error }}
        </p>
    </div>
</template>
