<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import BrandMark from '@/components/atoms/BrandMark.vue'
import SignOutButton from '@/components/atoms/SignOutButton.vue'
import SwitchLink from '@/components/atoms/SwitchLink.vue'
import FlashBanner from '@/components/molecules/FlashBanner.vue'
import { useFlashMessages } from '@/composables/useFlashMessages'
import type { ShellItem } from '@/types'
import { Menu, X } from 'lucide-vue-next'
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
import { computed, ref } from 'vue'

/**
 * The Bold frame for every signed-in page (DECISIONS 2026-10-28): an amber bar with the logo, the page's name and
 * `place` (the church) under it; the menu `items`, then Switch and Sign out. On a phone the menu is a sheet of big
 * buttons behind the Menu button (a reka-ui Dialog: focus stays inside, Esc closes and gives focus back); from 768px
 * it is a row in the bar. Which one shows is CSS alone (`md:`), so the first paint is already right. The flash
 * message sits under the bar; the page is the slot.
 *
 * Without `items` there is no menu at all — the public pages (welcome, register, sign in) and the few signed-in
 * pages with nowhere else to go (choose, nothing yet) get just the bar and the logo. `narrow` keeps the page to a
 * phone-like column on a wide screen, for the parent's and the public pages: one form, one column, easy to read.
 */
const props = withDefaults(
    defineProps<{ title: string; place?: string; items?: ShellItem[]; narrow?: boolean }>(),
    { place: undefined, items: undefined },
)

const width = computed(() => (props.narrow ? 'max-w-2xl' : 'max-w-5xl'))

const { message, dismiss } = useFlashMessages()
const open = ref(false)

/**
 * reka-ui's focus trap wraps a plain Tab only; Safari's Option+Tab (how its keyboard users reach every control) walked
 * out of the sheet. This wraps that one too.
 */
function wrapOptionTab(event: KeyboardEvent): void {
    if (event.key !== 'Tab' || !event.altKey) {
        return
    }
    const sheet = event.currentTarget as HTMLElement
    const controls = [...sheet.querySelectorAll<HTMLElement>('a[href], button:not([disabled])')]
    const [first, last] = [controls[0], controls[controls.length - 1]]
    const target = event.shiftKey
        ? document.activeElement === first && last
        : document.activeElement === last && first
    if (target) {
        event.preventDefault()
        target.focus()
    }
}
</script>

<template>
    <div class="bg-surface min-h-dvh">
        <header class="bg-bar text-on-bar focus-on-bar">
            <div class="mx-auto flex flex-col gap-1 px-4 py-2" :class="width">
                <div class="flex min-h-14 items-center gap-3">
                    <BrandMark />
                    <p class="flex min-w-0 flex-1 flex-col leading-tight">
                        <span class="truncate text-xl font-bold" data-testid="page-name">{{
                            title
                        }}</span>
                        <span v-if="place" class="truncate text-base font-medium">{{ place }}</span>
                    </p>
                    <DialogRoot v-if="items" v-model:open="open">
                        <DialogTrigger as-child>
                            <AppButton
                                variant="quiet"
                                size="icon"
                                class="md:hidden"
                                data-testid="menu-open"
                            >
                                <Menu class="size-7" aria-hidden="true" />
                                <span class="sr-only">Menu</span>
                            </AppButton>
                        </DialogTrigger>
                        <DialogPortal>
                            <DialogOverlay class="bg-text/40 fixed inset-0 z-40 md:hidden" />
                            <DialogContent
                                class="bg-surface rounded-b-card fixed inset-x-0 top-0 z-50 flex max-h-dvh flex-col gap-3 overflow-y-auto p-4 pb-6 md:hidden"
                                data-testid="menu-sheet"
                                @keydown="wrapOptionTab"
                            >
                                <div class="flex items-center gap-3">
                                    <DialogTitle class="text-text flex-1 text-2xl font-bold"
                                        >Menu</DialogTitle
                                    >
                                    <DialogClose as-child>
                                        <AppButton
                                            variant="quiet"
                                            size="icon"
                                            data-testid="menu-close"
                                        >
                                            <X class="size-7" aria-hidden="true" />
                                            <span class="sr-only">Close the menu</span>
                                        </AppButton>
                                    </DialogClose>
                                </div>
                                <DialogDescription v-if="place" class="text-text-muted text-lg">{{
                                    place
                                }}</DialogDescription>
                                <nav class="flex flex-col gap-3" aria-label="Menu">
                                    <AppButton
                                        v-for="item in items"
                                        :key="item.href"
                                        :href="item.href"
                                        :variant="item.current ? 'primary' : 'secondary'"
                                        size="lg"
                                        block
                                        :aria-current="item.current ? 'page' : undefined"
                                        :data-testid="item.testid"
                                        @click="open = false"
                                        >{{ item.label }}</AppButton
                                    >
                                    <SwitchLink variant="secondary" size="lg" block />
                                    <SignOutButton variant="secondary" size="lg" block />
                                </nav>
                            </DialogContent>
                        </DialogPortal>
                    </DialogRoot>
                </div>
                <nav
                    v-if="items"
                    class="hidden flex-wrap items-center gap-1 pb-1 md:flex"
                    aria-label="Menu"
                    data-testid="menu-row"
                >
                    <AppButton
                        v-for="item in items"
                        :key="item.href"
                        :href="item.href"
                        variant="quiet"
                        :class="[
                            'px-2.5!',
                            item.current ? 'underline decoration-4 underline-offset-8' : null,
                        ]"
                        :aria-current="item.current ? 'page' : undefined"
                        :data-testid="item.testid"
                        >{{ item.label }}</AppButton
                    >
                    <span class="ml-auto flex items-center gap-1">
                        <SwitchLink />
                        <SignOutButton />
                    </span>
                </nav>
            </div>
        </header>
        <FlashBanner :message="message" :narrow="narrow" @dismiss="dismiss" />
        <main class="mx-auto flex flex-col gap-6 p-4" :class="width">
            <slot />
        </main>
    </div>
</template>
