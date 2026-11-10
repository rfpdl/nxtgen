<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import ChoiceChips from '@/components/atoms/ChoiceChips.vue'
import EmptyState from '@/components/atoms/EmptyState.vue'
import ChurchShell from '@/components/layout/ChurchShell.vue'
import PageHeader from '@/components/atoms/PageHeader.vue'
import DoorTabs from '@/components/molecules/DoorTabs.vue'
import SessionControls from '@/components/molecules/SessionControls.vue'
import WhosInSquadCard from '@/components/molecules/WhosInSquadCard.vue'
import { useRollCall } from '@/composables/useRollCall'
import type { DoorSession, SessionChoice, WhosInSquad } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import { route } from 'ziggy-js'

/**
 * Who's in, the door's second tab (roll call, DECISIONS 2026-10-28), at the Session's own address (spec item 16:
 * `/<code>/whos-in`): the class and its date, as on Check in; the children checked in to it, by Squad, each Squad with "N of M present"; chips pick one Squad
 * (`?squad=`) or all. A tap on a child marks them present or unmarks them — shown at once, then confirmed by the
 * server; under each Squad, its head count is confirmed (issue 02). The list refreshes about every 20 seconds, so
 * children checked in at the door appear (useRollCall).
 */
const props = defineProps<{
    /** The Session at this address (spec item 16); closed, it is looked at, not changed (Roy 2026-11-10). */
    session: DoorSession
    /** The bar's dropdown (Roy 2026-11-10): the satellite's classes open now that this person may open. */
    open_classes: SessionChoice[]
    squads: WhosInSquad[]
    squad_id: number | null
}>()

const ALL = 'all'
const { pending, confirming, countErrors, mark, confirm, stop } = useRollCall(
    () => props.session.code,
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

const checkInHref = computed(() => route('session.checkin', props.session.code))

/** Another Squad: the taps not yet sent are dropped first, so none fires from the next page. */
function visit(params: { squad?: number | null }): void {
    stop()
    router.get(
        route('session.whos-in', props.session.code),
        { squad: params.squad ?? undefined },
        { preserveScroll: true },
    )
}

function pickSquad(id: string | number | null): void {
    visit({ squad: id === null || id === ALL ? null : Number(id) })
}
</script>

<template>
    <Head title="Who's in" />
    <ChurchShell
        title="Who's in"
        :class-switch="{
            current: { code: session.code, name: session.name },
            classes: open_classes,
        }"
    >
        <div class="flex flex-col gap-4" data-surface="door">
            <DoorTabs current="whos-in" :code="session.code" />
            <PageHeader
                :title="session.name"
                :sub="`${session.date_label} · ${session.times}`"
                title-testid="door-heading"
            />

            <SessionControls
                v-if="session.state !== 'open'"
                :class-name="session.name"
                :state="session.state"
                :notice="session.notice"
                :state-label="session.state_label"
                :state-color="session.state_color"
                :can-open="false"
            />
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
                    :read-only="session.state !== 'open'"
                    :confirming="confirming[squad.id] === true"
                    :count-error="countErrors[squad.id]"
                    @present="mark"
                    @confirm="(count: number) => confirm(squad.id, count)"
                />
            </template>
        </div>
    </ChurchShell>
</template>
