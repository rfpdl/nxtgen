<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import EmailText from '@/components/atoms/EmailText.vue'
import EmptyState from '@/components/atoms/EmptyState.vue'
import PageHeader from '@/components/atoms/PageHeader.vue'
import Pill from '@/components/atoms/Pill.vue'
import ResendLinkButton from '@/components/atoms/ResendLinkButton.vue'
import RowList from '@/components/atoms/RowList.vue'
import ChurchShell from '@/components/layout/ChurchShell.vue'
import Pager from '@/components/molecules/Pager.vue'
import RegistrationListFilters from '@/components/molecules/RegistrationListFilters.vue'
import { useChurch } from '@/composables/useChurch'
import type { FamilyListRow, LevelOption, ListFilters, Pagination, SatelliteOption } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import { route } from 'ziggy-js'

/**
 * The families list (plan §6): "Register a family" (the page's one main button), the Excel of what the list shows,
 * the search and filters, and one stacked row per family — the whole row opens it; its children are pills in their
 * Level's colour; an unconfirmed email says so and, where the viewer may, offers the link again (under the row's
 * link). 50 to a page.
 */
defineProps<{
    families: FamilyListRow[]
    filters: ListFilters
    pagination: Pagination
    options: { satellites: SatelliteOption[]; levels: LevelOption[] }
}>()

const church = useChurch()

/** Another page of the same filtered list: the filters stay in the address. */
function goTo(page: number): void {
    const current = Object.fromEntries(new URLSearchParams(window.location.search))
    router.get(route('church.families.index', church.value.slug), { ...current, page })
}

function apply(params: Record<string, string | number>): void {
    router.get(route('church.families.index', church.value.slug), params, { preserveScroll: true })
}

/** The download is what the list shows: the same filters, as the same address parameters. */
const exportHref = computed(() => {
    const query = new URLSearchParams(window.location.search).toString()
    return route('church.families.export', church.value.slug) + (query === '' ? '' : `?${query}`)
})
</script>

<template>
    <Head title="Families" />
    <ChurchShell>
        <PageHeader :title="`Families (${pagination.total})`">
            <template #actions>
                <AppButton
                    :href="route('church.families.register', church.slug)"
                    data-testid="register-family"
                    >Register a family</AppButton
                >
                <AppButton :href="exportHref" external variant="secondary" data-testid="export"
                    >Download Excel</AppButton
                >
            </template>
        </PageHeader>
        <RegistrationListFilters
            :key="JSON.stringify(filters)"
            family-toggles
            :filters="filters"
            :options="options"
            @apply="apply"
        />
        <RowList
            v-if="families.length > 0"
            :items="families"
            :item-key="(f) => f.id"
            :href="(f) => route('church.families.show', [church.slug, f.id])"
            row-testid="family-row"
        >
            <template #default="{ item: family }">
                <span class="text-text text-xl font-bold">{{ family.primary_name }}</span>
                <span class="text-text flex flex-wrap items-center gap-2">
                    <EmailText :email="family.email" />
                    <Pill v-if="!family.email_confirmed" variant="warning"
                        >Email not confirmed</Pill
                    >
                </span>
                <span v-if="family.children.length > 0" class="flex flex-wrap gap-1.5">
                    <Pill v-for="(child, i) in family.children" :key="i" :level="child.level">{{
                        child.nickname
                    }}</Pill>
                </span>
                <span class="text-text-muted">
                    {{ family.satellite }} · {{ family.registered_by }} ·
                    <span class="whitespace-nowrap">{{ family.registered_on }}</span>
                </span>
            </template>
            <template #after="{ item: family }">
                <ResendLinkButton v-if="family.can_resend" :family-id="family.id" />
            </template>
        </RowList>
        <EmptyState v-else title="No families match"
            >Try a shorter search or fewer filters, or register a new family.</EmptyState
        >
        <Pager
            :page="pagination.page"
            :last-page="pagination.last_page"
            :total="pagination.total"
            @go="goTo"
        />
    </ChurchShell>
</template>
