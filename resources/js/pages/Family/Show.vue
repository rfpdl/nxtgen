<script setup lang="ts">
import SignOutButton from '@/components/atoms/SignOutButton.vue'
import { Head } from '@inertiajs/vue3'
import FlashBanner from '@/components/molecules/FlashBanner.vue'
import FamilyChildCard from '@/components/molecules/FamilyChildCard.vue'
import AddChildForm from '@/components/organisms/AddChildForm.vue'
import EditChildForm from '@/components/organisms/EditChildForm.vue'
import FamilyDetailsForm from '@/components/organisms/FamilyDetailsForm.vue'
import { useFlashMessages } from '@/composables/useFlashMessages'
import type { BranchOption, FamilyDetails, FamilyPortal, GuardianEdit } from '@/types'
import { route } from 'ziggy-js'

defineProps<{
    church: { name: string; slug: string }
    family: FamilyPortal
    details: FamilyDetails
    me: GuardianEdit
    satellites: BranchOption[]
    relationships: { value: string; label: string }[]
    genders: { value: string; label: string }[]
}>()

const { message, dismiss } = useFlashMessages()
</script>

<template>
    <Head title="My family" />
    <FlashBanner :message="message" @dismiss="dismiss" />
    <main class="mx-auto flex max-w-xl flex-col gap-6 p-6">
        <header class="flex flex-col gap-1">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-text text-3xl font-semibold">My family</h1>
                <SignOutButton />
            </div>
            <p class="text-text text-lg">{{ church.name }} · {{ family.homeSatellite }}</p>
            <p v-if="family.area" class="text-text-muted">Lives in {{ family.area }}</p>
        </header>

        <section class="flex flex-col gap-2">
            <h2 class="text-text text-2xl font-semibold">Parents and guardians</h2>
            <p
                v-for="g in family.guardians"
                :key="`${g.firstName}-${g.lastName}`"
                class="text-text text-lg"
                data-testid="guardian"
            >
                {{ g.firstName }} {{ g.lastName }} · {{ g.relationship
                }}<span v-if="g.phone"> · {{ g.phone }}</span>
            </p>
            <FamilyDetailsForm
                :action="route('family.update', church.slug)"
                :details="details"
                :guardian="me"
                :satellites="satellites"
                :relationships="relationships"
            />
        </section>

        <section class="flex flex-col gap-3">
            <h2 class="text-text text-2xl font-semibold">Children</h2>
            <FamilyChildCard v-for="child in family.children" :key="child.id" :child="child">
                <EditChildForm
                    :action="route('family.children.update', [church.slug, child.id])"
                    :child="child"
                    :genders="genders"
                />
            </FamilyChildCard>
            <AddChildForm
                :action="route('family.children.store', church.slug)"
                :genders="genders"
            />
        </section>
    </main>
</template>
