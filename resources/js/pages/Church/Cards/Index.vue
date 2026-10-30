<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import EmptyState from '@/components/atoms/EmptyState.vue'
import TextField from '@/components/atoms/TextField.vue'
import LinkTabs from '@/components/atoms/LinkTabs.vue'
import PageHeader from '@/components/atoms/PageHeader.vue'
import SectionHeading from '@/components/atoms/SectionHeading.vue'
import ChurchShell from '@/components/layout/ChurchShell.vue'
import Pager from '@/components/molecules/Pager.vue'
import PrintListCard from '@/components/molecules/PrintListCard.vue'
import CardRequestRow from '@/components/organisms/CardRequestRow.vue'
import { useChurch } from '@/composables/useChurch'
import { useEnum } from '@/composables/useEnum'
import type {
    CardListTab,
    CardRequestRow as Row,
    MakePrintListInput,
    Pagination,
    PrintList,
} from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { route } from 'ziggy-js'

/**
 * Cards (name cards 03, DECISIONS 2026-10-29): the Card requests of the families an admin looks after, by step — Asked,
 * Approved, Printed, Given or refused (`enums.card_tab`), oldest first while there is something to do — or a child
 * found by name (`?q=`, kept across tabs and pages). Each row moves itself (CardRequestRow). On Approved: "Make print list" keeps the cards it shows as a
 * print list (name cards 04), and the lists still to print offer Download and "Mark these N printed" (PrintListCard).
 */
const props = defineProps<{
    requests: Row[]
    pagination: Pagination
    tab: CardListTab
    /** The child-name search, kept across tabs and pages (null = none). */
    q: string | null
    /** On Approved: the print lists still to print (GLOSSARY "Print list"); empty elsewhere. */
    print_lists: PrintList[]
}>()

const church = useChurch()
const { options } = useEnum('card_tab')

const tabs = computed(() =>
    options.value.map((t) => ({
        key: t.value,
        label: t.label,
        href: route('church.card-requests.index', { church: church.value.slug, ...where(t.value) }),
    })),
)

const EMPTY: Record<CardListTab, string> = {
    asked: 'No new cards asked for. Staff ask for one from a child’s card on the family page.',
    approved: 'No cards waiting to be printed.',
    printed: 'No printed cards waiting to be given.',
    closed: 'No cards given or refused yet.',
}

/** A tab's address, keeping the search. */
function where(tab: CardListTab): { tab: CardListTab; q?: string } {
    return props.q ? { tab, q: props.q } : { tab }
}

const search = ref(props.q ?? '')

function find(): void {
    const q = search.value.trim()
    router.get(
        route('church.card-requests.index', church.value.slug),
        q ? { tab: props.tab, q } : { tab: props.tab },
    )
}

/** "Make print list": the approved cards this tab shows — its search — kept as a list (name cards 04). */
function makePrintList(): void {
    const body: MakePrintListInput = { q: props.q }
    router.post(route('church.card-requests.print-lists.store', church.value.slug), body, {
        preserveScroll: true,
    })
}

function goTo(page: number): void {
    router.get(route('church.card-requests.index', church.value.slug), {
        ...where(props.tab),
        page,
    })
}
</script>

<template>
    <Head title="Cards" />
    <ChurchShell>
        <PageHeader
            :title="`Cards (${pagination.total})`"
            sub="New name cards: asked for, approved, printed, given"
            title-testid="cards-title"
        />
        <LinkTabs label="Cards" :tabs="tabs" :current="tab" testid-prefix="card-tab" />
        <section
            v-if="tab === 'approved' && (requests.length > 0 || print_lists.length > 0)"
            class="flex flex-col gap-3"
            data-testid="print-lists"
        >
            <div class="flex flex-wrap items-center justify-between gap-3">
                <SectionHeading>Print lists</SectionHeading>
                <AppButton
                    v-if="requests.length > 0"
                    data-testid="cards-make-print-list"
                    @click="makePrintList"
                    >Make print list</AppButton
                >
            </div>
            <PrintListCard v-for="list in print_lists" :key="list.id" :list="list" />
        </section>
        <form class="flex flex-wrap items-end gap-3" role="search" @submit.prevent="find">
            <TextField
                v-model="search"
                class="min-w-0 flex-1 basis-56"
                label="Find a child"
                hint="e.g. Amara Reyes"
                data-testid="cards-search"
            />
            <AppButton type="submit" variant="secondary" size="lg" data-testid="cards-search-go"
                >Search</AppButton
            >
        </form>
        <div v-if="requests.length > 0" class="flex flex-col gap-3">
            <CardRequestRow v-for="request in requests" :key="request.id" :request="request" />
        </div>
        <EmptyState v-else-if="q" data-testid="cards-empty"
            >No child called “{{ q }}” on this tab. Try another tab or a shorter search.</EmptyState
        >
        <EmptyState v-else data-testid="cards-empty">{{ EMPTY[tab] }}</EmptyState>
        <Pager
            :page="pagination.page"
            :last-page="pagination.last_page"
            :total="pagination.total"
            @go="goTo"
        />
    </ChurchShell>
</template>
