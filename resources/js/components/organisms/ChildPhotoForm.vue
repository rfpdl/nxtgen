<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import FileButton from '@/components/atoms/FileButton.vue'
import { photoForUpload } from '@/lib/photoForUpload'
import type { ChildPhotoInput, ChildPortal, Draft } from '@/types'
import { router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

/**
 * A child's photo (Roy 2026-10-16): a parent or a Lifeshaper can take one with the phone camera or choose one —
 * once consent is recorded. The parent agrees with its own button; a staff member can record that the parent said
 * yes in person (saved with their name, noted on the child). Only the parent takes consent back, which hides
 * the photo until they agree again. Choosing a file sends it straight away. On the kit (the Bold look): the
 * pickers are FileButtons.
 */
const props = defineProps<{
    child: ChildPortal
    who: 'guardian' | 'staff'
    action: string
    consentAction?: string
    withdrawAction?: string
}>()

const form = useForm<Pick<Draft<ChildPhotoInput>, 'photo'>>({ photo: null })

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
</script>

<template>
    <div class="flex flex-col gap-2" data-testid="child-photo">
        <div v-if="who === 'staff' && !child.photo_consent" class="flex flex-col gap-2">
            <p class="text-text-muted">The parent has not agreed to a photo yet.</p>
            <AppButton
                v-if="consentAction && !confirming"
                variant="secondary"
                class="self-start"
                data-testid="record-photo-consent"
                @click="confirming = true"
            >
                The parent said yes — record consent
            </AppButton>
            <div v-if="confirming" class="bg-warning-soft rounded-control flex flex-col gap-3 p-3">
                <p class="text-text font-semibold">
                    Are you sure the parent said yes to a photo of {{ child.nickname }}?
                </p>
                <div class="flex flex-wrap gap-3">
                    <AppButton data-testid="confirm-photo-consent" @click="giveConsent">
                        Yes, they said yes
                    </AppButton>
                    <AppButton
                        variant="secondary"
                        data-testid="cancel-photo-consent"
                        @click="confirming = false"
                    >
                        Cancel
                    </AppButton>
                </div>
            </div>
        </div>
        <div
            v-if="who === 'guardian' && consentAction && !child.photo_consent"
            class="flex flex-col gap-2"
        >
            <p class="text-text">
                I agree that NxtGen may keep a photo of {{ child.nickname }} so volunteers can
                recognise them at church. Only our volunteers and our family can see it, and I can
                take this back any time.
            </p>
            <AppButton
                variant="secondary"
                class="self-start"
                data-testid="give-photo-consent"
                @click="giveConsent"
            >
                Yes, I agree to a photo
            </AppButton>
        </div>
        <div v-if="child.photo_consent" class="flex flex-wrap gap-3">
            <!-- `image/*` (not a list of types) so iPhones offer every photo, HEIC included. -->
            <FileButton
                accept="image/*"
                capture="environment"
                data-testid="take-photo"
                @change="send"
            >
                {{ child.photo_url ? 'Take a new photo' : 'Take a photo' }}
            </FileButton>
            <FileButton accept="image/*" data-testid="choose-photo" @change="send">
                Choose a photo
            </FileButton>
            <AppButton
                v-if="who === 'guardian' && child.photo_consent"
                variant="link"
                data-testid="withdraw-photo-consent"
                @click="withdraw"
            >
                Take back my consent (hides the photo)
            </AppButton>
        </div>
        <p v-if="form.errors.photo" role="alert" class="text-danger font-semibold">
            {{ form.errors.photo }}
        </p>
    </div>
</template>
