<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import Card from '@/components/atoms/Card.vue'
import Pill from '@/components/atoms/Pill.vue'
import TextField from '@/components/atoms/TextField.vue'
import { useChurch } from '@/composables/useChurch'
import { useEnum } from '@/composables/useEnum'
import type { CardMove, CardRequestRow, Draft, RefuseCardRequestInput } from '@/types'
import { useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { route } from 'ziggy-js'

/**
 * One Card request on the Cards page (name cards 03): whose card (the full name, as printed), the family (its page a
 * tap away) and satellite, why, and the fee — amber while not paid. Its buttons are exactly the `moves` the server
 * sends (CardMoveEnum is the rule; the words are the page's `enums.card_move`), each a PUT that keeps the scroll;
 * Refuse… asks why first (required). One with no moves left says how it ended (Given, or Refused and why).
 */
const props = defineProps<{ request: CardRequestRow }>()

const church = useChurch()
const { options: moveNames } = useEnum('card_move')

/** Each move's action (Refuse is a form, below). */
const ROUTES: Record<Exclude<CardMove, 'refuse'>, string> = {
    mark_paid: 'church.card-requests.mark-paid',
    approve: 'church.card-requests.approve',
    mark_printed: 'church.card-requests.mark-printed',
    give: 'church.card-requests.give',
}

const buttons = computed(() =>
    props.request.moves.map((move) => ({
        move,
        label: moveNames.value.find((o) => o.value === move)?.label ?? move,
        href: move === 'refuse' ? null : route(ROUTES[move], [church.value.slug, props.request.id]),
    })),
)

const refusing = ref(false)
const form = useForm<Draft<RefuseCardRequestInput>>({ reason: '' })

function closeRefuse(): void {
    refusing.value = false
    form.reset()
    form.clearErrors()
}

function refuse(): void {
    form.put(route('church.card-requests.refuse', [church.value.slug, props.request.id]), {
        preserveScroll: true,
        onSuccess: closeRefuse,
    })
}
</script>

<template>
    <Card class="flex flex-col gap-2" data-testid="card-request">
        <p class="text-text text-xl font-bold" data-testid="card-child">{{ request.child_name }}</p>
        <AppButton
            variant="link"
            class="self-start"
            :href="route('church.families.show', [church.slug, request.family_id])"
            data-testid="card-family"
            >{{ request.family_name }} · {{ request.satellite }}</AppButton
        >
        <p class="text-text text-lg" data-testid="card-reason">
            {{ request.reason_label }}<template v-if="request.note"> — {{ request.note }}</template>
        </p>
        <p class="flex flex-wrap items-center gap-2">
            <Pill
                :variant="request.fee === 'not_paid' ? 'warning' : 'info'"
                data-testid="card-fee"
                >{{ request.fee_label }}</Pill
            >
            <span v-if="request.moves.length === 0" class="text-text-muted" data-testid="card-step"
                >{{ request.step_label
                }}<template v-if="request.refused_reason"
                    >: {{ request.refused_reason }}</template
                ></span
            >
        </p>
        <form
            v-if="refusing"
            class="flex flex-wrap items-end gap-3"
            novalidate
            @submit.prevent="refuse"
        >
            <TextField
                v-model="form.reason"
                class="min-w-0 flex-1 basis-64"
                label="Why refuse?"
                :error="form.errors.reason"
                data-testid="refuse-reason"
            />
            <AppButton
                type="submit"
                variant="danger"
                :loading="form.processing"
                data-testid="refuse-submit"
                >Refuse</AppButton
            >
            <AppButton variant="quiet" data-testid="refuse-cancel" @click="closeRefuse"
                >Cancel</AppButton
            >
        </form>
        <div v-else-if="buttons.length > 0" class="flex flex-wrap gap-3">
            <template v-for="b in buttons" :key="b.move">
                <AppButton
                    v-if="b.href === null"
                    variant="quiet"
                    :data-testid="`card-action-${b.move}`"
                    @click="refusing = true"
                    >{{ b.label }}</AppButton
                >
                <AppButton
                    v-else
                    :variant="b.move === 'mark_paid' ? 'secondary' : 'primary'"
                    :href="b.href"
                    method="put"
                    :preserve-scroll="true"
                    :data-testid="`card-action-${b.move}`"
                    >{{ b.label }}</AppButton
                >
            </template>
        </div>
    </Card>
</template>
