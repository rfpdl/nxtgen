import ScanCardSheet from '@/components/organisms/ScanCardSheet.vue'
import { ScanError } from '@/lib/cardScanner'
import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'

const startCardScan = vi.fn()

vi.mock('@/lib/cardScanner', async (importOriginal) => ({
    ...(await importOriginal<Record<string, unknown>>()),
    startCardScan: (...args: unknown[]) => startCardScan(...args),
}))

function sheet(open = true) {
    return mount(ScanCardSheet, { props: { open }, attachTo: document.body })
}

const find = (testid: string): HTMLElement | null =>
    document.body.querySelector(`[data-testid="${testid}"]`)

afterEach(() => {
    startCardScan.mockReset()
    document.body.innerHTML = ''
})

describe('ScanCardSheet', () => {
    it('opens the camera into the sheet, hands over the first card read and closes', async () => {
        let read: (code: string) => void = () => {}
        startCardScan.mockImplementation(
            async (_video: HTMLVideoElement, onCode: (code: string) => void) => {
                read = onCode
                return { stop: vi.fn() }
            },
        )
        const wrapper = sheet(false)
        await wrapper.setProps({ open: true })
        await flushPromises()

        expect(startCardScan).toHaveBeenCalledOnce()
        expect(startCardScan.mock.calls[0]?.[0]).toBeInstanceOf(HTMLVideoElement)
        expect(find('scan-message')?.textContent).toBe(
            'Point the camera at the barcode on the name card.',
        )

        read('AMARAJOYREYES')
        await flushPromises()
        expect(wrapper.emitted('code')).toEqual([['AMARAJOYREYES']])
        expect(wrapper.emitted('update:open')).toEqual([[false]])
    })

    it('says "Type the name instead" when the camera is refused', async () => {
        startCardScan.mockRejectedValue(new ScanError('refused'))
        const wrapper = sheet(false)
        await wrapper.setProps({ open: true })
        await flushPromises()

        const message = find('scan-message')
        expect(message?.textContent).toBe('The camera is not allowed. Type the name instead.')
        expect(message?.getAttribute('role')).toBe('alert')
        expect(wrapper.emitted('code')).toBeUndefined()
    })

    it('says so when this phone cannot scan', async () => {
        startCardScan.mockRejectedValue(new ScanError('unsupported'))
        const wrapper = sheet(false)
        await wrapper.setProps({ open: true })
        await flushPromises()

        expect(find('scan-message')?.textContent).toBe(
            'This phone cannot scan here. Type the name instead.',
        )
    })

    it('stops the camera when cancelled', async () => {
        const stop = vi.fn()
        startCardScan.mockResolvedValue({ stop })
        const wrapper = sheet(false)
        await wrapper.setProps({ open: true })
        await flushPromises()

        find('scan-cancel')?.click()
        await flushPromises()

        expect(wrapper.emitted('update:open')).toEqual([[false]])
        await wrapper.setProps({ open: false })
        expect(stop).toHaveBeenCalledOnce()
    })

    it('opens from its own "Scan card" button', async () => {
        startCardScan.mockResolvedValue({ stop: vi.fn() })
        const wrapper = sheet(false)

        await wrapper.find('[data-testid="scan-card"]').trigger('click')

        expect(wrapper.find('[data-testid="scan-card"]').text()).toBe('Scan card')
        expect(wrapper.emitted('update:open')).toEqual([[true]])
    })

    it('says this phone cannot scan when the decoder cannot load', async () => {
        startCardScan.mockRejectedValue(
            new TypeError('Failed to fetch dynamically imported module'),
        )
        const wrapper = sheet(false)
        await wrapper.setProps({ open: true })
        await flushPromises()

        expect(find('scan-message')?.textContent).toBe(
            'This phone cannot scan here. Type the name instead.',
        )
    })

    it('stops the camera when the page goes away', async () => {
        const stop = vi.fn()
        startCardScan.mockResolvedValue({ stop })
        const wrapper = sheet(false)
        await wrapper.setProps({ open: true })
        await flushPromises()

        wrapper.unmount()

        expect(stop).toHaveBeenCalledOnce()
    })

    it('stops a camera that starts only after the sheet was closed — even if it was opened again meanwhile', async () => {
        const first = { stop: vi.fn() }
        const second = { stop: vi.fn() }
        let resolveFirst: (scan: typeof first) => void = () => {}
        startCardScan
            .mockImplementationOnce(() => new Promise((resolve) => (resolveFirst = resolve)))
            .mockResolvedValueOnce(second)
        const wrapper = sheet(false)

        await wrapper.setProps({ open: true })
        await flushPromises()
        await wrapper.setProps({ open: false })
        await wrapper.setProps({ open: true })
        await flushPromises()
        resolveFirst(first)
        await flushPromises()

        // The late first camera is stopped at once; the second one runs until the sheet closes.
        expect(first.stop).toHaveBeenCalledOnce()
        expect(second.stop).not.toHaveBeenCalled()
        await wrapper.setProps({ open: false })
        expect(second.stop).toHaveBeenCalledOnce()
    })
})
