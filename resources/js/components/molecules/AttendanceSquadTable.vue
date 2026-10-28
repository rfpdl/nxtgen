<script setup lang="ts">
import Card from '@/components/atoms/Card.vue'
import Pill from '@/components/atoms/Pill.vue'
import RollCallMark from '@/components/atoms/RollCallMark.vue'
import RowList from '@/components/atoms/RowList.vue'
import { headCountLabel, headCountWarning } from '@/lib/rollCall'
import type { AttendanceSquad } from '@/types'
import { computed } from 'vue'

/**
 * One Squad of a Session on the Attendance page: its name (which says the Level) and how many are in, then each child
 * — nickname, full name, check-in time and who checked them in (the Excel's columns). A table from the sm breakpoint
 * up; on a phone, one stacked row per child so no column is squeezed. On the kit (the Bold look): a Card headed by
 * the Squad as a Pill in its Level's colour. The roll call (DECISIONS 2026-10-28): under the heading, the Squad's head
 * count ("Head count 4 · 13:20 by Joy", or "No head count") and the same warning as Who's in when it does not match;
 * beside each child, "Present ✓" or "Not marked".
 */
const props = defineProps<{ squad: AttendanceSquad }>()

const headCount = computed(() => {
    const count = props.squad.head_count
    if (count === null) return 'No head count'
    return `Head count ${count.count} · ${headCountLabel(count)}`
})
const warning = computed(() =>
    headCountWarning(
        props.squad.count,
        props.squad.head_count?.count ?? null,
        props.squad.children,
    ),
)
</script>

<template>
    <Card as="section" flush data-testid="attendance-squad">
        <h2
            class="border-border flex flex-wrap items-center justify-between gap-3 border-b-2 px-4 py-3"
        >
            <Pill :level="squad.level" class="text-lg" data-testid="squad-pill"
                ><span data-testid="squad-name">{{ squad.name }}</span></Pill
            >
            <span class="text-text text-lg font-semibold tabular-nums" data-testid="squad-count"
                >{{ squad.count }} in</span
            >
        </h2>
        <div class="border-border flex flex-col gap-2 border-b-2 px-4 py-3">
            <p
                class="text-lg font-semibold"
                :class="squad.head_count ? 'text-text' : 'text-text-muted'"
                data-testid="squad-head-count"
            >
                {{ headCount }}
            </p>
            <p
                v-if="warning"
                role="status"
                class="bg-warning-soft text-warning rounded-control p-3 text-lg font-semibold"
                data-testid="squad-mismatch"
            >
                ⚠ {{ warning }}
            </p>
        </div>
        <RowList
            v-if="squad.children.length > 0"
            plain
            class="sm:hidden"
            :items="squad.children"
            :item-key="(c) => c.id"
            row-testid="attendance-list-child"
            data-testid="attendance-list"
        >
            <template #default="{ item: child }">
                <p class="text-text flex flex-col">
                    <span class="text-lg font-bold">{{ child.nickname }}</span>
                    <span>{{ child.full_name }}</span>
                    <span class="text-text-muted tabular-nums"
                        >{{ child.checked_in_at
                        }}{{ child.checked_in_by ? ` · by ${child.checked_in_by}` : '' }}</span
                    >
                    <span class="mt-1" data-testid="child-roll-call"
                        ><RollCallMark :present="child.present"
                    /></span>
                </p>
            </template>
        </RowList>
        <table
            v-if="squad.children.length > 0"
            class="hidden w-full table-fixed text-left sm:table"
            data-testid="attendance-table"
        >
            <thead class="text-text-muted text-base">
                <tr>
                    <th class="px-4 py-2 font-semibold">Nickname</th>
                    <th class="py-2 pr-4 font-semibold">Full name</th>
                    <th class="py-2 pr-4 font-semibold">Checked in</th>
                    <th class="py-2 pr-4 font-semibold">By</th>
                    <th class="py-2 pr-4 font-semibold">Roll call</th>
                </tr>
            </thead>
            <tbody class="text-text">
                <tr
                    v-for="child in squad.children"
                    :key="child.id"
                    class="border-border border-t-2"
                    data-testid="attendance-child"
                >
                    <td class="px-4 py-3 font-bold">{{ child.nickname }}</td>
                    <td class="py-3 pr-4">{{ child.full_name }}</td>
                    <td class="py-3 pr-4 tabular-nums">{{ child.checked_in_at }}</td>
                    <td class="py-3 pr-4">{{ child.checked_in_by }}</td>
                    <td class="py-3 pr-4" data-testid="child-roll-call">
                        <RollCallMark :present="child.present" />
                    </td>
                </tr>
            </tbody>
        </table>
    </Card>
</template>
