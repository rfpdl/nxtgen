<script setup lang="ts">
import Card from '@/components/atoms/Card.vue'
import CheckRow from '@/components/atoms/CheckRow.vue'
import Pill from '@/components/atoms/Pill.vue'
import type { WhosInSquad } from '@/types'

/**
 * One Squad on Who's in (roll call, DECISIONS 2026-10-28): the Squad as a Pill in its Level's colour and "N of M
 * present", then each child checked in — the whole row is the tick (CheckRow, 64px tall): the nickname and the
 * allergy flag ⚠ as at the door. A tap asks the page to mark the child present, or unmark them.
 */
defineProps<{ squad: WhosInSquad }>()
const emit = defineEmits<{ present: [checkInId: number, present: boolean] }>()
</script>

<template>
    <Card as="section" flush data-testid="whos-in-squad">
        <h2
            class="border-border flex flex-wrap items-center justify-between gap-x-3 gap-y-1 border-b-2 px-4 py-3"
        >
            <Pill :level="squad.level" class="text-lg"
                ><span data-testid="squad-name">{{ squad.name }}</span></Pill
            >
            <span class="text-text text-lg font-semibold tabular-nums" data-testid="squad-present"
                >{{ squad.present }} of {{ squad.count }} present</span
            >
        </h2>
        <ul class="divide-border flex flex-col divide-y-2">
            <li
                v-for="child in squad.children"
                :key="child.id"
                class="px-3"
                data-testid="whos-in-child"
            >
                <CheckRow
                    :model-value="child.present"
                    data-testid="present-toggle"
                    @update:model-value="(on: boolean) => emit('present', child.id, on)"
                >
                    <span class="flex flex-wrap items-center gap-x-3 gap-y-1 py-2">
                        <span class="text-text text-xl font-bold" data-testid="child-name">{{
                            child.nickname
                        }}</span>
                        <Pill v-if="child.has_allergies" variant="danger" data-testid="allergy-flag"
                            >⚠ Allergies</Pill
                        >
                    </span>
                </CheckRow>
            </li>
        </ul>
    </Card>
</template>
