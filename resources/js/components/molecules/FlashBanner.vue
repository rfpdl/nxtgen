<script setup lang="ts">
import type { FlashMessage } from '@/composables/useFlashMessages'
import AppButton from '@/components/atoms/AppButton.vue'
import { AlertTriangle, Check, X } from 'lucide-vue-next'

/**
 * What the server just said, at the top of the screen.
 *
 * **Sticky, not merely placed at the top.** The message answers a POST, and the tap that made the
 * POST is often two hundred children down the roster — a banner under the header would arrive
 * off-screen, which is indistinguishable from the silence this component exists to end.
 *
 * **Errors and successes are not symmetrical.** An error is the only account of something that did
 * not happen, so it is loud, carries an icon, and stays until it is dismissed or replaced. A success
 * is usually a second telling of something the screen already shows — a tick, a star, a card back in
 * the drawer — so it is a quiet line. The door goes further and flashes no success at all, because
 * per-child outcomes beside each name say more than a summary sentence.
 *
 * **`role="alert"` only for errors.** On a success it would interrupt a screen reader mid-name for
 * information the list is already carrying; `aria-live="polite"` waits its turn.
 *
 * The Bold look (DECISIONS 2026-10-28): a solid band — green for done, red for not done — in bold type.
 *
 * Dismissible because it covers the heading while it is up, and a staff member who has read it should be
 * able to put it away rather than scroll around it. `narrow` lines it up with a narrow page (AppShell `narrow`).
 */
const { message, narrow = false } = defineProps<{
    message: FlashMessage | null
    narrow?: boolean
}>()

defineEmits<{ dismiss: [] }>()
</script>

<template>
    <!--
        Keyed on the message id, so an IDENTICAL message re-mounts and is announced again. Keying on
        the text would silently swallow the second of two identical sentences, which on this app is
        the common case: the same child, checked in, undone, checked in.
    -->
    <div
        v-if="message"
        :key="message.id"
        class="sticky top-0 z-30 px-4 py-2"
        :class="
            message.tone === 'error'
                ? 'bg-danger text-on-danger focus-on-danger'
                : 'bg-action text-on-action focus-on-action'
        "
        :role="message.tone === 'error' ? 'alert' : undefined"
        :aria-live="message.tone === 'error' ? undefined : 'polite'"
        :data-testid="`flash-${message.tone}`"
    >
        <div
            class="mx-auto flex w-full items-center gap-3"
            :class="narrow ? 'max-w-2xl' : 'max-w-5xl'"
        >
            <component
                :is="message.tone === 'error' ? AlertTriangle : Check"
                class="size-6 shrink-0"
                :stroke-width="3"
                aria-hidden="true"
            />

            <p class="min-w-0 flex-1 text-lg font-bold">
                {{ message.text }}
            </p>

            <AppButton
                variant="quiet"
                size="icon"
                data-testid="dismiss-flash"
                aria-label="Dismiss this message"
                @click="$emit('dismiss')"
            >
                <X class="size-6" aria-hidden="true" />
            </AppButton>
        </div>
    </div>
</template>
