<script setup lang="ts">
import FormField from '@/components/atoms/FormField.vue'
import type { FamilyFieldsDraft, SatelliteOption } from '@/types'

/**
 * The family's own fields — today's Google Form plus the home satellite — on registration and on editing.
 * The model is FamilyDetailsInput's family fields as a draft (registration shares its rules); errors arrive by
 * field name. Writes IN PLACE on the shared form object.
 */
defineProps<{
    satellites: SatelliteOption[]
    errors: Record<string, string | undefined>
}>()

const family = defineModel<FamilyFieldsDraft>({ required: true })

const input = 'min-h-touch rounded-md border border-border bg-surface px-4 text-lg text-text'
const check = 'min-h-touch text-text flex items-center gap-3 text-lg'
</script>

<template>
    <FormField label="Satellite you usually attend" required :error="errors['home_satellite_id']">
        <select v-model="family.home_satellite_id" :class="input" data-testid="home-satellite">
            <option value="">Choose…</option>
            <option v-for="s in satellites" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
    </FormField>
    <FormField
        label="Area where you live"
        hint="For example: Al Barsha"
        :error="errors['area_text']"
    >
        <input v-model="family.area_text" :class="input" data-testid="area" />
    </FormField>
    <FormField
        label="Facebook account"
        hint="To add you to the parents' group"
        :error="errors['fb_account']"
    >
        <input v-model="family.fb_account" :class="input" />
    </FormField>
    <label :class="check"
        ><input v-model="family.wants_fb_group" type="checkbox" class="size-6" /> Add me to the
        parents' FB group / chat</label
    >
    <label :class="check"
        ><input v-model="family.in_dgroup" type="checkbox" class="size-6" data-testid="in-dgroup" />
        I am in a DGroup</label
    >
    <template v-if="family.in_dgroup">
        <FormField label="DGroup area" :error="errors['dgroup_area_text']">
            <input v-model="family.dgroup_area_text" :class="input" data-testid="dgroup-area" />
        </FormField>
        <FormField label="DGroup leader's name" :error="errors['dgroup_leader_name']">
            <input v-model="family.dgroup_leader_name" :class="input" />
        </FormField>
        <FormField label="DGroup leader's phone" :error="errors['dgroup_leader_phone']">
            <input
                v-model="family.dgroup_leader_phone"
                type="tel"
                :class="input"
                data-testid="dgroup-leader-phone"
            />
        </FormField>
    </template>
    <label v-else :class="check"
        ><input
            v-model="family.wants_dgroup"
            type="checkbox"
            class="size-6"
            data-testid="wants-dgroup"
        />
        I would like to join a DGroup</label
    >
    <FormField
        label="Your lifestage"
        hint="For example: single, married, married with kids"
        :error="errors['lifestage']"
    >
        <input v-model="family.lifestage" :class="input" data-testid="lifestage" />
    </FormField>
</template>
