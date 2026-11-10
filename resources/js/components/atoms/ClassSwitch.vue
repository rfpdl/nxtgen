<script setup lang="ts">
import type { SessionChoice } from '@/types'
import { Link } from '@inertiajs/vue3'
import { onClickOutside } from '@vueuse/core'
import { ChevronDown } from 'lucide-vue-next'
import { computed, ref, useId, useTemplateRef } from 'vue'
import { route } from 'ziggy-js'

/**
 * The bar on a class's page (Roy 2026-11-10): the class this door is, and — with another class open now at its
 * satellite — a menu button whose classes are links to their own doors (`/<code>/checkin`, spec item 16), to jump
 * between doors. As SatelliteSwitch: a menu, not a select, so nothing moves until a class is chosen; Esc or a tap
 * outside closes it.
 */
const props = defineProps<{ current: SessionChoice; classes: SessionChoice[] }>()

/** A menu only with somewhere else to go — from a closed class, the one open now is enough (Astra, issue 13). */
const elsewhere = computed(() => props.classes.some((c) => c.code !== props.current.code))

const open = ref(false)
const menuId = `bar-classes-${useId()}`
const root = useTemplateRef<HTMLElement>('root')
onClickOutside(root, () => (open.value = false))
</script>

<template>
    <div v-if="elsewhere" ref="root" class="relative" @keydown.esc="open = false">
        <button
            type="button"
            :aria-expanded="open ? 'true' : 'false'"
            :aria-controls="menuId"
            :aria-label="`Class: ${current.name}`"
            class="rounded-control border-on-bar/40 flex min-h-12 max-w-56 items-center gap-1 border-2 px-3 text-base font-bold"
            data-testid="bar-class"
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
            aria-label="Classes open now"
            class="bg-surface rounded-card border-border absolute top-full left-0 z-30 mt-1 flex min-w-56 flex-col gap-1 border-2 p-2 shadow-lg md:right-0 md:left-auto"
            data-testid="bar-class-menu"
        >
            <Link
                v-for="choice in classes"
                :key="choice.code"
                :href="route('session.checkin', choice.code)"
                :aria-current="choice.code === current.code ? 'page' : undefined"
                class="rounded-control text-text hover:bg-surface-sunken flex min-h-12 items-center px-3 text-lg font-bold"
                :class="{ 'bg-primary-soft': choice.code === current.code }"
                @click="open = false"
            >
                {{ choice.name }}
            </Link>
        </nav>
    </div>
    <span v-else class="max-w-56 truncate text-base font-bold" data-testid="bar-class">{{
        current.name
    }}</span>
</template>
