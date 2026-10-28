<script setup lang="ts">
/**
 * The heading of a part of a page (the Bold look): an `h2` under the page's PageHeader, or an `h3` (`level="3"`)
 * inside a card. One size per level, so every section on every page starts the same way.
 */
withDefaults(defineProps<{ level?: '2' | '3' }>(), { level: '2' })
</script>

<template>
    <component
        :is="`h${level}`"
        class="text-text font-bold"
        :class="level === '2' ? 'text-2xl' : 'text-xl'"
    >
        <slot />
    </component>
</template>
