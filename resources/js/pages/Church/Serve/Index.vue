<script setup lang="ts">
import { useSatellite } from '@/composables/useSatellite'
import EmptyState from '@/components/atoms/EmptyState.vue'
import PageHeader from '@/components/atoms/PageHeader.vue'
import SectionHeading from '@/components/atoms/SectionHeading.vue'
import ChurchShell from '@/components/layout/ChurchShell.vue'
import ServeClassCard from '@/components/molecules/ServeClassCard.vue'
import type { ServePage } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import { route } from 'ziggy-js'

/**
 * Serve's first page (spec items 16–17): today, then "Open now" — the classes open, each a way to its door at its own
 * address — and "Today": the rest of today's classes at this satellite that the person's Role covers, not open yet
 * (an admin opens one here and goes on to its door; a Lifeshaper sees "Not open yet", Roy 2026-11-10) or closed.
 * None today: "No class today". Replaces the old door page, where a class was picked (Roy 2026-11-10).
 */
const props = defineProps<ServePage>()

const openNow = computed(() => props.classes.filter((c) => c.state === 'open'))
const rest = computed(() => props.classes.filter((c) => c.state !== 'open'))

const satellite = useSatellite()

function open(classId: number): void {
    router.post(route('serve.sessions.open', [satellite.value.slug, classId]))
}
</script>

<template>
    <Head title="Today" />
    <ChurchShell>
        <div class="flex flex-col gap-4" data-surface="door">
            <PageHeader :title="today" title-testid="serve-heading" />
            <EmptyState v-if="classes.length === 0" data-testid="no-class">
                No class today. Admins add classes on the Classes page.
            </EmptyState>
            <section v-if="openNow.length > 0" class="flex flex-col gap-3" data-testid="open-now">
                <SectionHeading>Open now</SectionHeading>
                <ServeClassCard
                    v-for="serveClass in openNow"
                    :key="serveClass.id"
                    :serve-class="serveClass"
                />
            </section>
            <section v-if="rest.length > 0" class="flex flex-col gap-3" data-testid="today">
                <SectionHeading>Today</SectionHeading>
                <ServeClassCard
                    v-for="serveClass in rest"
                    :key="serveClass.id"
                    :serve-class="serveClass"
                    @open="open"
                />
            </section>
        </div>
    </ChurchShell>
</template>
