<script setup lang="ts">
import ChurchShell from '@/components/layout/ChurchShell.vue'
import StaffMemberCard from '@/components/molecules/StaffMemberCard.vue'
import AddStaffForm from '@/components/organisms/AddStaffForm.vue'
import { useChurch } from '@/composables/useChurch'
import type { GrantOption, StaffMember } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

defineProps<{
    people: StaffMember[]
    grantOptions: GrantOption[]
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
        <h1 class="text-text text-2xl font-semibold">People who serve ({{ people.length }})</h1>
        <AddStaffForm :action="route('church.people.store', church.slug)" :options="grantOptions" />
        <div class="flex flex-col gap-2">
            <StaffMemberCard
                v-for="person in people"
                :key="person.id"
                :person="person"
                @remove="remove"
            />
        </div>
    </ChurchShell>
</template>
