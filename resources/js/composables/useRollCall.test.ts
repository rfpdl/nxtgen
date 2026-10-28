import { ROLL_CALL_POLL_MS, useRollCall } from '@/composables/useRollCall'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { defineComponent, h } from 'vue'

type PutOptions = {
    onFinish: () => void
    errorBag?: string
    onError?: (errors: Record<string, string>) => void
}
type PollOptions = {
    only: string[]
    onBefore: () => boolean
    onCancelToken: (token: { cancel: () => void }) => void
    onFinish: () => void
}

const puts: { url: string; data: Record<string, unknown>; options: PutOptions }[] = []
let pollInterval = 0
let pollOptions: () => PollOptions = () => {
    throw new Error('usePoll was not called')
}

vi.mock('@inertiajs/vue3', () => ({
    router: {
        put: (url: string, data: Record<string, unknown>, options: PutOptions) =>
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

        api.mark(31, true, 0)
        expect(api.pending[31]).toBe(true)
        await flush()
        expect(puts.map((p) => [p.url, p.data])).toEqual([
            ['church.door.present:["ccf",31]', { present: true, revision: 0 }],
        ])

        puts[0]?.options.onFinish()
        expect(api.pending[31]).toBeUndefined()
    })

    it('sends taps one after another, in the order made', async () => {
        const { api } = host()

        api.mark(31, true, 0)
        api.mark(32, true, 0)
        api.mark(31, false, 0)
        await flush()
        expect(puts).toHaveLength(1)

        puts[0]?.options.onFinish()
        await flush()
        expect(puts).toHaveLength(2)
        // Child 31 still has a tap to go, so it still shows it.
        expect(api.pending[31]).toBe(false)

        puts[1]?.options.onFinish()
        await flush()
        expect(puts.map((p) => p.data['present'])).toEqual([true, true, false])
    })

    it('skips the refresh while a tap is waiting, and cancels one already on its way', async () => {
        const { api } = host()
        const cancel = vi.fn()
        const refresh = pollOptions()
        expect(refresh.onBefore()).toBe(true)
        refresh.onCancelToken({ cancel })

        api.mark(31, true, 0)
        expect(cancel).toHaveBeenCalledOnce()
        expect(pollOptions().onBefore()).toBe(false)

        await flush()
        puts[0]?.options.onFinish()
        expect(pollOptions().onBefore()).toBe(true)
    })

    it('drops the taps not yet sent when the page is left', async () => {
        const { wrapper, api } = host()

        api.mark(31, true, 0)
        api.mark(32, true, 0)
        await flush()
        wrapper.unmount()
        puts[0]?.options.onFinish()
        await flush()

        expect(puts).toHaveLength(1)
    })

    it('drops the taps not yet sent on stop (a chip moving to another class or Squad)', async () => {
        const { api } = host()

        api.mark(31, true, 0)
        api.mark(32, true, 0)
        await flush()
        api.stop()
        expect(api.pending).toEqual({})
        puts[0]?.options.onFinish()
        await flush()

        expect(puts).toHaveLength(1)
        expect(pollOptions().onBefore()).toBe(true)
    })

    it('confirms a head count after the taps before it, showing "Confirming…" until answered', async () => {
        const { api } = host()

        api.mark(31, true, 0)
        api.confirm(1, 5, 4)
        expect(api.confirming[5]).toBe(true)
        expect(pollOptions().onBefore()).toBe(false)
        await flush()
        puts[0]?.options.onFinish()
        await flush()

        expect(puts[1]?.url).toBe('church.door.head-count:["ccf",1,5]')
        expect(puts[1]?.data).toEqual({ count: 4 })
        expect(puts[1]?.options.errorBag).toBe('head-count-5')
        puts[1]?.options.onFinish()
        expect(api.confirming[5]).toBeUndefined()
        expect(pollOptions().onBefore()).toBe(true)
    })

    it("keeps a refused count's message by Squad, and clears it on the next try", async () => {
        const { api } = host()

        api.confirm(1, 5, 999)
        await flush()
        puts[0]?.options.onError?.({ count: 'Please type a number from 0 to 200.' })
        puts[0]?.options.onFinish()
        expect(api.countErrors[5]).toBe('Please type a number from 0 to 200.')

        api.confirm(1, 5, 4)
        expect(api.countErrors[5]).toBeUndefined()
    })

    it('drops a head count not yet sent on stop', async () => {
        const { api } = host()

        api.mark(31, true, 0)
        api.confirm(1, 5, 4)
        await flush()
        api.stop()
        expect(api.confirming).toEqual({})
        puts[0]?.options.onFinish()
        await flush()

        expect(puts).toHaveLength(1)
    })

    it('keeps "Confirming…" until the last of two counts for one Squad is answered', async () => {
        const { api } = host()

        api.confirm(1, 5, 3)
        api.confirm(1, 5, 4)
        await flush()
        puts[0]?.options.onFinish()
        expect(api.confirming[5]).toBe(true)
        expect(pollOptions().onBefore()).toBe(false)

        await flush()
        puts[1]?.options.onFinish()
        expect(api.confirming[5]).toBeUndefined()
    })

    it('sends the revision of the Check-in it showed, so a tap never marks one revived since', async () => {
        const { api } = host()

        api.mark(31, true, 3)
        await flush()

        expect(puts[0]?.data).toEqual({ present: true, revision: 3 })
    })
})
