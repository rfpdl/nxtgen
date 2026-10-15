<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

/** "Sign out" for every signed-in page — families often share one phone. A POST, so a link preview cannot sign anyone out. */
</script>

<template>
    <Link
        :href="route('logout')"
        method="post"
        as="button"
        class="min-h-touch text-text-muted flex items-center underline-offset-4 hover:underline"
        data-testid="sign-out"
        >Sign out</Link
    >
</template>
