<script setup lang="ts">
import PageHeader from '@/components/atoms/PageHeader.vue'
import RowList from '@/components/atoms/RowList.vue'
import SectionHeading from '@/components/atoms/SectionHeading.vue'
import FamilyShell from '@/components/layout/FamilyShell.vue'
import FamilyChildCard from '@/components/molecules/FamilyChildCard.vue'
import AddChildForm from '@/components/organisms/AddChildForm.vue'
import ChildPhotoForm from '@/components/organisms/ChildPhotoForm.vue'
import EditChildForm from '@/components/organisms/EditChildForm.vue'
import FamilyDetailsForm from '@/components/organisms/FamilyDetailsForm.vue'
import { useChurch } from '@/composables/useChurch'
import type { FamilyDetails, FamilyPortal, GuardianEdit, SatelliteOption } from '@/types'
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import { route } from 'ziggy-js'

/**
 * The parent's own family page (the Bold look, phone-first): the children first — each a card with the Level
 * pill, the photo and Edit — then the parents and guardians with "Edit family details". The menu (FamilyShell)
 * holds Switch and Sign out.
 */
const props = defineProps<{
    family: FamilyPortal
    details: FamilyDetails
    me: GuardianEdit
    other_guardians: GuardianEdit[]
    satellites: SatelliteOption[]
}>()

const church = useChurch()

/** "The Reyes family", with "CCF DXB · Lives in Al Barsha" under it — the bar already says "My family". */
const title = computed(() => `The ${props.me.last_name} family`)

const sub = computed(() =>
    [props.family.home_satellite, props.family.area ? `Lives in ${props.family.area}` : null]
        .filter(Boolean)
        .join(' · '),
)
</script>

<template>
    <Head title="My family" />
    <FamilyShell>
        <PageHeader :title="title" :sub="sub" />

        <section class="flex flex-col gap-3">
            <SectionHeading>Children</SectionHeading>
            <FamilyChildCard v-for="child in family.children" :key="child.id" :child="child">
                <ChildPhotoForm
                    :child="child"
                    who="guardian"
                    :action="route('family.children.photo', [church.slug, child.id])"
                    :consent-action="
                        route('family.children.photo-consent.give', [church.slug, child.id])
                    "
                    :withdraw-action="
                        route('family.children.photo-consent', [church.slug, child.id])
                    "
                />
                <EditChildForm
                    :action="route('family.children.update', [church.slug, child.id])"
                    :child="child"
                />
            </FamilyChildCard>
            <AddChildForm :action="route('family.children.store', church.slug)" />
        </section>

        <section class="flex flex-col gap-3">
            <SectionHeading>Parents and guardians</SectionHeading>
            <RowList :items="family.guardians" :item-key="(g) => `${g.first_name}-${g.last_name}`">
                <template #default="{ item: g }">
                    <p class="text-text text-lg" data-testid="guardian">
                        <span class="font-bold">{{ g.first_name }} {{ g.last_name }}</span> ·
                        {{ g.relationship_label }}<span v-if="g.phone"> · {{ g.phone }}</span>
                    </p>
                </template>
            </RowList>
            <FamilyDetailsForm
                :action="route('family.update', church.slug)"
                :details="details"
                :guardian="me"
                :others="other_guardians"
                :satellites="satellites"
            />
        </section>
    </FamilyShell>
</template>
