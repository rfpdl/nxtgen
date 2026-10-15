import { photoForUpload } from '@/lib/photoForUpload'
import { afterEach, describe, expect, it, vi } from 'vitest'

/*
 * Photos are turned into a small upright JPEG ON THE PHONE before upload: iPhone HEIC becomes something the
 * server can read, and a 4 MB photo becomes ~300 KB on poor church wifi. If the browser cannot read the file,
 * the original goes up and the server decides.
 */

function fakeCanvas(blob: Blob | null) {
    const drawImage = vi.fn()
    const canvas = {
        width: 0,
        height: 0,
        getContext: () => ({ drawImage }),
        toBlob: (cb: (b: Blob | null) => void) => cb(blob),
    }
    vi.spyOn(document, 'createElement').mockImplementation(
        () => canvas as unknown as HTMLCanvasElement,
    )
    return { canvas, drawImage }
}

afterEach(() => vi.restoreAllMocks())

describe('photoForUpload', () => {
    it('turns an iPhone HEIC into a JPEG, shrunk so the long side is at most 1600px', async () => {
        vi.stubGlobal(
            'createImageBitmap',
            vi.fn(async () => ({ width: 4032, height: 3024, close: vi.fn() })),
        )
        const { canvas } = fakeCanvas(new Blob(['jpeg'], { type: 'image/jpeg' }))

        const out = await photoForUpload(
            new File(['heic'], 'IMG_0001.HEIC', { type: 'image/heic' }),
        )

        expect(out.name).toBe('IMG_0001.jpg')
        expect(out.type).toBe('image/jpeg')
        expect([canvas.width, canvas.height]).toEqual([1600, 1200])
    })

    it('does not enlarge a small photo', async () => {
        vi.stubGlobal(
            'createImageBitmap',
            vi.fn(async () => ({ width: 800, height: 600, close: vi.fn() })),
        )
        const { canvas } = fakeCanvas(new Blob(['jpeg'], { type: 'image/jpeg' }))

        await photoForUpload(new File(['x'], 'small.png', { type: 'image/png' }))

        expect([canvas.width, canvas.height]).toEqual([800, 600])
    })

    it('sends the original when the browser cannot read it', async () => {
        vi.stubGlobal(
            'createImageBitmap',
            vi.fn(async () => Promise.reject(new Error('cannot decode'))),
        )
        const original = new File(['?'], 'odd.heic', { type: 'image/heic' })

        expect(await photoForUpload(original)).toBe(original)
    })
})
