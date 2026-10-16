<script setup lang="ts">
import ChurchShell from '@/components/layout/ChurchShell.vue'
import FamilySearchResultCard from '@/components/molecules/FamilySearchResultCard.vue'
import AddChildForm from '@/components/organisms/AddChildForm.vue'
import RegisterFamilyForm from '@/components/organisms/RegisterFamilyForm.vue'
import { useChurch } from '@/composables/useChurch'
import type { FamilySearchResult, SatelliteOption } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { route } from 'ziggy-js'

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
        <h1 class="text-text text-2xl font-semibold">Register a family</h1>
        <p class="text-text text-lg">
            First, check if the family is already registered. Search the parent's name, email or
            phone, or a child's name.
        </p>
        <form class="flex gap-2" @submit.prevent="search">
            <input
                v-model="q"
                type="search"
                class="min-h-touch border-border bg-surface text-text flex-1 rounded-md border px-4 text-lg"
                placeholder="Name, email, phone…"
                data-testid="family-search"
            />
            <button
                type="submit"
                class="min-h-touch bg-primary text-on-primary rounded-md px-4 text-lg font-semibold"
            >
                Search
            </button>
        </form>

        <section v-if="query" class="flex flex-col gap-3">
            <p class="text-text">
                {{ results.length === 0 ? 'No family found.' : `Found ${results.length}:` }}
            </p>
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
            <button
                v-if="!showNew"
                type="button"
                class="min-h-touch border-primary text-primary self-start rounded-md border px-4 text-lg font-semibold"
                data-testid="register-new"
                @click="showNew = true"
            >
                Not found — register a new family
            </button>
            <RegisterFamilyForm
                v-else
                :action="route('church.families.store', church.slug)"
                :satellites="satellites"
            />
        </section>
    </ChurchShell>
</template>
