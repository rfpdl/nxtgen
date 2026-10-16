<script setup lang="ts">
import ChurchShell from '@/components/layout/ChurchShell.vue'
import Pager from '@/components/molecules/Pager.vue'
import RegistrationListFilters, {
    type RegistrationListFilterValues,
} from '@/components/molecules/RegistrationListFilters.vue'
import type { SatelliteOption, ChildListRow } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

/**
 * The children list (plan §6): every child in the satellites the viewer can see — group, age, status, an allergy
 * flag — with the same search and filters as the families list. Each child opens their family.
 */
const props = defineProps<{
    church: { name: string; slug: string }
    children: ChildListRow[]
    filters: RegistrationListFilterValues
    pagination: { page: number; lastPage: number; total: number; perPage: number }
    options: {
        satellites: SatelliteOption[]
        groups: { id: number; name: string }[]
        statuses: { value: string; label: string }[]
    }
}>()

/** Another page of the same filtered list: the filters stay in the address. */
function goTo(page: number): void {
    const current = Object.fromEntries(new URLSearchParams(window.location.search))
    router.get(route('church.children.index', props.church.slug), { ...current, page })
}

function apply(params: Record<string, string | number>): void {
    router.get(route('church.children.index', props.church.slug), params, { preserveScroll: true })
}
</script>

<template>
    <Head title="Children" />
    <ChurchShell :church="church">
        <h1 class="text-text text-2xl font-semibold">Children ({{ pagination.total }})</h1>
        <RegistrationListFilters
            :key="JSON.stringify(filters)"
            :filters="filters"
            :options="options"
            @apply="apply"
        />
        <ul class="flex flex-col gap-2">
            <li
                v-for="child in children"
                :key="child.id"
                class="border-border bg-surface flex flex-wrap items-center gap-x-4 gap-y-1 rounded-lg border p-4"
                data-testid="child-row"
            >
                <Link
                    :href="route('church.families.show', [church.slug, child.familyId])"
                    class="text-primary text-lg font-semibold underline"
                    >{{ child.nickname }}</Link
                >
                <span class="text-text">{{ child.fullName }} · {{ child.age }} years</span>
                <span class="text-text-muted"
                    >{{ child.group ?? 'No group' }} · {{ child.status }} ·
                    {{ child.satellite }}</span
                >
                <span
                    v-if="child.hasAllergies"
                    class="text-danger font-semibold"
                    data-testid="allergy-flag"
                    >Allergies</span
                >
            </li>
        </ul>
        <Pager
            :page="pagination.page"
            :last-page="pagination.lastPage"
            :total="pagination.total"
            @go="goTo"
        />
        <p v-if="children.length === 0" class="text-text-muted">No children match.</p>
    </ChurchShell>
</template>
