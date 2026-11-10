<script setup lang="ts">
import { useSatellite } from '@/composables/useSatellite'
import EmptyState from '@/components/atoms/EmptyState.vue'
import PageHeader from '@/components/atoms/PageHeader.vue'
import ChurchShell from '@/components/layout/ChurchShell.vue'
import ServeClassCard from '@/components/molecules/ServeClassCard.vue'
import type { ServeClass } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

/**
 * Serve's first page (spec items 16–17): today, and today's classes at this satellite that the person's Role covers
 * — an open one's door is at its own address; an admin opens one from here and goes on to its door (spec item 15).
 * Replaces the old door page, where a class was picked (Roy 2026-11-10).
 */
defineProps<{ today: string; classes: ServeClass[] }>()

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
            <ServeClassCard
                v-for="serveClass in classes"
                :key="serveClass.id"
                :serve-class="serveClass"
                @open="open"
            />
        </div>
    </ChurchShell>
</template>
