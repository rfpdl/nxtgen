import { readFileSync } from 'node:fs'
import { inflateRawSync } from 'node:zlib'

/**
 * The rows of a downloaded .xlsx's first sheet, each as its cells joined by `|` — the browser-side twin of Pest's
 * `exportedRows()`. An .xlsx is a zip; this reads its central directory, inflates `xl/worksheets/sheet1.xml` and
 * reads the inline-string cells OpenSpout writes (TextRow: every cell is text). Enough for our own downloads only.
 */
export function xlsxRows(path: string): string[] {
    const sheet = entry(readFileSync(path), 'xl/worksheets/sheet1.xml')

    return [...sheet.matchAll(/<row[^>]*>(.*?)<\/row>/gs)].map(([, row]) => {
        const cells: string[] = []
        for (const [, ref, body] of (row ?? '').matchAll(
            /<c r="([A-Z]+)\d+"[^>]*?(?:\/>|>(.*?)<\/c>)/gs,
        )) {
            const text = /<t[^>]*>(.*?)<\/t>/s.exec(body ?? '')?.[1] ?? ''
            cells[column(ref ?? 'A')] = unescape(text)
        }
        return Array.from(cells, (c) => c ?? '').join('|')
    })
}

function column(letters: string): number {
    return [...letters].reduce((n, l) => n * 26 + l.charCodeAt(0) - 64, 0) - 1
}

function unescape(text: string): string {
    return text
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>')
        .replace(/&quot;/g, '"')
        .replace(/&apos;/g, "'")
        .replace(/&amp;/g, '&')
}

/** One file of a zip, found through the central directory (sizes in local headers may be zero). */
function entry(zip: Buffer, name: string): string {
    const end = zip.lastIndexOf(Buffer.from([0x50, 0x4b, 0x05, 0x06]))
    let at = zip.readUInt32LE(end + 16)
    const count = zip.readUInt16LE(end + 10)
    for (let i = 0; i < count; i++) {
        const method = zip.readUInt16LE(at + 10)
        const size = zip.readUInt32LE(at + 20)
        const nameLength = zip.readUInt16LE(at + 28)
        const extra = zip.readUInt16LE(at + 30)
        const comment = zip.readUInt16LE(at + 32)
        const local = zip.readUInt32LE(at + 42)
        if (zip.toString('utf8', at + 46, at + 46 + nameLength) === name) {
            const start = local + 30 + zip.readUInt16LE(local + 26) + zip.readUInt16LE(local + 28)
            const data = zip.subarray(start, start + size)
            return (method === 8 ? inflateRawSync(data) : data).toString('utf8')
        }
        at += 46 + nameLength + extra + comment
    }
    throw new Error(`${name} is not in the download`)
}
