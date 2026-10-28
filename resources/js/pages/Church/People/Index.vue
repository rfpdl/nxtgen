<script setup lang="ts">
import PageHeader from '@/components/atoms/PageHeader.vue'
import ChurchShell from '@/components/layout/ChurchShell.vue'
import StaffMemberCard from '@/components/molecules/StaffMemberCard.vue'
import AddStaffForm from '@/components/organisms/AddStaffForm.vue'
import { useChurch } from '@/composables/useChurch'
import type { GrantOption, StaffMember } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

/** People who serve (plan §6): add someone (one role from what this viewer may give), and each person with their roles. */
defineProps<{
    people: StaffMember[]
    grant_options: GrantOption[]
}>()

const church = useChurch()

function remove(roleId: number): void {
    router.delete(route('church.people.roles.destroy', [church.value.slug, roleId]), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="People" />
    <ChurchShell>
        <PageHeader :title="`People who serve (${people.length})`" />
        <AddStaffForm
            :action="route('church.people.store', church.slug)"
            :options="grant_options"
        />
        <div class="grid gap-3 md:grid-cols-2">
            <StaffMemberCard
                v-for="person in people"
                :key="person.id"
                :person="person"
                @remove="remove"
            />
        </div>
    </ChurchShell>
</template>
