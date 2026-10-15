<script setup lang="ts">
import FormField from '@/components/atoms/FormField.vue'
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

/**
 * "Use a password instead" — the staff backup (plan D7) for a morning the email is slow. Collapsed by default:
 * the email link is how everyone signs in; parents have no password at all.
 */
const props = defineProps<{ action: string }>()

const open = ref(false)
const form = useForm({ email: '', password: '' })

function submit(): void {
    form.post(props.action, { preserveScroll: true, onFinish: () => form.reset('password') })
}

const input = 'min-h-touch border-border bg-surface text-text rounded-md border px-4 text-lg'
</script>

<template>
    <button
        v-if="!open"
        type="button"
        class="min-h-touch text-text-muted self-start underline"
        data-testid="use-password"
        @click="open = true"
    >
        Volunteer with a password? Use it instead
    </button>
    <form v-else class="border-border flex flex-col gap-4 border-t pt-6" @submit.prevent="submit">
        <h2 class="text-text text-xl font-semibold">Volunteer password</h2>
        <FormField label="Email" :error="form.errors.email">
            <input
                v-model="form.email"
                type="email"
                autocomplete="username"
                :class="input"
                data-testid="password-email"
            />
        </FormField>
        <FormField label="Password">
            <input
                v-model="form.password"
                type="password"
                autocomplete="current-password"
                :class="input"
                data-testid="password-password"
            />
        </FormField>
        <button
            type="submit"
            :disabled="form.processing"
            class="min-h-touch bg-primary text-on-primary rounded-md px-4 text-lg font-semibold"
            data-testid="password-submit"
        >
            Sign in
        </button>
    </form>
</template>
