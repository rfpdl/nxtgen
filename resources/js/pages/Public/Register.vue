<script setup lang="ts">
import PageHeader from '@/components/atoms/PageHeader.vue'
import AppShell from '@/components/layout/AppShell.vue'
import RegisterFamilyForm from '@/components/organisms/RegisterFamilyForm.vue'
import { useChurch } from '@/composables/useChurch'
import type { SatelliteOption } from '@/types'
import { Head } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

/** The church's registration link and QR code open this (the Bold look): the bar with no menu, then the form. */
defineProps<{ satellites: SatelliteOption[] }>()

const church = useChurch()
</script>

<template>
    <Head :title="`Register · ${church.name}`" />
    <AppShell title="Register" :place="church.name" narrow>
        <PageHeader
            title="Register your family"
            :sub="`For ${church.name} kids church. It takes about 5 minutes.`"
        />
        <RegisterFamilyForm
            :action="route('public.register.store', church.slug)"
            :satellites="satellites"
        />
    </AppShell>
</template>
