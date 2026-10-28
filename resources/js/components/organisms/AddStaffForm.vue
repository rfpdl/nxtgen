<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import Card from '@/components/atoms/Card.vue'
import SelectField from '@/components/atoms/SelectField.vue'
import TextField from '@/components/atoms/TextField.vue'
import type { AddStaffInput, Draft, GrantOption } from '@/types'
import { useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

/**
 * "Add someone who serves": name, email and ONE role from the choices the server says this viewer may give
 * (AccessScope::canGrant). An existing login, such as a parent, just gains the role.
 */
const props = defineProps<{ action: string; options: GrantOption[] }>()

const choice = ref<string | number | null>('0')
const roleOptions = computed(() =>
    props.options.map((o, i) => ({ value: String(i), label: o.label })),
)

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
    <Card as="section">
        <form class="flex flex-col gap-4" novalidate @submit.prevent="submit">
            <h2 class="text-text text-xl font-bold">Add someone</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <TextField
                    v-model="form.name"
                    label="Name"
                    autocomplete="off"
                    :error="form.errors.name"
                    data-testid="staff-name"
                />
                <TextField
                    v-model="form.email"
                    type="email"
                    label="Email"
                    autocomplete="off"
                    :error="form.errors.email"
                    data-testid="staff-email"
                />
            </div>
            <SelectField
                v-model="choice"
                label="Role"
                :options="roleOptions"
                data-testid="staff-role"
            />
            <AppButton
                type="submit"
                class="self-start"
                :loading="form.processing"
                data-testid="add-staff-submit"
            >
                Add and email a sign-in link
            </AppButton>
        </form>
    </Card>
</template>
