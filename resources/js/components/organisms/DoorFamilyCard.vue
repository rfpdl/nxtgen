<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import Card from '@/components/atoms/Card.vue'
import DoorChildRow from '@/components/molecules/DoorChildRow.vue'
import { useChurch } from '@/composables/useChurch'
import type { CheckInInput, ChildHealth, DoorFamily, LevelOption } from '@/types'
import { router } from '@inertiajs/vue3'
import { computed, reactive, ref } from 'vue'
import { route } from 'ziggy-js'

/**
 * A family found at the door (DECISIONS 2026-10-26): "Cruz family", the parent and their satellite, each child
 * (DoorChildRow) — tick, then "Check in N children" into the chosen class. Each ticked child's questions (make active
 * again? which Level?) are answered on the card before the button works; the server still checks every child.
 * Undo puts a child back out. With no class chosen there is nothing to check in to. A child's details open in their
 * row (DECISIONS 2026-10-28): the health details the page has fetched (`healths`, by child id — asked for up the
 * first time, `health`), and the family page's link when the viewer may open it (`can_open`).
 */
const props = withDefaults(
    defineProps<{
        family: DoorFamily
        levels: LevelOption[]
        classId: number | null
        healths: Record<number, ChildHealth | null>
        /** The child ONE name card named exactly (slice 3): ticked to start with, so "Check in 1 child" is one tap. */
        preTicked?: number[]
    }>(),
    { preTicked: () => [] },
)
const emit = defineEmits<{ health: [childId: number] }>()

const church = useChurch()

type Pick = { ticked: boolean; level: number | null; makeActive: boolean }
/**
 * A scanned child starts ticked only when nothing needs asking: active and not in yet (an inactive child is shown but
 * left for staff — "Make active again?" is theirs to answer; a Graduated one cannot be checked in). With no Level, the
 * age suggestion is picked.
 */
function startTicked(c: DoorFamily['children'][number]): boolean {
    return props.preTicked.includes(c.id) && c.check_in_id === null && c.status === 'active'
}
const picks = reactive<Record<number, Pick>>(
    Object.fromEntries(
        props.family.children.map((c) => [
            c.id,
            {
                ticked: startTicked(c),
                level: startTicked(c) && c.level === null ? c.suggested_level : null,
                makeActive: false,
            },
        ]),
    ),
)
const sending = ref(false)
const familyHref = computed(() =>
    props.family.can_open
        ? route('church.families.show', [church.value.slug, props.family.id])
        : null,
)

const ticked = computed(() => props.family.children.filter((c) => picks[c.id]?.ticked === true))
const ready = computed(
    () =>
        ticked.value.length > 0 &&
        ticked.value.every((c) => {
            const pick = picks[c.id]
            return (
                pick !== undefined &&
                (c.status !== 'inactive' || pick.makeActive) &&
                (c.level !== null || pick.level !== null)
            )
        }),
)
const label = computed(() => {
    const n = ticked.value.length
    return n === 0 ? 'Tick to check in' : `Check in ${n} ${n === 1 ? 'child' : 'children'}`
})

function checkIn(): void {
    if (props.classId === null || !ready.value) {
        return
    }
    const payload: CheckInInput = {
        children: ticked.value.map((c) => ({
            child_id: c.id,
            level: c.level === null ? (picks[c.id]?.level ?? null) : null,
            make_active: c.status === 'inactive' && picks[c.id]?.makeActive === true,
        })),
    }
    sending.value = true
    router.post(route('church.door.check-in', [church.value.slug, props.classId]), payload, {
        preserveScroll: true,
        onSuccess: () => {
            for (const pick of Object.values(picks)) {
                Object.assign(pick, { ticked: false, level: null, makeActive: false })
            }
        },
        onFinish: () => {
            sending.value = false
        },
    })
}

function undo(checkInId: number): void {
    router.put(
        route('church.door.undo', [church.value.slug, checkInId]),
        {},
        { preserveScroll: true },
    )
}
</script>

<template>
    <Card as="article" class="flex flex-col gap-1" data-testid="door-family">
        <h2 class="text-text text-2xl font-bold">{{ family.name }}</h2>
        <p class="text-text-muted text-lg">{{ family.primary_name }} · {{ family.satellite }}</p>
        <ul class="divide-border flex flex-col divide-y-2">
            <template v-for="child in family.children" :key="child.id">
                <DoorChildRow
                    v-if="picks[child.id]"
                    v-model:ticked="picks[child.id]!.ticked"
                    v-model:level="picks[child.id]!.level"
                    v-model:make-active="picks[child.id]!.makeActive"
                    :child="child"
                    :levels="levels"
                    :can-check-in="classId !== null"
                    :health="healths[child.id]"
                    :family-href="familyHref"
                    @undo="undo"
                    @health="emit('health', $event)"
                />
            </template>
        </ul>
        <AppButton
            v-if="classId !== null"
            size="lg"
            block
            class="mt-2 mb-1.5"
            :disabled="!ready"
            :loading="sending"
            data-testid="check-in"
            @click="checkIn"
        >
            {{ label }}
        </AppButton>
    </Card>
</template>
