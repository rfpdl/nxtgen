import MovingUpLastCard from '@/components/molecules/MovingUpLastCard.vue'
import type { MovingUp, MovingUpChild } from '@/types'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

vi.mock('@inertiajs/vue3', async (original) => ({
    ...(await import('@/tests/enumPage')).withEnums(await original<object>()),
    Link: {
        props: ['href', 'method', 'preserveScroll'],
        template:
            '<a :href="href" :data-method="method" :data-preserve-scroll="String(preserveScroll)"><slot /></a>',
    },
}))

vi.mock('ziggy-js', () => ({
    route: (name: string, params: unknown[]) => `/#${name}/${params.join('/')}`,
}))

const amara: MovingUpChild = {
    child_id: 3,
    nickname: 'Amara',
    full_name: 'Amara Joy Reyes',
    from: 'Level 1',
    to: 'Level 2',
    put_back: null,
}
const gabe: MovingUpChild = {
    child_id: 4,
    nickname: 'Gabe',
    full_name: 'Gabe Cruz',
    from: 'Level 4',
    to: 'Graduated',
    put_back: 'Ana Admin · Sat 7 Feb 10:31',
}
const last: MovingUp = {
    id: 9,
    made: 'Sat 7 Feb 2026 10:00',
    made_by: 'Ana Admin',
    is_recent: true,
    children: [amara, gabe],
}

describe('MovingUpLastCard', () => {
    it('says when and by whom, and each child from and to', () => {
        const card = mount(MovingUpLastCard, { props: { movingUp: last } })

        expect(card.find('[data-testid="moving-up-last-when"]').text()).toBe(
            'Sat 7 Feb 2026 10:00 by Ana Admin',
        )
        expect(card.findAll('[data-testid="moved-child-step"]').map((s) => s.text())).toEqual([
            'Level 1 → Level 2',
            'Level 4 → Graduated',
        ])
    })

    it('puts back a child not yet put back, keeping the scroll — and says who put back the other', () => {
        const card = mount(MovingUpLastCard, { props: { movingUp: last } })
        const buttons = card.findAll('[data-testid="moved-child-put-back-button"]')

        expect(buttons).toHaveLength(1)
        expect(buttons[0]?.attributes()).toMatchObject({
            href: '/#admin.moving-up.children.put-back/dubai/9/3',
            'data-method': 'put',
            'data-preserve-scroll': 'true',
        })
        expect(card.find('[data-testid="moved-child-put-back"]').text()).toBe(
            'Put back by Ana Admin · Sat 7 Feb 10:31',
        )
    })

    it('offers "Put everyone back" only while someone is left to put back', () => {
        expect(
            mount(MovingUpLastCard, { props: { movingUp: last } })
                .find('[data-testid="moving-up-put-back-everyone"]')
                .attributes('href'),
        ).toBe('/#admin.moving-up.put-back/dubai/9')
        expect(
            mount(MovingUpLastCard, { props: { movingUp: { ...last, children: [gabe] } } })
                .find('[data-testid="moving-up-put-back-everyone"]')
                .exists(),
        ).toBe(false)
    })
})
