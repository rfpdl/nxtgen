<script setup lang="ts">
import CheckRow from '@/components/atoms/CheckRow.vue'
import SelectField from '@/components/atoms/SelectField.vue'
import TextField from '@/components/atoms/TextField.vue'
import type { FamilyFieldsDraft, SatelliteOption } from '@/types'
import { computed } from 'vue'

/**
 * The family's own fields — today's Google Form plus the home satellite — on registration and on editing.
 * The model is FamilyDetailsInput's family fields as a draft (registration shares its rules); errors arrive by
 * field name. Writes IN PLACE on the shared form object. On the kit (the Bold look): the yes/no questions are big
 * ticks (CheckRow).
 */
const props = defineProps<{
    satellites: SatelliteOption[]
    errors: Record<string, string | undefined>
}>()

const family = defineModel<FamilyFieldsDraft>({ required: true })

const satelliteOptions = computed(() =>
    props.satellites.map((s) => ({ value: s.id, label: s.name })),
)

function setSatellite(value: string | number | null): void {
    family.value.home_satellite_id = value === null ? '' : Number(value)
}
</script>

<template>
    <SelectField
        label="Satellite you usually attend"
        required
        placeholder="Choose…"
        :options="satelliteOptions"
        :model-value="family.home_satellite_id === '' ? null : family.home_satellite_id"
        :error="errors['home_satellite_id']"
        data-testid="home-satellite"
        @update:model-value="setSatellite"
    />
    <TextField
        v-model="family.area_text"
        label="Area where you live"
        hint="For example: Al Barsha"
        :error="errors['area_text']"
        data-testid="area"
    />
    <TextField
        v-model="family.fb_account"
        label="Facebook account"
        hint="To add you to the parents' group"
        :error="errors['fb_account']"
    />
    <CheckRow
        :model-value="family.wants_fb_group === true"
        class="text-lg"
        @update:model-value="family.wants_fb_group = $event"
        >Add me to the parents' FB group / chat</CheckRow
    >
    <CheckRow
        :model-value="family.in_dgroup === true"
        class="text-lg"
        data-testid="in-dgroup"
        @update:model-value="family.in_dgroup = $event"
        >I am in a DGroup</CheckRow
    >
    <template v-if="family.in_dgroup">
        <TextField
            v-model="family.dgroup_area_text"
            label="DGroup area"
            :error="errors['dgroup_area_text']"
            data-testid="dgroup-area"
        />
        <TextField
            v-model="family.dgroup_leader_name"
            label="DGroup leader's name"
            :error="errors['dgroup_leader_name']"
        />
        <TextField
            v-model="family.dgroup_leader_phone"
            type="tel"
            label="DGroup leader's phone"
            :error="errors['dgroup_leader_phone']"
            data-testid="dgroup-leader-phone"
        />
    </template>
    <CheckRow
        v-else
        :model-value="family.wants_dgroup === true"
        class="text-lg"
        data-testid="wants-dgroup"
        @update:model-value="family.wants_dgroup = $event"
        >I would like to join a DGroup</CheckRow
    >
    <TextField
        v-model="family.lifestage"
        label="Your lifestage"
        hint="For example: single, married, married with kids"
        :error="errors['lifestage']"
        data-testid="lifestage"
    />
</template>
