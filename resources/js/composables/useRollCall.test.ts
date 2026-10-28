import { ROLL_CALL_POLL_MS, useRollCall } from '@/composables/useRollCall'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { defineComponent, h } from 'vue'

type PutOptions = { onFinish: () => void }
type PollOptions = {
    only: string[]
    onBefore: () => boolean
    onCancelToken: (token: { cancel: () => void }) => void
    onFinish: () => void
}

const puts: { url: string; data: { present: boolean }; options: PutOptions }[] = []
let pollInterval = 0
let pollOptions: () => PollOptions = () => {
    throw new Error('usePoll was not called')
}

vi.mock('@inertiajs/vue3', () => ({
    router: {
        put: (url: string, data: { present: boolean }, options: PutOptions) =>
            puts.push({ url, data, options }),
    },
    usePoll: (interval: number, options: () => PollOptions) => {
        pollInterval = interval
        pollOptions = options
    },
}))

vi.mock('ziggy-js', () => ({
    route: (name: string, params: unknown[]) => `${name}:${JSON.stringify(params)}`,
}))

function host() {
    let api!: ReturnType<typeof useRollCall>
    const wrapper = mount(
        defineComponent({
            setup() {
                api = useRollCall(() => 'ccf')
                return () => h('div')
            },
        }),
    )
    return { wrapper, api }
}

/** Lets the queue's promises run. */
async function flush(): Promise<void> {
    await new Promise((resolve) => setTimeout(resolve, 0))
}

beforeEach(() => {
    puts.length = 0
})

describe('useRollCall', () => {
    it('refreshes the list every 20 seconds, the Squads only', () => {
        host()

        expect(pollInterval).toBe(20_000)
        expect(ROLL_CALL_POLL_MS).toBe(20_000)
        expect(pollOptions().only).toEqual(['squads', 'squad_id'])
    })

    it('shows a tap at once, sends it, and lets the server answer once it is done', async () => {
        const { api } = host()

        api.mark(31, true)
        expect(api.pending[31]).toBe(true)
        await flush()
        expect(puts.map((p) => [p.url, p.data])).toEqual([
            ['church.door.present:["ccf",31]', { present: true }],
        ])

        puts[0]?.options.onFinish()
        expect(api.pending[31]).toBeUndefined()
    })

    it('sends taps one after another, in the order made', async () => {
        const { api } = host()

        api.mark(31, true)
        api.mark(32, true)
        api.mark(31, false)
        await flush()
        expect(puts).toHaveLength(1)

        puts[0]?.options.onFinish()
        await flush()
        expect(puts).toHaveLength(2)
        // Child 31 still has a tap to go, so it still shows it.
        expect(api.pending[31]).toBe(false)

        puts[1]?.options.onFinish()
        await flush()
        expect(puts.map((p) => p.data.present)).toEqual([true, true, false])
    })

    it('skips the refresh while a tap is waiting, and cancels one already on its way', async () => {
        const { api } = host()
        const cancel = vi.fn()
        const refresh = pollOptions()
        expect(refresh.onBefore()).toBe(true)
        refresh.onCancelToken({ cancel })

        api.mark(31, true)
        expect(cancel).toHaveBeenCalledOnce()
        expect(pollOptions().onBefore()).toBe(false)

        await flush()
        puts[0]?.options.onFinish()
        expect(pollOptions().onBefore()).toBe(true)
    })

    it('drops the taps not yet sent when the page is left', async () => {
        const { wrapper, api } = host()

        api.mark(31, true)
        api.mark(32, true)
        await flush()
        wrapper.unmount()
        puts[0]?.options.onFinish()
        await flush()

        expect(puts).toHaveLength(1)
    })

    it('drops the taps not yet sent on stop (a chip moving to another class or Squad)', async () => {
        const { api } = host()

        api.mark(31, true)
        api.mark(32, true)
        await flush()
        api.stop()
        expect(api.pending).toEqual({})
        puts[0]?.options.onFinish()
        await flush()

        expect(puts).toHaveLength(1)
        expect(pollOptions().onBefore()).toBe(true)
    })
})
