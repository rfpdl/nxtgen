<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import EmptyState from '@/components/atoms/EmptyState.vue'
import SectionHeading from '@/components/atoms/SectionHeading.vue'
import LinkCard from '@/components/molecules/LinkCard.vue'
import LinkForm from '@/components/organisms/LinkForm.vue'
import { useChurch } from '@/composables/useChurch'
import type { MoveSatelliteLinkInput, SatelliteLinks } from '@/types'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { route } from 'ziggy-js'

/**
 * One satellite on the Links page: the Links its QR poster shows, in order, one opened for editing at a time in
 * place, and "+ Add a link" until the poster holds `max` — as the Classes page.
 */
defineProps<{ satellite: SatelliteLinks; max: number }>()

const church = useChurch()
const editing = ref<number | null>(null)
const adding = ref(false)

function move(linkId: number, direction: 'up' | 'down'): void {
    const body: MoveSatelliteLinkInput = { direction }
    router.put(route('nxtgen.admin.links.move', [church.value.slug, linkId]), body, {
        preserveScroll: true,
    })
}

function remove(linkId: number): void {
    router.delete(route('nxtgen.admin.links.destroy', [church.value.slug, linkId]), {
        preserveScroll: true,
    })
}
</script>

<template>
    <section class="flex flex-col gap-3" :data-testid="`satellite-${satellite.id}`">
        <SectionHeading>{{ satellite.name }}</SectionHeading>
        <EmptyState v-if="satellite.links.length === 0"
            >No links on this poster yet. Add its Facebook or WhatsApp group below.</EmptyState
        >
        <template v-for="(link, i) in satellite.links" :key="link.id">
            <LinkForm
                v-if="editing === link.id"
                :action="route('nxtgen.admin.links.update', [church.slug, link.id])"
                :link="link"
                submit-label="Save"
                @done="editing = null"
            />
            <LinkCard
                v-else
                :link="link"
                :first="i === 0"
                :last="i === satellite.links.length - 1"
                @edit="editing = link.id"
                @move="(direction) => move(link.id, direction)"
                @remove="remove(link.id)"
            />
        </template>
        <div v-if="adding" class="flex flex-col gap-2" data-testid="add-link">
            <SectionHeading level="3">Add a link to {{ satellite.name }}'s poster</SectionHeading>
            <LinkForm
                :action="route('nxtgen.admin.links.store', [church.slug])"
                :satellite-id="satellite.id"
                submit-label="Add link"
                @done="adding = false"
            />
        </div>
        <AppButton
            v-else-if="satellite.links.length < max"
            variant="secondary"
            class="self-start"
            data-testid="open-add-link"
            @click="adding = true"
            >+ Add a link</AppButton
        >
        <p v-else class="text-text-muted text-lg" data-testid="links-full">
            The poster holds {{ max }} links. Remove one to add another.
        </p>
    </section>
</template>
