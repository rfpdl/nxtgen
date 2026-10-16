<script setup lang="ts">
import { photoForUpload } from '@/lib/photoForUpload'
import type { ChildPortal } from '@/types'
import { router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

/**
 * A child's photo (Roy 2026-10-16): a parent or a Lifeshaper can take one with the phone camera or choose one —
 * once consent is recorded. The parent agrees with its own button; a volunteer can record that the parent said
 * yes in person (saved with their name, noted on the child). Only the parent takes consent back, which hides
 * the photo until they agree again. Choosing a file sends it straight away.
 */
const props = defineProps<{
    child: ChildPortal
    who: 'parent' | 'staff'
    action: string
    consentAction?: string
    withdrawAction?: string
}>()

const form = useForm<{ photo: File | null }>({ photo: null })

async function send(event: Event): Promise<void> {
    const file = (event.target as HTMLInputElement).files?.[0]
    if (file === undefined) return
    // iPhone HEIC → JPEG, upright and small, before it leaves the phone.
    form.photo = await photoForUpload(file)
    form.post(props.action, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => form.reset(),
    })
}

/** Staff: the first tap asks "are you sure?"; only the second records the parent's yes (Roy 2026-10-16). */
const confirming = ref(false)

function giveConsent(): void {
    confirming.value = false
    if (props.consentAction) router.put(props.consentAction, {}, { preserveScroll: true })
}

function withdraw(): void {
    if (props.withdrawAction) router.delete(props.withdrawAction, { preserveScroll: true })
}

const button =
    'min-h-touch border-primary text-primary flex cursor-pointer items-center rounded-md border px-4 font-semibold'
</script>

<template>
    <div class="flex flex-col gap-2" data-testid="child-photo">
        <div v-if="who === 'staff' && !child.photoConsent" class="flex flex-col gap-2">
            <p class="text-text-muted">The parent has not agreed to a photo yet.</p>
            <button
                v-if="consentAction && !confirming"
                type="button"
                class="min-h-touch border-primary text-primary self-start rounded-md border px-4 font-semibold"
                data-testid="record-photo-consent"
                @click="confirming = true"
            >
                The parent said yes — record consent
            </button>
            <div
                v-if="confirming"
                class="border-warning bg-warning-soft flex flex-col gap-2 rounded-md border p-3"
            >
                <p class="text-text font-semibold">
                    Are you sure the parent said yes to a photo of {{ child.nickname }}?
                </p>
                <div class="flex gap-3">
                    <button
                        type="button"
                        class="min-h-touch bg-primary text-on-primary rounded-md px-4 font-semibold"
                        data-testid="confirm-photo-consent"
                        @click="giveConsent"
                    >
                        Yes, they said yes
                    </button>
                    <button
                        type="button"
                        class="min-h-touch text-text px-2 underline"
                        data-testid="cancel-photo-consent"
                        @click="confirming = false"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
        <div
            v-if="who === 'parent' && consentAction && !child.photoConsent"
            class="flex flex-col gap-2"
        >
            <p class="text-text">
                I agree that NxtGen may keep a photo of {{ child.nickname }} so volunteers can
                recognise them at church. Only our volunteers and our family can see it, and I can
                take this back any time.
            </p>
            <button
                type="button"
                class="min-h-touch border-primary text-primary self-start rounded-md border px-4 font-semibold"
                data-testid="give-photo-consent"
                @click="giveConsent"
            >
                Yes, I agree to a photo
            </button>
        </div>
        <div v-if="child.photoConsent" class="flex flex-wrap gap-3">
            <!-- `image/*` (not a list of types) so iPhones offer every photo, HEIC included. -->
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
                Take back my consent (hides the photo)
            </button>
        </div>
        <p v-if="form.errors.photo" role="alert" class="text-danger font-semibold">
            {{ form.errors.photo }}
        </p>
    </div>
</template>
