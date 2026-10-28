<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import ChildFieldset from '@/components/molecules/ChildFieldset.vue'
import type { ChildDraft, ChildPortal } from '@/types'
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
}>()

const open = ref(false)

const form = useForm<ChildDraft>({
    first_name: props.child.first_name,
    middle_name: props.child.middle_name ?? '',
    last_name: props.child.last_name,
    nickname: props.child.nickname,
    gender: props.child.gender,
    date_of_birth: props.child.date_of_birth,
    has_allergies: props.child.allergies !== null,
    allergies: props.child.allergies ?? '',
    illnesses: props.child.illnesses ?? '',
    dislikes: props.child.dislikes ?? '',
    fears: props.child.fears ?? '',
    guardian_notes: props.child.guardian_notes ?? '',
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
    <AppButton
        v-if="!open"
        variant="secondary"
        class="self-start"
        data-testid="edit-child"
        @click="open = true"
    >
        Edit {{ child.nickname }}'s details
    </AppButton>
    <form
        v-else
        class="border-border flex flex-col gap-4 border-t-2 pt-4"
        novalidate
        @submit.prevent="submit"
    >
        <ChildFieldset v-model="form" :index="0" :errors="errors()" :removable="false" />
        <div class="flex flex-wrap gap-3">
            <AppButton type="submit" :loading="form.processing" data-testid="edit-child-submit">
                Save
            </AppButton>
            <AppButton variant="secondary" @click="open = false">Cancel</AppButton>
        </div>
    </form>
</template>
