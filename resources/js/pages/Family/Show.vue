<script setup lang="ts">
import PageHeader from '@/components/atoms/PageHeader.vue'
import SectionHeading from '@/components/atoms/SectionHeading.vue'
import FamilyShell from '@/components/layout/FamilyShell.vue'
import FamilyChildCard from '@/components/molecules/FamilyChildCard.vue'
import AddChildForm from '@/components/organisms/AddChildForm.vue'
import ChildPhotoForm from '@/components/organisms/ChildPhotoForm.vue'
import EditChildForm from '@/components/organisms/EditChildForm.vue'
import FamilyDetailsForm from '@/components/organisms/FamilyDetailsForm.vue'
import GuardianList from '@/components/molecules/GuardianList.vue'
import type { FamilyHomePage } from '@/types'
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import { route } from 'ziggy-js'

/**
 * The parent's own family page (the Bold look, phone-first): the children first — each a card with the Level
 * pill, the photo and Edit — then the parents and guardians with "Edit family details". The menu (FamilyShell)
 * holds Switch and Sign out.
 */
const props = defineProps<FamilyHomePage>()

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
                    :action="route('family.children.photo', child.id)"
                    :consent-action="route('family.children.photo-consent.give', child.id)"
                    :withdraw-action="route('family.children.photo-consent', child.id)"
                    :delete-action="route('family.children.photo.destroy', child.id)"
                />
                <EditChildForm :action="route('family.children.update', child.id)" :child="child" />
            </FamilyChildCard>
            <AddChildForm :action="route('family.children.store')" />
        </section>

        <section class="flex flex-col gap-3">
            <SectionHeading>Parents and guardians</SectionHeading>
            <GuardianList :guardians="family.guardians" />
            <FamilyDetailsForm
                :action="route('family.update')"
                :details="details"
                :guardian="me"
                :others="other_guardians"
                :satellites="satellites"
            />
        </section>
    </FamilyShell>
</template>
