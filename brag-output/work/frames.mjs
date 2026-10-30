import { chromium } from 'playwright'
import { pathToFileURL } from 'node:url'
import { resolve } from 'node:path'
import { mkdirSync, readFileSync } from 'node:fs'
const MAP = JSON.parse(readFileSync('timemap.json', 'utf8'))
const story = (T) => { for (let i = 1; i < MAP.length; i++) { const [T0, t0] = MAP[i - 1], [T1, t1] = MAP[i]; if (T <= T1) return t0 + (t1 - t0) * (T - T0) / (T1 - T0) } return MAP.at(-1)[1] }
const FPS = 30, DUR = 60
mkdirSync('frames', { recursive: true })
const b = await chromium.launch()
const p = await b.newPage({ viewport: { width: 1920, height: 1080 } })
p.on('pageerror', (e) => console.log('ERR', e.message))
await p.goto(pathToFileURL(resolve('video.html')).href)
await p.evaluate(() => window.ready)
for (let f = 0; f < FPS * DUR; f++) {
  await p.evaluate(async (t) => {
    window.render(t)
    await Promise.all([...document.images].filter((i) => i.src).map((i) => i.decode().catch(() => {})))
    await new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r)))
  }, story(f / FPS))
  await p.screenshot({ path: `frames/f${String(f).padStart(4, '0')}.jpg`, type: 'jpeg', quality: 94 })
  if (f % 150 === 0) console.log('frame', f)
}
await b.close()
