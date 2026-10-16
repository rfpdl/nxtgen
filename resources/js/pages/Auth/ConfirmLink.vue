<script setup lang="ts">
import type { MagicLinkRoute } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

/**
 * The page an emailed link opens. One big button; pressing it is what uses the link (a scanner that only
 * fetches the page uses nothing). The server names the link's purpose (MagicLinkRouteEnum) and the address
 * the button posts to, so nothing here rebuilds a route.
 */
const props = defineProps<{ purpose: MagicLinkRoute; action: string }>()

const busy = ref(false)

/** Keyed by the generated enum: a new purpose does not typecheck until it has its words. */
const COPY: Record<MagicLinkRoute, { title: string; button: string }> = {
    login: { title: 'Sign in', button: 'Continue to sign in' },
    verify: { title: 'Confirm your email', button: 'Yes, this is my email' },
    claim: { title: 'Connect your family', button: 'Connect my family' },
}

const copy = computed(() => COPY[props.purpose])

function go(): void {
    busy.value = true
    router.post(props.action, {}, { onFinish: () => (busy.value = false) })
}
</script>

<template>
    <Head :title="copy.title" />
    <main class="mx-auto flex min-h-dvh max-w-md flex-col justify-center gap-6 p-6">
        <h1 class="text-text text-3xl font-semibold">{{ copy.title }}</h1>
        <button
            type="button"
            :disabled="busy"
            class="min-h-touch bg-primary text-on-primary rounded-md px-4 text-lg font-semibold"
            data-testid="confirm-link"
            @click="go"
        >
            {{ copy.button }}
        </button>
    </main>
</template>
