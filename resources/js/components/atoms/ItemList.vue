<script setup lang="ts">
import { computed } from 'vue'

/**
 * Typed text shown as its items, one chip each (Roy 2026-11-07): a comma separates them — "asthma, eczema" is two.
 * For allergies and illnesses; the text is stored as typed, so Excel and the forms keep it whole. The chip is
 * white with a hairline and takes the text colour around it (red inside the allergy warning).
 */
const props = defineProps<{ text: string }>()

const items = computed(() =>
    props.text
        .split(',')
        .map((item) => item.trim())
        .filter((item) => item !== ''),
)
</script>

<template>
    <ul class="inline-flex flex-wrap gap-1.5 align-middle">
        <li
            v-for="(item, index) in items"
            :key="index"
            class="bg-surface border-border rounded-full border px-3 py-0.5"
        >
            {{ item }}
        </li>
    </ul>
</template>
