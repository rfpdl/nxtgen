import ServeClassCard from '@/components/molecules/ServeClassCard.vue'
import type { ServeClass } from '@/types'
import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'

vi.mock('ziggy-js', () => ({
    route: (name: string, code: string) => `/${code}/${name.replace('session.', '')}`,
}))

const onePm: ServeClass = {
    id: 4,
    name: '1PM',
    times: '13:00–15:00',
    state: 'open',
    code: 'abcd2345',
    can_open: false,
}

describe('ServeClassCard', () => {
    it("takes anyone to an open class's door at its own address", () => {
        const wrapper = mount(ServeClassCard, { props: { serveClass: onePm } })

        expect(wrapper.text()).toContain('1PM')
        expect(wrapper.text()).toContain('13:00–15:00')
        expect(wrapper.find('[data-testid="serve-class-state"]').text()).toBe('Open')
        expect(wrapper.find('[data-testid="serve-door"]').attributes('href')).toBe(
            '/abcd2345/checkin',
        )
        expect(wrapper.find('[data-testid="serve-open"]').exists()).toBe(false)
    })

    it('says a class is not open yet, with Open for its admin only', async () => {
        const notOpen = { ...onePm, state: 'not_open' as const, code: null }
        const lifeshaper = mount(ServeClassCard, { props: { serveClass: notOpen } })
        expect(lifeshaper.find('[data-testid="serve-class-state"]').text()).toBe('Not open yet')
        expect(lifeshaper.find('[data-testid="serve-door"]').exists()).toBe(false)
        expect(lifeshaper.find('[data-testid="serve-open"]').exists()).toBe(false)

        const admin = mount(ServeClassCard, {
            props: { serveClass: { ...notOpen, can_open: true } },
        })
        await admin.find('[data-testid="serve-open"]').trigger('click')
        expect(admin.find('[data-testid="serve-open"]').text()).toBe('Open 1PM')
        expect(admin.emitted('open')).toEqual([[4]])
    })

    it('lets anyone look at a closed class, and its admin open it again', () => {
        const closed = { ...onePm, state: 'closed' as const, can_open: true }
        const wrapper = mount(ServeClassCard, { props: { serveClass: closed } })

        expect(wrapper.find('[data-testid="serve-class-state"]').text()).toBe('Closed')
        expect(wrapper.find('[data-testid="serve-door"]').text()).toBe('See who was in')
        expect(wrapper.find('[data-testid="serve-door"]').attributes('href')).toBe(
            '/abcd2345/whos-in',
        )
        expect(wrapper.find('[data-testid="serve-open"]').text()).toBe('Open 1PM again')
    })
})
