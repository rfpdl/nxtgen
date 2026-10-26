<script setup lang="ts">
import DoorChildRow from '@/components/molecules/DoorChildRow.vue'
import { useChurch } from '@/composables/useChurch'
import type { CheckInInput, DoorFamily, LevelOption } from '@/types'
import { router } from '@inertiajs/vue3'
import { computed, reactive, ref } from 'vue'
import { route } from 'ziggy-js'

/**
 * A family found at the door (DECISIONS 2026-10-26): "Cruz family", the parent and their satellite, each child
 * (DoorChildRow) — tick, then "Check in N children" into the chosen class. Each ticked child's questions (make active
 * again? which Level?) are answered on the card before the button works; the server still checks every child.
 * Undo puts a child back out. With no class chosen there is nothing to check in to.
 */
const props = defineProps<{ family: DoorFamily; levels: LevelOption[]; classId: number | null }>()
const emit = defineEmits<{ health: [childId: number] }>()

const church = useChurch()

type Pick = { ticked: boolean; level: number | null; makeActive: boolean }
const picks = reactive<Record<number, Pick>>(
    Object.fromEntries(
        props.family.children.map((c) => [c.id, { ticked: false, level: null, makeActive: false }]),
    ),
)
const sending = ref(false)

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
    return n === 0
        ? 'Tick the children to check in'
        : `Check in ${n} ${n === 1 ? 'child' : 'children'}`
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
    <article
        class="border-border bg-surface flex flex-col gap-2 rounded-lg border p-4"
        data-testid="door-family"
    >
        <h2 class="text-text text-2xl font-semibold">{{ family.name }}</h2>
        <p class="text-text">{{ family.primary_name }} · {{ family.satellite }}</p>
        <ul class="divide-border flex flex-col divide-y">
            <template v-for="child in family.children" :key="child.id">
                <DoorChildRow
                    v-if="picks[child.id]"
                    v-model:ticked="picks[child.id]!.ticked"
                    v-model:level="picks[child.id]!.level"
                    v-model:make-active="picks[child.id]!.makeActive"
                    :child="child"
                    :levels="levels"
                    :can-check-in="classId !== null"
                    @undo="undo"
                    @health="emit('health', $event)"
                />
            </template>
        </ul>
        <button
            v-if="classId !== null"
            type="button"
            class="bg-primary text-on-primary self-end rounded-md px-5 text-lg font-semibold disabled:opacity-60"
            :disabled="!ready || sending"
            data-testid="check-in"
            @click="checkIn"
        >
            {{ label }}
        </button>
    </article>
</template>
