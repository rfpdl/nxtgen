import GuardianList from '@/components/molecules/GuardianList.vue'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

describe('GuardianList', () => {
    it('lists each parent or guardian with their relationship, and the phone only when there is one', () => {
        const wrapper = mount(GuardianList, {
            props: {
                guardians: [
                    {
                        first_name: 'Mia',
                        last_name: 'Cruz',
                        relationship_label: 'Mother',
                        phone: '0559990000',
                    },
                    {
                        first_name: 'Nena',
                        last_name: 'Cruz',
                        relationship_label: 'Grandparent',
                        phone: null,
                    },
                ],
            },
        })

        expect(wrapper.findAll('[data-testid="guardian"]').map((g) => g.text())).toEqual([
            'Mia Cruz · Mother · 0559990000',
            'Nena Cruz · Grandparent',
        ])
        expect(wrapper.find('[data-testid="guardian"] .font-bold').text()).toBe('Mia Cruz')
    })
})
