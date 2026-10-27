<script setup lang="ts">
import ChurchShell from '@/components/layout/ChurchShell.vue'
import Pager from '@/components/molecules/Pager.vue'
import RegistrationListFilters from '@/components/molecules/RegistrationListFilters.vue'
import { useChurch } from '@/composables/useChurch'
import type { ChildListRow, LevelOption, ListFilters, Pagination, SatelliteOption } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

/**
 * The children list (plan §6): every child in the satellites the viewer can see — Level, age, status, an allergy
 * flag — with the same search and filters as the families list. Each child opens their family; a child whose family's
 * email is unconfirmed offers "Send the confirmation link again" (DECISIONS 2026-10-17).
 */
defineProps<{
    children: ChildListRow[]
    filters: ListFilters
    pagination: Pagination
    options: { satellites: SatelliteOption[]; levels: LevelOption[] }
}>()

const church = useChurch()

/** Another page of the same filtered list: the filters stay in the address. */
function goTo(page: number): void {
    const current = Object.fromEntries(new URLSearchParams(window.location.search))
    router.get(route('church.children.index', church.value.slug), { ...current, page })
}

function apply(params: Record<string, string | number>): void {
    router.get(route('church.children.index', church.value.slug), params, { preserveScroll: true })
}
</script>

<template>
    <Head title="Children" />
    <ChurchShell>
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
                    :href="route('church.families.show', [church.slug, child.family_id])"
                    class="text-primary text-lg font-semibold underline"
                    >{{ child.nickname }}</Link
                >
                <span class="text-text">{{ child.full_name }} · {{ child.age }} years</span>
                <span class="text-text-muted"
                    >{{ child.level ?? 'No Level' }} · {{ child.status_label }} ·
                    {{ child.satellite }}</span
                >
                <span
                    v-if="child.has_allergies"
                    class="text-danger font-semibold"
                    data-testid="allergy-flag"
                    >Allergies</span
                >
            </li>
        </ul>
        <Pager
            :page="pagination.page"
            :last-page="pagination.last_page"
            :total="pagination.total"
            @go="goTo"
        />
        <p v-if="children.length === 0" class="text-text-muted">No children match.</p>
    </ChurchShell>
</template>
