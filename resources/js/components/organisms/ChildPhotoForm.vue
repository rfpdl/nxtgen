<script setup lang="ts">
import type { ChildPortal } from '@/types'
import { router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

/**
 * A child's photo (Roy 2026-10-16): a parent or a Lifeshaper can take one with the phone camera or choose one —
 * once the PARENT has agreed. A parent agrees here (and can take it back, which deletes the photo); staff can
 * never agree for them. Choosing a file sends it straight away.
 */
const props = defineProps<{
    child: ChildPortal
    who: 'parent' | 'staff'
    action: string
    withdrawAction?: string
}>()

const agreed = ref(props.child.photoConsent)
const form = useForm<{ photo: File | null; consent: boolean }>({ photo: null, consent: false })

function send(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0]
    if (file === undefined) return
    form.photo = file
    form.consent = props.who === 'parent' && agreed.value
    form.post(props.action, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => form.reset(),
    })
}

function withdraw(): void {
    if (props.withdrawAction) router.delete(props.withdrawAction, { preserveScroll: true })
}

const button =
    'min-h-touch border-primary text-primary flex cursor-pointer items-center rounded-md border px-4 font-semibold'
</script>

<template>
    <div class="flex flex-col gap-2" data-testid="child-photo">
        <p v-if="who === 'staff' && !child.photoConsent" class="text-text-muted">
            The parent has not agreed to a photo yet. They can say yes on their family page.
        </p>
        <label
            v-if="who === 'parent' && !child.photoConsent"
            class="text-text flex items-start gap-3"
        >
            <input
                v-model="agreed"
                type="checkbox"
                class="mt-1 size-5"
                data-testid="photo-consent"
            />
            <span
                >I agree that NxtGen may keep a photo of {{ child.nickname }} so volunteers can
                recognise them at church. Only our volunteers and our family can see it, and I can
                take this back any time.</span
            >
        </label>
        <div v-if="agreed || child.photoConsent" class="flex flex-wrap gap-3">
            <label :class="button">
                {{ child.photoUrl ? 'Take a new photo' : 'Take a photo' }}
                <input
                    type="file"
                    accept="image/*"
                    capture="environment"
                    class="sr-only"
                    data-testid="take-photo"
                    @change="send"
                />
            </label>
            <label :class="button">
                Choose a photo
                <input
                    type="file"
                    accept="image/*"
                    class="sr-only"
                    data-testid="choose-photo"
                    @change="send"
                />
            </label>
            <button
                v-if="who === 'parent' && child.photoConsent"
                type="button"
                class="min-h-touch text-danger px-2 underline"
                data-testid="withdraw-photo-consent"
                @click="withdraw"
            >
                Take back my consent (deletes the photo)
            </button>
        </div>
        <p
            v-if="form.errors.photo || form.errors.consent"
            role="alert"
            class="text-danger font-semibold"
        >
            {{ form.errors.photo ?? form.errors.consent }}
        </p>
    </div>
</template>
