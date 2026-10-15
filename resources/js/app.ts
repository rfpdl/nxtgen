import '../css/app.css'

import { createInertiaApp } from '@inertiajs/vue3'
import { createPinia } from 'pinia'
import type { DefineComponent } from 'vue'
import { createApp, h } from 'vue'
import { ZiggyVue } from 'ziggy-js'

const appName = import.meta.env.VITE_APP_NAME ?? 'NxtGen'

void createInertiaApp({
    title: (title) => (title ? `${title} · ${appName}` : appName),

    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),

    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(createPinia())
            .use(ZiggyVue)
            .mount(el)
    },

    // The door is the reason this is here. Inertia's default progress bar appears after 250ms, which
    // on the venue's wifi means a teacher taps, sees nothing, and taps again. It shows immediately,
    // and in the brand colour so it reads as the app working rather than the browser struggling.
    progress: {
        delay: 0,
        color: 'oklch(0.48 0.11 226)',
    },
})

/**
 * Resolve an Inertia page, failing loudly on a name that does not exist.
 *
 * Inertia's own helper throws a message naming only the glob, which during a fast build-out is a
 * five-minute hunt. This names the page and lists what is available.
 */
function resolvePageComponent(
    path: string,
    pages: Record<string, () => Promise<DefineComponent>>,
): Promise<DefineComponent> {
    const page = pages[path]

    if (!page) {
        throw new Error(
            `Inertia page not found: ${path}\nAvailable:\n  ${Object.keys(pages).sort().join('\n  ')}`,
        )
    }

    return page()
}
