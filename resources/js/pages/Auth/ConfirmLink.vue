<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import Card from '@/components/atoms/Card.vue'
import PageHeader from '@/components/atoms/PageHeader.vue'
import AppShell from '@/components/layout/AppShell.vue'
import type { MagicLinkRoute } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

/**
 * The page an emailed link opens. One big button; pressing it is what uses the link (a scanner that only
 * fetches the page uses nothing). The server names the link's purpose (MagicLinkRouteEnum) and the address
 * the button posts to, so nothing here rebuilds a route. On the kit (the Bold look): the bar, a line that says what
 * pressing does, and one big button.
 */
const props = defineProps<{ purpose: MagicLinkRoute; action: string }>()

const busy = ref(false)

/** Keyed by the generated enum: a new purpose does not typecheck until it has its words. */
const COPY: Record<MagicLinkRoute, { title: string; sub: string; button: string }> = {
    login: {
        title: 'Sign in',
        sub: 'Press the button to finish signing in.',
        button: 'Continue to sign in',
    },
    verify: {
        title: 'Confirm your email',
        sub: 'Press the button to tell us this email is yours.',
        button: 'Yes, this is my email',
    },
    claim: {
        title: 'Connect your family',
        sub: 'Press the button to connect your family to this email.',
        button: 'Connect my family',
    },
}

const copy = computed(() => COPY[props.purpose])

function go(): void {
    busy.value = true
    router.post(props.action, {}, { onFinish: () => (busy.value = false) })
}
</script>

<template>
    <Head :title="copy.title" />
    <AppShell :title="copy.title" narrow>
        <PageHeader :title="copy.title" :sub="copy.sub" />
        <Card>
            <AppButton size="lg" block :loading="busy" data-testid="confirm-link" @click="go">
                {{ copy.button }}
            </AppButton>
        </Card>
    </AppShell>
</template>
