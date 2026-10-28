<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import Card from '@/components/atoms/Card.vue'
import EmailText from '@/components/atoms/EmailText.vue'
import Pill from '@/components/atoms/Pill.vue'
import type { StaffMember } from '@/types'

/** One person who serves, with each role they hold. Remove appears only where the viewer may remove it. */
defineProps<{ person: StaffMember }>()

defineEmits<{ remove: [roleId: number] }>()
</script>

<template>
    <Card as="article" class="flex flex-col gap-2" data-testid="staff-member">
        <h3 class="text-text text-xl font-bold">{{ person.name }}</h3>
        <p class="text-text-muted"><EmailText :email="person.email" /></p>
        <ul class="flex flex-col gap-1">
            <li v-for="role in person.roles" :key="role.id" class="flex items-center gap-3">
                <Pill variant="info">{{ role.label }}</Pill>
                <AppButton
                    v-if="role.can_remove"
                    variant="link"
                    data-testid="remove-role"
                    @click="$emit('remove', role.id)"
                >
                    Remove
                </AppButton>
            </li>
        </ul>
    </Card>
</template>
