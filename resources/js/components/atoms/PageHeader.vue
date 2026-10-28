<script setup lang="ts">
/**
 * The top of a page's content (the Bold look): its one `h1`, an optional line under it (`sub`), and the page's own
 * buttons on the right (`#actions`) — they wrap under the title on a phone. `titleTestid` marks the heading.
 */
withDefaults(defineProps<{ title: string; sub?: string; titleTestid?: string }>(), {
    sub: undefined,
    titleTestid: undefined,
})
</script>

<template>
    <header class="flex flex-wrap items-end justify-between gap-3">
        <div class="flex min-w-0 flex-col gap-0.5">
            <h1 class="text-text text-2xl font-bold sm:text-3xl" :data-testid="titleTestid">
                {{ title }}
            </h1>
            <p v-if="sub" class="text-text-muted text-lg">{{ sub }}</p>
        </div>
        <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </header>
</template>
