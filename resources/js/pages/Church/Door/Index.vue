<script setup lang="ts">
import { useSatellite } from '@/composables/useSatellite'
import AppButton from '@/components/atoms/AppButton.vue'
import EmptyState from '@/components/atoms/EmptyState.vue'
import Notice from '@/components/atoms/Notice.vue'
import PageHeader from '@/components/atoms/PageHeader.vue'
import TextField from '@/components/atoms/TextField.vue'
import ChurchShell from '@/components/layout/ChurchShell.vue'
import DoorCheckedInList from '@/components/molecules/DoorCheckedInList.vue'
import DoorTabs from '@/components/molecules/DoorTabs.vue'
import SessionControls from '@/components/molecules/SessionControls.vue'
import DoorFamilyCard from '@/components/organisms/DoorFamilyCard.vue'
import ScanCardSheet from '@/components/organisms/ScanCardSheet.vue'
import type {
    ChildHealth,
    DoorCheckIn,
    DoorFamily,
    DoorScan,
    LevelOption,
    DoorSession,
    SessionChoice,
} from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { Search } from 'lucide-vue-next'
import { computed, reactive, ref } from 'vue'
import { route } from 'ziggy-js'

/**
 * A class's door (DECISIONS 2026-10-26), phone first, at its Session's own address (spec item 16: `/<code>/checkin`):
 * the class, its date and times, how many are checked in, the church-wide search, a card per family found, and below
 * them who is in, with Undo (DECISIONS 2026-10-27) — the count is that list's length. A child's health details come
 * only when their row is first opened — a partial reload of `health`, kept here by child id so each row shows its own
 * (DECISIONS 2026-10-28).
 * Above it, the door's tabs: Check in · Who's in (roll call), at the same code. Under the search, "Scan card" reads a
 * NXTGEN name card with the camera (slice 3; a desk scanner types the code into the search instead): the card's
 * child shows ticked on their family card, or "No child matches this card: …".
 * Children are checked in only while an admin has the class open (spec item 15): until then, and once closed, the
 * search is hidden and the list has no Undo; the class's admin opens and closes it here (SessionControls).
 */
const props = defineProps<{
    /** The Session at this address (spec item 16): its class, date and state (spec item 15). */
    session: DoorSession
    /** The bar's dropdown (Roy 2026-11-10): the satellite's classes open now that this person may open. */
    open_classes: SessionChoice[]
    /** An admin of the class: closes it here, and opens it again. */
    can_open: boolean
    checked_in: DoorCheckIn[]
    query: string
    /** The search was a name card (slice 3): its code and the children it named — ticked on their family cards. */
    scan: DoorScan | null
    families: DoorFamily[]
    levels: LevelOption[]
    health?: ChildHealth | null
}>()

const satellite = useSatellite()
const q = ref(props.query)
/** The health details fetched so far, by child id (null: none to show). */
const healths = reactive<Record<number, ChildHealth | null>>({})
const scanning = ref(false)

function visit(params: { q?: string; card?: boolean }): void {
    router.get(
        route('session.checkin', props.session.code),
        {
            q: params.q ?? props.query,
            card: params.card ? 1 : undefined,
        },
        { preserveScroll: true },
    )
}

/** A card no child matches (nothing else found) is an alert; a close match or several children ask staff to check. */
const scanTone = computed(() => (props.scan?.match === 'none' ? 'danger' : 'warning'))

/** A name card the camera read: search by it, as a card (so no match says so). */
function scanned(code: string): void {
    q.value = code
    visit({ q: code, card: true })
}

function undo(checkInId: number): void {
    router.put(route('session.undo', [props.session.code, checkInId]), {}, { preserveScroll: true })
}

const isOpen = computed(() => props.session.state === 'open')

function openClass(): void {
    router.post(
        route('serve.sessions.open', [satellite.value.slug, props.session.class_id]),
        {},
        { preserveScroll: true },
    )
}

function closeClass(): void {
    router.put(route('session.close', props.session.code), {}, { preserveScroll: true })
}

function openHealth(childId: number): void {
    router.reload({
        only: ['health'],
        data: { health: childId },
        onSuccess: (page) => {
            const health = (page.props.health ?? null) as ChildHealth | null
            healths[childId] = health?.id === childId ? health : null
        },
    })
}
</script>

<template>
    <Head title="Check in" />
    <ChurchShell
        title="Check in"
        :class-switch="{
            current: { code: session.code, name: session.name },
            classes: open_classes,
        }"
    >
        <div class="flex flex-col gap-4" data-surface="door">
            <DoorTabs current="check-in" :code="session.code" />
            <PageHeader
                :title="session.name"
                :sub="`${session.date_label} · ${session.times}`"
                title-testid="door-heading"
            />
            <p class="text-text text-xl font-semibold" data-testid="checked-in-count">
                <strong class="text-primary text-4xl font-bold tabular-nums">{{
                    checked_in.length
                }}</strong>
                checked in
            </p>
            <SessionControls
                :class-name="session.name"
                :state="session.state"
                :notice="session.notice"
                :state-label="session.state_label"
                :state-color="session.state_color"
                :can-open="can_open"
                @open="openClass"
                @close="closeClass"
            />

            <template v-if="isOpen">
                <form class="flex items-end gap-2" role="search" @submit.prevent="visit({ q })">
                    <TextField
                        v-model="q"
                        class="flex-1"
                        type="search"
                        label="Search a child's or parent's name"
                        hide-label
                        placeholder="Search a name"
                        data-testid="door-search"
                    >
                        <template #leading><Search class="size-6" /></template>
                    </TextField>
                    <AppButton type="submit" variant="secondary" size="lg">Search</AppButton>
                </form>
                <ScanCardSheet v-model:open="scanning" @code="scanned" />

                <Notice v-if="scan?.note" :tone="scanTone" data-testid="scan-note">{{
                    scan.note
                }}</Notice>
                <EmptyState v-if="query && families.length === 0 && scan?.match !== 'none'">
                    No family found. Check the spelling, or register them from the Families list.
                </EmptyState>
                <DoorFamilyCard
                    v-for="family in families"
                    :key="`${family.id}-${scan?.code ?? ''}`"
                    :family="family"
                    :pre-ticked="scan?.child_ids ?? []"
                    :levels="levels"
                    :session-code="session.code"
                    :healths="healths"
                    @health="openHealth"
                />
            </template>

            <DoorCheckedInList :check-ins="checked_in" :read-only="!isOpen" @undo="undo" />
        </div>
    </ChurchShell>
</template>
