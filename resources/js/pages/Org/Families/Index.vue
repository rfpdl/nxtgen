<script setup lang="ts">
import OrgShell from '@/components/layout/OrgShell.vue'
import RegistrationListFilters, {
    type RegistrationListFilterValues,
} from '@/components/molecules/RegistrationListFilters.vue'
import type { BranchOption, FamilyListRow } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import { route } from 'ziggy-js'

const props = defineProps<{
    church: { name: string; slug: string }
    families: FamilyListRow[]
    filters: RegistrationListFilterValues
    options: {
        satellites: BranchOption[]
        groups: { id: number; name: string }[]
        statuses: { value: string; label: string }[]
    }
}>()

function apply(params: Record<string, string | number>): void {
    router.get(route('org.families.index', props.church.slug), params, { preserveScroll: true })
}

/** The download is what the list shows: the same filters, as the same address parameters. */
const exportHref = computed(() => {
    const query = new URLSearchParams(window.location.search).toString()
    return route('org.families.export', props.church.slug) + (query === '' ? '' : `?${query}`)
})
</script>

<template>
    <Head title="Families" />
    <OrgShell :church="church">
        <div class="flex flex-wrap items-center gap-4">
            <h1 class="text-text text-2xl font-semibold">Families ({{ families.length }})</h1>
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
                    :href="route('org.families.show', [church.slug, family.id])"
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
        <p v-if="families.length === 0" class="text-text-muted">No families match.</p>
    </OrgShell>
</template>
