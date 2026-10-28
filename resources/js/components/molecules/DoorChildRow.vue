<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import CheckRow from '@/components/atoms/CheckRow.vue'
import Disclosure from '@/components/atoms/Disclosure.vue'
import Pill from '@/components/atoms/Pill.vue'
import SelectField from '@/components/atoms/SelectField.vue'
import ChildHealthPanel from '@/components/molecules/ChildHealthPanel.vue'
import type { ChildHealth, DoorChild, LevelOption } from '@/types'
import { computed, ref } from 'vue'

/**
 * One child on a door family card (DECISIONS 2026-10-26, 2026-10-28). Two parts: a tick zone on the left, the full
 * height of the row (a Graduated child cannot be ticked), and the rest of the row, one button that opens the child's
 * details in place — nickname, this satellite's Squad as a Level-coloured Pill and the allergy flag ⚠ (red); open,
 * the health details, status and Level, and "Open family page" when the viewer may open the family (`familyHref`).
 * The health details are asked for (`health`) on the first open only; `health` undefined = not asked yet, null =
 * none to show. A child who is in shows ✓ and Undo. Ticking an inactive child asks "Make active again?"; ticking a
 * child with no Level asks for one, the age suggestion picked first. The answers are the card's (v-model).
 */
const props = withDefaults(
    defineProps<{
        child: DoorChild
        levels: LevelOption[]
        canCheckIn: boolean
        health?: ChildHealth | null
        familyHref?: string | null
    }>(),
    { health: undefined, familyHref: null },
)
const ticked = defineModel<boolean>('ticked', { default: false })
const level = defineModel<number | null>('level', { default: null })
const makeActive = defineModel<boolean>('makeActive', { default: false })
const emit = defineEmits<{ undo: [checkInId: number]; health: [childId: number] }>()

const open = ref(false)
const isIn = computed(() => props.child.check_in_id !== null)
const graduated = computed(() => props.child.status === 'graduated')
const inactive = computed(() => props.child.status === 'inactive')
const needsLevel = computed(() => props.child.level === null)
const levelName = computed(() => props.child.level?.name ?? 'No Level yet')

function toggle(on: boolean): void {
    open.value = on
    if (on && props.health === undefined) {
        emit('health', props.child.id)
    }
}

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
    <li class="flex items-stretch gap-1" data-testid="door-child">
        <span
            v-if="isIn"
            class="bg-success-soft text-success rounded-tick mt-2 grid size-16 shrink-0 place-items-center text-3xl font-bold"
            aria-hidden="true"
            >✓</span
        >
        <CheckRow
            v-else
            stretch
            :model-value="ticked"
            :disabled="graduated || !canCheckIn"
            :label="`Tick ${child.nickname}`"
            data-testid="tick-child"
            @update:model-value="tick"
        />

        <div class="flex min-w-0 flex-1 flex-col gap-2 pb-2">
            <Disclosure :open="open" data-testid="child-expand" @update:open="toggle">
                <span class="flex flex-col gap-2">
                    <span
                        class="text-text text-xl font-bold"
                        :title="child.full_name"
                        data-testid="child-name"
                        >{{ child.nickname }}</span
                    >
                    <span class="flex flex-wrap items-center gap-2">
                        <Pill :level="child.level">{{ child.squad ?? 'No Level yet' }}</Pill>
                        <Pill v-if="isIn" variant="success" data-testid="checked-in"
                            >✓ Checked in</Pill
                        >
                        <Pill v-if="child.has_allergies" variant="danger" data-testid="allergy-flag"
                            >⚠ Allergies</Pill
                        >
                        <Pill v-if="inactive">Inactive</Pill>
                        <span v-if="graduated" class="text-text-muted font-semibold"
                            >Graduated — cannot be checked in</span
                        >
                    </span>
                </span>
                <template #panel>
                    <div
                        class="border-border rounded-control flex flex-col gap-3 border-2 p-3"
                        data-testid="child-details"
                    >
                        <p
                            v-if="health === undefined"
                            class="text-text"
                            data-testid="health-loading"
                        >
                            Getting the health details…
                        </p>
                        <ChildHealthPanel v-else-if="health" :health="health" />
                        <p v-else class="text-text" data-testid="health-none">
                            No health details to show.
                        </p>
                        <dl class="text-text grid grid-cols-[auto_1fr] gap-x-3 gap-y-1">
                            <dt class="text-text-muted">Status</dt>
                            <dd data-testid="child-status">{{ child.status_label }}</dd>
                            <dt class="text-text-muted">Level</dt>
                            <dd data-testid="child-level">{{ levelName }}</dd>
                        </dl>
                        <AppButton
                            v-if="familyHref"
                            variant="secondary"
                            class="self-start"
                            :href="familyHref"
                            data-testid="open-family"
                        >
                            Open family page
                        </AppButton>
                    </div>
                </template>
            </Disclosure>
            <AppButton
                v-if="isIn"
                variant="secondary"
                class="self-start"
                data-testid="undo"
                @click="emit('undo', child.check_in_id ?? 0)"
            >
                Undo
            </AppButton>
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
