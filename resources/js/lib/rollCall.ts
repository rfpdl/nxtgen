/**
 * The roll call's warning (DECISIONS 2026-10-28), one rule for Who's in and Attendance: when a Squad's confirmed head
 * count is not the number checked in, "Checked in 5, counted 4." — and, once some children are marked present, "Not
 * marked: Isabel, Mia" (the checked-in children not marked). Null when there is no count, or it matches.
 */
export function headCountWarning(
    checkedIn: number,
    headCount: number | null,
    children: { nickname: string; present: boolean }[],
): string | null {
    if (headCount === null || headCount === checkedIn) {
        return null
    }
    const notMarked = children.some((c) => c.present)
        ? children.filter((c) => !c.present).map((c) => c.nickname)
        : []

    return [
        `Checked in ${checkedIn}, counted ${headCount}.`,
        notMarked.length > 0 ? `Not marked: ${notMarked.join(', ')}` : null,
    ]
        .filter(Boolean)
        .join(' ')
}

/**
 * When a Squad's head count was confirmed and by whom — "13:20 by Joy", or "13:20" once their login has gone — the one
 * label both screens build on: Who's in "✓ Confirmed 13:20 by Joy (4)", Attendance "Head count 4 · 13:20 by Joy".
 */
export function headCountLabel(count: {
    count: number
    counted_at: string
    counted_by: string
}): string {
    return count.counted_by ? `${count.counted_at} by ${count.counted_by}` : count.counted_at
}
