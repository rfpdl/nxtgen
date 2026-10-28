<script setup lang="ts">
/**
 * The NXTGEN logo as drawn (Roy, from the shirt): an amber disc, a white box with an orange rule, and NXT GEN in
 * cyan with an orange X. The `brand-*` colours are for this mark only — never text or a button (app.css).
 */
</script>

<template>
    <svg viewBox="0 0 48 48" role="img" aria-label="NxtGen" class="size-10 shrink-0">
        <circle cx="24" cy="24" r="24" class="fill-brand-amber" />
        <rect
            x="8"
            y="13"
            width="32"
            height="22"
            class="fill-surface stroke-brand-orange"
            stroke-width="2"
        />
        <g font-size="9" font-weight="800" text-anchor="middle" letter-spacing="0.4">
            <!-- prettier-ignore -->
            <text x="24" y="22.5" class="fill-brand-cyan">N<tspan class="fill-brand-orange">X</tspan>T</text>
            <text x="24" y="31.5" class="fill-brand-cyan">GEN</text>
        </g>
    </svg>
</template>
