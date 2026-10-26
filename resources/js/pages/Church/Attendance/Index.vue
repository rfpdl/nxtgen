<script setup lang="ts">
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
 * retired class that met stays in the picker, marked "(retired)".
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

function valueOf(event: Event): string {
    return (event.target as HTMLInputElement | HTMLSelectElement).value
}
</script>

<template>
    <Head title="Attendance" />
    <ChurchShell>
        <h1 class="text-text text-2xl font-semibold">Attendance</h1>

        <form class="flex flex-wrap items-end gap-4" @submit.prevent>
            <label class="text-text flex flex-col gap-1">
                <span class="font-semibold">Satellite</span>
                <select
                    :value="satellite_id ?? ''"
                    class="min-h-touch border-input bg-surface rounded-md border px-3"
                    data-testid="attendance-satellite"
                    @change="pick({ satellite: Number(valueOf($event)) })"
                >
                    <option v-for="s in satellites" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
            </label>
            <label class="text-text flex flex-col gap-1">
                <span class="font-semibold">Class</span>
                <select
                    :value="class_id ?? ''"
                    :disabled="!satellite || satellite.classes.length === 0"
                    class="min-h-touch border-input bg-surface rounded-md border px-3"
                    data-testid="attendance-class"
                    @change="pick({ class: Number(valueOf($event)) })"
                >
                    <option v-for="c in satellite?.classes ?? []" :key="c.id" :value="c.id">
                        {{ classLabel(c) }}
                    </option>
                </select>
            </label>
            <label class="text-text flex flex-col gap-1">
                <span class="font-semibold">Date</span>
                <input
                    type="date"
                    :value="date"
                    class="min-h-touch border-input bg-surface rounded-md border px-3"
                    data-testid="attendance-date"
                    @change="valueOf($event) !== '' && pick({ date: valueOf($event) })"
                />
            </label>
            <a
                v-if="schedule"
                :href="exportHref"
                class="min-h-touch border-border text-text flex items-center rounded-md border px-4 font-semibold"
                data-testid="attendance-export"
                >Download Excel</a
            >
        </form>

        <p v-if="!schedule" class="text-text" data-testid="attendance-no-class">
            This satellite has no classes yet. Admins add them on the Classes page.
        </p>
        <template v-else>
            <p class="text-text text-xl font-semibold" data-testid="attendance-heading">
                {{ satellite?.name }} · {{ classLabel(schedule) }} · {{ date_label }}
            </p>
            <p class="text-text text-lg" data-testid="attendance-total">{{ total }} checked in</p>
            <p v-if="total === 0" class="text-text" data-testid="attendance-empty">
                No one was checked in.
            </p>
            <AttendanceSquadTable v-for="squad in squads" :key="squad.name" :squad="squad" />
        </template>
    </ChurchShell>
</template>
