<script setup lang="ts">
import FamilySearchResultCard from '@/components/molecules/FamilySearchResultCard.vue'
import { useChurch } from '@/composables/useChurch'
import type { DuplicatePair, DuplicateStatus } from '@/types'
import { route } from 'ziggy-js'

/**
 * One possible duplicate on a family's page (plan §6): the newer record's family beside the earlier one — a pair is
 * found at registration and also when a guardian or child is added later — each in the church-wide lite view (a side
 * opens only when the viewer may open it), with the two answers. "Not the same
 * family" clears a false flag; "Same family" flags the pair for the later merge — nothing is merged here.
 */
defineProps<{ pair: DuplicatePair }>()

const emit = defineEmits<{ answer: [pairId: number, status: Exclude<DuplicateStatus, 'open'>] }>()

const church = useChurch()

const familyHref = (id: number): string => route('church.families.show', [church.value.slug, id])
</script>

<template>
    <article
        class="border-border bg-surface flex flex-col gap-3 rounded-lg border p-4"
        data-testid="duplicate-pair"
    >
        <p class="text-text font-semibold">Possible duplicate: {{ pair.reason_label }}</p>
        <div class="grid gap-3 md:grid-cols-2">
            <div class="flex flex-col gap-1">
                <p class="text-text-muted">Added more recently</p>
                <FamilySearchResultCard
                    :result="pair.subject"
                    :href="familyHref(pair.subject.id)"
                    :can-add-child="false"
                />
            </div>
            <div class="flex flex-col gap-1">
                <p class="text-text-muted">Registered earlier</p>
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
                @click="emit('answer', pair.id, 'dismissed')"
            >
                Not the same family
            </button>
            <button
                type="button"
                class="min-h-touch bg-primary text-on-primary rounded-md px-4 text-lg font-semibold"
                data-testid="confirm-duplicate"
                @click="emit('answer', pair.id, 'confirmed')"
            >
                Same family — flag for merging
            </button>
        </div>
    </article>
</template>
