<script setup lang="ts">
import { useSatellite } from '@/composables/useSatellite'
import AppButton from '@/components/atoms/AppButton.vue'
import EmptyState from '@/components/atoms/EmptyState.vue'
import PageHeader from '@/components/atoms/PageHeader.vue'
import SelectField from '@/components/atoms/SelectField.vue'
import TextField from '@/components/atoms/TextField.vue'
import ChurchShell from '@/components/layout/ChurchShell.vue'
import AttendanceSquadTable from '@/components/molecules/AttendanceSquadTable.vue'
import type { AttendanceClass, AttendanceSquad } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import { route } from 'ziggy-js'

/**
 * Attendance (DECISIONS 2026-10-26: reports are admins only, with Excel): at the satellite in the address (DECISIONS
 * 2026-11-08: no satellite to pick), pick a class and a date; the children checked in to that Session by Squad, with a count per Squad and in total; and the same rows as Excel. A
 * retired class that met stays in the picker, marked "(retired)". On the kit (the Bold look): each Squad a Card
 * headed by a Pill in its Level's colour.
 */
const props = defineProps<{
    classes: AttendanceClass[]
    class_id: number | null
    date: string
    date_label: string
    squads: AttendanceSquad[]
    total: number
}>()

const here = useSatellite()

const schedule = computed(() => props.classes.find((c) => c.id === props.class_id) ?? null)

const query = computed(() => ({
    class: props.class_id ?? undefined,
    date: props.date,
}))

const exportHref = computed(
    () =>
        route('admin.attendance.export', here.value.slug) +
        '?' +
        new URLSearchParams({
            class: String(props.class_id ?? ''),
            date: props.date,
        }).toString(),
)

/** A new class or date keeps the rest. */
function pick(change: { class?: number; date?: string }): void {
    router.get(
        route('admin.attendance.show', here.value.slug),
        { ...query.value, ...change },
        {
            preserveScroll: true,
        },
    )
}

/** "1PM", or "1PM (retired)" for a retired class kept for its Sessions. */
function classLabel(c: AttendanceClass): string {
    return c.retired ? `${c.name} (retired)` : c.name
}

const classOptions = computed(() =>
    props.classes.map((c) => ({ value: c.id, label: classLabel(c) })),
)

/** The control's own value on `change` — sent even when the same choice is picked again, as before. */
function valueOf(event: Event): string {
    return (event.target as HTMLInputElement | HTMLSelectElement).value
}

function pickDate(event: Event): void {
    if (valueOf(event) !== '') pick({ date: valueOf(event) })
}
</script>

<template>
    <Head title="Attendance" />
    <ChurchShell>
        <PageHeader title="Attendance">
            <template #actions>
                <AppButton
                    v-if="schedule"
                    :href="exportHref"
                    external
                    variant="secondary"
                    data-testid="attendance-export"
                    >Download Excel</AppButton
                >
            </template>
        </PageHeader>

        <form class="grid gap-3 sm:grid-cols-2" @submit.prevent>
            <SelectField
                label="Class"
                :options="classOptions"
                :model-value="class_id"
                :disabled="classes.length === 0"
                data-testid="attendance-class"
                @change="pick({ class: Number(valueOf($event)) })"
            />
            <TextField
                type="date"
                label="Date"
                :model-value="date"
                data-testid="attendance-date"
                @change="pickDate"
            />
        </form>

        <EmptyState v-if="!schedule" data-testid="attendance-no-class">
            This satellite has no classes yet. Admins add them on the Classes page.
        </EmptyState>
        <template v-else>
            <div class="flex flex-col gap-1">
                <p class="text-text text-xl font-bold" data-testid="attendance-heading">
                    {{ here.name }} · {{ classLabel(schedule) }} · {{ date_label }}
                </p>
                <p class="text-text text-xl font-semibold" data-testid="attendance-total">
                    <strong class="text-primary text-4xl font-bold tabular-nums">{{
                        total
                    }}</strong>
                    checked in
                </p>
            </div>
            <EmptyState v-if="total === 0" data-testid="attendance-empty">
                No one was checked in.
            </EmptyState>
            <AttendanceSquadTable v-for="squad in squads" :key="squad.name" :squad="squad" />
        </template>
    </ChurchShell>
</template>
