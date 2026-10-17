<script setup lang="ts">
import FormField from '@/components/atoms/FormField.vue'
import FlashBanner from '@/components/molecules/FlashBanner.vue'
import { useFlashMessages } from '@/composables/useFlashMessages'
import type { UpdatePasswordInput } from '@/types'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

/** A staff member's backup password (plan D7): for when the email link is slow. The email link keeps working. */
defineProps<{ has_password: boolean }>()

const { message, dismiss } = useFlashMessages()
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

const input = 'min-h-touch border-border bg-surface text-text rounded-md border px-4 text-lg'
</script>

<template>
    <Head title="Backup password" />
    <FlashBanner :message="message" @dismiss="dismiss" />
    <main class="mx-auto flex min-h-dvh max-w-md flex-col justify-center gap-6 p-6">
        <h1 class="text-text text-3xl font-semibold">Backup password</h1>
        <p class="text-text text-lg">
            For a morning when the email link is slow to arrive. You can still sign in with the
            email link any time.
            <span v-if="has_password">You already have one; saving replaces it.</span>
        </p>
        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <FormField
                label="New password"
                hint="At least 10 characters — a short sentence is easiest."
                :error="form.errors.password"
            >
                <input
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    :class="input"
                    data-testid="new-password"
                />
            </FormField>
            <FormField label="The same password again">
                <input
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    :class="input"
                    data-testid="new-password-confirm"
                />
            </FormField>
            <button
                type="submit"
                :disabled="form.processing"
                class="min-h-touch bg-primary text-on-primary rounded-md px-4 text-lg font-semibold"
                data-testid="save-password"
            >
                Save password
            </button>
        </form>
        <Link :href="route('home')" class="text-text-muted underline">Back</Link>
    </main>
</template>
