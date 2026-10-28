<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import Card from '@/components/atoms/Card.vue'
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
    <Card as="article" class="flex flex-col gap-3" data-testid="duplicate-pair">
        <p class="text-text text-lg font-bold">Possible duplicate: {{ pair.reason_label }}</p>
        <div class="grid gap-3 md:grid-cols-2">
            <div class="flex min-w-0 flex-col gap-1">
                <p class="text-text font-semibold">Added more recently</p>
                <FamilySearchResultCard
                    :result="pair.subject"
                    :href="familyHref(pair.subject.id)"
                    :can-add-child="false"
                />
            </div>
            <div class="flex min-w-0 flex-col gap-1">
                <p class="text-text font-semibold">Registered earlier</p>
                <FamilySearchResultCard
                    :result="pair.candidate"
                    :href="familyHref(pair.candidate.id)"
                    :can-add-child="false"
                />
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            <AppButton
                variant="secondary"
                data-testid="dismiss-duplicate"
                @click="emit('answer', pair.id, 'dismissed')"
            >
                Not the same family
            </AppButton>
            <AppButton
                data-testid="confirm-duplicate"
                @click="emit('answer', pair.id, 'confirmed')"
            >
                Same family — flag for merging
            </AppButton>
        </div>
    </Card>
</template>
