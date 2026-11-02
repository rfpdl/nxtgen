import { test, type Download } from '@playwright/test'

/**
 * Where a download is on this machine. `download.path()` works only for a browser launched here; Firefox on a Mac runs
 * in Docker (playwright.config.ts), so the file is copied into this test's output folder instead — the same for all.
 */
export async function savedDownload(download: Download): Promise<string> {
    const path = test.info().outputPath(download.suggestedFilename())
    await download.saveAs(path)
    return path
}
