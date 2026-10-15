import { useFlashMessages } from '@/composables/useFlashMessages'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { defineComponent, h, nextTick, reactive } from 'vue'

/*
 * A stand-in for Inertia's page object. It is `reactive` and its `flash` is REPLACED rather than
 * mutated on each "response", because that is what Inertia does — and the identity change is the
 * thing this composable depends on.
 */
const page = reactive<{ props: { flash: { success?: string | null; error?: string | null } } }>({
    props: { flash: {} },
})

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => page,
}))

/*
 * Mounted rather than called bare: the composable uses `watch`, which needs a component's effect
 * scope to be collected with. Same harness as `useCardScan.test.ts`.
 */
function harness() {
    const api: { value: ReturnType<typeof useFlashMessages> | null } = { value: null }

    mount(
        defineComponent({
            setup() {
                api.value = useFlashMessages()

                return () => h('div')
            },
        }),
    )

    return api.value!
}

/** One "response from the server". */
async function respond(flash: { success?: string | null; error?: string | null }): Promise<void> {
    page.props.flash = flash
    await nextTick()
}

beforeEach(() => {
    page.props.flash = {}
})

describe('useFlashMessages', () => {
    it('is silent when the server said nothing', () => {
        const flash = harness()

        expect(flash.message.value).toBeNull()
    })

    it('picks up a message the page arrived with', () => {
        // A flash can be present on the very first render — a redirect straight into a screen.
        page.props.flash = { success: 'Igo checked in.' }

        const flash = harness()

        expect(flash.message.value?.text).toBe('Igo checked in.')
        expect(flash.message.value?.tone).toBe('success')
    })

    it('picks up a message that arrives later', async () => {
        const flash = harness()

        await respond({ error: 'No temporary card is numbered T-0042.' })

        expect(flash.message.value?.text).toBe('No temporary card is numbered T-0042.')
        expect(flash.message.value?.tone).toBe('error')
    })

    /*
     * THE ONE THAT MATTERS. Inertia hands over a new `flash` object every response, but the string
     * inside can be identical — check a child in, undo, check them in again. Watching the text would
     * see no change and the third message would never appear, on the app where the same sentence
     * about the same child is the common case rather than an edge one.
     */
    it('announces an identical message a second time', async () => {
        const flash = harness()

        await respond({ success: 'Igo checked in.' })
        const first = flash.message.value?.id

        await respond({ success: 'Igo checked in.' })

        expect(flash.message.value?.text).toBe('Igo checked in.')
        expect(flash.message.value?.id).not.toBe(first)
    })

    it('shows the error when a response carries both', async () => {
        // A family tap where one sibling was refused. The refusal is the one somebody has to act on.
        const flash = harness()

        await respond({ success: '2 checked in', error: '1 could not be' })

        expect(flash.message.value?.tone).toBe('error')
        expect(flash.message.value?.text).toBe('1 could not be')
    })

    it('is cleared by the next request rather than by a timer', async () => {
        // Every action on these screens is a request, so the next one replaces or nulls the message —
        // which is what a timer would approximate, without a timer's habit of removing an error
        // mid-sentence.
        const flash = harness()

        await respond({ error: 'That check-in was undone.' })
        await respond({})

        expect(flash.message.value).toBeNull()
    })

    it('treats an empty string as nothing to say', async () => {
        const flash = harness()

        await respond({ success: '' })

        expect(flash.message.value).toBeNull()
    })

    it('can be put away', async () => {
        const flash = harness()

        await respond({ error: 'Something went wrong.' })
        flash.dismiss()

        expect(flash.message.value).toBeNull()
    })

    it('shows the next message after one was dismissed', async () => {
        // Dismissal is about the message on screen, not about the channel. A volunteer who put one
        // away must still get the next one.
        const flash = harness()

        await respond({ error: 'First problem.' })
        flash.dismiss()
        await respond({ error: 'Second problem.' })

        expect(flash.message.value?.text).toBe('Second problem.')
    })
})
