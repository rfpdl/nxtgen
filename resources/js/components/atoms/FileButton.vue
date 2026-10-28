<script setup lang="ts">
/**
 * A button that opens the file picker (the Bold look: a secondary AppButton's look and press). It is a label around
 * a file input kept for a screen reader only, so the camera or the photo library opens on a tap; the focus ring
 * shows on the button. Attributes (`data-testid`…) land on the input; `class` on the button. `change` is the
 * input's own event.
 */
defineOptions({ inheritAttrs: false })

withDefaults(defineProps<{ accept?: string; capture?: 'user' | 'environment' }>(), {
    accept: undefined,
    capture: undefined,
})

defineEmits<{ change: [event: Event] }>()
</script>

<template>
    <label
        class="rounded-control bg-surface text-text border-control-edge shadow-press-control hover:bg-surface-sunken has-[input:focus-visible]:outline-focus inline-flex min-h-12 cursor-pointer items-center justify-center gap-2 border-2 px-4 text-lg font-semibold transition-transform select-none active:translate-y-[5px] active:shadow-none has-[input:focus-visible]:outline-[3px] has-[input:focus-visible]:outline-offset-2 has-[input:focus-visible]:outline-solid"
        :class="$attrs.class"
    >
        <slot />
        <input
            v-bind="{ ...$attrs, class: undefined }"
            type="file"
            :accept="accept"
            :capture="capture"
            class="sr-only"
            @change="$emit('change', $event)"
        />
    </label>
</template>
