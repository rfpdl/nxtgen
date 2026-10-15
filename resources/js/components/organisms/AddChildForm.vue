<script setup lang="ts">
import ChildFieldset, { type ChildForm } from '@/components/molecules/ChildFieldset.vue'
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

/** "Add a child": the same fields as registration, posted to `action` (the parent's family page or a staff route). */
const props = defineProps<{
    action: string
    genders: { value: string; label: string }[]
    startOpen?: boolean
}>()

const open = ref(props.startOpen ?? false)

const form = useForm<ChildForm>({
    first_name: '',
    middle_name: '',
    last_name: '',
    nickname: '',
    gender: '',
    date_of_birth: '',
    allergies: '',
    illnesses: '',
    dislikes: '',
    fears: '',
    parent_notes: '',
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
        <button
            v-if="!open"
            type="button"
            class="min-h-touch border-primary text-primary self-start rounded-md border px-4 text-lg font-semibold"
            data-testid="open-add-child"
            @click="open = true"
        >
            + Add a child
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
                    data-testid="add-child-submit"
                >
                    Add child
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
    </div>
</template>
