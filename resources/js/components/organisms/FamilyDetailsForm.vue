<script setup lang="ts">
import FamilyFieldset from '@/components/molecules/FamilyFieldset.vue'
import GuardianFieldset from '@/components/molecules/GuardianFieldset.vue'
import OtherGuardiansFieldset from '@/components/organisms/OtherGuardiansFieldset.vue'
import type { FamilyDetails, FamilyDetailsDraft, GuardianEdit, SatelliteOption } from '@/types'
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

/**
 * "Edit family details": the family's fields, one parent's own fields and the family's other guardians (the
 * registration form's, through the same fieldsets), starting from what is saved, PUT to `action`. The other
 * guardians are sent as the form shows them, with the ids of the saved ones taken off (`removed_guardian_ids`) — a
 * guardian someone else added meanwhile is left alone. Never the email — it has its own confirm-by-link flow
 * (plan §4.4).
 */
const props = defineProps<{
    action: string
    details: FamilyDetails
    guardian: GuardianEdit
    /** The family's other guardians, as saved (each with its id). */
    others: GuardianEdit[]
    satellites: SatelliteOption[]
}>()

const open = ref(false)

const form = useForm<FamilyDetailsDraft>({
    home_satellite_id: props.details.home_satellite_id,
    area_text: props.details.area_text ?? '',
    fb_account: props.details.fb_account ?? '',
    wants_fb_group: props.details.wants_fb_group,
    in_dgroup: props.details.in_dgroup,
    dgroup_area_text: props.details.dgroup_area_text ?? '',
    dgroup_leader_name: props.details.dgroup_leader_name ?? '',
    dgroup_leader_phone: props.details.dgroup_leader_phone ?? '',
    wants_dgroup: props.details.wants_dgroup,
    lifestage: props.details.lifestage ?? '',
    notes: props.details.notes ?? '',
    guardian: {
        first_name: props.guardian.first_name,
        last_name: props.guardian.last_name,
        relationship: props.guardian.relationship,
        phone: props.guardian.phone,
        ministries: [...props.guardian.ministries],
    },
    guardians: drafts(props.others),
    removed_guardian_ids: [],
})

/** The other guardians as the form edits them: copies, so editing never changes what is saved. */
function drafts(others: GuardianEdit[]): FamilyDetailsDraft['guardians'] {
    return others.map((g) => ({
        id: g.id,
        first_name: g.first_name,
        last_name: g.last_name,
        relationship: g.relationship,
        phone: g.phone,
        ministries: [...g.ministries],
    }))
}

const errors = (): Record<string, string | undefined> =>
    form.errors as Record<string, string | undefined>

function submit(): void {
    form.put(props.action, {
        preserveScroll: true,
        // Only a save that worked starts again from what is saved (a new guardian now has its id); a refused one
        // keeps what was typed, so each error stays on its row.
        onSuccess: () => {
            form.guardians = drafts(props.others)
            form.removed_guardian_ids = []
            open.value = false
        },
    })
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
        <GuardianFieldset v-model="form.guardian" :errors="errors()" :with-email="false" />
        <OtherGuardiansFieldset
            v-model="form.guardians"
            :errors="errors()"
            @removed="(id) => form.removed_guardian_ids.push(id)"
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
