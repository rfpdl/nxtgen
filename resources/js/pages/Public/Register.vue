<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import RegisterFamilyForm from '@/components/organisms/RegisterFamilyForm.vue'
import { useChurch } from '@/composables/useChurch'
import type { SatelliteOption } from '@/types'
import { route } from 'ziggy-js'

defineProps<{ satellites: SatelliteOption[] }>()

const church = useChurch()
</script>

<template>
    <Head :title="`Register · ${church.name}`" />
    <main class="mx-auto flex max-w-xl flex-col gap-6 p-6">
        <header class="flex flex-col gap-2">
            <h1 class="text-3xl font-semibold text-text">Register your family</h1>
            <p class="text-lg text-text">For {{ church.name }} kids church. It takes about 5 minutes.</p>
        </header>
        <RegisterFamilyForm :action="route('public.register.store', church.slug)" :satellites="satellites" />
    </main>
</template>
