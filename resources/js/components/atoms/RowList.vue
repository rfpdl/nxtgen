<script setup lang="ts" generic="T">
import Card from '@/components/atoms/Card.vue'
import { Link } from '@inertiajs/vue3'
import { ChevronRight } from 'lucide-vue-next'

/**
 * A list of rows (the Bold look): a flush Card with a line between rows, each row padded. With `href` each row is
 * one link — the whole row, ending in a › chevron, no underline; `#after` holds what must sit outside the link (a
 * button), hidden when empty. `plain` drops the card, for rows inside a card of their own. `rowTestid` marks each
 * row.
 */
withDefaults(
    defineProps<{
        items: T[]
        itemKey: (item: T) => string | number
        href?: (item: T) => string
        rowTestid?: string
        plain?: boolean
    }>(),
    { href: undefined, rowTestid: undefined },
)
</script>

<template>
    <component
        :is="plain ? 'ul' : Card"
        v-bind="plain ? {} : { as: 'ul', flush: true }"
        class="divide-border flex flex-col divide-y-2"
    >
        <li
            v-for="item in items"
            :key="itemKey(item)"
            class="flex flex-col"
            :data-testid="rowTestid"
        >
            <Link
                v-if="href"
                :href="href(item)"
                class="text-text hover:bg-surface-sunken flex min-h-16 items-center gap-3 px-4 py-3"
            >
                <span class="flex min-w-0 flex-1 flex-col items-start gap-1.5"
                    ><slot :item="item"
                /></span>
                <ChevronRight
                    class="text-text-muted size-7 shrink-0"
                    aria-hidden="true"
                    data-testid="row-chevron"
                />
            </Link>
            <div v-else class="px-4 py-3"><slot :item="item" /></div>
            <div class="px-4 pb-3 empty:hidden"><slot name="after" :item="item" /></div>
        </li>
    </component>
</template>
