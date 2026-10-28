import { headCountLabel, headCountWarning } from '@/lib/rollCall'
import { describe, expect, it } from 'vitest'

const girls = [
    { nickname: 'Amara', present: true },
    { nickname: 'Isabel', present: false },
    { nickname: 'Mia', present: false },
]

describe('headCountWarning', () => {
    it('says nothing with no count, or a count that matches', () => {
        expect(headCountWarning(3, null, girls)).toBeNull()
        expect(headCountWarning(3, 3, girls)).toBeNull()
    })

    it('says the two numbers, and names who is not marked once some are', () => {
        expect(headCountWarning(3, 2, girls)).toBe(
            'Checked in 3, counted 2. Not marked: Isabel, Mia',
        )
    })

    it('names no one while no child is marked, nor when every child is', () => {
        const none = girls.map((c) => ({ ...c, present: false }))
        const all = girls.map((c) => ({ ...c, present: true }))

        expect(headCountWarning(3, 4, none)).toBe('Checked in 3, counted 4.')
        expect(headCountWarning(3, 4, all)).toBe('Checked in 3, counted 4.')
    })
})

describe('headCountLabel', () => {
    it('says when, and by whom while their login is there', () => {
        expect(headCountLabel({ count: 4, counted_at: '13:20', counted_by: 'Joy' })).toBe(
            '13:20 by Joy',
        )
        expect(headCountLabel({ count: 4, counted_at: '13:20', counted_by: '' })).toBe('13:20')
    })
})
