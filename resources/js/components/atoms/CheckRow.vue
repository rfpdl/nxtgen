<script setup lang="ts">
import { Check } from 'lucide-vue-next'
import { useSlots } from 'vue'

/**
 * A big tick (the Bold look): a 30px box in a row at least 64px tall, ticked = green with a white check. A real
 * checkbox to a screen reader (`role="checkbox"`, `aria-checked`). With a default slot the whole row is the tick
 * and the slot is its label; without one it is a 64px square, named by `label`, beside content of its own —
 * `stretch` makes that square a 64px-wide zone the full height of its row, the box at the top (the door's child row).
 */
withDefaults(defineProps<{ disabled?: boolean; label?: string; stretch?: boolean }>(), {
    label: undefined,
})
const checked = defineModel<boolean>({ default: false })
const slots = useSlots()
</script>

<template>
    <button
        type="button"
        role="checkbox"
        :aria-checked="checked ? 'true' : 'false'"
        :aria-label="label"
        :disabled="disabled"
        class="rounded-control text-text flex min-h-16 gap-3 text-left disabled:cursor-not-allowed"
        :class="[
            slots.default ? 'w-full pr-3' : 'min-w-16 justify-center',
            stretch ? 'items-start self-stretch pt-[1.0625rem]' : 'items-center',
        ]"
        @click="checked = !checked"
    >
        <span
            class="rounded-tick grid size-[1.875rem] shrink-0 place-items-center border-[3px]"
            :class="
                checked
                    ? 'bg-action border-action text-on-action'
                    : disabled
                      ? 'border-border bg-surface-sunken'
                      : 'border-border-strong bg-surface'
            "
            data-testid="tick-box"
        >
            <Check v-if="checked" class="size-5" :stroke-width="3.2" aria-hidden="true" />
        </span>
        <span v-if="slots.default" class="min-w-0 flex-1"><slot /></span>
    </button>
</template>
