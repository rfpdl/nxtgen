<script setup lang="ts">
import FormField from '@/components/atoms/FormField.vue'
import { useEnum } from '@/composables/useEnum'
import type { GuardianDraft } from '@/types'
import { computed } from 'vue'

/**
 * A parent's own fields, on registration (with the email) and on editing (without: the email changes only
 * through the confirm-by-link flow, plan §4.4). The model is GuardianDetailsInput / GuardianInput as a draft;
 * errors arrive keyed `{errorKey}.{field}` (`guardian` — or `guardians.1` for one of the family's OTHER guardians,
 * which `other` words as someone else and which never has an email). Test ids start with `testid`. Writes IN PLACE
 * on the shared object (see ChildFieldset: autofill). The relationships come from the page's `enums.relationship`.
 */
const props = withDefaults(
    defineProps<{
        errors: Record<string, string | undefined>
        withEmail: boolean
        errorKey?: string
        testid?: string
        other?: boolean
    }>(),
    { errorKey: 'guardian', testid: 'guardian', other: false },
)

const guardian = defineModel<GuardianDraft>({ required: true })

const { options: relationships } = useEnum('relationship')

const error = (field: string): string | undefined => props.errors[`${props.errorKey}.${field}`]

const label = computed(() =>
    props.other
        ? {
              first: 'First name',
              last: 'Last name',
              relationship: "They are the child's",
              ministry: 'CCF ministry they serve in',
          }
        : {
              first: 'Your first name',
              last: 'Your last name',
              relationship: "You are the child's",
              ministry: 'CCF ministry you serve in',
          },
)

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
    <FormField :label="label.first" required :error="error('first_name')">
        <input
            v-model="guardian.first_name"
            :class="input"
            :autocomplete="other ? 'off' : 'given-name'"
            :data-testid="`${testid}-first-name`"
        />
    </FormField>
    <FormField :label="label.last" required :error="error('last_name')">
        <input
            v-model="guardian.last_name"
            :class="input"
            :autocomplete="other ? 'off' : 'family-name'"
            :data-testid="`${testid}-last-name`"
        />
    </FormField>
    <FormField :label="label.relationship" required :error="error('relationship')">
        <select
            v-model="guardian.relationship"
            :class="input"
            :data-testid="`${testid}-relationship`"
        >
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
            :autocomplete="other ? 'off' : 'tel'"
            :data-testid="`${testid}-phone`"
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
            :data-testid="`${testid}-email`"
        />
    </FormField>
    <FormField :label="label.ministry" hint="Leave empty if none" :error="error('ministries')">
        <input v-model="ministry" :class="input" :data-testid="`${testid}-ministry`" />
    </FormField>
</template>
