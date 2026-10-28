import EmailText from '@/components/atoms/EmailText.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

describe('EmailText', () => {
    it('reads as the address, breaking after the @ or a dot, dash or underscore of the name — never in the domain', () => {
        const wrapper = mount(EmailText, { props: { email: 'maria_2-x.y@e2e.test' } })

        expect(wrapper.text()).toBe('maria_2-x.y@e2e.test')
        expect(wrapper.html()).toContain('maria_<wbr>2-<wbr>x.<wbr>y@<wbr>e2e.test')
    })

    it('copes with no @ (a masked or odd value)', () => {
        expect(mount(EmailText, { props: { email: 'n/a' } }).text()).toBe('n/a')
    })

    it('breaks anywhere only as the last resort, for a piece longer than the line', () => {
        expect(mount(EmailText, { props: { email: 'a@b.c' } }).classes()).toContain('wrap-anywhere')
    })
})
