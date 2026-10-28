<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import TextField from '@/components/atoms/TextField.vue'
import type { WhosInSquad } from '@/types'
import { computed, ref, watch } from 'vue'

/**
 * A Squad's head count, under its list on Who's in (roll call, DECISIONS 2026-10-28): a number — the last count
 * confirmed, else the number checked in, following the server until the leader types and again once their confirm is
 * answered — and "Confirm head count"; once confirmed, "✓ Confirmed 13:20 by Joy (4)". When the count is not the number checked in now: "Checked in 5, counted 4." and, once some children are marked
 * present, "Not marked: Isabel, Mia" — worked out from the Squad as shown, so a later Check-in, Undo or tap shows at
 * once. A count that is not a whole number from 0 to 200 is caught here, before it is sent. On a phone the field and
 * the button are stacked full width, so neither wraps its words; from the sm breakpoint up, one row.
 */
const props = withDefaults(
    defineProps<{ squad: WhosInSquad; confirming?: boolean; error?: string }>(),
    { error: undefined },
)
const emit = defineEmits<{ confirm: [count: number] }>()

const SAY = 'Please type a number from 0 to 200.'
/** What the server says the count is: the last one confirmed, else the number checked in. */
const fromServer = computed(() => String(props.squad.head_count?.count ?? props.squad.count))
const typed = ref<string | number | null>(fromServer.value)
const touched = ref(false)
const mistake = ref<string | undefined>()

watch(fromServer, (value) => {
    if (!touched.value) typed.value = value
})

// A confirm answered without a refusal: follow the server again (it now has the number confirmed).
watch(
    () => props.confirming,
    (now, before) => {
        if (before && !now && !props.error) {
            touched.value = false
            typed.value = fromServer.value
        }
    },
)

const confirmed = computed(() => props.squad.head_count)
const notMarked = computed(() =>
    props.squad.present > 0
        ? props.squad.children.filter((c) => !c.present).map((c) => c.nickname)
        : [],
)
const mismatch = computed(() =>
    confirmed.value === null || confirmed.value.count === props.squad.count
        ? null
        : [
              `Checked in ${props.squad.count}, counted ${confirmed.value.count}.`,
              notMarked.value.length > 0 ? `Not marked: ${notMarked.value.join(', ')}` : null,
          ]
              .filter(Boolean)
              .join(' '),
)

function onInput(value: string | number | null): void {
    typed.value = value
    touched.value = true
    mistake.value = undefined
}

function submit(): void {
    const text = String(typed.value ?? '').trim()
    const count = Number(text)
    if (!/^\d+$/.test(text) || count > 200) {
        mistake.value = SAY
        return
    }
    emit('confirm', count)
}
</script>

<template>
    <form class="flex flex-col gap-3" novalidate data-testid="head-count" @submit.prevent="submit">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
            <TextField
                :model-value="typed"
                label="Head count"
                inputmode="numeric"
                pattern="[0-9]*"
                autocomplete="off"
                class="sm:w-40"
                :error="mistake ?? error"
                data-testid="head-count-input"
                @update:model-value="onInput"
            />
            <AppButton
                type="submit"
                variant="secondary"
                size="lg"
                class="w-full sm:mt-[2.125rem] sm:w-auto"
                :loading="confirming"
                data-testid="head-count-confirm"
                >Confirm head count</AppButton
            >
        </div>
        <p
            v-if="confirmed"
            class="text-success text-lg font-bold"
            data-testid="head-count-confirmed"
        >
            ✓ Confirmed {{ confirmed.counted_at
            }}{{ confirmed.counted_by ? ` by ${confirmed.counted_by}` : '' }} ({{
                confirmed.count
            }})
        </p>
        <p
            v-if="mismatch"
            role="status"
            class="bg-warning-soft text-warning rounded-control p-3 text-lg font-semibold"
            data-testid="head-count-mismatch"
        >
            ⚠ {{ mismatch }}
        </p>
    </form>
</template>
