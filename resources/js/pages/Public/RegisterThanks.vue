<script setup lang="ts">
import Card from '@/components/atoms/Card.vue'
import PageHeader from '@/components/atoms/PageHeader.vue'
import AppShell from '@/components/layout/AppShell.vue'
import { useChurch } from '@/composables/useChurch'
import { Head } from '@inertiajs/vue3'
import { MailCheck } from 'lucide-vue-next'

/** After registering (the Bold look): the same thank-you for everyone — it never says whether an email is known. */
const church = useChurch()
</script>

<template>
    <Head title="Thank you" />
    <AppShell title="Register" :place="church.name" narrow>
        <PageHeader title="Thank you" />
        <Card as="section" class="flex items-start gap-4">
            <MailCheck class="text-action size-10 shrink-0" aria-hidden="true" />
            <p class="text-text text-xl">
                {{ church.name }} has your details. If you can't find our email, check your spam
                folder, or ask a Lifeshaper at church to send it again.
            </p>
        </Card>
    </AppShell>
</template>
