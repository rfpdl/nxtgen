import SessionControls from '@/components/molecules/SessionControls.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

describe('SessionControls', () => {
    it('says a class is not open yet, and offers Open to its admin only', async () => {
        const admin = mount(SessionControls, {
            props: {
                className: '1PM',
                state: 'not_open',
                notice: '1PM is not open yet.',
                stateLabel: 'Not open yet',
                stateColor: 'muted',
                canOpen: true,
            },
        })
        expect(admin.text()).toContain('1PM is not open yet.')
        await admin.find('[data-testid="session-open"]').trigger('click')
        expect(admin.emitted('open')).toHaveLength(1)

        const lifeshaper = mount(SessionControls, {
            props: {
                className: '1PM',
                state: 'not_open',
                notice: '1PM is not open yet.',
                stateLabel: 'Not open yet',
                stateColor: 'muted',
                canOpen: false,
            },
        })
        expect(lifeshaper.text()).toContain('An admin opens it.')
        expect(lifeshaper.find('[data-testid="session-open"]').exists()).toBe(false)
    })

    it('shows an open class as open; its admin closes it after a second tap', async () => {
        const wrapper = mount(SessionControls, {
            props: {
                className: '1PM',
                state: 'open',
                notice: null,
                stateLabel: 'Open',
                stateColor: 'success',
                canOpen: true,
            },
        })
        expect(wrapper.find('[data-testid="session-state"]').text()).toBe('Open')

        await wrapper.find('[data-testid="session-close"]').trigger('click')
        expect(wrapper.emitted('close')).toBeUndefined()
        expect(wrapper.text()).toContain('Close 1PM? Nothing can change after')
        await wrapper.find('[data-testid="confirm-close"]').trigger('click')
        expect(wrapper.emitted('close')).toHaveLength(1)
    })

    it('offers no Close to a Lifeshaper', () => {
        const wrapper = mount(SessionControls, {
            props: {
                className: '1PM',
                state: 'open',
                notice: null,
                stateLabel: 'Open',
                stateColor: 'success',
                canOpen: false,
            },
        })
        expect(wrapper.find('[data-testid="session-close"]').exists()).toBe(false)
    })

    it('says a closed class can be looked at, not changed — and its admin may open it again', async () => {
        const wrapper = mount(SessionControls, {
            props: {
                className: '1PM',
                state: 'closed',
                notice: '1PM is closed.',
                stateLabel: 'Closed',
                stateColor: 'warning',
                canOpen: true,
            },
        })
        expect(wrapper.text()).toContain('1PM is closed. It can be looked at, not changed.')
        expect(wrapper.find('[data-testid="session-open"]').text()).toBe('Open 1PM again')
        await wrapper.find('[data-testid="session-open"]').trigger('click')
        expect(wrapper.emitted('open')).toHaveLength(1)
    })
})
