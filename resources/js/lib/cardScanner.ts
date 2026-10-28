/**
 * Reading a NXTGEN name card with the phone's back camera (slice 3): the card carries a Code 39 barcode of the child's
 * full name ("ADAMMATTHIASABERGOS"). The decoder (@zxing/browser, pinned) is loaded only when a scan starts, so the
 * door page itself stays small. Calls `onCode` once, with the first code read, and stops the camera.
 */

import type { DecodeHintType } from '@zxing/library'

/** Why a scan could not start: the camera was refused, or this browser has none to give. */
export type ScanProblem = 'refused' | 'unsupported'

export type CardScan = { stop: () => void }

export class ScanError extends Error {
    constructor(public readonly problem: ScanProblem) {
        super(problem)
    }
}

export async function startCardScan(
    video: HTMLVideoElement,
    onCode: (code: string) => void,
): Promise<CardScan> {
    if (!navigator.mediaDevices?.getUserMedia) {
        throw new ScanError('unsupported')
    }

    // The decoder is its own download: a failed fetch (offline, an old phone) means this phone cannot scan here.
    const [{ BrowserMultiFormatOneDReader }, { BarcodeFormat, DecodeHintType }] = await Promise.all(
        [import('@zxing/browser'), import('@zxing/library')],
    ).catch(() => {
        throw new ScanError('unsupported')
    })
    const hints = new Map<DecodeHintType, unknown>([
        [DecodeHintType.POSSIBLE_FORMATS, [BarcodeFormat.CODE_39]],
    ])
    const reader = new BrowserMultiFormatOneDReader(hints)

    let done = false
    try {
        const controls = await reader.decodeFromConstraints(
            { video: { facingMode: 'environment' }, audio: false },
            video,
            (result, _error, controls) => {
                if (result && !done) {
                    done = true
                    controls.stop()
                    onCode(result.getText())
                }
            },
        )
        return { stop: () => controls.stop() }
    } catch (error) {
        const name = error instanceof Error ? error.name : ''
        throw new ScanError(
            name === 'NotAllowedError' || name === 'SecurityError' ? 'refused' : 'unsupported',
        )
    }
}
