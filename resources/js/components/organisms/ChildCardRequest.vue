<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import ChoiceChips from '@/components/atoms/ChoiceChips.vue'
import Pill from '@/components/atoms/Pill.vue'
import TextArea from '@/components/atoms/TextArea.vue'
import { useEnum } from '@/composables/useEnum'
import type { CardFee, CardRequest, CardRequestInput, Draft } from '@/types'
import { useForm } from '@inertiajs/vue3'
import { computed, ref, useId } from 'vue'

/**
 * A child's name card on the staff family page (name cards issue 01, DECISIONS 2026-10-29). With an open Card request
 * it says where it is in the server's words — "Card: Asked · not paid"; otherwise "New card…" opens a picker: Lost or Damaged (the page's
 * `enums.card_reason`) and an optional note, posted to `action`. Saved, the page comes back with the request and the
 * picker closes. Staff only: a parent's page never has it.
 */
const props = defineProps<{ request: CardRequest | null; action: string }>()

/** The Pill's colour comes from the fee state alone (CardFeeEnum::color()): amber while not paid. */
const FEE_VARIANTS: Record<CardFee, 'warning' | 'info'> = {
    not_paid: 'warning',
    paid: 'info',
    free: 'info',
}

const { options: reasons } = useEnum('card_reason')
const chips = computed(() =>
    reasons.value.map((r) => ({
        value: r.value,
        label: r.label,
        testid: `card-reason-${r.value}`,
    })),
)
const questionId = useId()

const open = ref(false)
const form = useForm<Draft<CardRequestInput>>({ reason: '', note: '' })

function close(): void {
    open.value = false
    form.reset()
    form.clearErrors()
}

function submit(): void {
    form.post(props.action, { preserveScroll: true, onSuccess: close })
}
</script>

<template>
    <div class="border-border flex flex-col gap-3 border-b-2 pb-3" data-testid="child-card-request">
        <p v-if="request">
            <Pill :variant="FEE_VARIANTS[request.fee]" data-testid="card-request-step"
                >Card: {{ request.summary }}</Pill
            >
        </p>
        <AppButton
            v-else-if="!open"
            variant="secondary"
            class="self-start"
            data-testid="new-card"
            @click="open = true"
            >New card…</AppButton
        >
        <form
            v-else
            class="flex flex-col gap-3"
            novalidate
            data-testid="card-request-form"
            @submit.prevent="submit"
        >
            <p :id="questionId" class="text-text text-lg font-semibold">Why a new card?</p>
            <ChoiceChips
                v-model="form.reason"
                :labelledby="questionId"
                :options="chips"
                required
                :error="form.errors.reason"
                data-testid="card-reason"
            />
            <TextArea
                v-model="form.note"
                label="Note (optional)"
                :rows="2"
                :error="form.errors.note"
                data-testid="card-note"
            />
            <div class="flex flex-wrap gap-3">
                <AppButton
                    type="submit"
                    :loading="form.processing"
                    data-testid="card-request-submit"
                    >Ask for a new card</AppButton
                >
                <AppButton variant="quiet" data-testid="card-request-cancel" @click="close"
                    >Cancel</AppButton
                >
            </div>
        </form>
    </div>
</template>
