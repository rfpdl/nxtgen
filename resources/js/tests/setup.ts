/**
 * Test environment setup.
 *
 * **Why `localStorage` is installed by hand.** happy-dom v20 on Node 26 provides `window` and
 * `document` but no `localStorage`, and Node's own experimental implementation refuses to initialise
 * without `--localstorage-file`. So a component that reads storage throws
 * `Cannot read properties of undefined` in tests while working perfectly in a browser — which reads
 * like a broken component rather than a missing environment feature.
 *
 * A real in-memory `Storage` rather than a mock, so that a component with a documented fallback for
 * storage being *unavailable* (private browsing, disabled by policy) can have that path tested by
 * making these methods throw. A stub returning `undefined` makes the happy path and the failure path
 * look the same.
 *
 * **Nothing reads storage right now** — the text-size control that did was removed (`TODO.md` #3).
 * This stays because it is environment setup, not product surface: without it the symptom is
 * `Cannot read properties of undefined` in a component that works fine in a browser, which costs
 * whoever meets it an hour. Offline check-in (`TODO.md` #1) is browser storage by definition.
 */
class MemoryStorage implements Storage {
    private data = new Map<string, string>()

    get length(): number {
        return this.data.size
    }

    clear(): void {
        this.data.clear()
    }

    getItem(key: string): string | null {
        return this.data.get(key) ?? null
    }

    key(index: number): string | null {
        return [...this.data.keys()][index] ?? null
    }

    removeItem(key: string): void {
        this.data.delete(key)
    }

    setItem(key: string, value: string): void {
        this.data.set(key, String(value))
    }
}

for (const target of [window, globalThis]) {
    if (!('localStorage' in target) || !target.localStorage) {
        Object.defineProperty(target, 'localStorage', {
            value: new MemoryStorage(),
            configurable: true,
            writable: true,
        })
    }
}
