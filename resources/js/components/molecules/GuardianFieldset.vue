<script setup lang="ts">
import SelectField from '@/components/atoms/SelectField.vue'
import TextField from '@/components/atoms/TextField.vue'
import { useEnum } from '@/composables/useEnum'
import type { GuardianDraft } from '@/types'
import { computed } from 'vue'

/**
 * A parent's own fields, on registration (with the email) and on editing (without: the email changes only
 * through the confirm-by-link flow, plan §4.4). The model is GuardianDetailsInput / GuardianInput as a draft;
 * errors arrive keyed `{errorKey}.{field}` (`guardian` — or `guardians.1` for one of the family's OTHER guardians,
 * which `other` words as someone else and which never has an email). Test ids start with `testid`. Writes IN PLACE
 * on the shared object (see ChildFieldset: autofill). The relationships come from the page's `enums.relationship`.
 * On the kit (the Bold look): TextField and SelectField.
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

const relationshipOptions = computed(() =>
    relationships.value.map((r) => ({ value: r.value, label: r.label })),
)
</script>

<template>
    <TextField
        v-model="guardian.first_name"
        :label="label.first"
        required
        :error="error('first_name')"
        :autocomplete="other ? 'off' : 'given-name'"
        :data-testid="`${testid}-first-name`"
    />
    <TextField
        v-model="guardian.last_name"
        :label="label.last"
        required
        :error="error('last_name')"
        :autocomplete="other ? 'off' : 'family-name'"
        :data-testid="`${testid}-last-name`"
    />
    <SelectField
        v-model="guardian.relationship"
        :label="label.relationship"
        required
        :options="relationshipOptions"
        :error="error('relationship')"
        :data-testid="`${testid}-relationship`"
    />
    <TextField
        v-model="guardian.phone"
        type="tel"
        label="Mobile number"
        required
        :error="error('phone')"
        :autocomplete="other ? 'off' : 'tel'"
        :data-testid="`${testid}-phone`"
    />
    <TextField
        v-if="withEmail"
        v-model="guardian.email"
        type="email"
        label="Email"
        required
        hint="We send a link here so you can sign in. No password needed."
        :error="error('email')"
        autocomplete="email"
        :data-testid="`${testid}-email`"
    />
    <TextField
        v-model="ministry"
        :label="label.ministry"
        hint="Leave empty if none"
        :error="error('ministries')"
        :data-testid="`${testid}-ministry`"
    />
</template>
