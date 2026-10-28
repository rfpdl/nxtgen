<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import Card from '@/components/atoms/Card.vue'
import PageHeader from '@/components/atoms/PageHeader.vue'
import TextField from '@/components/atoms/TextField.vue'
import AppShell from '@/components/layout/AppShell.vue'
import type { UpdatePasswordInput } from '@/types'
import { Head, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

/**
 * A staff member's backup password (plan D7): for when the email link is slow. The email link keeps working. On the
 * kit (the Bold look): the bar (no menu — it is reached from the menu, and Back returns), one Card, the two fields
 * and the one button.
 */
defineProps<{ has_password: boolean }>()

const form = useForm<UpdatePasswordInput & { password_confirmation: string }>({
    password: '',
    password_confirmation: '',
})

function submit(): void {
    form.put(route('account.password.update'), {
        preserveScroll: true,
        onFinish: () => form.reset(),
    })
}
</script>

<template>
    <Head title="Backup password" />
    <AppShell title="Backup password" narrow>
        <PageHeader
            title="Backup password"
            :sub="`For a morning when the email link is slow to arrive. You can still sign in with the email link any time.${has_password ? ' You already have one; saving replaces it.' : ''}`"
        />
        <Card>
            <form class="flex flex-col gap-4" @submit.prevent="submit">
                <TextField
                    v-model="form.password"
                    type="password"
                    label="New password"
                    hint="At least 10 characters — a short sentence is easiest."
                    autocomplete="new-password"
                    :error="form.errors.password"
                    data-testid="new-password"
                />
                <TextField
                    v-model="form.password_confirmation"
                    type="password"
                    label="The same password again"
                    autocomplete="new-password"
                    data-testid="new-password-confirm"
                />
                <AppButton
                    type="submit"
                    size="lg"
                    block
                    :loading="form.processing"
                    data-testid="save-password"
                >
                    Save password
                </AppButton>
            </form>
        </Card>
        <AppButton :href="route('home')" variant="link" class="self-start">Back</AppButton>
    </AppShell>
</template>
