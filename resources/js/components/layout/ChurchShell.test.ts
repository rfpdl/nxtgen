import ChurchShell from '@/components/layout/ChurchShell.vue'
import { makeSatellite } from '@/tests/satellite'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const page = {
    url: '/dubai/serve/door?class=1',
    props: {
        flash: {},
        church: { name: 'CCF', slug: 'ccf' },
        satellite: makeSatellite(),
        open_satellites: [{ name: 'CCF Dubai', slug: 'dubai' }],
        auth: { user: { id: 1, name: 'Roy' }, can_switch: false },
        church_nav: {
            area: 'serve' as string | null,
            can_use_admin: false,
            can_use_full_admin: false,
            can_manage_people: false,
            can_manage_classes: false,
            can_see_attendance: false,
            can_manage_cards: false,
            can_move_up: false,
            can_manage_links: false,
        },
    },
}

vi.mock('@inertiajs/vue3', () => ({
    Link: { props: ['href', 'method', 'as'], template: '<a :href="href"><slot /></a>' },
    usePage: () => page,
}))

const PATHS: Record<string, string> = {
    'serve.door.show': 'serve/door',
    'serve.families.index': 'serve/families',
    'serve.poster': 'serve/poster',
    'admin.families.index': 'admin/families',
    'admin.children.index': 'admin/children',
    'admin.people.index': 'admin/people',
    'admin.schedules.index': 'admin/classes',
    'admin.card-requests.index': 'admin/card-requests',
    'admin.attendance.show': 'admin/attendance',
    'admin.links.index': 'admin/links',
}

vi.mock('ziggy-js', () => ({
    route: (name: string, slug?: string) =>
        PATHS[name] ? `http://app.test/${slug}/${PATHS[name]}` : `http://app.test/#${name}`,
}))

beforeEach(() => {
    page.url = '/dubai/serve/door?class=1'
    page.props.church_nav.area = 'serve'
    page.props.church_nav.can_use_admin = false
    page.props.church_nav.can_use_full_admin = false
    page.props.church_nav.can_manage_classes = false
    page.props.church_nav.can_see_attendance = false
})

/** The menu as an admin sees it in Admin. */
function inAdmin(): void {
    page.url = '/dubai/admin/families'
    page.props.church_nav.area = 'admin'
    page.props.church_nav.can_use_admin = true
    page.props.church_nav.can_use_full_admin = true
}

const labels = (wrapper: ReturnType<typeof mount>): string[] =>
    wrapper.findAll('[data-testid="menu-row"] > a').map((a) => a.text())

