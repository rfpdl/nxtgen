<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import ChoiceChips from '@/components/atoms/ChoiceChips.vue'
import EmptyState from '@/components/atoms/EmptyState.vue'
import PageHeader from '@/components/atoms/PageHeader.vue'
import TextField from '@/components/atoms/TextField.vue'
import ChurchShell from '@/components/layout/ChurchShell.vue'
import ChildHealthPanel from '@/components/molecules/ChildHealthPanel.vue'
import DoorCheckedInList from '@/components/molecules/DoorCheckedInList.vue'
import DoorFamilyCard from '@/components/organisms/DoorFamilyCard.vue'
import { useChurch } from '@/composables/useChurch'
import type { ChildHealth, DoorCheckIn, DoorClass, DoorFamily, LevelOption } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { Search } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { route } from 'ziggy-js'

/**
 * The door (DECISIONS 2026-10-26), phone first: today, the class picker (today's classes at the satellites the
 * viewer's Role covers), how many are checked in, the church-wide search, a card per family found, and below them
 * who is in the chosen class, with Undo (DECISIONS 2026-10-27) — the count is that list's length. A child's health
 * details come only when their name is tapped — a partial reload of `health`. On the kit (the Bold look): the class
 * picker is a row of pressable chips, not a dropdown.
 */
const props = defineProps<{
    today: string
    classes: DoorClass[]
    class_id: number | null
    checked_in: DoorCheckIn[]
    query: string
    families: DoorFamily[]
    levels: LevelOption[]
    health?: ChildHealth | null
}>()

const church = useChurch()
const q = ref(props.query)
const showHealth = ref(false)

const chosen = computed(() => props.classes.find((c) => c.id === props.class_id) ?? null)
const manySatellites = computed(() => new Set(props.classes.map((c) => c.satellite)).size > 1)

/** A chip per class: "1PM" over "13:00–15:00", and the satellite when the viewer's Role covers more than one. */
const classChips = computed(() =>
    props.classes.map((c: DoorClass) => ({
        value: c.id,
        label: c.name,
        sub: [c.times, manySatellites.value ? c.satellite : null].filter(Boolean).join(' · '),
    })),
)

function visit(params: { class?: number | null; q?: string }): void {
    router.get(
        route('church.door.show', church.value.slug),
        { class: params.class ?? props.class_id ?? undefined, q: params.q ?? props.query },
        { preserveScroll: true },
    )
}

function pickClass(id: string | number | null): void {
    if (id !== null) {
        visit({ class: Number(id) })
    }
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
        onSuccess: () => {
            showHealth.value = true
        },
    })
}
</script>

<template>
    <Head title="Check in" />
    <ChurchShell>
        <div class="flex flex-col gap-4" data-surface="door">
            <PageHeader
                :title="`${chosen ? chosen.satellite : church.name} · ${today}`"
                title-testid="door-heading"
            />
            <ChoiceChips
                v-if="classes.length > 0"
                label="Class"
                :options="classChips"
                :model-value="class_id"
                data-testid="door-class"
                @update:model-value="pickClass"
            />
            <EmptyState v-else data-testid="no-class">
                No class meets today. Admins add classes on the Classes page.
            </EmptyState>
            <p v-if="chosen" class="text-text text-xl font-semibold" data-testid="checked-in-count">
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

            <ChildHealthPanel
                v-if="showHealth && health"
                :health="health"
                @close="showHealth = false"
            />

            <EmptyState v-if="query && families.length === 0">
                No family found. Check the spelling, or register them from the Families list.
            </EmptyState>
            <DoorFamilyCard
                v-for="family in families"
                :key="`${family.id}-${class_id}`"
                :family="family"
                :levels="levels"
                :class-id="class_id"
                @health="openHealth"
            />

            <DoorCheckedInList v-if="chosen" :check-ins="checked_in" @undo="undo" />
        </div>
    </ChurchShell>
</template>
