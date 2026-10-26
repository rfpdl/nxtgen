<script setup lang="ts">
import type { DoorChild, LevelOption } from '@/types'
import { computed } from 'vue'

/**
 * One child on a door family card (DECISIONS 2026-10-26): a box to tick, the nickname (tap it for the health
 * details), this satellite's Squad and an allergy flag ⚠ — no photo, no phone. A child who is in shows ✓ and Undo.
 * A Graduated child cannot be ticked. Ticking an inactive child asks "Make active again?"; ticking a child with no
 * Level asks for one, the age suggestion picked first. The answers are the card's (v-model).
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

function onTick(event: Event): void {
    tick((event.target as HTMLInputElement).checked)
}

function onLevel(event: Event): void {
    const value = (event.target as HTMLSelectElement).value
    level.value = value === '' ? null : Number(value)
}
</script>

<template>
    <li
        class="grid grid-cols-[2.5rem_minmax(0,1fr)] items-start gap-x-3 py-3"
        data-testid="door-child"
    >
        <span
            v-if="isIn"
            class="text-success flex h-10 items-center justify-center text-2xl font-bold"
            aria-hidden="true"
            >✓</span
        >
        <input
            v-else
            type="checkbox"
            class="accent-primary mt-1 size-8"
            :checked="ticked"
            :disabled="graduated || !canCheckIn"
            :aria-label="`Tick ${child.nickname}`"
            data-testid="tick-child"
            @change="onTick"
        />

        <div class="flex min-w-0 flex-col gap-2">
            <p class="flex flex-wrap items-baseline gap-x-2">
                <button
                    type="button"
                    class="text-text text-left text-xl font-semibold underline underline-offset-4"
                    :title="`${child.full_name} — health details`"
                    data-testid="child-name"
                    @click="emit('health', child.id)"
                >
                    {{ child.nickname }}
                </button>
                <span class="text-text">{{ child.squad ?? 'No Level yet' }}</span>
            </p>

            <p
                v-if="child.has_allergies || inactive || graduated || isIn"
                class="flex flex-wrap items-center gap-x-4 gap-y-2"
            >
                <span v-if="isIn" class="text-success font-semibold" data-testid="checked-in"
                    >✓ Checked in</span
                >
                <span
                    v-if="child.has_allergies"
                    class="text-danger font-bold"
                    title="Has allergies — tap the name"
                    data-testid="allergy-flag"
                    >⚠ Allergies</span
                >
                <span v-if="inactive" class="text-text-muted font-semibold">Inactive</span>
                <span v-if="graduated" class="text-text-muted font-semibold"
                    >Graduated — cannot be checked in</span
                >
                <button
                    v-if="isIn"
                    type="button"
                    class="border-border-strong text-text rounded-md border px-4"
                    data-testid="undo"
                    @click="emit('undo', child.check_in_id ?? 0)"
                >
                    Undo
                </button>
            </p>

            <div
                v-if="ticked && inactive"
                class="bg-warning-soft text-text flex flex-wrap items-center gap-3 rounded-md p-3"
                data-testid="make-active"
            >
                <p class="w-full">{{ child.nickname }} is inactive. Make them active again?</p>
                <p v-if="makeActive" class="text-success font-semibold">
                    Yes — {{ child.nickname }} will be made active.
                </p>
                <button
                    v-else
                    type="button"
                    class="bg-primary text-on-primary rounded-md px-4 font-semibold"
                    data-testid="make-active-yes"
                    @click="makeActive = true"
                >
                    Yes, make active
                </button>
                <button
                    type="button"
                    class="text-text px-2 underline"
                    data-testid="make-active-no"
                    @click="tick(false)"
                >
                    No
                </button>
            </div>

            <label
                v-if="ticked && needsLevel"
                class="bg-warning-soft text-text flex flex-wrap items-center gap-3 rounded-md p-3"
            >
                <span>{{ child.nickname }} has no Level yet. Pick one:</span>
                <span class="relative flex">
                    <select
                        :value="level ?? ''"
                        class="min-h-touch-door border-input bg-surface min-w-0 appearance-none rounded-md border pr-10 pl-3"
                        data-testid="pick-level"
                        @change="onLevel"
                    >
                        <option value="">Choose…</option>
                        <option v-for="option in levels" :key="option.level" :value="option.level">
                            {{ option.name }}
                        </option>
                    </select>
                    <span
                        class="text-text pointer-events-none absolute top-1/2 right-3 -translate-y-1/2"
                        aria-hidden="true"
                        >▾</span
                    >
                </span>
            </label>
        </div>
    </li>
</template>
