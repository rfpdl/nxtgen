<script setup lang="ts">
import Card from '@/components/atoms/Card.vue'
import Pill from '@/components/atoms/Pill.vue'
import type { ChildPortal } from '@/types'
import { computed } from 'vue'

/**
 * One child, for the child's own family and for staff who can see the family (plan §3.3: health details are
 * theirs to see). The nickname leads: it is what the child is called. On the kit (the Bold look): a Card with the
 * photo beside the names, the Level as a Pill in its colour and the status beside it; allergies stand out (red);
 * the rest of the health details follow only when given. The photo, if any, is a short-lived signed link. The
 * default slot holds actions (Edit, photo; for staff, Level and status), under a line.
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
    <Card as="article" class="flex flex-col gap-3" data-testid="family-child">
        <div class="flex items-start gap-4">
            <img
                v-if="child.photo_url"
                :src="child.photo_url"
                :alt="`Photo of ${child.nickname}`"
                class="rounded-control size-24 shrink-0 object-cover"
                data-testid="child-photo-img"
            />
            <div class="flex min-w-0 flex-col gap-1">
                <h3 class="text-text text-2xl font-bold">{{ child.nickname }}</h3>
                <p class="text-text text-lg">
                    {{
                        [child.first_name, child.middle_name, child.last_name]
                            .filter(Boolean)
                            .join(' ')
                    }}
                </p>
                <p class="text-text-muted">Born {{ child.date_of_birth }}</p>
                <p class="flex flex-wrap items-center gap-2">
                    <Pill :level="child.level" data-testid="child-level-pill">{{
                        child.level?.name ?? 'No Level yet'
                    }}</Pill>
                    <Pill data-testid="child-status-pill">{{ child.status_label }}</Pill>
                </p>
            </div>
        </div>
        <p
            v-if="child.allergies"
            class="bg-danger-soft text-danger rounded-control p-3 text-lg font-bold"
            data-testid="allergy"
        >
            ⚠ Allergies: {{ child.allergies }}
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
        <div v-if="$slots.default" class="border-border flex flex-col gap-4 border-t-2 pt-4">
            <slot />
        </div>
    </Card>
</template>
