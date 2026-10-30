<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import Card from '@/components/atoms/Card.vue'
import { useChurch } from '@/composables/useChurch'
import type { PrintList } from '@/types'
import { computed } from 'vue'
import { route } from 'ziggy-js'

/**
 * A print list still to print, on Cards' Approved tab (name cards 04, GLOSSARY "Print list"): "Print list · 12 cards ·
 * made Sat 31 Oct 10:31 by Grace", "Download" (its Excel, again whenever needed) and "Mark these 10 printed" — its own
 * cards still approved, a PUT that keeps the scroll. The counts are the viewer's (the server's).
 */
const props = defineProps<{ list: PrintList }>()

const church = useChurch()

const what = computed(() =>
    [
        'Print list',
        props.list.cards === 1 ? '1 card' : `${props.list.cards} cards`,
        `made ${props.list.made}${props.list.made_by ? ` by ${props.list.made_by}` : ''}`,
    ].join(' · '),
)
const markLabel = computed(() =>
    props.list.to_print === 1 ? 'Mark it printed' : `Mark these ${props.list.to_print} printed`,
)
</script>

<template>
    <Card class="flex flex-col gap-3" data-testid="print-list">
        <p class="text-text text-lg font-semibold" data-testid="print-list-what">{{ what }}</p>
        <div class="flex flex-wrap gap-3">
            <AppButton
                variant="secondary"
                external
                :href="route('church.card-requests.print-lists.show', [church.slug, list.id])"
                data-testid="print-list-download"
                >Download</AppButton
            >
            <AppButton
                :href="
                    route('church.card-requests.print-lists.mark-printed', [church.slug, list.id])
                "
                method="put"
                :preserve-scroll="true"
                data-testid="print-list-mark"
                >{{ markLabel }}</AppButton
            >
        </div>
    </Card>
</template>
