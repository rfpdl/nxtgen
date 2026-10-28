<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import RollCallMark from '@/components/atoms/RollCallMark.vue'
import SectionHeading from '@/components/atoms/SectionHeading.vue'
import type { ChildCheckIn } from '@/types'
import { computed, ref } from 'vue'

/**
 * A child's attendance on the staff family page (slice 3, DECISIONS 2026-10-28 night), newest first as the server
 * sends it: "Sat 31 Oct · 1PM · CCF DXB · Level 2 Girls" and "Present ✓" or "Not marked" (RollCallMark). The last 12,
 * then "Show all N". Never a parent's: the server sends it to staff only. Empty: "Not checked in yet."
 */
const SHOWN = 12

const props = defineProps<{ checkIns: ChildCheckIn[] }>()

const all = ref(false)
const shown = computed(() => (all.value ? props.checkIns : props.checkIns.slice(0, SHOWN)))
</script>

<template>
    <section
        class="border-border flex flex-col gap-2 border-b-2 pb-3"
        data-testid="child-attendance"
    >
        <SectionHeading level="3">Attendance</SectionHeading>
        <p
            v-if="checkIns.length === 0"
            class="text-text-muted text-lg"
            data-testid="child-attendance-empty"
        >
            Not checked in yet.
        </p>
        <ul v-else class="divide-border flex flex-col divide-y-2">
            <li
                v-for="checkIn in shown"
                :key="checkIn.id"
                class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 py-2"
                data-testid="child-attendance-line"
            >
                <span class="text-text text-lg" data-testid="child-attendance-where"
                    >{{ checkIn.date }} · {{ checkIn.class }} · {{ checkIn.satellite }} ·
                    {{ checkIn.squad }}</span
                >
                <span data-testid="child-roll-call"
                    ><RollCallMark :present="checkIn.present"
                /></span>
            </li>
        </ul>
        <AppButton
            v-if="!all && checkIns.length > SHOWN"
            variant="link"
            data-testid="child-attendance-all"
            @click="all = true"
            >Show all {{ checkIns.length }}</AppButton
        >
    </section>
</template>
