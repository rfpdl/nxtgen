<script setup lang="ts">
import EmptyState from '@/components/atoms/EmptyState.vue'
import PageHeader from '@/components/atoms/PageHeader.vue'
import Pill from '@/components/atoms/Pill.vue'
import RowList from '@/components/atoms/RowList.vue'
import ChurchShell from '@/components/layout/ChurchShell.vue'
import Pager from '@/components/molecules/Pager.vue'
import RegistrationListFilters from '@/components/molecules/RegistrationListFilters.vue'
import { useChurch } from '@/composables/useChurch'
import type { ChildListRow, LevelOption, ListFilters, Pagination, SatelliteOption } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

/**
 * The children list (plan §6): every child in the satellites the viewer can see — Level, age, status, an allergy
 * flag — with the same search and filters as the families list. Each child opens their family; a child whose family's
 * email is unconfirmed offers "Send the confirmation link again" (DECISIONS 2026-10-17).
 * On the kit (the Bold look): one stacked row per child — the whole row opens the family — the Level a Pill in its
 * own colour.
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
        <PageHeader :title="`Children (${pagination.total})`" />
        <RegistrationListFilters
            :key="JSON.stringify(filters)"
            :filters="filters"
            :options="options"
            @apply="apply"
        />
        <RowList
            v-if="children.length > 0"
            :items="children"
            :item-key="(c) => c.id"
            :href="(c) => route('church.families.show', [church.slug, c.family_id])"
            row-testid="child-row"
        >
            <template #default="{ item: child }">
                <span class="text-text text-xl font-bold">{{ child.nickname }}</span>
                <span class="text-text">{{ child.full_name }} · {{ child.age }} years</span>
                <span class="flex flex-wrap items-center gap-1.5">
                    <Pill :level="child.level">{{ child.level?.name ?? 'No Level' }}</Pill>
                    <Pill>{{ child.status_label }}</Pill>
                    <Pill v-if="child.has_allergies" variant="danger" data-testid="allergy-flag"
                        >⚠ Allergies</Pill
                    >
                </span>
                <span class="text-text-muted">{{ child.satellite }}</span>
            </template>
        </RowList>
        <EmptyState v-else title="No children match"
            >Try a shorter search or fewer filters.</EmptyState
        >
        <Pager
            :page="pagination.page"
            :last-page="pagination.last_page"
            :total="pagination.total"
            @go="goTo"
        />
    </ChurchShell>
</template>
