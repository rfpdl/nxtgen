import { readFileSync } from 'node:fs'
import { join } from 'node:path'
import { describe, expect, it } from 'vitest'

/**
 * The contrast floor, measured from the tokens themselves (app.css), so a token edit that breaks it fails here
 * rather than in a bright foyer. Text on staff screens is ≥7:1 (plan §5.10); a control's edge and the focus ring are
 * ≥3:1 against what they sit on (WCAG 1.4.11 / 2.4.13).
 */
const css = readFileSync(join(process.cwd(), 'resources/css/app.css'), 'utf-8')
const theme = css.slice(css.indexOf('@theme {'), css.indexOf('@theme inline'))
const tokens = Object.fromEntries(
    [...theme.matchAll(/--color-([\w-]+):\s*([^;]+);/g)].map((m) => [m[1], m[2]?.trim()]),
)

function linear(channel: number): number {
    return channel <= 0.04045 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4
}

/** Linear sRGB of a `#rrggbb` or `oklch(L C H)` token value. */
function rgb(value: string): [number, number, number] {
    const hex = /^#([0-9a-f]{6})$/i.exec(value)
    if (hex) {
        const n = parseInt(hex[1] ?? '0', 16)
        return [linear((n >> 16) / 255), linear(((n >> 8) & 255) / 255), linear((n & 255) / 255)]
    }
    const lch = /^oklch\(([\d.]+)\s+([\d.]+)\s+([\d.]+)\)$/.exec(value)
    if (!lch) {
        throw new Error(`Cannot read the colour ${value}`)
    }
    const [L, C, H] = [Number(lch[1]), Number(lch[2]), (Number(lch[3]) * Math.PI) / 180]
    const [a, b] = [C * Math.cos(H), C * Math.sin(H)]
    const l = (L + 0.3963377774 * a + 0.2158037573 * b) ** 3
    const m = (L - 0.1055613458 * a - 0.0638541728 * b) ** 3
    const s = (L - 0.0894841775 * a - 1.291485548 * b) ** 3
    const clamp = (v: number): number => Math.min(1, Math.max(0, v))
    return [
        clamp(4.0767416621 * l - 3.3077115913 * m + 0.2309699292 * s),
        clamp(-1.2684380046 * l + 2.6097574011 * m - 0.3413193965 * s),
        clamp(-0.0041960863 * l - 0.7034186147 * m + 1.707614701 * s),
    ]
}

function luminance(token: string): number {
    const value = tokens[token]
    if (value === undefined) {
        throw new Error(`No token --color-${token}`)
    }
    const [r, g, b] = rgb(value)
    return 0.2126 * r + 0.7152 * g + 0.0722 * b
}

function contrast(fg: string, bg: string): number {
    const [x, y] = [luminance(fg), luminance(bg)]
    return (Math.max(x, y) + 0.05) / (Math.min(x, y) + 0.05)
}

describe('the contrast floor', () => {
    it.each([
        ['text', 'surface'],
        ['text-muted', 'surface'],
        ['on-bar', 'bar'],
        ['on-action', 'action'],
        ['on-action', 'action-strong'],
        ['on-danger', 'danger'],
        ['on-primary', 'primary'],
        ['primary', 'primary-soft'],
        ['danger', 'danger-soft'],
        ['success', 'success-soft'],
        ['warning', 'warning-soft'],
        ['info', 'info-soft'],
        ['on-level-1', 'level-1-soft'],
        ['on-level-2', 'level-2-soft'],
        ['on-level-3', 'level-3-soft'],
        ['on-level-4', 'level-4-soft'],
    ])('text %s on %s is at least 7:1', (fg, bg) => {
        expect(contrast(fg, bg)).toBeGreaterThanOrEqual(7)
    })

    it.each([
        // A control's edge — the empty tick box, an input — against the page.
        ['border-strong', 'surface'],
        ['border-strong', 'surface-sunken'],
        // The focus ring, wherever a control can sit.
        ['focus', 'surface'],
        ['focus', 'surface-sunken'],
        ['focus', 'primary-soft'],
        ['on-bar', 'bar'],
        ['on-action', 'action'],
        ['on-danger', 'danger'],
    ])('the edge %s on %s is at least 3:1', (fg, bg) => {
        expect(contrast(fg, bg)).toBeGreaterThanOrEqual(3)
    })

    it('reads both kinds of colour the tokens are written in', () => {
        expect(contrast('text', 'surface')).toBeGreaterThan(15)
        expect(contrast('on-action', 'action')).toBeGreaterThan(7)
    })

    it('has one naming scheme: text on a colour is on-<colour>', () => {
        expect(Object.keys(tokens).filter((t) => /-ink$|^on-dark$/.test(t))).toEqual([])
    })
})
