<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import ChoiceChips from '@/components/atoms/ChoiceChips.vue'
import SelectField from '@/components/atoms/SelectField.vue'
import TextArea from '@/components/atoms/TextArea.vue'
import TextField from '@/components/atoms/TextField.vue'
import { useEnum } from '@/composables/useEnum'
import type { ChildDraft } from '@/types'
import { computed } from 'vue'

/**
 * The fields for one child on the registration form (on the kit: the Bold look). The model is the server's
 * ChildInput as a draft (`has_allergies` stays null until the parent answers — Yes/No chips with neither picked: a
 * blank must never be read as "no allergies", review B6). Errors arrive keyed `children.{index}.{field}`, so each
 * child shows only its own. Boy/girl come from the page's `enums.gender`. The fieldset draws no card of its own —
 * the form around it decides.
 */
const props = defineProps<{
    index: number
    errors: Record<string, string | undefined>
    removable: boolean
}>()

const child = defineModel<ChildDraft>({ required: true })

defineEmits<{ remove: [] }>()

const { options: genders } = useEnum('gender')

const error = (field: keyof ChildDraft): string | undefined =>
    props.errors[`children.${props.index}.${field}`]

/*
 * Write each field IN PLACE on the shared reactive object. Replacing the whole object per keystroke used a
 * stale copy when several fields changed in the same tick (phone autofill), so earlier fields were lost —
 * found in the browser, pinned by the "autofill" test.
 */

const genderOptions = computed(() => genders.value.map((g) => ({ value: g.value, label: g.label })))

function setGender(value: string | number | null): void {
    child.value.gender = (value ?? '') as ChildDraft['gender']
}

const allergyOptions = computed(() => [
    { value: 'yes', label: 'Yes', testid: `child-${props.index}-has-allergies-yes` },
    { value: 'no', label: 'No', testid: `child-${props.index}-has-allergies-no` },
])
const allergyAnswer = computed(() =>
    child.value.has_allergies === null ? null : child.value.has_allergies ? 'yes' : 'no',
)

function setAllergies(value: string | number | null): void {
    child.value.has_allergies = value === null ? null : value === 'yes'
}
</script>

<template>
    <fieldset class="flex min-w-0 flex-col gap-4">
        <legend class="text-text mb-4 text-2xl font-bold">Child {{ index + 1 }}</legend>

        <TextField
            v-model="child.first_name"
            label="First name"
            required
            :error="error('first_name')"
            :data-testid="`child-${index}-first-name`"
            autocomplete="off"
        />
        <TextField v-model="child.middle_name" label="Middle name" :error="error('middle_name')" />
        <TextField
            v-model="child.last_name"
            label="Last name"
            required
            :error="error('last_name')"
            :data-testid="`child-${index}-last-name`"
        />
        <TextField
            v-model="child.nickname"
            label="Nickname"
            hint="What we call your child. It goes big on the ID card."
            required
            :error="error('nickname')"
            :data-testid="`child-${index}-nickname`"
        />
        <SelectField
            label="Boy or girl"
            required
            placeholder="Choose…"
            :options="genderOptions"
            :model-value="child.gender || null"
            :error="error('gender')"
            :data-testid="`child-${index}-gender`"
            @update:model-value="setGender"
        />
        <TextField
            v-model="child.date_of_birth"
            type="date"
            label="Birthday"
            required
            :error="error('date_of_birth')"
            :data-testid="`child-${index}-dob`"
        />
        <div class="flex flex-col gap-1.5">
            <p :id="`child-${index}-has-allergies-label`" class="text-text text-lg font-semibold">
                Does your child have any allergies?<span class="text-danger" aria-hidden="true">
                    *</span
                >
            </p>
            <ChoiceChips
                :labelledby="`child-${index}-has-allergies-label`"
                required
                :error="error('has_allergies')"
                :options="allergyOptions"
                :model-value="allergyAnswer"
                @update:model-value="setAllergies"
            />
        </div>
        <TextField
            v-if="child.has_allergies === true"
            v-model="child.allergies"
            label="What is your child allergic to?"
            hint="Food or anything else."
            required
            :error="error('allergies')"
            :data-testid="`child-${index}-allergies`"
        />
        <TextField
            v-model="child.illnesses"
            label="Sickness or illness"
            :error="error('illnesses')"
        />
        <TextField
            v-model="child.dislikes"
            label="Things your child dislikes"
            :error="error('dislikes')"
        />
        <TextField
            v-model="child.fears"
            label="Things your child is afraid of"
            :error="error('fears')"
        />
        <TextArea
            v-model="child.guardian_notes"
            label="Anything else we should know"
            :rows="3"
            :error="error('guardian_notes')"
        />

        <AppButton
            v-if="removable"
            variant="danger"
            class="self-start"
            :data-testid="`child-${index}-remove`"
            @click="$emit('remove')"
        >
            Remove this child
        </AppButton>
    </fieldset>
</template>
