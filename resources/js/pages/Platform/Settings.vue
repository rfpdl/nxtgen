<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import Card from '@/components/atoms/Card.vue'
import DateField from '@/components/atoms/DateField.vue'
import PageHeader from '@/components/atoms/PageHeader.vue'
import SelectField from '@/components/atoms/SelectField.vue'
import TextField from '@/components/atoms/TextField.vue'
import PlatformShell from '@/components/layout/PlatformShell.vue'
import type { ChurchSettings } from '@/types'
import { Head, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import { route } from 'ziggy-js'

/**
 * The church's settings (spec item 12): its time zone, the fee for a lost or damaged name card (in dirhams) and the
 * date Moving-Up measures ages on. A card already asked for keeps its fee.
 */
const props = defineProps<{
    settings: ChurchSettings
    timezones: string[]
}>()

const form = useForm({
    timezone: props.settings.timezone,
    card_fee: props.settings.card_fee,
    moving_up_date: props.settings.moving_up_date ?? '',
})

const timezoneOptions = computed(() => props.timezones.map((t) => ({ value: t, label: t })))

function save(): void {
    form.put(route('platform.settings.update'), { preserveScroll: true })
}
</script>

<template>
    <Head title="Settings" />
    <PlatformShell title="Settings">
        <PageHeader title="Settings" sub="For the whole church." />
        <Card as="form" class="flex flex-col gap-4" @submit.prevent="save">
            <SelectField
                v-model="form.timezone"
                label="Time zone"
                :options="timezoneOptions"
                :error="form.errors.timezone"
                data-testid="settings-timezone"
            />
            <TextField
                v-model="form.card_fee"
                label="Card fee (AED)"
                hint="For a lost or damaged name card. A first card is free."
                inputmode="decimal"
                :error="form.errors.card_fee"
                data-testid="settings-card-fee"
            />
            <DateField
                v-model="form.moving_up_date"
                label="Moving-Up date"
                :error="form.errors.moving_up_date"
                data-testid="settings-moving-up-date"
            />
            <AppButton type="submit" :disabled="form.processing" data-testid="settings-save"
                >Save settings</AppButton
            >
        </Card>
    </PlatformShell>
</template>
