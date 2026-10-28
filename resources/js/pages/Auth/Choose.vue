<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import PageHeader from '@/components/atoms/PageHeader.vue'
import SignOutButton from '@/components/atoms/SignOutButton.vue'
import AppShell from '@/components/layout/AppShell.vue'
import type { LandingOption } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

/**
 * One login, more than one place: pick one. The pick is remembered on this device (POST /home/choice). On the kit
 * (the Bold look): each place is one big button, its church under its name.
 */
defineProps<{ options: LandingOption[] }>()

function choose(option: LandingOption): void {
    router.post(route('home.choice'), { url: option.url })
}
</script>

<template>
    <Head title="Where to?" />
    <AppShell title="Where to?" narrow>
        <PageHeader
            title="Where would you like to go?"
            sub="We will remember your choice on this phone or computer."
        />
        <nav class="flex flex-col gap-3" aria-label="Places">
            <AppButton
                v-for="option in options"
                :key="option.url"
                variant="secondary"
                size="lg"
                block
                class="flex-col py-3"
                data-testid="landing-option"
                @click="choose(option)"
            >
                <span class="text-xl font-bold">{{ option.label }}</span>
                <span class="text-text-muted text-base font-medium">{{ option.church_name }}</span>
            </AppButton>
        </nav>
        <SignOutButton class="self-start" />
    </AppShell>
</template>
