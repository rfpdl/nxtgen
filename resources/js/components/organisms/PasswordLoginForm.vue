<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import Card from '@/components/atoms/Card.vue'
import SectionHeading from '@/components/atoms/SectionHeading.vue'
import TextField from '@/components/atoms/TextField.vue'
import type { PasswordLoginInput } from '@/types'
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

/**
 * "Use a password instead" — the staff backup (plan D7) for a morning the email is slow. Collapsed by default:
 * the email link is how everyone signs in; parents have no password at all. On the kit (the Bold look): a Card with
 * two TextFields and one big button.
 */
const props = defineProps<{ action: string }>()

const open = ref(false)
const form = useForm<PasswordLoginInput>({ email: '', password: '' })

function submit(): void {
    form.post(props.action, { preserveScroll: true, onFinish: () => form.reset('password') })
}
</script>

<template>
    <AppButton
        v-if="!open"
        variant="link"
        class="self-start"
        data-testid="use-password"
        @click="open = true"
    >
        Volunteer with a password? Use it instead
    </AppButton>
    <Card v-else>
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
