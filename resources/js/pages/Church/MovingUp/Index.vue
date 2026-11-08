<script setup lang="ts">
import { useSatellite } from '@/composables/useSatellite'
import AppButton from '@/components/atoms/AppButton.vue'
import Card from '@/components/atoms/Card.vue'
import CheckRow from '@/components/atoms/CheckRow.vue'
import EmptyState from '@/components/atoms/EmptyState.vue'
import Notice from '@/components/atoms/Notice.vue'
import PageHeader from '@/components/atoms/PageHeader.vue'
import Pill from '@/components/atoms/Pill.vue'
import SectionHeading from '@/components/atoms/SectionHeading.vue'
import ChurchShell from '@/components/layout/ChurchShell.vue'
import MovingUpLastCard from '@/components/molecules/MovingUpLastCard.vue'
import type { MoveChildrenUpInput, MovingUp, MovingUpCandidate } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { route } from 'ziggy-js'

/**
 * Moving-Up (TODO #27; DECISIONS 2026-11-06), church admins only: every child who will move — the Active children with
 * a Level, lowest first — grouped by Level, each ticked with where they go ("Level 3", or "Graduated" from the top);
 * the admin unticks anyone who stays, presses "Move up N children" and confirms — warned first if the last Moving-Up
 * is recent (another would move everyone up a second Level). The form sends each ticked child with the Level shown, so
 * a list that changed meanwhile moves nobody (the server says to look again). Above it, the last Moving-Up, to put
 * back (MovingUpLastCard).
 */
const props = defineProps<{
    children: MovingUpCandidate[]
    /** Active children with no Level yet: not moved. */
    without_level: number
    last: MovingUp | null
}>()

const satellite = useSatellite()

/** Child id → ticked. Everyone starts ticked; a fresh list (after a move) starts again. */
const ticked = ref<Record<number, boolean>>({})
watch(
    () => props.children,
    (children) => (ticked.value = Object.fromEntries(children.map((c) => [c.id, true]))),
    { immediate: true },
)

const groups = computed(() => {
    const byLevel = new Map<number, MovingUpCandidate[]>()
    for (const child of props.children) {
        byLevel.set(child.level.number, [...(byLevel.get(child.level.number) ?? []), child])
    }
    return [...byLevel.values()].map((children) => ({
        level: children[0]!.level,
        movesTo: children[0]!.moves_to,
        children,
    }))
})

/** "Untick all" on a Level that is all ticked, else "Tick all" — e.g. keep a whole Level back. */
function allTicked(children: MovingUpCandidate[]): boolean {
    return children.every((c) => ticked.value[c.id] === true)
}

function tickAll(children: MovingUpCandidate[], on: boolean): void {
    for (const child of children) ticked.value[child.id] = on
}

const chosen = computed(() => props.children.filter((c) => ticked.value[c.id] === true))
const moving = ref(false)
const confirming = ref(false)
const error = ref<string | null>(null)
const count = computed(() =>
    chosen.value.length === 1 ? '1 child' : `${chosen.value.length} children`,
)

function moveUp(): void {
    error.value = null
    const body: MoveChildrenUpInput = {
        children: chosen.value.map((c) => ({ child_id: c.id, level: c.level.number })),
    }
    router.post(route('admin.moving-up.store', satellite.value.slug), body, {
        preserveScroll: true,
        onStart: () => (moving.value = true),
        onFinish: () => {
            moving.value = false
            confirming.value = false
        },
        onError: (errors) => (error.value = Object.values(errors)[0] ?? 'Please look again.'),
    })
}
</script>

<template>
    <Head title="Moving-Up" />
    <ChurchShell>
        <PageHeader
            title="Moving-Up"
            sub="Every child goes up one Level; from Level 4 they graduate. Untick anyone who stays."
        />
        <MovingUpLastCard v-if="last" :moving-up="last" />
        <Notice v-if="without_level > 0" tone="warning" data-testid="moving-up-without-level">
            {{ without_level === 1 ? '1 child has' : `${without_level} children have` }} no Level
            yet, so will not move. Give them one on Children first.
        </Notice>
        <EmptyState v-if="children.length === 0" data-testid="moving-up-empty"
            >No active children with a Level to move up.</EmptyState
        >
        <template v-else>
            <section
                v-for="group in groups"
                :key="group.level.number"
                class="flex flex-col gap-3"
                data-testid="moving-up-level"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <SectionHeading>{{ group.level.name }} → {{ group.movesTo }}</SectionHeading>
                    <AppButton
                        variant="secondary"
                        size="sm"
                        data-testid="moving-up-tick-all"
                        @click="tickAll(group.children, !allTicked(group.children))"
                        >{{ allTicked(group.children) ? 'Untick all' : 'Tick all' }}</AppButton
                    >
                </div>
                <Card flush class="divide-border flex flex-col divide-y-2">
                    <CheckRow
                        v-for="child in group.children"
                        :key="child.id"
                        v-model="ticked[child.id]"
                        class="px-4"
                        data-testid="moving-up-child"
                    >
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="text-lg font-semibold">{{ child.nickname }}</span>
                            <Pill :level="child.level" />
                        </span>
                        <span class="text-text-muted block"
                            >{{ child.full_name }} · {{ child.satellite }}</span
                        >
                    </CheckRow>
                </Card>
            </section>
            <Notice v-if="error" tone="danger" data-testid="moving-up-error">{{ error }}</Notice>
            <Card v-if="confirming" class="flex flex-col gap-3" data-testid="moving-up-confirming">
                <Notice v-if="last?.is_recent" tone="warning" data-testid="moving-up-recent">
                    A Moving-Up already ran on {{ last.made }}. Moving up again moves these children
                    up a second Level.
                </Notice>
                <p class="text-text text-lg font-semibold">Move up {{ count }} now?</p>
                <div class="flex flex-wrap gap-3">
                    <AppButton
                        size="lg"
                        :loading="moving"
                        data-testid="moving-up-confirm"
                        @click="moveUp"
                        >Yes, move up {{ count }}</AppButton
                    >
                    <AppButton
                        variant="secondary"
                        size="lg"
                        data-testid="moving-up-cancel"
                        @click="confirming = false"
                        >Cancel</AppButton
                    >
                </div>
            </Card>
            <AppButton
                v-else
                size="lg"
                :disabled="chosen.length === 0"
                data-testid="moving-up-submit"
                @click="confirming = true"
                >Move up {{ count }}</AppButton
            >
        </template>
    </ChurchShell>
</template>
