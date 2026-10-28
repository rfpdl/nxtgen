<script setup lang="ts">
import { computed } from 'vue'

/**
 * An email address that wraps cleanly on a phone (the Bold look): it may break after the `@`, or after a `.`, `-` or
 * `_` in the name before it (a `<wbr>` there); the domain stays whole, so "maria@e2e.test" never splits as
 * "…@e2e.t / est" or "…@e2e. / test". Only a single piece longer than the line breaks anywhere (`wrap-anywhere`).
 */
const props = defineProps<{ email: string }>()

/** "a.b@c.d" → ["a.", "b@", "c.d"]: each piece ends where a line may break. */
const pieces = computed(() => {
    const at = props.email.lastIndexOf('@')
    const name = at === -1 ? props.email : props.email.slice(0, at + 1)
    const domain = at === -1 ? [] : [props.email.slice(at + 1)]
    return [...name.split(/(?<=[._-])/), ...domain].filter((piece) => piece !== '')
})
</script>

<template>
    <span class="wrap-anywhere"
        ><template v-for="(piece, i) in pieces" :key="i"
            ><wbr v-if="i > 0" />{{ piece }}</template
        ></span
    >
</template>
