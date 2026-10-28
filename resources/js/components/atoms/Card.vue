<script setup lang="ts">
/**
 * A white card (the Bold look): rounded, a 2px edge and a thicker 5px bottom so it sits on the page like a tile.
 * `as` picks the element (`article` for a thing, `section` for a part of the page); `flush` drops the padding for
 * a card of rows that pad themselves.
 */
withDefaults(defineProps<{ as?: string; flush?: boolean }>(), { as: 'div' })
</script>

<template>
    <component
        :is="as"
        class="bg-surface border-border rounded-card min-w-0 border-2 border-b-[5px]"
        :class="flush ? 'overflow-hidden' : 'p-4'"
    >
        <slot />
    </component>
</template>
