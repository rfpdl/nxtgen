<script setup lang="ts">
import FormField from '@/components/atoms/FormField.vue'
import type { AddStaffInput, Draft, GrantOption } from '@/types'
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

/**
 * "Add someone who serves": name, email and ONE role from the choices the server says this viewer may give
 * (AccessScope::canGrant). An existing login, such as a parent, just gains the role.
 */
const props = defineProps<{ action: string; options: GrantOption[] }>()

const choice = ref('0')

const form = useForm<Draft<AddStaffInput>>({
    name: '',
    email: '',
    role: '',
    scope_type: '',
    scope_id: 0,
})

function submit(): void {
    const picked = props.options[Number(choice.value)]
    if (picked === undefined) return
    form.role = picked.role
    form.scope_type = picked.scope_type
    form.scope_id = picked.scope_id
    form.post(props.action, { preserveScroll: true, onSuccess: () => form.reset() })
}
</script>

<template>
    <form
        class="border-border bg-surface flex flex-col gap-4 rounded-lg border p-4"
        novalidate
        @submit.prevent="submit"
    >
        <h2 class="text-text text-xl font-semibold">Add someone</h2>
        <FormField label="Name" :error="form.errors.name">
            <input
                v-model="form.name"
                type="text"
                autocomplete="off"
                class="min-h-touch border-input rounded-md border px-3"
                data-testid="staff-name"
            />
        </FormField>
        <FormField label="Email" :error="form.errors.email">
            <input
                v-model="form.email"
                type="email"
                autocomplete="off"
                class="min-h-touch border-input rounded-md border px-3"
                data-testid="staff-email"
            />
        </FormField>
        <FormField label="Role">
            <select
                v-model="choice"
                class="min-h-touch border-input rounded-md border px-3"
                data-testid="staff-role"
            >
                <option v-for="(option, i) in options" :key="option.label" :value="String(i)">
                    {{ option.label }}
                </option>
            </select>
        </FormField>
        <button
            type="submit"
            :disabled="form.processing"
            class="min-h-touch bg-primary text-on-primary self-start rounded-md px-4 text-lg font-semibold"
            data-testid="add-staff-submit"
        >
            Add and email a sign-in link
        </button>
    </form>
</template>
