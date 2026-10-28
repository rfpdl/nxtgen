<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import TextField from '@/components/atoms/TextField.vue'
import type { CorrectFamilyEmailInput } from '@/types'
import { useForm } from '@inertiajs/vue3'

/**
 * "Typed the email wrong? Correct it": staff who may edit a family put right an unconfirmed email (PUT to `action`);
 * the new address gets the confirmation link. Cleared once saved; a refused one keeps what was typed, with the error.
 */
const props = defineProps<{ action: string }>()

const form = useForm<CorrectFamilyEmailInput>({ email: '' })

function submit(): void {
    form.put(props.action, { preserveScroll: true, onSuccess: () => form.reset() })
}
</script>

<template>
    <form class="flex flex-wrap items-end gap-3" novalidate @submit.prevent="submit">
        <TextField
            v-model="form.email"
            class="min-w-0 flex-1 basis-64"
            type="email"
            label="Typed the email wrong? Correct it:"
            :error="form.errors.email"
            data-testid="correct-email"
        />
        <AppButton type="submit" variant="secondary" size="lg" :loading="form.processing"
            >Save email</AppButton
        >
    </form>
</template>
