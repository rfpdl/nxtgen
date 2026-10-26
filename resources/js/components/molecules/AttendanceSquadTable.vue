<script setup lang="ts">
import type { AttendanceSquad } from '@/types'

/**
 * One Squad of a Session on the Attendance page: its name (which says the Level) and how many are in, then each child
 * — nickname, full name, check-in time and who checked them in (the Excel's columns). A table from the sm breakpoint
 * up; on a phone, one stacked row per child so no column is squeezed.
 */
defineProps<{ squad: AttendanceSquad }>()
</script>

<template>
    <section
        class="border-border bg-surface flex flex-col gap-2 rounded-lg border p-4"
        data-testid="attendance-squad"
    >
        <h2 class="text-text flex items-baseline gap-3 text-lg font-semibold">
            <span data-testid="squad-name">{{ squad.name }}</span>
            <span class="text-text-muted text-base font-normal" data-testid="squad-count"
                >{{ squad.count }} in</span
            >
        </h2>
        <ul class="flex flex-col sm:hidden" data-testid="attendance-list">
            <li
                v-for="child in squad.children"
                :key="child.id"
                class="border-border text-text flex flex-col border-t py-2"
                data-testid="attendance-list-child"
            >
                <span class="font-semibold">{{ child.nickname }}</span>
                <span>{{ child.full_name }}</span>
                <span class="text-text-muted"
                    >{{ child.checked_in_at
                    }}{{ child.checked_in_by ? ` · by ${child.checked_in_by}` : '' }}</span
                >
            </li>
        </ul>
        <table class="hidden w-full text-left sm:table" data-testid="attendance-table">
            <thead class="text-text-muted text-sm">
                <tr>
                    <th class="py-1 pr-4 font-normal">Nickname</th>
                    <th class="py-1 pr-4 font-normal">Full name</th>
                    <th class="py-1 pr-4 font-normal">Checked in</th>
                    <th class="py-1 font-normal">By</th>
                </tr>
            </thead>
            <tbody class="text-text">
                <tr
                    v-for="child in squad.children"
                    :key="child.id"
                    class="border-border border-t"
                    data-testid="attendance-child"
                >
                    <td class="py-2 pr-4 font-semibold">{{ child.nickname }}</td>
                    <td class="py-2 pr-4">{{ child.full_name }}</td>
                    <td class="py-2 pr-4">{{ child.checked_in_at }}</td>
                    <td class="py-2">{{ child.checked_in_by }}</td>
                </tr>
            </tbody>
        </table>
    </section>
</template>
