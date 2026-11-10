<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import Card from '@/components/atoms/Card.vue'
import Pill from '@/components/atoms/Pill.vue'
import type { ServeClass } from '@/types'
import { computed } from 'vue'
import { route } from 'ziggy-js'

/**
 * One of today's classes on Serve's first page (spec items 16–17): its name and times, where it stands (spec item 15),
 * and the way on — an open class's door at its own address (`/<code>/checkin`), a closed one's Who's in to look at; "Open" for
 * its admin while it is not open. Opening is the page's to send.
 */
const props = defineProps<{ serveClass: ServeClass }>()
const emit = defineEmits<{ open: [classId: number] }>()

const STATES = {
    open: { label: 'Open', variant: 'success' },
    not_open: { label: 'Not open yet', variant: 'neutral' },
    closed: { label: 'Closed', variant: 'warning' },
} as const

const state = computed(() => STATES[props.serveClass.state])
/** Open: its door. Closed: its Who's in, to look at (spec item 16). */
const door = computed(() => {
    const { code, state } = props.serveClass
    if (code === null) return null
    return route(state === 'closed' ? 'session.whos-in' : 'session.checkin', code)
})
</script>

<template>
    <Card
        as="article"
        class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between"
        data-testid="serve-class"
    >
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
            <h3 class="text-text text-xl font-bold">{{ serveClass.name }}</h3>
            <p class="text-text-muted text-lg">{{ serveClass.times }}</p>
            <Pill :variant="state.variant" data-testid="serve-class-state">{{ state.label }}</Pill>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <AppButton
                v-if="door !== null"
                :href="door"
                :variant="serveClass.state === 'open' ? 'primary' : 'secondary'"
                size="lg"
                data-testid="serve-door"
                >{{ serveClass.state === 'open' ? 'Go to the door' : 'See who was in' }}</AppButton
            >
            <AppButton
                v-if="serveClass.can_open && serveClass.state !== 'open'"
                size="lg"
                data-testid="serve-open"
                @click="emit('open', serveClass.id)"
                >{{
                    serveClass.state === 'closed'
                        ? `Open ${serveClass.name} again`
                        : `Open ${serveClass.name}`
                }}</AppButton
            >
        </div>
    </Card>
</template>
