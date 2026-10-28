<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import EmptyState from '@/components/atoms/EmptyState.vue'
import PageHeader from '@/components/atoms/PageHeader.vue'
import SelectField from '@/components/atoms/SelectField.vue'
import TextField from '@/components/atoms/TextField.vue'
import ChurchShell from '@/components/layout/ChurchShell.vue'
import AttendanceSquadTable from '@/components/molecules/AttendanceSquadTable.vue'
import { useChurch } from '@/composables/useChurch'
import type { AttendanceSatellite, AttendanceSquad } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import { route } from 'ziggy-js'

/**
 * Attendance (DECISIONS 2026-10-26: reports are admins only, with Excel): pick a satellite, a class and a date; the
 * children checked in to that Session by Squad, with a count per Squad and in total; and the same rows as Excel. A
 * retired class that met stays in the picker, marked "(retired)". On the kit (the Bold look): each Squad a Card
 * headed by a Pill in its Level's colour.
 */
const props = defineProps<{
    satellites: AttendanceSatellite[]
    satellite_id: number | null
    class_id: number | null
    date: string
    date_label: string
    squads: AttendanceSquad[]
    total: number
}>()

const church = useChurch()

const satellite = computed(() => props.satellites.find((s) => s.id === props.satellite_id) ?? null)
const schedule = computed(
    () => satellite.value?.classes.find((c) => c.id === props.class_id) ?? null,
)

const query = computed(() => ({
    satellite: props.satellite_id ?? undefined,
    class: props.class_id ?? undefined,
    date: props.date,
}))

const exportHref = computed(
    () =>
        route('church.attendance.export', church.value.slug) +
        '?' +
        new URLSearchParams({
            satellite: String(props.satellite_id ?? ''),
            class: String(props.class_id ?? ''),
            date: props.date,
        }).toString(),
)

/** A new satellite starts on its first class; a new class or date keeps the rest. */
function pick(change: { satellite?: number; class?: number; date?: string }): void {
    const params =
        change.satellite !== undefined
            ? { satellite: change.satellite, date: props.date }
            : { ...query.value, ...change }
    router.get(route('church.attendance.show', church.value.slug), params, {
        preserveScroll: true,
    })
}

/** "1PM", or "1PM (retired)" for a retired class kept for its Sessions. */
function classLabel(c: AttendanceSatellite['classes'][number]): string {
    return c.retired ? `${c.name} (retired)` : c.name
}

const satelliteOptions = computed(() =>
    props.satellites.map((s) => ({ value: s.id, label: s.name })),
)
const classOptions = computed(() =>
    (satellite.value?.classes ?? []).map((c) => ({ value: c.id, label: classLabel(c) })),
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

        <form class="grid gap-3 sm:grid-cols-3" @submit.prevent>
            <SelectField
                label="Satellite"
                :options="satelliteOptions"
                :model-value="satellite_id"
                data-testid="attendance-satellite"
                @change="pick({ satellite: Number(valueOf($event)) })"
            />
            <SelectField
                label="Class"
                :options="classOptions"
                :model-value="class_id"
                :disabled="!satellite || satellite.classes.length === 0"
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
                    {{ satellite?.name }} · {{ classLabel(schedule) }} · {{ date_label }}
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
