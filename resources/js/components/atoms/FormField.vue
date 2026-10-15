<script setup lang="ts">
/** One labelled form control: label, optional hint, the control (slot), and its error. */
defineProps<{ label: string; required?: boolean; hint?: string; error?: string }>()
</script>

<template>
    <label class="text-text flex flex-col gap-1.5">
        <span class="text-lg font-medium">
            {{ label }}<span v-if="required" class="text-danger" aria-hidden="true"> *</span>
        </span>
        <span v-if="hint" class="text-text-muted text-base">{{ hint }}</span>
        <slot />
        <span v-if="error" role="alert" class="text-danger text-base font-semibold">{{
            error
        }}</span>
    </label>
</template>
