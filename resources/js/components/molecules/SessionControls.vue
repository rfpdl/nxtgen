<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import Notice from '@/components/atoms/Notice.vue'
import Pill from '@/components/atoms/Pill.vue'
import type { EnumColor, SessionState } from '@/types'
import { ref } from 'vue'

/**
 * Where today's class stands at its door (spec item 15; Roy 2026-11-10: an admin opens and closes the class): not
 * open yet, open, or closed — looked at, not changed. Its admin (`canOpen`) opens it, closes it after a second tap
 * that says what closing means, and opens it again the same day.
 */
defineProps<{
    className: string
    state: SessionState
    /** Why nothing changes now — the backend's words (SessionStateEnum::refusal), the same a refused check-in gets. */
    notice: string | null
    /** The state's word and colour when open — the backend's (SessionStateEnum::label, ::color). */
    stateLabel: string
    stateColor: EnumColor
    canOpen: boolean
}>()
const emit = defineEmits<{ open: []; close: [] }>()

const confirming = ref(false)

function close(): void {
    confirming.value = false
    emit('close')
}
</script>

<template>
    <div class="flex flex-col gap-3" data-testid="session-controls">
        <Notice v-if="state === 'not_open'" tone="warning">
            {{ notice }}
            <template v-if="!canOpen">An admin opens it.</template>
        </Notice>
        <Notice v-else-if="state === 'closed'" tone="warning">
            {{ notice }} It can be looked at, not changed.
        </Notice>
        <div v-else class="flex flex-wrap items-center gap-3">
            <Pill :variant="stateColor" data-testid="session-state">{{ stateLabel }}</Pill>
            <AppButton
                v-if="canOpen && !confirming"
                variant="link"
                data-testid="session-close"
                @click="confirming = true"
                >Close {{ className }}</AppButton
            >
        </div>
        <div
            v-if="confirming"
            class="bg-warning-soft rounded-control flex flex-col gap-3 p-3"
            data-testid="session-close-confirm"
        >
            <p class="text-text font-semibold">
                Close {{ className }}? Nothing can change after — no check-in, no Undo, no roll
                call. You can open it again today.
            </p>
            <div class="flex flex-wrap gap-3">
                <AppButton variant="danger" data-testid="confirm-close" @click="close"
                    >Yes, close it</AppButton
                >
                <AppButton variant="secondary" data-testid="keep-open" @click="confirming = false"
                    >Keep it open</AppButton
                >
            </div>
        </div>
        <AppButton
            v-if="canOpen && state !== 'open'"
            class="self-start"
            size="lg"
            data-testid="session-open"
            @click="emit('open')"
            >{{ state === 'closed' ? `Open ${className} again` : `Open ${className}` }}</AppButton
        >
    </div>
</template>
