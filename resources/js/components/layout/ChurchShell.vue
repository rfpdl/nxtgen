<script setup lang="ts">
import { useSatellite } from '@/composables/useSatellite'
import AppShell from '@/components/layout/AppShell.vue'
import ClassSwitch from '@/components/atoms/ClassSwitch.vue'
import SatelliteSwitch from '@/components/atoms/SatelliteSwitch.vue'
import { useChurch } from '@/composables/useChurch'
import type { SessionChoice, SharedProps, ShellItem } from '@/types'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { route } from 'ziggy-js'

/**
 * The church staff frame (AppShell, the Bold look), one menu per area (Roy 2026-11-06, `church_nav.area`). Serve:
 * Check in (the door), Families, QR poster, and "Admin" for an admin. Admin: Families, Children, then Classes,
 * Attendance, Cards, Links and People as the person may, and "Serve". Then Password, Switch and Sign out. Every link
 * is at the satellite in the address (`/{satellite}/admin|serve/…`, the shared `satellite`).
 * Registering starts from the Families list's button, and possible duplicates from its "duplicates" filter
 * (DECISIONS 2026-10-17, after first look) — neither has a tab.
 * The bar names the page: `title` if given, else the menu item whose address the page is under, else the church.
 * The church is the page's shared `church`; what to show comes from the shared `church_nav`.
 */
const props = defineProps<{
    title?: string
    /** On a class's door or Who's in (Roy 2026-11-10): the class, and its satellite's classes open now — the bar's dropdown. */
    classSwitch?: { current: SessionChoice; classes: SessionChoice[] }
}>()

const church = useChurch()
const satellite = useSatellite()
const page = usePage<SharedProps>()

const items = computed<ShellItem[]>(() => {
    const nav = page.props.church_nav
    const slug = satellite.value.slug
    const inAdmin = nav?.area === 'admin'
    // A class admin (TODO #9) has only Classes and Attendance in Admin.
    const full = nav?.can_use_full_admin === true
    const all: ((ShellItem & { exact?: boolean }) | false)[] = inAdmin
        ? [
              full && {
                  label: 'Families',
                  href: route('admin.families.index', slug),
                  testid: 'nav-families',
              },
              full && { label: 'Children', href: route('admin.children.index', slug) },
              nav?.can_manage_classes === true && {
                  label: 'Classes',
                  href: route('admin.schedules.index', slug),
              },
              nav?.can_see_attendance === true && {
                  label: 'Attendance',
                  href: route('admin.attendance.show', slug),
              },
              nav?.can_manage_cards === true && {
                  label: 'Cards',
                  href: route('admin.card-requests.index', slug),
              },
              nav?.can_manage_links === true && {
                  label: 'Links',
                  href: route('admin.links.index', slug),
              },
              nav?.can_manage_people === true && {
                  label: 'People',
                  href: route('admin.people.index', slug),
              },
              { label: 'Serve', href: route('serve.home', slug), testid: 'nav-serve' },
          ]
        : [
              // Serve's first page: current there only — every Serve address is under it.
              { label: 'Today', href: route('serve.home', slug), testid: 'nav-today', exact: true },
              {
                  label: 'Families',
                  href: route('serve.families.index', slug),
                  testid: 'nav-families',
              },
              { label: 'QR poster', href: route('serve.poster', slug) },
              nav?.can_use_admin === true && {
                  label: 'Admin',
                  href: route(full ? 'admin.families.index' : 'admin.schedules.index', slug),
                  testid: 'nav-admin',
              },
          ]
    // A backup password is for staff who stay — never a helper for the day only (Codex).
    if (nav?.can_set_password === true) {
        all.push({ label: 'Password', href: route('account.password'), testid: 'backup-password' })
    }
    const path = page.url.split('?')[0] ?? ''
    return all
        .filter((item): item is ShellItem & { exact?: boolean } => item !== false)
        .map(({ exact, ...item }) => ({
            ...item,
            current: exact === true ? path === pathOf(item.href) : isUnder(path, pathOf(item.href)),
        }))
})

const title = computed(
    () => props.title ?? items.value.find((item) => item.current)?.label ?? church.value.name,
)

function pathOf(href: string): string {
    return new URL(href, 'http://x').pathname
}

/** `/dubai/admin/families/12` is under `/dubai/admin/families`; `/dubai/admin/families-x` is not. */
function isUnder(path: string, base: string): boolean {
    return path === base || path.startsWith(`${base}/`)
}
</script>

<template>
    <AppShell :title="title" :items="items">
        <template #end>
            <!-- On a class's page the dropdown jumps between its satellite's open classes (Roy 2026-11-10). -->
            <ClassSwitch
                v-if="props.classSwitch !== undefined"
                :current="props.classSwitch.current"
                :classes="props.classSwitch.classes"
            />
            <SatelliteSwitch v-else :current="satellite" :satellites="page.props.open_satellites" />
        </template>
        <slot />
    </AppShell>
</template>
