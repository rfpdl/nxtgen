<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import Card from '@/components/atoms/Card.vue'
import EmptyState from '@/components/atoms/EmptyState.vue'
import Pill from '@/components/atoms/Pill.vue'
import type { DoorCheckIn } from '@/types'

/**
 * Who is checked in to the chosen class today, at the door (DECISIONS 2026-10-27): newest first as the server sends
 * them, each with the nickname (bold), this satellite's Squad, the time and Undo — so the page is never empty before
 * a search. Undo is the page's to send. The Squad is a Pill in its Level's colour (the Bold look).
 */
defineProps<{ checkIns: DoorCheckIn[] }>()
const emit = defineEmits<{ undo: [checkInId: number] }>()
</script>

<template>
    <section class="flex flex-col gap-2" data-testid="checked-in-list">
        <h2 class="text-text text-xl font-bold">Checked in</h2>
        <EmptyState v-if="checkIns.length === 0" data-testid="checked-in-empty"
            >No one checked in yet. Search a child's or parent's name to check them in.</EmptyState
        >
        <Card v-else as="ul" flush class="divide-border flex flex-col divide-y-2">
            <li
                v-for="checkIn in checkIns"
                :key="checkIn.id"
                class="flex min-h-16 items-center gap-3 px-4 py-2"
                data-testid="checked-in-row"
            >
                <p class="flex min-w-0 flex-1 flex-col items-start gap-1">
                    <span class="text-text text-lg font-bold" data-testid="checked-in-name">{{
                        checkIn.nickname
                    }}</span>
                    <Pill :level="checkIn.level">{{ checkIn.squad }}</Pill>
                </p>
                <span class="text-text-muted tabular-nums">{{ checkIn.checked_in_at }}</span>
                <AppButton
                    variant="secondary"
                    data-testid="checked-in-undo"
                    @click="emit('undo', checkIn.id)"
                >
                    Undo
                </AppButton>
            </li>
        </Card>
    </section>
</template>
