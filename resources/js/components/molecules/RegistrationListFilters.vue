<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import CheckRow from '@/components/atoms/CheckRow.vue'
import SelectField from '@/components/atoms/SelectField.vue'
import TextField from '@/components/atoms/TextField.vue'
import { useEnum } from '@/composables/useEnum'
import type { LevelOption, ListFilters, SatelliteOption } from '@/types'
import { Search } from 'lucide-vue-next'
import { computed, reactive } from 'vue'

/**
 * Search and filters for the registration lists — families and children (plan §6). The two family-only filters
 * (unconfirmed email, possible duplicate) show only with `familyToggles`. Emits `apply` with only the filters in use, shaped as
 * the address parameters the server reads (FamilyListQuery), so a filtered list can be bookmarked and the Excel
 * download matches it. The satellite choice appears only when there is more than one to choose. The statuses
 * come from the page's `enums.child_status`. On the kit (the Bold look): the search with its button first, then the
 * pick-one lists (each applies at once) and the ticks; phone first, side by side on a wide screen.
 */
const props = defineProps<{
    familyToggles?: boolean
    filters: ListFilters
    options: {
        satellites: SatelliteOption[]
        levels: LevelOption[]
    }
}>()

const emit = defineEmits<{ apply: [params: Record<string, string | number>] }>()

const { options: statuses } = useEnum('child_status')

const f = reactive({ ...props.filters })

function apply(): void {
    const params: Record<string, string | number> = {}
    if (f.q.trim() !== '') params.q = f.q.trim()
    if (f.satellite !== null) params.satellite = f.satellite
    if (f.status !== null) params.status = f.status
    if (f.level !== null) params.level = f.level
    if (f.unconfirmed) params.unconfirmed = 1
    if (f.duplicates) params.duplicates = 1
    emit('apply', params)
}

function clear(): void {
    emit('apply', {})
}

const satelliteOptions = computed(() =>
    props.options.satellites.map((s) => ({ value: s.id, label: s.name })),
)
const statusOptions = computed(() =>
    statuses.value.map((s) => ({ value: s.value, label: s.label })),
)
const levelOptions = computed(() =>
    props.options.levels.map((l) => ({ value: l.level, label: l.name })),
)

/** A pick or a tick applies straight away, as the old dropdowns did on change. */
function set<K extends 'satellite' | 'status' | 'level' | 'unconfirmed' | 'duplicates'>(
    key: K,
    value: unknown,
): void {
    ;(f as Record<string, unknown>)[key] = value
    apply()
}
</script>

<template>
    <form class="flex flex-col gap-3" role="search" @submit.prevent="apply">
        <div class="flex items-end gap-2">
            <TextField
                v-model="f.q"
                class="flex-1"
                type="search"
                label="Search"
                hide-label
                placeholder="Name, email, phone, child…"
                data-testid="filter-q"
            >
                <template #leading><Search class="size-6" /></template>
            </TextField>
            <AppButton type="submit" variant="secondary" size="lg">Search</AppButton>
        </div>
        <div class="grid gap-3 sm:grid-cols-3">
            <SelectField
                v-if="options.satellites.length > 1"
                label="Satellite"
                placeholder="All satellites"
                :options="satelliteOptions"
                :model-value="f.satellite"
                data-testid="filter-satellite"
                @update:model-value="set('satellite', $event)"
            />
            <SelectField
                label="Child status"
                placeholder="Any status"
                :options="statusOptions"
                :model-value="f.status"
                data-testid="filter-status"
                @update:model-value="set('status', $event)"
            />
            <SelectField
                label="Level"
                placeholder="Any Level"
                :options="levelOptions"
                :model-value="f.level"
                data-testid="filter-level"
                @update:model-value="set('level', $event)"
            />
        </div>
        <div class="flex flex-wrap items-center gap-x-4">
            <CheckRow
                v-if="familyToggles"
                class="w-auto!"
                :model-value="f.unconfirmed"
                data-testid="filter-unconfirmed"
                @update:model-value="set('unconfirmed', $event)"
                >Email not confirmed</CheckRow
            >
            <CheckRow
                v-if="familyToggles"
                class="w-auto!"
                :model-value="f.duplicates"
                data-testid="filter-duplicates"
                @update:model-value="set('duplicates', $event)"
                >Possible duplicate</CheckRow
            >
            <AppButton variant="link" class="ml-auto" data-testid="filter-clear" @click="clear">
                Clear filters
            </AppButton>
        </div>
    </form>
</template>
