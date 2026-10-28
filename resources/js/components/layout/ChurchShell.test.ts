import ChurchShell from '@/components/layout/ChurchShell.vue'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const page = {
    url: '/ccf/door?class=1',
    props: {
        flash: {},
        church: { name: 'CCF', slug: 'ccf' },
        auth: { user: null, can_switch: false },
        church_nav: {
            can_manage_people: false,
            can_manage_classes: false,
            can_see_attendance: false,
        },
    },
}

vi.mock('@inertiajs/vue3', () => ({
    Link: { props: ['href', 'method', 'as'], template: '<a :href="href"><slot /></a>' },
    usePage: () => page,
}))

const PATHS: Record<string, string> = {
    'church.door.show': 'door',
    'church.families.index': 'families',
    'church.children.index': 'children',
    'church.poster': 'poster',
    'church.people.index': 'people',
    'church.schedules.index': 'classes',
    'church.attendance.show': 'attendance',
}

vi.mock('ziggy-js', () => ({
    route: (name: string, slug?: string) =>
        PATHS[name] ? `http://app.test/${slug}/${PATHS[name]}` : `http://app.test/#${name}`,
}))

beforeEach(() => {
    page.url = '/ccf/door?class=1'
})

describe('ChurchShell', () => {
    it('names the church it shares, and links to the door and the registration lists', () => {
        const wrapper = mount(ChurchShell, {
            slots: { default: '<p>body</p>' },
        })

        expect(wrapper.text()).toContain('CCF')
        expect(wrapper.find('[data-testid="nav-door"]').text()).toBe('Check in')
        expect(wrapper.text()).toContain('Families')
        expect(wrapper.text()).toContain('Children')
        expect(wrapper.text()).toContain('body')
    })

    it('names the page in the bar: from the menu item it is under, else the title it is given, else the church', () => {
        expect(mount(ChurchShell).find('[data-testid="page-name"]').text()).toBe('Check in')
        expect(mount(ChurchShell).find('[aria-current="page"]').attributes('data-testid')).toBe(
            'nav-door',
        )

        page.url = '/ccf/families/12'
        expect(mount(ChurchShell).find('[data-testid="page-name"]').text()).toBe('Families')

        expect(
            mount(ChurchShell, { props: { title: 'Register a family' } })
                .find('[data-testid="page-name"]')
                .text(),
        ).toBe('Register a family')

        page.url = '/ccf'
        expect(mount(ChurchShell).find('[data-testid="page-name"]').text()).toBe('CCF')
    })

    it('has no Register or Duplicates tab: both start from the Families list', () => {
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
        page.props.church_nav.can_manage_people = false
        expect(mount(ChurchShell).text()).not.toContain('People')

        page.props.church_nav.can_manage_people = true
        expect(mount(ChurchShell).text()).toContain('People')
    })

    it('shows Classes only to someone who may manage classes', () => {
        page.props.church_nav.can_manage_classes = false
        expect(mount(ChurchShell).text()).not.toContain('Classes')

        page.props.church_nav.can_manage_classes = true
        expect(mount(ChurchShell).text()).toContain('Classes')
    })

    it('shows Attendance only to someone who may see it', () => {
        page.props.church_nav.can_see_attendance = false
        expect(mount(ChurchShell).text()).not.toContain('Attendance')

        page.props.church_nav.can_see_attendance = true
        expect(mount(ChurchShell).text()).toContain('Attendance')
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
