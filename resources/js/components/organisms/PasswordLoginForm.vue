<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import Card from '@/components/atoms/Card.vue'
import SectionHeading from '@/components/atoms/SectionHeading.vue'
import TextField from '@/components/atoms/TextField.vue'
import type { PasswordLoginInput } from '@/types'
import { useForm } from '@inertiajs/vue3'

/**
 * The staff backup password (plan D7) for a morning the email is slow — on its own page, /loginp, never on /login
 * (Roy 2026-11-06): the email link is how everyone signs in; parents have no password at all. On the kit (the Bold
 * look): a Card with two TextFields and one big button.
 */
const props = defineProps<{ action: string }>()

const form = useForm<PasswordLoginInput>({ email: '', password: '' })

function submit(): void {
    form.post(props.action, { preserveScroll: true, onFinish: () => form.reset('password') })
}
</script>

<template>
    <Card>
        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <SectionHeading>Volunteer password</SectionHeading>
            <TextField
                v-model="form.email"
                type="email"
                label="Email"
                autocomplete="username"
                :error="form.errors.email"
                data-testid="password-email"
            />
            <TextField
                v-model="form.password"
                type="password"
                label="Password"
                autocomplete="current-password"
                data-testid="password-password"
            />
            <AppButton
                type="submit"
                size="lg"
                block
                :loading="form.processing"
                data-testid="password-submit"
            >
                Sign in
            </AppButton>
        </form>
    </Card>
</template>
