<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import Card from '@/components/atoms/Card.vue'
import PageHeader from '@/components/atoms/PageHeader.vue'
import TextField from '@/components/atoms/TextField.vue'
import AppShell from '@/components/layout/AppShell.vue'
import PasswordLoginForm from '@/components/organisms/PasswordLoginForm.vue'
import type { RequestMagicLinkInput } from '@/types'
import { Head, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

/**
 * Sign in for everyone (the Bold look): type the email, get a link — one field, one big button. The volunteers'
 * backup password waits below, closed.
 */
const form = useForm<RequestMagicLinkInput>({ email: '' })

function submit(): void {
    form.post(route('auth.link.request'), { preserveScroll: true, onSuccess: () => form.reset() })
}
</script>

<template>
    <Head title="Sign in" />
    <AppShell title="Sign in" narrow>
        <PageHeader
            title="Sign in"
            sub="Enter your email and we will send you a link. No password needed."
        />
        <Card>
            <form class="flex flex-col gap-4" @submit.prevent="submit">
                <TextField
                    v-model="form.email"
                    type="email"
                    label="Email"
                    autocomplete="email"
                    required
                    :error="form.errors.email"
                    data-testid="login-email"
                />
                <AppButton
                    type="submit"
                    size="lg"
                    block
                    :loading="form.processing"
                    data-testid="login-submit"
                >
                    Email me a link
                </AppButton>
            </form>
        </Card>
        <PasswordLoginForm :action="route('auth.password.login')" />
    </AppShell>
</template>
