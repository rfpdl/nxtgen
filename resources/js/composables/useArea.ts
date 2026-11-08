import type { SharedProps } from '@/types'
import { usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

/** The staff area a page is in (the shared `church_nav.area`): Admin, else Serve. */
function staffArea(): 'admin' | 'serve' {
    return usePage<SharedProps>().props.church_nav?.area === 'admin' ? 'admin' : 'serve'
}

/**
 * A family page's address in the area this page is in (Roy 2026-11-06): the families list, register, a family and
 * their actions are in both Admin and Serve, so a page shown in both never leaves its area. `name` without the area
 * — `families.show`; the server's RouteArea does the same for redirects.
 */
export function areaRoute(name: string, params?: Parameters<typeof route>[1]): string {
    return String(route(`${staffArea()}.${name}`, params))
}

/** Whether this staff page is in Admin — where the admin-only extras (the Excel) are. */
export function inAdminArea(): boolean {
    return staffArea() === 'admin'
}
