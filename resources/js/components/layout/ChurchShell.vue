<script setup lang="ts">
import AppShell from '@/components/layout/AppShell.vue'
import { useChurch } from '@/composables/useChurch'
import type { SharedProps, ShellItem } from '@/types'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { route } from 'ziggy-js'

/**
 * The church staff frame (AppShell, the Bold look): Check in (the door, for every staff member), the Release 1
 * tools, People, Classes and Attendance (admins), Password, Switch and Sign out.
 * Registering starts from the Families list's button, and possible duplicates from its "duplicates" filter
 * (DECISIONS 2026-10-17, after first look) — neither has a tab.
 * The bar names the page: `title` if given, else the menu item whose address the page is under, else the church.
 * The church is the page's shared `church`; what to show comes from the shared `church_nav`.
 */
const props = defineProps<{ title?: string }>()

const church = useChurch()
const page = usePage<SharedProps>()

const items = computed<ShellItem[]>(() => {
    const nav = page.props.church_nav
    const slug = church.value.slug
    const all: (ShellItem | false)[] = [
        { label: 'Check in', href: route('church.door.show', slug), testid: 'nav-door' },
        { label: 'Families', href: route('church.families.index', slug) },
        { label: 'Children', href: route('church.children.index', slug) },
        { label: 'QR poster', href: route('church.poster', slug) },
        nav?.can_manage_people === true && {
            label: 'People',
            href: route('church.people.index', slug),
        },
        nav?.can_manage_classes === true && {
            label: 'Classes',
            href: route('church.schedules.index', slug),
        },
        nav?.can_see_attendance === true && {
            label: 'Attendance',
            href: route('church.attendance.show', slug),
        },
        { label: 'Password', href: route('account.password'), testid: 'backup-password' },
    ]
    const path = page.url.split('?')[0] ?? ''
    return all
        .filter((item): item is ShellItem => item !== false)
        .map((item) => ({ ...item, current: isUnder(path, pathOf(item.href)) }))
})

const title = computed(
    () => props.title ?? items.value.find((item) => item.current)?.label ?? church.value.name,
)

function pathOf(href: string): string {
    return new URL(href, 'http://x').pathname
}

/** `/ccf/families/12` is under `/ccf/families`; `/ccf/families-x` is not. */
function isUnder(path: string, base: string): boolean {
    return path === base || path.startsWith(`${base}/`)
}
</script>

<template>
    <AppShell :title="title" :place="church.name" :items="items">
        <slot />
    </AppShell>
</template>
