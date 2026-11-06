<script setup lang="ts">
import RowList from '@/components/atoms/RowList.vue'
import type { GuardianPortal } from '@/types'

/** A family's parents and guardians, one row each: the name in bold, the relationship, the phone if there is one. */
defineProps<{ guardians: GuardianPortal[] }>()
</script>

<template>
    <RowList :items="guardians" :item-key="(g) => `${g.first_name}-${g.last_name}`">
        <template #default="{ item: g }">
            <p class="text-text text-lg" data-testid="guardian">
                <span class="font-bold">{{ g.first_name }} {{ g.last_name }}</span> ·
                {{ g.relationship_label }}<span v-if="g.phone"> · {{ g.phone }}</span>
            </p>
        </template>
    </RowList>
</template>