describe('ChurchShell', () => {
    it('in Serve: the door, Families and the QR poster — and no Admin for someone who is not an admin', () => {
        const wrapper = mount(ChurchShell, {
            slots: { default: '<p>body</p>' },
        })

        expect(wrapper.text()).toContain('CCF')
        expect(wrapper.find('[data-testid="nav-door"]').text()).toBe('Check in')
        expect(labels(wrapper)).toEqual(['Check in', 'Families', 'QR poster', 'Password'])
        expect(wrapper.find('[data-testid="nav-families"]').attributes('href')).toBe(
            'http://app.test/dubai/serve/families',
        )
        expect(wrapper.text()).toContain('body')
    })

    it('in Serve: "Admin" for an admin, to their Admin area', () => {
        page.props.church_nav.can_use_admin = true
        page.props.church_nav.can_use_full_admin = true

        expect(mount(ChurchShell).find('[data-testid="nav-admin"]').attributes('href')).toBe(
            'http://app.test/dubai/admin/families',
        )
    })

    it('gives a class admin (TODO #9) only Classes and Attendance in Admin, and "Admin" opens their Classes', () => {
        page.props.church_nav.can_use_admin = true
        page.props.church_nav.can_manage_classes = true
        page.props.church_nav.can_see_attendance = true
        expect(mount(ChurchShell).find('[data-testid="nav-admin"]').attributes('href')).toBe(
            'http://app.test/dubai/admin/classes',
        )

        page.url = '/dubai/admin/classes'
        page.props.church_nav.area = 'admin'
        expect(labels(mount(ChurchShell))).toEqual(['Classes', 'Attendance', 'Serve', 'Password'])
    })

    it('in Admin: Families and Children, the admin pages they may open, then "Serve" — no door or poster', () => {
        inAdmin()
        Object.assign(page.props.church_nav, {
            can_manage_people: true,
            can_manage_classes: true,
            can_see_attendance: true,
            can_manage_cards: true,
            can_manage_links: true,
        })
        const wrapper = mount(ChurchShell)

        expect(labels(wrapper)).toEqual([
            'Families',
            'Children',
            'Classes',
            'Attendance',
            'Cards',
            'Links',
            'People',
            'Serve',
            'Password',
        ])
        expect(wrapper.find('[data-testid="nav-families"]').attributes('href')).toBe(
            'http://app.test/dubai/admin/families',
        )
        expect(wrapper.find('[data-testid="nav-serve"]').attributes('href')).toBe(
            'http://app.test/dubai/serve/door',
        )
        expect(wrapper.find('[data-testid="page-name"]').text()).toBe('Families')
        Object.assign(page.props.church_nav, {
            can_manage_people: false,
            can_manage_classes: false,
            can_see_attendance: false,
            can_manage_cards: false,
            can_manage_links: false,
        })
    })

    it("shows the Admin menu on the People page too — the satellite's Admin, like the rest", () => {
        page.url = '/dubai/admin/people'
        page.props.church_nav.area = 'admin'
        page.props.church_nav.can_use_admin = true
        page.props.church_nav.can_use_full_admin = true
        page.props.church_nav.can_manage_people = true

        const wrapper = mount(ChurchShell)
        expect(labels(wrapper)).toContain('Children')
        expect(wrapper.find('[data-testid="page-name"]').text()).toBe('People')
        page.props.church_nav.can_manage_people = false
    })

    it('names the page in the bar: from the menu item it is under, else the title it is given, else the church', () => {
        expect(mount(ChurchShell).find('[data-testid="page-name"]').text()).toBe('Check in')
        expect(mount(ChurchShell).find('[aria-current="page"]').attributes('data-testid')).toBe(
            'nav-door',
        )

        page.url = '/dubai/serve/families/12'
        expect(mount(ChurchShell).find('[data-testid="page-name"]').text()).toBe('Families')

        expect(
            mount(ChurchShell, { props: { title: 'Register a family' } })
                .find('[data-testid="page-name"]')
                .text(),
        ).toBe('Register a family')

        page.url = '/dubai'
        expect(mount(ChurchShell).find('[data-testid="page-name"]').text()).toBe('CCF')
    })

    it('has no Register or Duplicates tab: both start from the Families list', () => {
        inAdmin()
        page.props.church_nav.can_manage_people = true
        const wrapper = mount(ChurchShell)

        expect(wrapper.text()).not.toContain('Register a family')
        expect(wrapper.text()).not.toContain('Duplicates')
    })

    it('offers the backup password and sign out', () => {
        const wrapper = mount(ChurchShell)

        expect(wrapper.find('[data-testid="backup-password"]').text()).toBe('Password')
        expect(wrapper.find('[data-testid="sign-out"]').exists()).toBe(true)
    })

    it('shows People only to someone who may manage people', () => {
        inAdmin()
        page.props.church_nav.can_manage_people = false
        expect(mount(ChurchShell).text()).not.toContain('People')

        page.props.church_nav.can_manage_people = true
        expect(mount(ChurchShell).text()).toContain('People')
    })

    it('shows Classes only to someone who may manage classes', () => {
        inAdmin()
        page.props.church_nav.can_manage_classes = false
        expect(mount(ChurchShell).text()).not.toContain('Classes')

        page.props.church_nav.can_manage_classes = true
        expect(mount(ChurchShell).text()).toContain('Classes')
    })

    it('shows Attendance only to someone who may see it', () => {
        inAdmin()
        page.props.church_nav.can_see_attendance = false
        expect(mount(ChurchShell).text()).not.toContain('Attendance')

        page.props.church_nav.can_see_attendance = true
        expect(mount(ChurchShell).text()).toContain('Attendance')
    })

    it('shows Cards only to someone who may manage cards — admins', () => {
        inAdmin()
        page.props.church_nav.can_manage_cards = false
        expect(mount(ChurchShell).text()).not.toContain('Cards')

        page.props.church_nav.can_manage_cards = true
        expect(mount(ChurchShell).text()).toContain('Cards')
        page.props.church_nav.can_manage_cards = false
    })

    it('puts the menu behind a Menu button on a phone, as a sheet of big buttons', async () => {
        const wrapper = mount(ChurchShell, { attachTo: document.body })

        await wrapper.find('[data-testid="menu-open"]').trigger('click')
        await new Promise((resolve) => setTimeout(resolve))

        const sheet = document.body.querySelector('[data-testid="menu-sheet"]')
        expect(sheet?.getAttribute('role')).toBe('dialog')
        expect(sheet?.querySelector('[data-testid="nav-door"]')?.textContent).toBe('Check in')
        expect(sheet?.querySelector('[data-testid="backup-password"]')).not.toBeNull()
        expect(sheet?.querySelector('[data-testid="sign-out"]')).not.toBeNull()
        wrapper.unmount()
    })
})
