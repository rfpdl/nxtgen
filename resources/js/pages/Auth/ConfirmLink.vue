<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import Card from '@/components/atoms/Card.vue'
import PageHeader from '@/components/atoms/PageHeader.vue'
import AppShell from '@/components/layout/AppShell.vue'
import type { MagicLinkRoute } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'

/**
 * The page an emailed link opens. One big button; pressing it is what uses the link (a scanner that only
 * fetches the page uses nothing). The server names the link's purpose, gives its words (MagicLinkRouteEnum) and the
 * address the button posts to, so nothing here rebuilds a route or retypes a word. On the kit (the Bold look): the
 * bar, a line that says what pressing does, and one big button.
 */
const props = defineProps<{
    purpose: MagicLinkRoute
    title: string
    prompt: string
    button: string
    action: string
}>()

const busy = ref(false)

function go(): void {
    busy.value = true
    router.post(props.action, {}, { onFinish: () => (busy.value = false) })
}
</script>

<template>
    <Head :title="title" />
    <AppShell narrow>
        <PageHeader :title="title" :sub="prompt" />
        <Card>
            <AppButton size="lg" block :loading="busy" data-testid="confirm-link" @click="go">
                {{ button }}
            </AppButton>
        </Card>
    </AppShell>
</template>
