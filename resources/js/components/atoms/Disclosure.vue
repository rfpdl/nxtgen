<script setup lang="ts">
import { ChevronDown } from 'lucide-vue-next'
import { useId } from 'vue'

/**
 * Show more, in place (the Bold look): one big button (`aria-expanded`, `aria-controls`) holding the summary (the
 * default slot) and a chevron that turns over when open; the `panel` slot shows under it only while open. The
 * button takes the attributes given (a `data-testid`, a label). Open is the caller's (v-model:open), so it can load
 * what the panel needs on the first open.
 */
defineOptions({ inheritAttrs: false })
const open = defineModel<boolean>('open', { default: false })
const panelId = `disclosure-${useId()}`
</script>

<template>
    <div class="flex min-w-0 flex-col gap-2">
        <button
            v-bind="$attrs"
            type="button"
            :aria-expanded="open ? 'true' : 'false'"
            :aria-controls="panelId"
            class="rounded-control text-text flex min-h-16 w-full items-start gap-2 py-2 text-left"
            @click="open = !open"
        >
            <span class="min-w-0 flex-1"><slot /></span>
            <ChevronDown
                class="text-text-muted mt-1 size-7 shrink-0 transition-transform"
                :class="{ 'rotate-180': open }"
                aria-hidden="true"
                data-testid="chevron"
            />
        </button>
        <div v-show="open" :id="panelId">
            <slot v-if="open" name="panel" />
        </div>
    </div>
</template>
