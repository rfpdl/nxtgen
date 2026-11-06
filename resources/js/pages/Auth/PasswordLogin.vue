<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import PageHeader from '@/components/atoms/PageHeader.vue'
import AppShell from '@/components/layout/AppShell.vue'
import PasswordLoginForm from '@/components/organisms/PasswordLoginForm.vue'
import { Head } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

/** /loginp: the volunteers' backup password, open straight away. The email link is one tap back. */
</script>

<template>
    <Head title="Sign in with a password" />
    <AppShell title="NxtGen" narrow>
        <PageHeader title="Sign in" sub="For volunteers who set a backup password." />
        <PasswordLoginForm :action="route('auth.password.login')" />
        <AppButton
            :href="route('login')"
            variant="link"
            class="self-start"
            data-testid="use-email-link"
        >
            Use an email link instead
        </AppButton>
    </AppShell>
</template>
