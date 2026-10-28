<script setup lang="ts">
import Card from '@/components/atoms/Card.vue'
import PageHeader from '@/components/atoms/PageHeader.vue'
import SignOutButton from '@/components/atoms/SignOutButton.vue'
import AppShell from '@/components/layout/AppShell.vue'
import { Head } from '@inertiajs/vue3'

/** Signed in, but the email is linked to nothing yet (the Bold look): say what to do, and Sign out. */
</script>

<template>
    <Head title="Nothing here yet" />
    <AppShell title="NxtGen" narrow>
        <PageHeader title="Nothing here yet" />
        <Card class="flex flex-col gap-4">
            <p class="text-text text-xl">
                You are signed in, but this email is not linked to a family or a volunteer role yet.
                Ask a Lifeshaper at church for the registration link, or for a leader to add you.
            </p>
            <SignOutButton variant="secondary" size="lg" block />
        </Card>
    </AppShell>
</template>
