<script setup lang="ts">
import FormField from '@/components/atoms/FormField.vue'
import { useEnum } from '@/composables/useEnum'
import type { GuardianDraft } from '@/types'
import { computed } from 'vue'

/**
 * A parent's own fields, on registration (with the email) and on editing (without: the email changes only
 * through the confirm-by-link flow, plan §4.4). The model is GuardianDetailsInput / GuardianInput as a draft;
 * errors arrive keyed `guardian.{field}`. Writes IN PLACE on the shared object (see ChildFieldset: autofill).
 * The relationships come from the page's `enums.relationship`.
 */
const props = defineProps<{
    errors: Record<string, string | undefined>
    withEmail: boolean
}>()

const guardian = defineModel<GuardianDraft>({ required: true })

const { options: relationships } = useEnum('relationship')

const error = (field: string): string | undefined => props.errors[`guardian.${field}`]

/** The Google Form asks for one ministry; the server keeps a list so a second can be added later. */
const ministry = computed({
    get: () => guardian.value.ministries[0] ?? '',
    set: (value: string) => {
        guardian.value.ministries = value.trim() === '' ? [] : [value.trim()]
    },
})

const input = 'min-h-touch rounded-md border border-border bg-surface px-4 text-lg text-text'
</script>

<template>
    <FormField label="Your first name" required :error="error('first_name')">
        <input
            v-model="guardian.first_name"
            :class="input"
            autocomplete="given-name"
            data-testid="guardian-first-name"
        />
    </FormField>
    <FormField label="Your last name" required :error="error('last_name')">
        <input
            v-model="guardian.last_name"
            :class="input"
            autocomplete="family-name"
            data-testid="guardian-last-name"
        />
    </FormField>
    <FormField label="You are the child's" required :error="error('relationship')">
        <select v-model="guardian.relationship" :class="input" data-testid="guardian-relationship">
            <option v-for="r in relationships" :key="r.value" :value="r.value">
                {{ r.label }}
            </option>
        </select>
    </FormField>
    <FormField label="Mobile number" required :error="error('phone')">
        <input
            v-model="guardian.phone"
            type="tel"
            :class="input"
            autocomplete="tel"
            data-testid="guardian-phone"
        />
    </FormField>
    <FormField
        v-if="withEmail"
        label="Email"
        required
        hint="We send a link here so you can sign in. No password needed."
        :error="error('email')"
    >
        <input
            v-model="guardian.email"
            type="email"
            :class="input"
            autocomplete="email"
            data-testid="guardian-email"
        />
    </FormField>
    <FormField
        label="CCF ministry you serve in"
        hint="Leave empty if none"
        :error="error('ministries')"
    >
        <input v-model="ministry" :class="input" data-testid="ministry" />
    </FormField>
</template>
