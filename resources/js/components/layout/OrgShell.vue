<script setup lang="ts">
import SignOutButton from '@/components/atoms/SignOutButton.vue'
import FlashBanner from '@/components/molecules/FlashBanner.vue'
import { useFlashMessages } from '@/composables/useFlashMessages'
import type { SharedProps } from '@/types'
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { route } from 'ziggy-js'

/** The church staff frame: church name, the Release 1 tools, People (admins), sign out, and the flash message. */
defineProps<{ church: { name: string; slug: string } }>()

const { message, dismiss } = useFlashMessages()
const page = usePage<SharedProps>()
const canManagePeople = computed(() => page.props.orgNav?.canManagePeople === true)
</script>

<template>
    <div class="bg-surface-sunken min-h-dvh">
        <header class="border-border bg-surface border-b">
            <nav class="mx-auto flex max-w-5xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3">
                <span class="text-text text-lg font-semibold">{{ church.name }}</span>
                <Link
                    :href="route('org.families.index', church.slug)"
                    class="min-h-touch text-text flex items-center underline-offset-4 hover:underline"
                    >Families</Link
                >
                <Link
                    :href="route('org.families.register', church.slug)"
                    class="min-h-touch text-text flex items-center underline-offset-4 hover:underline"
                    >Register a family</Link
                >
                <Link
                    v-if="canManagePeople"
                    :href="route('org.people.index', church.slug)"
                    class="min-h-touch text-text flex items-center underline-offset-4 hover:underline"
                    >People</Link
                >
                <SignOutButton class="ml-auto" />
            </nav>
        </header>
        <FlashBanner :message="message" @dismiss="dismiss" />
        <main class="mx-auto flex max-w-5xl flex-col gap-6 p-4">
            <slot />
        </main>
    </div>
</template>
