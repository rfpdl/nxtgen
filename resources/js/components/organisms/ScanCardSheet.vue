<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import { ScanError, startCardScan, type CardScan, type ScanProblem } from '@/lib/cardScanner'
import {
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogOverlay,
    DialogPortal,
    DialogRoot,
    DialogTitle,
    DialogTrigger,
} from 'reka-ui'
import { ScanLine } from 'lucide-vue-next'
import { nextTick, onBeforeUnmount, ref, watch } from 'vue'

/**
 * "Scan card" at the door (slice 3): the button, and the sheet it opens from the bottom of the screen (a reka-ui
 * Dialog, like the menu: focus stays inside, Esc closes and gives focus back to the button) showing the back camera. The first NXTGEN name card it reads is handed to the page
 * (`code`) and the sheet closes. A refused or missing camera says so — "Type the name instead" — and the big Cancel
 * sits at the bottom, under the thumb.
 */
const open = defineModel<boolean>('open', { default: false })
const emit = defineEmits<{ code: [code: string] }>()

const video = ref<HTMLVideoElement | null>(null)
const problem = ref<ScanProblem | null>(null)
let scan: CardScan | null = null

const MESSAGES: Record<ScanProblem, string> = {
    refused: 'The camera is not allowed. Type the name instead.',
    unsupported: 'This phone cannot scan here. Type the name instead.',
}

/** Each opening's own number: a camera that starts after its sheet was closed (or opened again) is stopped at once. */
let opening = 0

async function start(): Promise<void> {
    const mine = ++opening
    problem.value = null
    await nextTick()
    if (mine !== opening || !open.value || video.value === null) {
        return
    }
    try {
        const started = await startCardScan(video.value, (code) => {
            if (mine !== opening) return
            emit('code', code)
            open.value = false
        })
        if (mine === opening && open.value) {
            scan = started
        } else {
            started.stop()
        }
    } catch (error) {
        if (mine === opening) {
            problem.value = error instanceof ScanError ? error.problem : 'unsupported'
        }
    }
}

function stop(): void {
    opening++
    scan?.stop()
    scan = null
}

watch(open, (now) => (now ? void start() : stop()))
onBeforeUnmount(stop)
</script>

<template>
    <DialogRoot v-model:open="open">
        <DialogTrigger as-child>
            <AppButton
                variant="secondary"
                size="lg"
                class="w-full sm:w-auto sm:self-start"
                data-testid="scan-card"
            >
                <ScanLine class="size-6" aria-hidden="true" /> Scan card
            </AppButton>
        </DialogTrigger>
        <DialogPortal>
            <DialogOverlay class="bg-text/40 fixed inset-0 z-40" />
            <DialogContent
                class="bg-surface rounded-t-card fixed inset-x-0 bottom-0 z-50 mx-auto flex max-h-dvh w-full max-w-xl flex-col gap-3 overflow-y-auto p-4 pb-6"
                data-testid="scan-sheet"
            >
                <DialogTitle class="text-text text-2xl font-bold">Scan a name card</DialogTitle>
                <DialogDescription
                    class="text-lg"
                    :class="problem ? 'text-danger font-semibold' : 'text-text'"
                    :role="problem ? 'alert' : undefined"
                    data-testid="scan-message"
                    >{{
                        problem
                            ? MESSAGES[problem]
                            : 'Point the camera at the barcode on the name card.'
                    }}</DialogDescription
                >
                <video
                    v-show="!problem"
                    ref="video"
                    class="rounded-control bg-text aspect-[4/3] w-full object-cover"
                    autoplay
                    muted
                    playsinline
                    data-testid="scan-video"
                ></video>
                <DialogClose as-child>
                    <AppButton variant="secondary" size="lg" block data-testid="scan-cancel"
                        >Cancel</AppButton
                    >
                </DialogClose>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
