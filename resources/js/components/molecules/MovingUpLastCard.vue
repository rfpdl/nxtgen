<script setup lang="ts">
import { useSatellite } from '@/composables/useSatellite'
import AppButton from '@/components/atoms/AppButton.vue'
import Card from '@/components/atoms/Card.vue'
import RowList from '@/components/atoms/RowList.vue'
import SectionHeading from '@/components/atoms/SectionHeading.vue'
import type { MovingUp, MovingUpChild } from '@/types'
import { computed } from 'vue'
import { route } from 'ziggy-js'

/**
 * The church's last Moving-Up (DECISIONS 2026-11-06): when and by whom, each child it moved ("Level 1 → Level 2", or
 * "→ Graduated"), and the way back — "Put back" on each child not yet put back, "Put everyone back" while any is
 * left. A child put back says by whom and when. Each is a PUT that keeps the scroll; the server says what happened.
 */
const props = defineProps<{ movingUp: MovingUp }>()

const satellite = useSatellite()

const waiting = computed(() => props.movingUp.children.filter((c) => c.put_back === null).length)
const when = computed(
    () => `${props.movingUp.made}${props.movingUp.made_by ? ` by ${props.movingUp.made_by}` : ''}`,
)

function putBack(child: MovingUpChild): string {
    return route('admin.moving-up.children.put-back', [
        satellite.value.slug,
        props.movingUp.id,
        child.child_id,
    ])
}
</script>

<template>
    <section class="flex flex-col gap-3" data-testid="moving-up-last">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <SectionHeading>Last Moving-Up</SectionHeading>
                <p class="text-text-muted" data-testid="moving-up-last-when">{{ when }}</p>
            </div>
            <AppButton
                v-if="waiting > 0"
                variant="secondary"
                :href="route('admin.moving-up.put-back', [satellite.slug, movingUp.id])"
                method="put"
                :preserve-scroll="true"
                data-testid="moving-up-put-back-everyone"
                >Put everyone back</AppButton
            >
        </div>
        <Card v-if="movingUp.children.length === 0" class="text-text-muted">Nobody moved.</Card>
        <RowList
            v-else
            :items="movingUp.children"
            :item-key="(c) => c.child_id"
            row-testid="moved-child"
        >
            <template #default="{ item }">
                <p class="text-text text-lg font-semibold">{{ item.nickname }}</p>
                <p class="text-text-muted">{{ item.full_name }}</p>
                <p class="text-text" data-testid="moved-child-step">
                    {{ item.from }} → {{ item.to }}
                </p>
                <p v-if="item.put_back" class="text-text-muted" data-testid="moved-child-put-back">
                    Put back by {{ item.put_back }}
                </p>
            </template>
            <template #after="{ item }">
                <AppButton
                    v-if="item.put_back === null"
                    variant="secondary"
                    size="sm"
                    :href="putBack(item)"
                    method="put"
                    :preserve-scroll="true"
                    data-testid="moved-child-put-back-button"
                    >Put back</AppButton
                >
            </template>
        </RowList>
    </section>
</template>
