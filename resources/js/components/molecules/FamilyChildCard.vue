<script setup lang="ts">
import type { ChildPortal } from '@/types'
import { computed } from 'vue'

/**
 * One child, for the child's own family and for staff who can see the family (plan §3.3: health details are
 * theirs to see). The nickname leads: it is what the child is called. Allergies stand out; the rest of the
 * health details follow only when given. The photo, if any, is a short-lived signed link. The default slot
 * holds actions (Edit, photo).
 */
const props = defineProps<{ child: ChildPortal }>()

const health = computed(() =>
    [
        ['Sickness or illness', props.child.illnesses],
        ['Dislikes', props.child.dislikes],
        ['Afraid of', props.child.fears],
        ['From the family', props.child.guardian_notes],
    ].filter((row): row is [string, string] => typeof row[1] === 'string' && row[1] !== ''),
)
</script>

<template>
    <article
        class="border-border bg-surface flex flex-col gap-1 rounded-lg border p-4"
        data-testid="family-child"
    >
        <img
            v-if="child.photo_url"
            :src="child.photo_url"
            :alt="`Photo of ${child.nickname}`"
            class="size-28 rounded-lg object-cover"
            data-testid="child-photo-img"
        />
        <h3 class="text-text text-xl font-semibold">{{ child.nickname }}</h3>
        <p class="text-text text-lg">
            {{ [child.first_name, child.middle_name, child.last_name].filter(Boolean).join(' ') }}
        </p>
        <p class="text-text-muted">
            Born {{ child.date_of_birth }} · {{ child.level ?? 'No Level yet' }}
        </p>
        <p v-if="child.allergies" class="text-danger font-semibold" data-testid="allergy">
            Allergies: {{ child.allergies }}
        </p>
        <dl
            v-if="health.length > 0"
            class="text-text grid gap-x-3 gap-y-1 sm:grid-cols-[auto_1fr]"
            data-testid="health"
        >
            <template v-for="[label, value] in health" :key="label">
                <dt class="text-text-muted">{{ label }}</dt>
                <dd>{{ value }}</dd>
            </template>
        </dl>
        <slot />
    </article>
</template>
