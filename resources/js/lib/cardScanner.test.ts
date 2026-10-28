import { ScanError, startCardScan } from '@/lib/cardScanner'
import { afterEach, describe, expect, it, vi } from 'vitest'

/* The camera scan for name cards: Code 39 only, the back camera, one code then stop; a refusal said as such. */

const decodeFromConstraints = vi.fn()
const hintsSeen: unknown[] = []

vi.mock('@zxing/browser', () => ({
    BrowserMultiFormatOneDReader: class {
        constructor(hints: unknown) {
            hintsSeen.push(hints)
        }
        decodeFromConstraints = decodeFromConstraints
    },
}))

vi.mock('@zxing/library', () => ({
    BarcodeFormat: { CODE_39: 'CODE_39' },
    DecodeHintType: { POSSIBLE_FORMATS: 'POSSIBLE_FORMATS' },
}))

function withCamera(): void {
    Object.defineProperty(navigator, 'mediaDevices', {
        value: { getUserMedia: vi.fn() },
        configurable: true,
    })
}

afterEach(() => {
    decodeFromConstraints.mockReset()
    hintsSeen.length = 0
    Object.defineProperty(navigator, 'mediaDevices', { value: undefined, configurable: true })
})

describe('startCardScan', () => {
    it('reads Code 39 from the back camera, hands over the first code once, and stops', async () => {
        withCamera()
        const stop = vi.fn()
        decodeFromConstraints.mockImplementation(async (constraints, _video, callback) => {
            expect(constraints).toEqual({ video: { facingMode: 'environment' }, audio: false })
            callback({ getText: () => 'AMARAJOYREYES' }, undefined, { stop })
            callback({ getText: () => 'AMARAJOYREYES' }, undefined, { stop })
            return { stop }
        })
        const codes: string[] = []

        await startCardScan(document.createElement('video'), (code) => codes.push(code))

        expect(codes).toEqual(['AMARAJOYREYES'])
        expect(stop).toHaveBeenCalledOnce()
        expect(hintsSeen[0]).toEqual(new Map([['POSSIBLE_FORMATS', ['CODE_39']]]))
    })

    it('says the camera was refused', async () => {
        withCamera()
        decodeFromConstraints.mockRejectedValue(
            Object.assign(new Error('no'), { name: 'NotAllowedError' }),
        )

        await expect(startCardScan(document.createElement('video'), vi.fn())).rejects.toEqual(
            new ScanError('refused'),
        )
    })

    it('says scanning is not possible without a camera to ask for', async () => {
        await expect(startCardScan(document.createElement('video'), vi.fn())).rejects.toMatchObject(
            {
                problem: 'unsupported',
            },
        )
    })
})
