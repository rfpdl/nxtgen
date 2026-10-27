<script setup lang="ts">
import ChurchShell from '@/components/layout/ChurchShell.vue'
import ChildHealthPanel from '@/components/molecules/ChildHealthPanel.vue'
import DoorCheckedInList from '@/components/molecules/DoorCheckedInList.vue'
import DoorFamilyCard from '@/components/organisms/DoorFamilyCard.vue'
import { useChurch } from '@/composables/useChurch'
import type { ChildHealth, DoorCheckIn, DoorClass, DoorFamily, LevelOption } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { route } from 'ziggy-js'

/**
 * The door (DECISIONS 2026-10-26), phone first: today, the class picker (today's classes at the satellites the
 * viewer's Role covers), how many are checked in, the church-wide search, a card per family found, and below them
 * who is in the chosen class, with Undo (DECISIONS 2026-10-27) — the count is that list's length. A child's health
 * details come only when their name is tapped — a partial reload of `health`.
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

/** "1PM · 13:00–15:00", and the satellite when the viewer's Role covers more than one. */
function optionLabel(c: DoorClass): string {
    return [c.name, c.times, manySatellites.value ? c.satellite : null].filter(Boolean).join(' · ')
}

function visit(params: { class?: number | null; q?: string }): void {
    router.get(
        route('church.door.show', church.value.slug),
        { class: params.class ?? props.class_id ?? undefined, q: params.q ?? props.query },
        { preserveScroll: true },
    )
}

function pickClass(event: Event): void {
    visit({ class: Number((event.target as HTMLSelectElement).value) })
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
            <header class="flex flex-col gap-2">
                <h1 class="text-text text-2xl font-semibold" data-testid="door-heading">
                    {{ chosen ? chosen.satellite : church.name }} · {{ today }}
                </h1>
                <label v-if="classes.length > 0" class="text-text flex items-center gap-3">
                    <span class="font-semibold">Class</span>
                    <span class="relative flex min-w-0 flex-1">
                        <select
                            :value="class_id ?? ''"
                            class="min-h-touch-door border-input bg-surface min-w-0 flex-1 appearance-none rounded-md border pr-10 pl-3 text-lg"
                            data-testid="door-class"
                            @change="pickClass"
                        >
                            <option v-for="c in classes" :key="c.id" :value="c.id">
                                {{ optionLabel(c) }}
                            </option>
                        </select>
                        <span
                            class="text-text pointer-events-none absolute top-1/2 right-3 -translate-y-1/2"
                            aria-hidden="true"
                            >▾</span
                        >
                    </span>
                </label>
                <p v-else class="text-text text-lg" data-testid="no-class">
                    No class meets today. Admins add classes on the Classes page.
                </p>
                <p
                    v-if="chosen"
                    class="text-text text-xl font-semibold"
                    data-testid="checked-in-count"
                >
                    {{ checked_in.length }} checked in
                </p>
            </header>

            <form class="flex gap-2" role="search" @submit.prevent="visit({ q })">
                <input
                    v-model="q"
                    type="search"
                    class="min-h-touch-door border-border bg-surface text-text min-w-0 flex-1 rounded-md border px-4 text-lg"
                    placeholder="Search name or parent"
                    aria-label="Search a child's or parent's name"
                    data-testid="door-search"
                />
                <button
                    type="submit"
                    class="bg-primary text-on-primary rounded-md px-4 text-lg font-semibold"
                >
                    Search
                </button>
            </form>

            <ChildHealthPanel
                v-if="showHealth && health"
                :health="health"
                @close="showHealth = false"
            />

            <p v-if="query && families.length === 0" class="text-text">
                No family found. Check the spelling, or register them from the Families list.
            </p>
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
