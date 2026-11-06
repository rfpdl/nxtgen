<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import Card from '@/components/atoms/Card.vue'
import type { SatelliteLink } from '@/types'
import { ref } from 'vue'

/**
 * One Link (GLOSSARY) on the Links page: its label and address, with Edit, Up and Down (not past either end), and
 * Remove — which asks once more before it is sent.
 */
defineProps<{ link: SatelliteLink; first: boolean; last: boolean }>()

const emit = defineEmits<{ edit: []; move: [direction: 'up' | 'down']; remove: [] }>()

const confirming = ref(false)

function remove(): void {
    confirming.value = false
    emit('remove')
}
</script>

<template>
    <Card as="article" class="flex flex-col gap-3" data-testid="link">
        <div class="flex min-w-0 flex-col gap-1">
            <h3 class="text-text text-xl font-bold" data-testid="link-name">{{ link.label }}</h3>
            <p class="text-text-muted text-lg break-all">{{ link.url }}</p>
        </div>
        <div v-if="!confirming" class="flex flex-wrap items-center gap-3">
            <AppButton variant="secondary" data-testid="edit-link" @click="emit('edit')">
                Edit
            </AppButton>
            <AppButton
                variant="quiet"
                :disabled="first"
                data-testid="link-up"
                @click="emit('move', 'up')"
                >Move up</AppButton
            >
            <AppButton
                variant="quiet"
                :disabled="last"
                data-testid="link-down"
                @click="emit('move', 'down')"
                >Move down</AppButton
            >
            <AppButton variant="link" data-testid="remove-link" @click="confirming = true">
                Remove
            </AppButton>
        </div>
        <div v-else class="bg-warning-soft rounded-control flex flex-col gap-3 p-3">
            <p class="text-text font-semibold">Remove "{{ link.label }}" from the poster?</p>
            <div class="flex flex-wrap gap-3">
                <AppButton variant="danger" data-testid="confirm-remove-link" @click="remove">
                    Yes, remove it
                </AppButton>
                <AppButton variant="secondary" data-testid="keep-link" @click="confirming = false">
                    Keep it
                </AppButton>
            </div>
        </div>
    </Card>
</template>
