import ChildPhotoForm from '@/components/organisms/ChildPhotoForm.vue'
import type { ChildPortal } from '@/types'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const post = vi.fn()
const del = vi.fn()
const put = vi.fn()
let form: Record<string, unknown>

// The conversion has its own tests (photoForUpload.test.ts); here the file passes straight through.
vi.mock('@/lib/photoForUpload', () => ({ photoForUpload: async (f: File) => f }))

vi.mock('@inertiajs/vue3', async () => {
    const { reactive } = await import('vue')
    return {
        useForm: (data: Record<string, unknown>) => {
            form = reactive({ ...data, errors: {}, processing: false, post, reset: vi.fn() })
            return form
        },
        router: {
            delete: (...args: unknown[]) => del(...args),
            put: (...args: unknown[]) => put(...args),
        },
    }
})

const child = (over: Partial<ChildPortal> = {}): ChildPortal => ({
    id: 4,
    firstName: 'Alexis',
    middleName: null,
    lastName: 'Ramos',
    nickname: 'Amara',
    gender: 'girl',
    dateOfBirth: '2019-05-10',
    allergies: null,
    illnesses: null,
    dislikes: null,
    fears: null,
    parentNotes: null,
    group: null,
    status: 'Active',
    photoConsent: false,
    photoUrl: null,
    ...over,
})

const photo = new File(['x'], 'IMG_0001.jpg', { type: 'image/jpeg' })

async function pick(wrapper: ReturnType<typeof mount>, testid: string): Promise<void> {
    const input = wrapper.find(`[data-testid="${testid}"]`)
    Object.defineProperty(input.element, 'files', { value: [photo] })
    await input.trigger('change')
    await new Promise((r) => setTimeout(r, 0))
}

describe('ChildPhotoForm', () => {
    beforeEach(() => {
        post.mockReset()
        del.mockReset()
        put.mockReset()
    })

    it('asks a parent to agree first — saved on its own, so a volunteer can take the first photo', async () => {
        const wrapper = mount(ChildPhotoForm, {
            props: {
                child: child(),
                who: 'parent',
                action: '/p',
                consentAction: '/c',
                withdrawAction: '/w',
            },
        })
        expect(wrapper.find('[data-testid="take-photo"]').exists()).toBe(false)

        await wrapper.find('[data-testid="give-photo-consent"]').trigger('click')

        expect(put).toHaveBeenCalledWith('/c', {}, expect.anything())
    })

    it('lets a parent who agreed take or choose a photo', async () => {
        const wrapper = mount(ChildPhotoForm, {
            props: {
                child: child({ photoConsent: true }),
                who: 'parent',
                action: '/p',
                consentAction: '/c',
                withdrawAction: '/w',
            },
        })
        expect(wrapper.find('[data-testid="take-photo"]').attributes('capture')).toBe('environment')
        expect(wrapper.find('[data-testid="choose-photo"]').attributes('capture')).toBeUndefined()

        await pick(wrapper, 'choose-photo')

        expect(form).toMatchObject({ photo })
        expect(post).toHaveBeenCalledWith('/p', expect.objectContaining({ forceFormData: true }))
    })

    it('lets a parent who agreed take it back', async () => {
        const wrapper = mount(ChildPhotoForm, {
            props: {
                child: child({ photoConsent: true, photoUrl: '/x' }),
                who: 'parent',
                action: '/p',
                withdrawAction: '/w',
            },
        })

        await wrapper.find('[data-testid="withdraw-photo-consent"]').trigger('click')

        expect(del).toHaveBeenCalledWith('/w', expect.anything())
    })

    it('tells staff to ask the parent when there is no consent, and never offers to agree', () => {
        const wrapper = mount(ChildPhotoForm, {
            props: { child: child(), who: 'staff', action: '/s' },
        })

        expect(wrapper.text()).toContain('The parent has not agreed to a photo yet')
        expect(wrapper.find('[data-testid="give-photo-consent"]').exists()).toBe(false)
        expect(wrapper.find('[data-testid="take-photo"]').exists()).toBe(false)
    })

    it('lets staff take one once the parent agreed', async () => {
        const wrapper = mount(ChildPhotoForm, {
            props: { child: child({ photoConsent: true }), who: 'staff', action: '/s' },
        })

        await pick(wrapper, 'take-photo')

        expect(post).toHaveBeenCalledWith('/s', expect.anything())
        expect(wrapper.find('[data-testid="withdraw-photo-consent"]').exists()).toBe(false)
    })
})
