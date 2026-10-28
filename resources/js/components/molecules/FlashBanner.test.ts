import FlashBanner from '@/components/molecules/FlashBanner.vue'
import type { FlashMessage } from '@/composables/useFlashMessages'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

const message = (over: Partial<FlashMessage> = {}): FlashMessage => ({
    id: 1,
    tone: 'success',
    text: 'Igo checked in.',
    ...over,
})

const banner = (props: { message: FlashMessage | null }) => mount(FlashBanner, { props })

describe('FlashBanner', () => {
    it('is nothing at all when there is nothing to say', () => {
        // Not an empty bar. A band of blank colour above the roster costs vertical space on the
        // screen that has least of it.
        const wrapper = banner({ message: null })

        expect(wrapper.find('[data-testid="flash-success"]').exists()).toBe(false)
        expect(wrapper.find('[data-testid="flash-error"]').exists()).toBe(false)
        expect(wrapper.text()).toBe('')
    })

    it('shows what the server said', () => {
        expect(banner({ message: message() }).text()).toContain('Igo checked in.')
    })

    describe('errors and successes are not symmetrical', () => {
        /*
         * An error is the ONLY account of something that did not happen — so it interrupts. A success
         * is usually a second telling of something the screen already shows, so it waits its turn
         * rather than cutting across a name being read out.
         */
        it('interrupts a screen reader for an error, and not for a success', () => {
            const error = banner({ message: message({ tone: 'error', text: 'It did not.' }) })
            const success = banner({ message: message() })

            expect(error.find('[data-testid="flash-error"]').attributes('role')).toBe('alert')
            expect(
                error.find('[data-testid="flash-error"]').attributes('aria-live'),
            ).toBeUndefined()

            expect(success.find('[data-testid="flash-success"]').attributes('role')).toBeUndefined()
            expect(success.find('[data-testid="flash-success"]').attributes('aria-live')).toBe(
                'polite',
            )
        })

        it('is coloured by tone', () => {
            expect(
                banner({ message: message({ tone: 'error' }) })
                    .find('[role="alert"]')
                    .classes(),
            ).toContain('bg-danger')

            expect(
                banner({ message: message() }).find('[data-testid="flash-success"]').classes(),
            ).toContain('bg-action')
        })

        it("rings a focused control in the band's own text colour, which shows on it", () => {
            expect(
                banner({ message: message({ tone: 'error' }) })
                    .find('[role="alert"]')
                    .classes(),
            ).toContain('focus-on-danger')
            expect(
                banner({ message: message() }).find('[data-testid="flash-success"]').classes(),
            ).toContain('focus-on-action')
        })
    })

    /*
     * The message answers a POST, and the tap that made the POST is often two hundred children down
     * the roster. Placed at the top of the document it would arrive off-screen — indistinguishable
     * from the silence this component exists to end.
     */
    it('follows the screen down', () => {
        expect(
            banner({ message: message() }).find('[data-testid="flash-success"]').classes(),
        ).toContain('sticky')
    })

    it('can be put away', () => {
        // It covers the heading while it is up, and a staff member who has read it should be able to
        // move it rather than scroll around it.
        const wrapper = banner({ message: message() })

        wrapper.find('[data-testid="dismiss-flash"]').trigger('click')

        expect(wrapper.emitted('dismiss')).toHaveLength(1)
    })

    it('names the dismiss control for anyone who cannot see the cross', () => {
        expect(
            banner({ message: message() })
                .find('[data-testid="dismiss-flash"]')
                .attributes('aria-label'),
        ).toBe('Dismiss this message')
    })

    it('lines up with a narrow page when asked', () => {
        expect(banner({ message: message() }).html()).toContain('max-w-5xl')
        expect(
            mount(FlashBanner, { props: { message: message(), narrow: true } }).html(),
        ).toContain('max-w-2xl')
    })
})
