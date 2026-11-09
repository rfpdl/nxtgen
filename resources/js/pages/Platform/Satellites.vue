<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import Card from '@/components/atoms/Card.vue'
import Disclosure from '@/components/atoms/Disclosure.vue'
import PageHeader from '@/components/atoms/PageHeader.vue'
import TextField from '@/components/atoms/TextField.vue'
import PlatformShell from '@/components/layout/PlatformShell.vue'
import type { PlatformSatellite } from '@/types'
import { Head, useForm, type InertiaForm } from '@inertiajs/vue3'
import { reactive, watch } from 'vue'
import { route } from 'ziggy-js'

/**
 * The platform's satellites (spec item 12): add one (its name and address; its QR code and Squads come with it), and
 * for each its address, its registration code — what its QR poster carries, never changed — and how many admins it
 * has; rename it or move its address, and open it (its Admin).
 */
const props = defineProps<{ satellites: PlatformSatellite[] }>()

const adding = useForm({ name: '', slug: '' })
function add(): void {
    adding.post(route('platform.satellites.store'), {
        preserveScroll: true,
        onSuccess: () => adding.reset(),
    })
}

// A form per satellite, made for each one the page gets — a satellite just added too (the page stays mounted).
const editing = reactive<Record<number, InertiaForm<{ name: string; slug: string }>>>({})
watch(
    () => props.satellites,
    (satellites) => {
        for (const s of satellites) {
            editing[s.id] ??= useForm({ name: s.name, slug: s.slug })
        }
    },
    { immediate: true },
)
const open = reactive<Record<number, boolean>>({})
function save(satellite: PlatformSatellite): void {
    editing[satellite.id]?.put(route('platform.satellites.update', satellite.slug), {
        preserveScroll: true,
        onSuccess: () => (open[satellite.id] = false),
    })
}
</script>

<template>
    <Head title="Satellites" />
    <PlatformShell title="Satellites">
        <PageHeader
            title="Satellites"
            sub="Each satellite's address, its QR code and its admins."
        />
        <Card
            as="form"
            class="flex flex-col gap-4"
            data-testid="add-satellite"
            @submit.prevent="add"
        >
            <h2 class="text-text text-xl font-bold">Add a satellite</h2>
            <TextField
                v-model="adding.name"
                label="Name"
                hint='e.g. "CCF Al Ain"'
                :error="adding.errors.name"
                data-testid="satellite-name"
            />
            <TextField
                v-model="adding.slug"
                label="Address"
                hint='Small letters and dashes, e.g. "al-ain" — its pages are /al-ain/…'
                :error="adding.errors.slug"
                data-testid="satellite-slug"
            />
            <AppButton
                type="submit"
                :disabled="adding.processing"
                data-testid="add-satellite-submit"
                >Add satellite</AppButton
            >
        </Card>
        <ul class="flex flex-col gap-3">
            <li
                v-for="satellite in satellites"
                :key="satellite.id"
                data-testid="platform-satellite"
            >
                <Card class="flex flex-col gap-2">
                    <span class="text-text text-xl font-bold">{{ satellite.name }}</span>
                    <span class="text-text-muted text-lg">
                        /{{ satellite.slug }} · QR code {{ satellite.code }} ·
                        {{ satellite.admins === 1 ? '1 admin' : `${satellite.admins} admins` }}
                    </span>
                    <AppButton
                        :href="route('satellite.home', satellite.slug)"
                        variant="secondary"
                        class="self-start"
                        data-testid="open-satellite"
                        >Open {{ satellite.name }}</AppButton
                    >
                    <Disclosure v-model:open="open[satellite.id]" data-testid="edit-satellite">
                        Rename it, or change its address
                        <template #panel>
                            <form
                                v-if="editing[satellite.id]"
                                class="flex flex-col gap-3"
                                @submit.prevent="save(satellite)"
                            >
                                <TextField
                                    v-model="editing[satellite.id]!.name"
                                    label="Name"
                                    :error="editing[satellite.id]!.errors.name"
                                />
                                <TextField
                                    v-model="editing[satellite.id]!.slug"
                                    label="Address"
                                    hint="Its QR code keeps working at the new address."
                                    :error="editing[satellite.id]!.errors.slug"
                                />
                                <AppButton
                                    type="submit"
                                    class="self-start"
                                    data-testid="save-satellite"
                                    >Save</AppButton
                                >
                            </form>
                        </template>
                    </Disclosure>
                </Card>
            </li>
        </ul>
    </PlatformShell>
</template>
