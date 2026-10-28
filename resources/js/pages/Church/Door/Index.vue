<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import EmptyState from '@/components/atoms/EmptyState.vue'
import Notice from '@/components/atoms/Notice.vue'
import TextField from '@/components/atoms/TextField.vue'
import ChurchShell from '@/components/layout/ChurchShell.vue'
import DoorCheckedInList from '@/components/molecules/DoorCheckedInList.vue'
import DoorClassPicker from '@/components/molecules/DoorClassPicker.vue'
import DoorTabs from '@/components/molecules/DoorTabs.vue'
import DoorFamilyCard from '@/components/organisms/DoorFamilyCard.vue'
import ScanCardSheet from '@/components/organisms/ScanCardSheet.vue'
import { useChurch } from '@/composables/useChurch'
import type {
    ChildHealth,
    DoorCheckIn,
    DoorClass,
    DoorFamily,
    DoorScan,
    LevelOption,
} from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { Search } from 'lucide-vue-next'
import { computed, reactive, ref } from 'vue'
import { route } from 'ziggy-js'

/**
 * The door (DECISIONS 2026-10-26), phone first: today, the class picker (today's classes at the satellites the
 * viewer's Role covers), how many are checked in, the church-wide search, a card per family found, and below them
 * who is in the chosen class, with Undo (DECISIONS 2026-10-27) — the count is that list's length. A child's health
 * details come only when their row is first opened — a partial reload of `health`, kept here by child id so each row
 * shows its own (DECISIONS 2026-10-28). On the kit (the Bold look): the class picker is a row of pressable chips
 * (DoorClassPicker, shared with Who's in).
 * Above it, the door's tabs: Check in · Who's in (roll call), keeping the class. Under the search, "Scan card" reads a
 * NXTGEN name card with the camera (slice 3; a desk scanner types the code into the search instead): the card's
 * child shows ticked on their family card, or "No child matches this card: …".
 */
const props = defineProps<{
    today: string
    classes: DoorClass[]
    class_id: number | null
    checked_in: DoorCheckIn[]
    query: string
    /** The search was a name card (slice 3): its code and the children it named — ticked on their family cards. */
    scan: DoorScan | null
    families: DoorFamily[]
    levels: LevelOption[]
    health?: ChildHealth | null
}>()

const church = useChurch()
const q = ref(props.query)
/** The health details fetched so far, by child id (null: none to show). */
const healths = reactive<Record<number, ChildHealth | null>>({})
const scanning = ref(false)

function visit(params: { class?: number | null; q?: string; card?: boolean }): void {
    router.get(
        route('church.door.show', church.value.slug),
        {
            class: params.class ?? props.class_id ?? undefined,
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
    router.put(
        route('church.door.undo', [church.value.slug, checkInId]),
        {},
        { preserveScroll: true },
    )
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
    <ChurchShell>
        <div class="flex flex-col gap-4" data-surface="door">
            <DoorTabs current="check-in" :class-id="class_id" />
            <DoorClassPicker
                :today="today"
                :classes="classes"
                :class-id="class_id"
                @pick="(id) => visit({ class: id })"
            />
            <p
                v-if="class_id !== null"
                class="text-text text-xl font-semibold"
                data-testid="checked-in-count"
            >
                <strong class="text-primary text-4xl font-bold tabular-nums">{{
                    checked_in.length
                }}</strong>
                checked in
            </p>

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
                :key="`${family.id}-${class_id}-${scan?.code ?? ''}`"
                :family="family"
                :pre-ticked="scan?.child_ids ?? []"
                :levels="levels"
                :class-id="class_id"
                :healths="healths"
                @health="openHealth"
            />

            <DoorCheckedInList v-if="class_id !== null" :check-ins="checked_in" @undo="undo" />
        </div>
    </ChurchShell>
</template>
