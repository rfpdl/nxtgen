<script setup lang="ts">
/**
 * What a list says when it has nothing in it (the Bold look): a dashed panel with a sentence that tells the
 * person what to do next (the slot), an optional bold `title`, and an optional button (`#action`). Never a blank
 * space — an empty screen looks broken.
 */
withDefaults(defineProps<{ title?: string }>(), { title: undefined })
</script>

<template>
    <div
        class="border-border-strong rounded-card text-text flex flex-col items-center gap-3 border-2 border-dashed p-6 text-center"
    >
        <p v-if="title" class="text-xl font-bold">{{ title }}</p>
        <p class="text-lg"><slot /></p>
        <slot name="action" />
    </div>
</template>
