<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import CheckRow from '@/components/atoms/CheckRow.vue'
import Pill from '@/components/atoms/Pill.vue'
import SelectField from '@/components/atoms/SelectField.vue'
import type { DoorChild, LevelOption } from '@/types'
import { HeartPulse } from 'lucide-vue-next'
import { computed } from 'vue'

/**
 * One child on a door family card (DECISIONS 2026-10-26): a box to tick, the nickname with a Health button beside it
 * (the health details), this satellite's Squad and an allergy flag ⚠ — no photo, no phone. A child who is in shows
 * ✓ and Undo. A Graduated child cannot be ticked. Ticking an inactive child asks "Make active again?"; ticking a
 * child with no Level asks for one, the age suggestion picked first. The answers are the card's (v-model).
 * On the kit (the Bold look): a big tick (CheckRow) beside the name rather than the whole row, because the row also
 * holds Health (pending Roy's OK); the Squad as a Level-coloured Pill and the allergy flag as a red one.
 */
const props = defineProps<{ child: DoorChild; levels: LevelOption[]; canCheckIn: boolean }>()
const ticked = defineModel<boolean>('ticked', { default: false })
const level = defineModel<number | null>('level', { default: null })
const makeActive = defineModel<boolean>('makeActive', { default: false })
const emit = defineEmits<{ undo: [checkInId: number]; health: [childId: number] }>()

const isIn = computed(() => props.child.check_in_id !== null)
const graduated = computed(() => props.child.status === 'graduated')
const inactive = computed(() => props.child.status === 'inactive')
const needsLevel = computed(() => props.child.level === null)

function tick(on: boolean): void {
    ticked.value = on
    if (on && needsLevel.value && level.value === null) {
        level.value = props.child.suggested_level
    }
    if (!on && makeActive.value) {
        makeActive.value = false
    }
}

const levelOptions = computed(() => props.levels.map((o) => ({ value: o.level, label: o.name })))

function onLevel(value: string | number | null): void {
    level.value = value === null ? null : Number(value)
}
</script>

<template>
    <li class="flex items-start gap-2 py-2" data-testid="door-child">
        <span
            v-if="isIn"
            class="bg-success-soft text-success rounded-tick grid size-16 shrink-0 place-items-center text-3xl font-bold"
            aria-hidden="true"
            >✓</span
        >
        <CheckRow
            v-else
            :model-value="ticked"
            :disabled="graduated || !canCheckIn"
            :label="`Tick ${child.nickname}`"
            data-testid="tick-child"
            @update:model-value="tick"
        />

        <div class="flex min-w-0 flex-1 flex-col gap-2 py-1">
            <p class="flex flex-wrap items-center gap-x-3 gap-y-1">
                <span
                    class="text-text text-xl font-bold"
                    :title="child.full_name"
                    data-testid="child-name"
                    >{{ child.nickname }}</span
                >
                <AppButton
                    variant="secondary"
                    size="sm"
                    :aria-label="`Health details for ${child.nickname}`"
                    data-testid="child-health-open"
                    @click="emit('health', child.id)"
                >
                    <HeartPulse class="size-5" aria-hidden="true" />Health
                </AppButton>
            </p>
            <p class="flex flex-wrap items-center gap-2">
                <Pill :level="child.level">{{ child.squad ?? 'No Level yet' }}</Pill>
                <Pill v-if="isIn" variant="success" data-testid="checked-in">✓ Checked in</Pill>
                <Pill
                    v-if="child.has_allergies"
                    variant="danger"
                    title="Has allergies — tap Health"
                    data-testid="allergy-flag"
                    >⚠ Allergies</Pill
                >
                <Pill v-if="inactive">Inactive</Pill>
                <span v-if="graduated" class="text-text-muted font-semibold"
                    >Graduated — cannot be checked in</span
                >
                <AppButton
                    v-if="isIn"
                    variant="secondary"
                    data-testid="undo"
                    @click="emit('undo', child.check_in_id ?? 0)"
                >
                    Undo
                </AppButton>
            </p>

            <div
                v-if="ticked && inactive"
                class="bg-warning-soft text-text rounded-control flex flex-wrap items-center gap-3 p-3"
                data-testid="make-active"
            >
                <p class="w-full">{{ child.nickname }} is inactive. Make them active again?</p>
                <p v-if="makeActive" class="text-success font-semibold">
                    Yes — {{ child.nickname }} will be made active.
                </p>
                <AppButton v-else data-testid="make-active-yes" @click="makeActive = true">
                    Yes, make active
                </AppButton>
                <AppButton variant="link" data-testid="make-active-no" @click="tick(false)">
                    No
                </AppButton>
            </div>

            <div v-if="ticked && needsLevel" class="bg-warning-soft rounded-control p-3">
                <SelectField
                    :label="`${child.nickname} has no Level yet. Pick one:`"
                    :options="levelOptions"
                    placeholder="Choose…"
                    :model-value="level"
                    data-testid="pick-level"
                    @update:model-value="onLevel"
                />
            </div>
        </div>
    </li>
</template>
