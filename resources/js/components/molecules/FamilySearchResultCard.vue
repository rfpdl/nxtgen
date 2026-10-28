<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import Card from '@/components/atoms/Card.vue'
import EmailText from '@/components/atoms/EmailText.vue'
import Pill from '@/components/atoms/Pill.vue'
import ResendLinkButton from '@/components/atoms/ResendLinkButton.vue'
import type { FamilySearchResult } from '@/types'
import { ChevronRight } from 'lucide-vue-next'

/**
 * A church-wide search hit when registering for a parent: "already registered?" at a glance. The email is
 * masked; the family opens only when the staff member's scope allows it. Also one side of a possible duplicate,
 * where "Add a child" does not belong (`canAddChild: false`). An unconfirmed email offers "Send the confirmation link
 * again" when the server says the viewer may (`can_resend`) — a visiting family too. The children are pills in their
 * Level's colour; the name (when it opens) is a bold row ending in a › chevron.
 */
withDefaults(defineProps<{ result: FamilySearchResult; href: string; canAddChild?: boolean }>(), {
    canAddChild: true,
})

defineEmits<{ addChild: [] }>()
</script>

<template>
    <Card as="article" class="flex flex-col gap-2" data-testid="search-result">
        <a
            v-if="result.can_open"
            :href="href"
            class="text-text flex min-h-12 items-center justify-between gap-3 text-xl font-bold"
            >{{ result.primary_name
            }}<ChevronRight class="text-text-muted size-7 shrink-0" aria-hidden="true"
        /></a>
        <h3 v-else class="text-text text-xl font-bold">{{ result.primary_name }}</h3>
        <p class="text-text flex flex-wrap items-center gap-2">
            <EmailText :email="result.masked_email" />
            <Pill v-if="!result.email_confirmed" variant="warning">Email not confirmed</Pill>
        </p>
        <p v-if="result.children.length > 0" class="flex flex-wrap gap-1.5">
            <Pill v-for="(child, i) in result.children" :key="i" :level="child.level">{{
                child.nickname
            }}</Pill>
        </p>
        <p class="text-text-muted">{{ result.satellite }}</p>
        <div v-if="result.can_resend || canAddChild" class="flex flex-wrap gap-3 pt-1">
            <ResendLinkButton v-if="result.can_resend" :family-id="result.id" />
            <AppButton
                v-if="canAddChild"
                variant="secondary"
                data-testid="search-add-child"
                @click="$emit('addChild')"
            >
                Add a child to this family
            </AppButton>
        </div>
    </Card>
</template>
