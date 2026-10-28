<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import Card from '@/components/atoms/Card.vue'
import ChildFieldset from '@/components/molecules/ChildFieldset.vue'
import type { ChildDraft } from '@/types'
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

/** "Add a child": the same fields as registration, posted to `action` (the parent's family page or a staff route). */
const props = defineProps<{
    action: string
    startOpen?: boolean
}>()

const open = ref(props.startOpen ?? false)

const form = useForm<ChildDraft>({
    first_name: '',
    middle_name: '',
    last_name: '',
    nickname: '',
    gender: '',
    date_of_birth: '',
    has_allergies: null,
    allergies: '',
    illnesses: '',
    dislikes: '',
    fears: '',
    guardian_notes: '',
})

const errors = (): Record<string, string | undefined> =>
    Object.fromEntries(
        Object.entries(form.errors as Record<string, string | undefined>).map(([k, v]) => [
            `children.0.${k}`,
            v,
        ]),
    )

function submit(): void {
    form.post(props.action, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
            open.value = false
        },
    })
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <AppButton
            v-if="!open"
            variant="secondary"
            class="self-start"
            data-testid="open-add-child"
            @click="open = true"
        >
            + Add a child
        </AppButton>
        <Card v-else>
            <form class="flex flex-col gap-4" novalidate @submit.prevent="submit">
                <ChildFieldset v-model="form" :index="0" :errors="errors()" :removable="false" />
                <div class="flex flex-wrap gap-3">
                    <AppButton
                        type="submit"
                        :loading="form.processing"
                        data-testid="add-child-submit"
                    >
                        Add child
                    </AppButton>
                    <AppButton variant="secondary" @click="open = false">Cancel</AppButton>
                </div>
            </form>
        </Card>
    </div>
</template>
