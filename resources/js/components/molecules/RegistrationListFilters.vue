<script setup lang="ts">
import { reactive } from 'vue'

/**
 * Search and filters for the registration lists — families and children (plan §6). The two family-only filters
 * (unconfirmed email, possible duplicate) show only with `familyToggles`. Emits `apply` with only the filters in use, shaped as
 * the address parameters the server reads (FamilyListQuery), so a filtered list can be bookmarked and the Excel
 * download matches it. The satellite choice appears only when there is more than one to choose.
 */
export type RegistrationListFilterValues = {
    q: string
    satellite: number | null
    status: string | null
    group: number | null
    unconfirmed: boolean
    duplicates: boolean
}

const props = defineProps<{
    familyToggles?: boolean
    filters: RegistrationListFilterValues
    options: {
        satellites: { id: number; name: string }[]
        groups: { id: number; name: string }[]
        statuses: { value: string; label: string }[]
    }
}>()

const emit = defineEmits<{ apply: [params: Record<string, string | number>] }>()

const f = reactive({ ...props.filters })

function apply(): void {
    const params: Record<string, string | number> = {}
    if (f.q.trim() !== '') params.q = f.q.trim()
    if (f.satellite !== null) params.satellite = f.satellite
    if (f.status !== null) params.status = f.status
    if (f.group !== null) params.group = f.group
    if (f.unconfirmed) params.unconfirmed = 1
    if (f.duplicates) params.duplicates = 1
    emit('apply', params)
}

function clear(): void {
    emit('apply', {})
}

const control = 'min-h-touch border-border bg-surface text-text rounded-md border px-3'
</script>

<template>
    <form class="flex flex-wrap items-end gap-3" @submit.prevent="apply">
        <input
            v-model="f.q"
            type="search"
            placeholder="Name, email, phone, child…"
            aria-label="Search families"
            :class="[control, 'min-w-56 flex-1']"
            data-testid="filter-q"
        />
        <select
            v-if="options.satellites.length > 1"
            v-model="f.satellite"
            aria-label="Satellite"
            :class="control"
            data-testid="filter-satellite"
            @change="apply"
        >
            <option :value="null">All satellites</option>
            <option v-for="s in options.satellites" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
        <select
            v-model="f.status"
            aria-label="Child status"
            :class="control"
            data-testid="filter-status"
            @change="apply"
        >
            <option :value="null">Any status</option>
            <option v-for="s in options.statuses" :key="s.value" :value="s.value">
                {{ s.label }}
            </option>
        </select>
        <select
            v-model="f.group"
            aria-label="Group"
            :class="control"
            data-testid="filter-group"
            @change="apply"
        >
            <option :value="null">Any group</option>
            <option v-for="g in options.groups" :key="g.id" :value="g.id">{{ g.name }}</option>
        </select>
        <label v-if="familyToggles" class="min-h-touch text-text flex items-center gap-2">
            <input
                v-model="f.unconfirmed"
                type="checkbox"
                class="size-5"
                data-testid="filter-unconfirmed"
                @change="apply"
            />
            Email not confirmed
        </label>
        <label v-if="familyToggles" class="min-h-touch text-text flex items-center gap-2">
            <input
                v-model="f.duplicates"
                type="checkbox"
                class="size-5"
                data-testid="filter-duplicates"
                @change="apply"
            />
            Possible duplicate
        </label>
        <button
            type="submit"
            class="min-h-touch bg-primary text-on-primary rounded-md px-4 font-semibold"
        >
            Search
        </button>
        <button
            type="button"
            class="min-h-touch text-text px-2 underline"
            data-testid="filter-clear"
            @click="clear"
        >
            Clear
        </button>
    </form>
</template>
