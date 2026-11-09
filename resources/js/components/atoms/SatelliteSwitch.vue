<script setup lang="ts">
import type { SatelliteChoice } from '@/types'
import { Link } from '@inertiajs/vue3'
import { onClickOutside } from '@vueuse/core'
import { ChevronDown } from 'lucide-vue-next'
import { ref, useId, useTemplateRef } from 'vue'
import { route } from 'ziggy-js'

/**
 * The bar's satellite (spec item 13): the satellite this page is about. For someone who may open two or more (Roy
 * 2026-11-08) it is a menu button whose satellites are links to their homes (Admin for an admin, else Serve). Not a
 * select: an arrow key on a select moves to the next satellite at once (review, issue 08) — here nothing moves until a
 * satellite is chosen. Esc or a tap outside closes it.
 */
defineProps<{ current: SatelliteChoice; satellites: SatelliteChoice[] }>()

const open = ref(false)
const menuId = `bar-satellites-${useId()}`
const root = useTemplateRef<HTMLElement>('root')
onClickOutside(root, () => (open.value = false))
</script>

<template>
    <div v-if="satellites.length > 1" ref="root" class="relative">
        <button
            type="button"
            :aria-expanded="open ? 'true' : 'false'"
            :aria-controls="menuId"
            :aria-label="`Satellite: ${current.name}`"
            class="rounded-control border-on-bar/40 flex min-h-12 max-w-56 items-center gap-1 border-2 px-3 text-base font-bold"
            data-testid="bar-satellite"
            @click="open = !open"
        >
            <span class="truncate">{{ current.name }}</span>
            <ChevronDown
                class="size-5 shrink-0 transition-transform"
                :class="{ 'rotate-180': open }"
                aria-hidden="true"
            />
        </button>
        <nav
            v-show="open"
            :id="menuId"
            aria-label="Satellites"
            class="bg-surface rounded-card border-border absolute top-full left-0 z-30 mt-1 flex min-w-56 flex-col gap-1 border-2 p-2 shadow-lg md:right-0 md:left-auto"
            data-testid="bar-satellite-menu"
            @keydown.esc="open = false"
        >
            <Link
                v-for="satellite in satellites"
                :key="satellite.slug"
                :href="route('satellite.home', satellite.slug)"
                :aria-current="satellite.slug === current.slug ? 'page' : undefined"
                class="rounded-control text-text hover:bg-surface-sunken flex min-h-12 items-center px-3 text-lg font-bold"
                :class="{ 'bg-primary-soft': satellite.slug === current.slug }"
                @click="open = false"
            >
                {{ satellite.name }}
            </Link>
        </nav>
    </div>
    <span v-else class="max-w-56 truncate text-base font-bold" data-testid="bar-satellite">{{
        current.name
    }}</span>
</template>
