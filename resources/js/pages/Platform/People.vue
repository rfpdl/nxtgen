<script setup lang="ts">
import PageHeader from '@/components/atoms/PageHeader.vue'
import PlatformShell from '@/components/layout/PlatformShell.vue'
import StaffMemberCard from '@/components/molecules/StaffMemberCard.vue'
import AddStaffForm from '@/components/organisms/AddStaffForm.vue'
import type { PlatformPeoplePage } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

/**
 * The platform's People (spec item 12): give any Role anywhere — a new satellite's first admin — and everyone who
 * serves, at every satellite, with their Roles.
 */
defineProps<PlatformPeoplePage>()

function remove(roleId: number): void {
    router.delete(route('platform.people.roles.destroy', roleId), { preserveScroll: true })
}
</script>

<template>
    <Head title="People" />
    <PlatformShell title="People">
        <PageHeader :title="`People who serve (${people.length})`" sub="At every satellite." />
        <AddStaffForm :action="route('platform.people.store')" :options="grant_options" />
        <div class="grid gap-3 md:grid-cols-2">
            <StaffMemberCard
                v-for="person in people"
                :key="person.id"
                :person="person"
                @remove="remove"
            />
        </div>
    </PlatformShell>
</template>
