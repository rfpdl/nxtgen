<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import FlashBanner from '@/components/molecules/FlashBanner.vue'
import PasswordLoginForm from '@/components/organisms/PasswordLoginForm.vue'
import { useFlashMessages } from '@/composables/useFlashMessages'
import { route } from 'ziggy-js'

const { message, dismiss } = useFlashMessages()
const form = useForm({ email: '' })

function submit(): void {
    form.post(route('auth.link.request'), { preserveScroll: true, onSuccess: () => form.reset() })
}
</script>

<template>
    <Head title="Sign in" />
    <FlashBanner :message="message" @dismiss="dismiss" />
    <main class="mx-auto flex min-h-dvh max-w-md flex-col justify-center gap-6 p-6">
        <h1 class="text-text text-3xl font-semibold">Sign in</h1>
        <p class="text-text text-lg">
            Enter your email and we will send you a link. No password needed.
        </p>
        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <label class="text-text flex flex-col gap-2 text-lg">
                Email
                <input
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    required
                    data-testid="login-email"
                    class="min-h-touch border-border bg-surface text-text rounded-md border px-4 text-lg"
                />
            </label>
            <p v-if="form.errors.email" class="text-danger" data-testid="login-email-error">
                {{ form.errors.email }}
            </p>
            <button
                type="submit"
                :disabled="form.processing"
                data-testid="login-submit"
                class="min-h-touch bg-primary text-on-primary rounded-md px-4 text-lg font-semibold"
            >
                Email me a link
            </button>
        </form>
        <PasswordLoginForm :action="route('auth.password.login')" />
    </main>
</template>
