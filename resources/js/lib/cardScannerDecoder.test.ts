import { BarcodeFormat, BrowserMultiFormatOneDReader } from '@zxing/browser'
import { DecodeHintType } from '@zxing/library'
import { describe, expect, it } from 'vitest'

/* The real decoder, unmocked: what cardScanner calls on it is there, so a version bump that drops it fails here. */

describe('the name-card decoder (@zxing/browser, pinned)', () => {
    it('reads Code 39 from a camera through decodeFromConstraints', () => {
        expect(typeof BrowserMultiFormatOneDReader.prototype.decodeFromConstraints).toBe('function')
        expect(BarcodeFormat.CODE_39).toBeDefined()
        expect(DecodeHintType.POSSIBLE_FORMATS).toBeDefined()
    })
})
