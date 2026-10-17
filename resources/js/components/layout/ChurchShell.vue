<script setup lang="ts">
import SignOutButton from '@/components/atoms/SignOutButton.vue'
import SwitchLink from '@/components/atoms/SwitchLink.vue'
import FlashBanner from '@/components/molecules/FlashBanner.vue'
import { useChurch } from '@/composables/useChurch'
import { useFlashMessages } from '@/composables/useFlashMessages'
import type { SharedProps } from '@/types'
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { route } from 'ziggy-js'

/**
 * The church staff frame: church name, the Release 1 tools, People (admins), sign out, and the flash message.
 * Registering starts from the Families list's button, and possible duplicates from its "duplicates" filter
 * (DECISIONS 2026-10-17, after first look) — neither has a tab.
 * The church is the page's shared `church`; what to show comes from the shared `church_nav`.
 */
const church = useChurch()

const { message, dismiss } = useFlashMessages()
const page = usePage<SharedProps>()
const canManagePeople = computed(() => page.props.church_nav?.can_manage_people === true)
</script>

<template>
    <div class="bg-surface-sunken min-h-dvh">
        <header class="border-border bg-surface border-b">
            <nav class="mx-auto flex max-w-5xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3">
                <span class="text-text text-lg font-semibold">{{ church.name }}</span>
                <Link
                    :href="route('church.families.index', church.slug)"
                    class="min-h-touch text-text flex items-center underline-offset-4 hover:underline"
                    >Families</Link
                >
                <Link
                    :href="route('church.children.index', church.slug)"
                    class="min-h-touch text-text flex items-center underline-offset-4 hover:underline"
                    >Children</Link
                >
                <Link
                    :href="route('church.poster', church.slug)"
                    class="min-h-touch text-text flex items-center underline-offset-4 hover:underline"
                    >QR poster</Link
                >
                <Link
                    v-if="canManagePeople"
                    :href="route('church.people.index', church.slug)"
                    class="min-h-touch text-text flex items-center underline-offset-4 hover:underline"
                    >People</Link
                >
                <Link
                    :href="route('account.password')"
                    class="min-h-touch text-text-muted ml-auto flex items-center underline-offset-4 hover:underline"
                    data-testid="backup-password"
                    >Password</Link
                >
                <SwitchLink />
                <SignOutButton />
            </nav>
        </header>
        <FlashBanner :message="message" @dismiss="dismiss" />
        <main class="mx-auto flex max-w-5xl flex-col gap-6 p-4">
            <slot />
        </main>
    </div>
</template>
