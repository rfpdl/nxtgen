import LinkForm from '@/components/organisms/LinkForm.vue'
import type { SatelliteLink } from '@/types'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const post = vi.fn()
const put = vi.fn()
let form: Record<string, unknown>

vi.mock('@inertiajs/vue3', async () => {
    const { reactive } = await import('vue')
    return {
        useForm: (data: Record<string, unknown>) => {
            form = reactive({ ...data, errors: {}, processing: false, post, put, reset: vi.fn() })
            return form
        },
    }
})

const whatsapp: SatelliteLink = { id: 7, label: 'WhatsApp', url: 'https://chat.whatsapp.com/abc' }

describe('LinkForm', () => {
    beforeEach(() => {
        post.mockReset()
        put.mockReset()
    })

    it('adds a Link at the satellite in the address — "Facebook group" or "WhatsApp group" a tap away as the label', async () => {
        const wrapper = mount(LinkForm, {
            props: { action: '/dubai/admin/links', submitLabel: 'Add link' },
        })

        const starters = wrapper.findAll('[data-testid="link-starter"]')
        expect(starters.map((b) => b.text())).toEqual(['Facebook group', 'WhatsApp group'])
        await starters[1]?.trigger('click')
        await wrapper.find('[data-testid="link-url"]').setValue('https://chat.whatsapp.com/abc')
        await wrapper.find('form').trigger('submit')

        expect(form).not.toHaveProperty('satellite_id')
        expect(form).toMatchObject({
            label: 'WhatsApp group',
            url: 'https://chat.whatsapp.com/abc',
        })
        expect(post).toHaveBeenCalledWith('/dubai/admin/links', expect.anything())
    })

    it('changes a saved Link in place — its label and address filled in, no starters', async () => {
        const wrapper = mount(LinkForm, {
            props: { action: '/dubai/admin/links/7', link: whatsapp, submitLabel: 'Save' },
        })

        expect(wrapper.find('[data-testid="link-starter"]').exists()).toBe(false)
        expect((wrapper.find('[data-testid="link-url"]').element as HTMLInputElement).value).toBe(
            'https://chat.whatsapp.com/abc',
        )
        await wrapper.find('form').trigger('submit')

        expect(form).not.toHaveProperty('satellite_id')
        expect(put).toHaveBeenCalledWith('/dubai/admin/links/7', expect.anything())
    })

    it("shows the server's words on each field, and says it is done when cancelled", async () => {
        const wrapper = mount(LinkForm, {
            props: { action: '/dubai/admin/links', submitLabel: 'Add link' },
        })
        form.errors = { url: 'Please paste the whole link, starting with https://' }
        await wrapper.vm.$nextTick()

        expect(wrapper.find('[role="alert"]').text()).toBe(
            'Please paste the whole link, starting with https://',
        )
        await wrapper.find('[data-testid="link-cancel"]').trigger('click')
        expect(wrapper.emitted('done')).toHaveLength(1)
    })
})
