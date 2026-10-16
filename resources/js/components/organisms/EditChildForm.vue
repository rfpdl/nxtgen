<script setup lang="ts">
import ChildFieldset, { type ChildForm } from '@/components/molecules/ChildFieldset.vue'
import type { ChildPortal } from '@/types'
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

/**
 * "Edit" under a child: the same fields as registration (ChildFieldset), starting from what is saved, PUT to
 * `action` (the parent's family page, or staff who may edit the family). Never status or Level.
 * A child saved with no allergies was answered "no" — it is shown that way, not as unanswered.
 */
const props = defineProps<{
    action: string
    child: ChildPortal
    genders: { value: string; label: string }[]
}>()

const open = ref(false)

const form = useForm<ChildForm>({
    first_name: props.child.firstName,
    middle_name: props.child.middleName ?? '',
    last_name: props.child.lastName,
    nickname: props.child.nickname,
    gender: props.child.gender,
    date_of_birth: props.child.dateOfBirth,
    has_allergies: props.child.allergies !== null,
    allergies: props.child.allergies ?? '',
    illnesses: props.child.illnesses ?? '',
    dislikes: props.child.dislikes ?? '',
    fears: props.child.fears ?? '',
    parent_notes: props.child.parentNotes ?? '',
})

const errors = (): Record<string, string | undefined> =>
    Object.fromEntries(
        Object.entries(form.errors as Record<string, string | undefined>).map(([k, v]) => [
            `children.0.${k}`,
            v,
        ]),
    )

function submit(): void {
    form.put(props.action, { preserveScroll: true, onSuccess: () => (open.value = false) })
}
</script>

<template>
    <button
        v-if="!open"
        type="button"
        class="min-h-touch text-primary self-start text-lg font-semibold underline"
        data-testid="edit-child"
        @click="open = true"
    >
        Edit {{ child.nickname }}'s details
    </button>
    <form v-else class="flex flex-col gap-4" novalidate @submit.prevent="submit">
        <ChildFieldset
            v-model="form"
            :index="0"
            :genders="genders"
            :errors="errors()"
            :removable="false"
        />
        <div class="flex gap-3">
            <button
                type="submit"
                :disabled="form.processing"
                class="min-h-touch bg-primary text-on-primary rounded-md px-4 text-lg font-semibold"
                data-testid="edit-child-submit"
            >
                Save
            </button>
            <button
                type="button"
                class="min-h-touch text-text px-4 text-lg underline"
                @click="open = false"
            >
                Cancel
            </button>
        </div>
    </form>
</template>
