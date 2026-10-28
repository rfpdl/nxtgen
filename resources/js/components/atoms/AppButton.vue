<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { LoaderCircle } from 'lucide-vue-next'
import { computed } from 'vue'

/**
 * THE button (the Bold look, DECISIONS 2026-10-28). `primary` is the green pressable one — it sits on a thick edge of
 * its own colour and drops onto it when pressed; `secondary` is white on a grey edge; `quiet` (in the colour of what
 * it sits on) and `link` are flat. With `href` it is an Inertia Link (a non-GET one renders as a button); a disabled
 * or loading link does not navigate and says so (`aria-disabled`). `type` is for a plain button only.
 */
type Variant = 'primary' | 'secondary' | 'quiet' | 'danger' | 'link'

const props = withDefaults(
    defineProps<{
        variant?: Variant
        size?: 'sm' | 'md' | 'lg' | 'icon'
        type?: 'button' | 'submit'
        disabled?: boolean
        loading?: boolean
        href?: string
        method?: 'get' | 'post' | 'put' | 'delete'
        block?: boolean
    }>(),
    { variant: 'primary', size: 'md', type: 'button', href: undefined, method: 'get' },
)

if (props.href && props.type === 'submit') {
    console.warn('AppButton: a link cannot submit a form — drop `href` or `type="submit"`.')
}

const PRESS = 'not-disabled:active:translate-y-[5px] not-disabled:active:shadow-none'
const VARIANTS: Record<Variant, string> = {
    primary: `bg-action text-on-action shadow-press-action hover:bg-action-strong ${PRESS}`,
    secondary: `bg-surface text-text border-2 border-control-edge shadow-press-control hover:bg-surface-sunken ${PRESS}`,
    quiet: 'bg-transparent hover:bg-current/10',
    danger: `bg-danger text-on-danger shadow-press-danger ${PRESS}`,
    link: 'text-text underline underline-offset-4 text-left',
}

const SIZES = {
    sm: 'min-h-touch px-3 text-base',
    md: 'min-h-12 px-4 text-lg',
    lg: 'min-h-touch-door px-6 text-xl',
    icon: 'size-12 shrink-0',
} as const

const inactive = computed(() => props.disabled || props.loading)
const classes = computed(() => [
    'inline-flex items-center justify-center gap-2 rounded-control font-semibold transition-transform select-none',
    'disabled:cursor-not-allowed disabled:opacity-60 aria-disabled:pointer-events-none aria-disabled:opacity-60',
    VARIANTS[props.variant],
    props.variant === 'link' ? 'min-h-touch' : SIZES[props.size],
    props.block ? 'w-full' : null,
])
</script>

<template>
    <Link
        v-if="href && !inactive"
        :href="href"
        :method="method"
        :as="method === 'get' ? 'a' : 'button'"
        :class="classes"
    >
        <slot />
    </Link>
    <a
        v-else-if="href && method === 'get'"
        role="link"
        aria-disabled="true"
        :aria-busy="loading ? 'true' : undefined"
        :class="classes"
    >
        <LoaderCircle
            v-if="loading"
            class="size-5 animate-spin"
            aria-hidden="true"
            data-testid="button-spinner"
        />
        <slot />
    </a>
    <button
        v-else
        :type="href ? 'button' : type"
        :disabled="inactive"
        :aria-busy="loading ? 'true' : undefined"
        :class="classes"
    >
        <LoaderCircle
            v-if="loading"
            class="size-5 animate-spin"
            aria-hidden="true"
            data-testid="button-spinner"
        />
        <slot />
    </button>
</template>
