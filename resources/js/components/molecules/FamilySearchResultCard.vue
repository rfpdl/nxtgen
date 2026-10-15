<script setup lang="ts">
import type { FamilySearchResult } from '@/types'

/**
 * A church-wide search hit when registering for a parent: "already registered?" at a glance. The email is
 * masked; the family opens only when the volunteer's scope allows it.
 */
defineProps<{ result: FamilySearchResult; href: string }>()

defineEmits<{ addChild: [] }>()
</script>

<template>
    <article
        class="border-border bg-surface flex flex-col gap-1 rounded-lg border p-4"
        data-testid="search-result"
    >
        <a
            v-if="result.canOpen"
            :href="href"
            class="text-primary text-xl font-semibold underline"
            >{{ result.primaryName }}</a
        >
        <h3 v-else class="text-text text-xl font-semibold">{{ result.primaryName }}</h3>
        <p class="text-text">
            {{ result.maskedEmail
            }}<span v-if="!result.emailConfirmed" class="text-warning ml-2 font-semibold"
                >Email not confirmed</span
            >
        </p>
        <p class="text-text-muted">{{ result.satellite }} · {{ result.children.join(', ') }}</p>
        <button
            type="button"
            class="min-h-touch text-primary self-start text-lg font-semibold underline"
            data-testid="search-add-child"
            @click="$emit('addChild')"
        >
            Add a child to this family
        </button>
    </article>
</template>
