<script setup lang="ts">
import type { DoorCheckIn } from '@/types'

/**
 * Who is checked in to the chosen class today, at the door (DECISIONS 2026-10-27): newest first as the server sends
 * them, each with the nickname (bold), this satellite's Squad, the time and Undo — so the page is never empty before
 * a search. Undo is the page's to send.
 */
defineProps<{ checkIns: DoorCheckIn[] }>()
const emit = defineEmits<{ undo: [checkInId: number] }>()
</script>

<template>
    <section class="flex flex-col gap-2" data-testid="checked-in-list">
        <h2 class="text-text text-xl font-semibold">Checked in ({{ checkIns.length }})</h2>
        <p v-if="checkIns.length === 0" class="text-text" data-testid="checked-in-empty">
            No one checked in yet. Search a child's or parent's name to check them in.
        </p>
        <ul
            v-else
            class="divide-border border-border bg-surface flex flex-col divide-y rounded-lg border"
        >
            <li
                v-for="checkIn in checkIns"
                :key="checkIn.id"
                class="flex items-center gap-3 px-4 py-2"
                data-testid="checked-in-row"
            >
                <p class="flex min-w-0 flex-1 flex-wrap items-baseline gap-x-2">
                    <span class="text-text text-lg font-bold" data-testid="checked-in-name">{{
                        checkIn.nickname
                    }}</span>
                    <span class="text-text-muted">{{ checkIn.squad }}</span>
                </p>
                <span class="text-text tabular-nums">{{ checkIn.checked_in_at }}</span>
                <button
                    type="button"
                    class="min-h-touch-door border-border-strong text-text rounded-md border px-4"
                    data-testid="checked-in-undo"
                    @click="emit('undo', checkIn.id)"
                >
                    Undo
                </button>
            </li>
        </ul>
    </section>
</template>
