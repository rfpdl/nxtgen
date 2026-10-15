<script setup lang="ts">
import OrgShell from '@/components/layout/OrgShell.vue'
import type { FamilyListRow } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

const props = defineProps<{
    church: { name: string; slug: string }
    families: FamilyListRow[]
    filters: { unconfirmed: boolean }
}>()

function toggleUnconfirmed(): void {
    router.get(
        route('org.families.index', props.church.slug),
        props.filters.unconfirmed ? {} : { unconfirmed: 1 },
        { preserveScroll: true },
    )
}
</script>

<template>
    <Head title="Families" />
    <OrgShell :church="church">
        <div class="flex flex-wrap items-center gap-4">
            <h1 class="text-text text-2xl font-semibold">Families ({{ families.length }})</h1>
            <label class="min-h-touch text-text flex items-center gap-2">
                <input
                    type="checkbox"
                    class="size-5"
                    :checked="filters.unconfirmed"
                    data-testid="filter-unconfirmed"
                    @change="toggleUnconfirmed"
                />
                Email not confirmed only
            </label>
            <a
                :href="route('org.families.export', church.slug)"
                class="min-h-touch border-border bg-surface text-text ml-auto flex items-center rounded-md border px-4"
                data-testid="export"
                >Download Excel</a
            >
        </div>
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
        <p v-if="families.length === 0" class="text-text-muted">No families yet.</p>
    </OrgShell>
</template>
