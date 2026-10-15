<script setup lang="ts">
import FormField from '@/components/atoms/FormField.vue'

/**
 * The fields for one child on the registration form. Field names match the server's ChildInput, and errors
 * arrive keyed `children.{index}.{field}`, so each child shows only its own.
 */
export type ChildForm = {
    first_name: string
    middle_name: string
    last_name: string
    nickname: string
    gender: string
    date_of_birth: string
    allergies: string
    illnesses: string
    dislikes: string
    fears: string
    parent_notes: string
}

const props = defineProps<{
    index: number
    genders: { value: string; label: string }[]
    errors: Record<string, string | undefined>
    removable: boolean
}>()

const child = defineModel<ChildForm>({ required: true })

defineEmits<{ remove: [] }>()

const error = (field: keyof ChildForm): string | undefined =>
    props.errors[`children.${props.index}.${field}`]

/*
 * Write each field IN PLACE on the shared reactive object. Replacing the whole object per keystroke used a
 * stale copy when several fields changed in the same tick (phone autofill), so earlier fields were lost —
 * found in the browser, pinned by the "autofill" test.
 */

const input = 'min-h-touch rounded-md border border-border bg-surface px-4 text-lg text-text'
</script>

<template>
    <fieldset class="border-border flex flex-col gap-4 rounded-lg border p-4">
        <legend class="text-text px-2 text-xl font-semibold">Child {{ index + 1 }}</legend>

        <FormField label="First name" required :error="error('first_name')">
            <input
                v-model="child.first_name"
                :class="input"
                :data-testid="`child-${index}-first-name`"
                autocomplete="off"
            />
        </FormField>
        <FormField label="Middle name" :error="error('middle_name')">
            <input v-model="child.middle_name" :class="input" />
        </FormField>
        <FormField label="Last name" required :error="error('last_name')">
            <input
                v-model="child.last_name"
                :class="input"
                :data-testid="`child-${index}-last-name`"
            />
        </FormField>
        <FormField
            label="Nickname"
            hint="What we call your child. It goes big on the ID card."
            :error="error('nickname')"
        >
            <input
                v-model="child.nickname"
                :class="input"
                :data-testid="`child-${index}-nickname`"
            />
        </FormField>
        <FormField label="Boy or girl" required :error="error('gender')">
            <select v-model="child.gender" :class="input" :data-testid="`child-${index}-gender`">
                <option value="">Choose…</option>
                <option v-for="g in genders" :key="g.value" :value="g.value">{{ g.label }}</option>
            </select>
        </FormField>
        <FormField label="Birthday" required :error="error('date_of_birth')">
            <input
                v-model="child.date_of_birth"
                type="date"
                :class="input"
                :data-testid="`child-${index}-dob`"
            />
        </FormField>
        <FormField
            label="Allergies"
            hint="Food or anything else. Leave empty if none."
            :error="error('allergies')"
        >
            <input
                v-model="child.allergies"
                :class="input"
                :data-testid="`child-${index}-allergies`"
            />
        </FormField>
        <FormField label="Sickness or illness" :error="error('illnesses')">
            <input v-model="child.illnesses" :class="input" />
        </FormField>
        <FormField label="Things your child dislikes" :error="error('dislikes')">
            <input v-model="child.dislikes" :class="input" />
        </FormField>
        <FormField label="Things your child is afraid of" :error="error('fears')">
            <input v-model="child.fears" :class="input" />
        </FormField>
        <FormField label="Anything else we should know" :error="error('parent_notes')">
            <textarea
                v-model="child.parent_notes"
                rows="3"
                class="border-border bg-surface text-text rounded-md border p-4 text-lg"
            />
        </FormField>

        <button
            v-if="removable"
            type="button"
            class="text-danger self-start text-lg font-semibold underline"
            :data-testid="`child-${index}-remove`"
            @click="$emit('remove')"
        >
            Remove this child
        </button>
    </fieldset>
</template>
