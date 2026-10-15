<script setup lang="ts">
import OrgShell from '@/components/layout/OrgShell.vue'
import FamilySearchResultCard from '@/components/molecules/FamilySearchResultCard.vue'
import AddChildForm from '@/components/organisms/AddChildForm.vue'
import RegisterFamilyForm from '@/components/organisms/RegisterFamilyForm.vue'
import type { BranchOption, FamilySearchResult } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { route } from 'ziggy-js'

type Option = { value: string; label: string }

const props = defineProps<{
    church: { name: string; slug: string }
    query: string
    results: FamilySearchResult[]
    satellites: BranchOption[]
    relationships: Option[]
    genders: Option[]
}>()

const q = ref(props.query)
const addingTo = ref<number | null>(null)
const showNew = ref(false)

function search(): void {
    router.get(
        route('org.families.register', props.church.slug),
        { q: q.value },
        { preserveState: true, preserveScroll: true },
    )
}
</script>

<template>
    <Head title="Register a family" />
    <OrgShell :church="church">
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
                    :href="route('org.families.show', [church.slug, result.id])"
                    @add-child="addingTo = result.id"
                />
                <AddChildForm
                    v-if="addingTo === result.id"
                    :action="route('org.families.children.store', [church.slug, result.id])"
                    :genders="genders"
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
                :action="route('org.families.store', church.slug)"
                :satellites="satellites"
                :relationships="relationships"
                :genders="genders"
            />
        </section>
    </OrgShell>
</template>
