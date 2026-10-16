<script setup lang="ts">
import FamilyFieldset, { type FamilyFields } from '@/components/molecules/FamilyFieldset.vue'
import GuardianFieldset, { type GuardianFields } from '@/components/molecules/GuardianFieldset.vue'
import type { FamilyDetails, GuardianEdit } from '@/types'
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

/**
 * "Edit family details": the family's fields and one parent's own fields (the registration form's, through the
 * same fieldsets), starting from what is saved, PUT to `action`. Never the email — it has its own
 * confirm-by-link flow (plan §4.4).
 */
const props = defineProps<{
    action: string
    details: FamilyDetails
    guardian: GuardianEdit
    satellites: { id: number; name: string }[]
    relationships: { value: string; label: string }[]
}>()

const open = ref(false)

const form = useForm<FamilyFields & { notes: string; guardian: GuardianFields }>({
    home_satellite_id: props.details.homeSatelliteId,
    area_text: props.details.areaText ?? '',
    fb_account: props.details.fbAccount ?? '',
    wants_fb_group: props.details.wantsFbGroup,
    in_dgroup: props.details.inDgroup,
    dgroup_area_text: props.details.dgroupAreaText ?? '',
    dgroup_leader_name: props.details.dgroupLeaderName ?? '',
    dgroup_leader_phone: props.details.dgroupLeaderPhone ?? '',
    wants_dgroup: props.details.wantsDgroup,
    lifestage: props.details.lifestage ?? '',
    notes: props.details.notes ?? '',
    guardian: {
        first_name: props.guardian.firstName,
        last_name: props.guardian.lastName,
        relationship: props.guardian.relationship,
        phone: props.guardian.phone,
        ministries: [...props.guardian.ministries],
    },
})

const errors = (): Record<string, string | undefined> =>
    form.errors as Record<string, string | undefined>

function submit(): void {
    form.put(props.action, { preserveScroll: true, onSuccess: () => (open.value = false) })
}
</script>

<template>
    <button
        v-if="!open"
        type="button"
        class="min-h-touch border-primary text-primary self-start rounded-md border px-4 text-lg font-semibold"
        data-testid="edit-family"
        @click="open = true"
    >
        Edit family details
    </button>
    <form v-else class="flex flex-col gap-4" novalidate @submit.prevent="submit">
        <GuardianFieldset
            v-model="form.guardian"
            :relationships="relationships"
            :errors="errors()"
            :with-email="false"
        />
        <FamilyFieldset v-model="form" :satellites="satellites" :errors="errors()" />
        <div class="flex gap-3">
            <button
                type="submit"
                :disabled="form.processing"
                class="min-h-touch bg-primary text-on-primary rounded-md px-4 text-lg font-semibold"
                data-testid="edit-family-submit"
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
