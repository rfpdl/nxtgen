<script setup lang="ts">
import ChurchShell from '@/components/layout/ChurchShell.vue'
import StaffMemberCard from '@/components/molecules/StaffMemberCard.vue'
import AddStaffForm from '@/components/organisms/AddStaffForm.vue'
import type { GrantOption, StaffMember } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

const props = defineProps<{
    church: { name: string; slug: string }
    people: StaffMember[]
    grantOptions: GrantOption[]
}>()

function remove(roleId: number): void {
    router.delete(route('church.people.roles.destroy', [props.church.slug, roleId]), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="People" />
    <ChurchShell :church="church">
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
