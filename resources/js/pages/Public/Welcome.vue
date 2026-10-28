<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import BrandMark from '@/components/atoms/BrandMark.vue'
import Card from '@/components/atoms/Card.vue'
import AppShell from '@/components/layout/AppShell.vue'
import { Head } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

/**
 * The front door for a visitor (the Bold look): the logo, a warm hello, and two big buttons — "Register your family"
 * (the church's form, `register_url`) first, then "Sign in". No menu: there is nowhere else to go yet. With no
 * `register_url` (the church is not set up yet) only Sign in shows.
 */
defineProps<{ register_url: string | null }>()
</script>

<template>
    <Head title="Welcome" />
    <AppShell title="NxtGen" place="Kids church" narrow>
        <Card as="section" class="flex flex-col items-center gap-5 py-8 text-center">
            <BrandMark large />
            <h1 class="text-text text-3xl font-bold">Welcome to NxtGen</h1>
            <p class="text-text text-xl">
                Kids church registration and check-in. We are glad you are here.
            </p>
            <div class="flex w-full flex-col gap-3 pt-2">
                <AppButton
                    v-if="register_url"
                    :href="register_url"
                    size="lg" block data-testid="welcome-register">
                    Register your family
                </AppButton>
                <AppButton
                    :href="route('login')"
                    variant="secondary"
                    size="lg"
                    block
                    data-testid="welcome-sign-in"
                >
                    Sign in
                </AppButton>
            </div>
            <p class="text-text-muted text-lg">
                New here? Register once. Already registered? Sign in with your email — no password
                needed.
            </p>
        </Card>
    </AppShell>
</template>
