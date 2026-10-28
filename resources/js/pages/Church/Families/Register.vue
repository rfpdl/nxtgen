<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import EmptyState from '@/components/atoms/EmptyState.vue'
import PageHeader from '@/components/atoms/PageHeader.vue'
import TextField from '@/components/atoms/TextField.vue'
import ChurchShell from '@/components/layout/ChurchShell.vue'
import FamilySearchResultCard from '@/components/molecules/FamilySearchResultCard.vue'
import AddChildForm from '@/components/organisms/AddChildForm.vue'
import RegisterFamilyForm from '@/components/organisms/RegisterFamilyForm.vue'
import { useChurch } from '@/composables/useChurch'
import type { FamilySearchResult, SatelliteOption } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { Search } from 'lucide-vue-next'
import { ref } from 'vue'
import { route } from 'ziggy-js'

/**
 * Register a family (staff, plan §6 R1), search first: is the family already here (church-wide, masked — add a child
 * to it), and only then "Not found — register a new family", which opens the registration form.
 */
const props = defineProps<{
    query: string
    results: FamilySearchResult[]
    satellites: SatelliteOption[]
}>()

const church = useChurch()

const q = ref(props.query)
const addingTo = ref<number | null>(null)
const showNew = ref(false)

function search(): void {
    router.get(
        route('church.families.register', church.value.slug),
        { q: q.value },
        { preserveState: true, preserveScroll: true },
    )
}
</script>

<template>
    <Head title="Register a family" />
    <ChurchShell>
        <PageHeader
            title="Register a family"
            sub="First, check if the family is already registered. Search the parent's name, email or phone, or a child's name."
        />
        <form class="flex items-end gap-2" role="search" @submit.prevent="search">
            <TextField
                v-model="q"
                class="flex-1"
                type="search"
                label="Search families"
                hide-label
                placeholder="Name, email, phone…"
                data-testid="family-search"
            >
                <template #leading><Search class="size-6" /></template>
            </TextField>
            <AppButton type="submit" variant="secondary" size="lg">Search</AppButton>
        </form>

        <section v-if="query" class="flex flex-col gap-3">
            <EmptyState v-if="results.length === 0"
                >No family found. Register them as a new family below.</EmptyState
            >
            <p v-else class="text-text text-lg font-semibold">Found {{ results.length }}:</p>
            <template v-for="result in results" :key="result.id">
                <FamilySearchResultCard
                    :result="result"
                    :href="route('church.families.show', [church.slug, result.id])"
                    @add-child="addingTo = result.id"
                />
                <AddChildForm
                    v-if="addingTo === result.id"
                    :action="route('church.families.children.store', [church.slug, result.id])"
                    start-open
                />
            </template>
        </section>

        <section class="flex flex-col gap-3">
            <AppButton
                v-if="!showNew"
                size="lg"
                class="self-start"
                data-testid="register-new"
                @click="showNew = true"
            >
                Not found — register a new family
            </AppButton>
            <RegisterFamilyForm
                v-else
                :action="route('church.families.store', church.slug)"
                :satellites="satellites"
            />
        </section>
    </ChurchShell>
</template>
