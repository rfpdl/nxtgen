<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import ChoiceChips from '@/components/atoms/ChoiceChips.vue'
import EmptyState from '@/components/atoms/EmptyState.vue'
import ChurchShell from '@/components/layout/ChurchShell.vue'
import DoorClassPicker from '@/components/molecules/DoorClassPicker.vue'
import DoorTabs from '@/components/molecules/DoorTabs.vue'
import WhosInSquadCard from '@/components/molecules/WhosInSquadCard.vue'
import { useChurch } from '@/composables/useChurch'
import { useRollCall } from '@/composables/useRollCall'
import type { DoorClass, WhosInSquad } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import { route } from 'ziggy-js'

/**
 * Who's in, the door's second tab (roll call, DECISIONS 2026-10-28): the same class picker and day as Check in; the
 * children checked in to the chosen class, by Squad, each Squad with "N of M present"; chips pick one Squad
 * (`?squad=`) or all. A tap on a child marks them present or unmarks them — shown at once, then confirmed by the
 * server; under each Squad, its head count is confirmed (issue 02). The list refreshes about every 20 seconds, so
 * children checked in at the door appear (useRollCall).
 */
const props = defineProps<{
    today: string
    classes: DoorClass[]
    class_id: number | null
    squads: WhosInSquad[]
    squad_id: number | null
}>()

const ALL = 'all'
const church = useChurch()
const { pending, confirming, countErrors, mark, confirm, stop } = useRollCall(
    () => church.value.slug,
)

const squadChips = computed(() => [
    { value: ALL, label: 'All Squads', testid: 'squad-chip' },
    ...props.squads.map((s) => ({ value: s.id, label: s.name, testid: 'squad-chip' })),
])

/** The Squads shown (the one picked, or all), with any tap still on its way already counted. */
const shown = computed(() =>
    props.squads
        .filter((s) => props.squad_id === null || s.id === props.squad_id)
        .map((s) => {
            const children = s.children.map((c) => ({ ...c, present: pending[c.id] ?? c.present }))
            return { ...s, children, present: children.filter((c) => c.present).length }
        }),
)

const checkInHref = computed(() =>
    route('church.door.show', { church: church.value.slug, class: props.class_id ?? undefined }),
)

/** Another class or Squad: the taps not yet sent are dropped first, so none fires from the next page. */
function visit(params: { class?: number; squad?: number | null }): void {
    stop()
    router.get(
        route('church.door.whos-in', church.value.slug),
        { class: params.class ?? props.class_id ?? undefined, squad: params.squad ?? undefined },
        { preserveScroll: true },
    )
}

function pickSquad(id: string | number | null): void {
    visit({ squad: id === null || id === ALL ? null : Number(id) })
}
</script>

<template>
    <Head title="Who's in" />
    <ChurchShell>
        <div class="flex flex-col gap-4" data-surface="door">
            <DoorTabs current="whos-in" :class-id="class_id" />
            <DoorClassPicker
                :today="today"
                :classes="classes"
                :class-id="class_id"
                @pick="(id) => visit({ class: id })"
            />

            <template v-if="class_id !== null">
                <EmptyState
                    v-if="squads.length === 0"
                    title="No one checked in yet"
                    data-testid="whos-in-empty"
                >
                    Children show here, by Squad, once they are checked in at the door.
                    <template #action>
                        <AppButton :href="checkInHref" data-testid="go-check-in"
                            >Go to Check in</AppButton
                        >
                    </template>
                </EmptyState>
                <template v-else>
                    <ChoiceChips
                        label="Squad"
                        :options="squadChips"
                        :model-value="squad_id ?? ALL"
                        data-testid="squad-picker"
                        @update:model-value="pickSquad"
                    />
                    <WhosInSquadCard
                        v-for="squad in shown"
                        :key="squad.id"
                        :squad="squad"
                        :confirming="confirming[squad.id] === true"
                        :count-error="countErrors[squad.id]"
                        @present="mark"
                        @confirm="
                            (count: number) =>
                                class_id !== null && confirm(class_id, squad.id, count)
                        "
                    />
                </template>
            </template>
        </div>
    </ChurchShell>
</template>
