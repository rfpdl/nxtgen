<script setup lang="ts">
import ChurchShell from '@/components/layout/ChurchShell.vue'
import Pager from '@/components/molecules/Pager.vue'
import RegistrationListFilters, {
    type RegistrationListFilterValues,
} from '@/components/molecules/RegistrationListFilters.vue'
import type { LevelOption, SatelliteOption, FamilyListRow } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import { route } from 'ziggy-js'

const props = defineProps<{
    church: { name: string; slug: string }
    families: FamilyListRow[]
    filters: RegistrationListFilterValues
    pagination: { page: number; lastPage: number; total: number; perPage: number }
    options: {
        satellites: SatelliteOption[]
        levels: LevelOption[]
        statuses: { value: string; label: string }[]
    }
}>()

/** Another page of the same filtered list: the filters stay in the address. */
function goTo(page: number): void {
    const current = Object.fromEntries(new URLSearchParams(window.location.search))
    router.get(route('church.families.index', props.church.slug), { ...current, page })
}

function apply(params: Record<string, string | number>): void {
    router.get(route('church.families.index', props.church.slug), params, { preserveScroll: true })
}

/** The download is what the list shows: the same filters, as the same address parameters. */
const exportHref = computed(() => {
    const query = new URLSearchParams(window.location.search).toString()
    return route('church.families.export', props.church.slug) + (query === '' ? '' : `?${query}`)
})
</script>

<template>
    <Head title="Families" />
    <ChurchShell :church="church">
        <div class="flex flex-wrap items-center gap-4">
            <h1 class="text-text text-2xl font-semibold">Families ({{ pagination.total }})</h1>
            <a
                :href="exportHref"
                class="min-h-touch border-border bg-surface text-text ml-auto flex items-center rounded-md border px-4"
                data-testid="export"
                >Download Excel</a
            >
        </div>
        <RegistrationListFilters
            :key="JSON.stringify(filters)"
            family-toggles
            :filters="filters"
            :options="options"
            @apply="apply"
        />
        <ul class="flex flex-col gap-2">
            <li
                v-for="family in families"
                :key="family.id"
                class="border-border bg-surface rounded-lg border p-4"
                data-testid="family-row"
            >
                <Link
                    :href="route('church.families.show', [church.slug, family.id])"
                    class="text-primary text-lg font-semibold underline"
                    >{{ family.primaryName }}</Link
                >
                <p class="text-text">
                    {{ family.email
                    }}<span v-if="!family.emailConfirmed" class="text-warning ml-2 font-semibold"
                        >Email not confirmed</span
                    >
                </p>
                <p class="text-text-muted">
                    {{ family.satellite }} · {{ family.children.join(', ') }} ·
                    {{ family.registeredBy }} · {{ family.registeredOn }}
                </p>
            </li>
        </ul>
        <Pager
            :page="pagination.page"
            :last-page="pagination.lastPage"
            :total="pagination.total"
            @go="goTo"
        />
        <p v-if="families.length === 0" class="text-text-muted">No families match.</p>
    </ChurchShell>
</template>
