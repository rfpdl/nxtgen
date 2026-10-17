<script setup lang="ts">
import GuardianFieldset from '@/components/molecules/GuardianFieldset.vue'
import type { OtherGuardianDraft } from '@/types'

/**
 * The family's OTHER parents and guardians (a father, a grandparent …) on the registration forms and "Edit family
 * details" (DECISIONS 2026-10-17, after first look): each through GuardianFieldset, without an email — only the
 * parent who registers signs in. Add and remove change the list IN PLACE; a saved guardian keeps its `id`, so the
 * server knows which one changed. Taking a saved one off says so (`removed`, with its id) — the server removes only
 * the guardians it is told to, never one it is not shown. Errors arrive keyed `guardians.{index}.{field}`.
 */
defineProps<{ errors: Record<string, string | undefined> }>()

const emit = defineEmits<{ removed: [id: number] }>()

const guardians = defineModel<OtherGuardianDraft[]>({ required: true })

function add(): void {
    guardians.value.push({
        id: null,
        first_name: '',
        last_name: '',
        relationship: 'father',
        phone: '',
        ministries: [],
    })
}

function remove(index: number): void {
    const [gone] = guardians.value.splice(index, 1)
    if (typeof gone?.id === 'number') emit('removed', gone.id)
}
</script>

<template>
    <fieldset
        v-for="(_, index) in guardians"
        :key="index"
        class="border-border flex flex-col gap-4 rounded-lg border p-4"
        :data-testid="`other-guardian-${index}-row`"
    >
        <legend class="text-text px-2 text-xl font-semibold">Another parent or guardian</legend>
        <GuardianFieldset
            v-model="guardians[index]!"
            :errors="errors"
            :with-email="false"
            :error-key="`guardians.${index}`"
            :testid="`other-guardian-${index}`"
            other
        />
        <button
            type="button"
            class="text-danger self-start text-lg font-semibold underline"
            :data-testid="`other-guardian-${index}-remove`"
            @click="remove(index)"
        >
            Remove this parent or guardian
        </button>
    </fieldset>
    <button
        type="button"
        class="min-h-touch border-primary text-primary self-start rounded-md border px-4 text-lg font-semibold"
        data-testid="add-guardian"
        @click="add"
    >
        + Add another parent or guardian
    </button>
</template>
