<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import Card from '@/components/atoms/Card.vue'
import TextField from '@/components/atoms/TextField.vue'
import type { Draft, SatelliteLink, SatelliteLinkInput } from '@/types'
import { useForm } from '@inertiajs/vue3'

/**
 * Add or change a Link (GLOSSARY): what the poster says under its QR, and the address it opens. Given a
 * `satelliteId` it ADDS one there (POST), with "Facebook group" and "WhatsApp group" a tap away as the label; given a
 * saved `link` it CHANGES that one (PUT). The server takes only an https address. `done` says it was saved or
 * cancelled, so the page can close it.
 */
const props = defineProps<{
    action: string
    submitLabel: string
    satelliteId?: number
    link?: SatelliteLink
}>()

const emit = defineEmits<{ done: [] }>()

const form = useForm<Draft<SatelliteLinkInput> & { satellite_id?: number }>({
    ...(props.satelliteId !== undefined ? { satellite_id: props.satelliteId } : {}),
    label: props.link?.label ?? '',
    url: props.link?.url ?? '',
})

const STARTERS = ['Facebook group', 'WhatsApp group']

function submit(): void {
    if (props.link === undefined) {
        form.post(props.action, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset()
                emit('done')
            },
        })
        return
    }
    form.put(props.action, { preserveScroll: true, onSuccess: () => emit('done') })
}
</script>

<template>
    <Card>
        <form
            class="flex flex-col gap-4"
            novalidate
            data-testid="link-form"
            @submit.prevent="submit"
        >
            <div v-if="link === undefined" class="flex flex-wrap gap-2">
                <AppButton
                    v-for="starter in STARTERS"
                    :key="starter"
                    variant="secondary"
                    data-testid="link-starter"
                    @click="form.label = starter"
                    >{{ starter }}</AppButton
                >
            </div>
            <TextField
                v-model="form.label"
                label="What the poster says"
                hint="e.g. Parents' WhatsApp group"
                autocomplete="off"
                :error="form.errors.label"
                data-testid="link-label"
            />
            <TextField
                v-model="form.url"
                type="url"
                label="Link"
                hint="Paste the whole link, starting with https://"
                autocomplete="off"
                :error="form.errors.url"
                data-testid="link-url"
            />
            <div class="flex flex-wrap gap-3">
                <AppButton type="submit" :loading="form.processing" data-testid="link-submit">
                    {{ submitLabel }}
                </AppButton>
                <AppButton variant="secondary" data-testid="link-cancel" @click="emit('done')">
                    Cancel
                </AppButton>
            </div>
        </form>
    </Card>
</template>
