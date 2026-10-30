import { chromium } from 'playwright'
import { pathToFileURL } from 'node:url'
import { resolve } from 'node:path'
const times = process.argv.slice(2).map(Number)
const b = await chromium.launch()
const p = await b.newPage({ viewport: { width: 1920, height: 1080 } })
p.on('console', (m) => console.log('console', m.text()))
p.on('pageerror', (e) => console.log('ERR', e.message))
await p.goto(pathToFileURL(resolve('video.html')).href)
await p.evaluate(() => window.ready)
for (const t of times) {
  await p.evaluate((t) => window.render(t), t)
  await p.waitForTimeout(80)
  await p.screenshot({ path: `stills/t${t.toFixed(2)}.jpg`, type: 'jpeg', quality: 70 })
}
await b.close()
