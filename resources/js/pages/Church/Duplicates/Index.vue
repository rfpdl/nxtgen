<script setup lang="ts">
import ChurchShell from '@/components/layout/ChurchShell.vue'
import FamilySearchResultCard from '@/components/molecules/FamilySearchResultCard.vue'
import { useChurch } from '@/composables/useChurch'
import type { DuplicatePair, ResolveDuplicateInput } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

/**
 * Possible duplicates to review (plan §6): the new family beside the existing one. "Not the same" dismisses;
 * "Same family" flags the pair for the later merge — nothing is merged here.
 */
defineProps<{ duplicates: DuplicatePair[] }>()

const church = useChurch()

const familyHref = (id: number): string => route('church.families.show', [church.value.slug, id])

function resolve(pairId: number, status: 'dismissed' | 'confirmed'): void {
    const body: ResolveDuplicateInput = { status }
    router.put(route('church.duplicates.resolve', [church.value.slug, pairId]), body, {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Possible duplicates" />
    <ChurchShell>
        <h1 class="text-text text-2xl font-semibold">
            Possible duplicates ({{ duplicates.length }})
        </h1>
        <p v-if="duplicates.length === 0" class="text-text-muted">Nothing to review.</p>
        <article
            v-for="pair in duplicates"
            :key="pair.id"
            class="border-border bg-surface flex flex-col gap-3 rounded-lg border p-4"
            data-testid="duplicate-pair"
        >
            <p class="text-text font-semibold">{{ pair.reason_label }}</p>
            <div class="grid gap-3 md:grid-cols-2">
                <div class="flex flex-col gap-1">
                    <p class="text-text-muted">Just registered</p>
                    <FamilySearchResultCard
                        :result="pair.subject"
                        :href="familyHref(pair.subject.id)"
                        :can-add-child="false"
                    />
                </div>
                <div class="flex flex-col gap-1">
                    <p class="text-text-muted">Already here</p>
                    <FamilySearchResultCard
                        :result="pair.candidate"
                        :href="familyHref(pair.candidate.id)"
                        :can-add-child="false"
                    />
                </div>
            </div>
            <div class="flex flex-wrap gap-3">
                <button
                    type="button"
                    class="min-h-touch border-border bg-surface text-text rounded-md border px-4 text-lg"
                    data-testid="dismiss-duplicate"
                    @click="resolve(pair.id, 'dismissed')"
                >
                    Not the same family
                </button>
                <button
                    type="button"
                    class="min-h-touch bg-primary text-on-primary rounded-md px-4 text-lg font-semibold"
                    data-testid="confirm-duplicate"
                    @click="resolve(pair.id, 'confirmed')"
                >
                    Same family — flag for merging
                </button>
            </div>
        </article>
    </ChurchShell>
</template>
