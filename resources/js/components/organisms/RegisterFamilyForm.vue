<script setup lang="ts">
import AppButton from '@/components/atoms/AppButton.vue'
import Card from '@/components/atoms/Card.vue'
import CheckRow from '@/components/atoms/CheckRow.vue'
import SectionHeading from '@/components/atoms/SectionHeading.vue'
import ChildFieldset from '@/components/molecules/ChildFieldset.vue'
import FamilyFieldset from '@/components/molecules/FamilyFieldset.vue'
import GuardianFieldset from '@/components/molecules/GuardianFieldset.vue'
import OtherGuardiansFieldset from '@/components/organisms/OtherGuardiansFieldset.vue'
import type { ChildDraft, Draft, RegisterFamilyInput, SatelliteOption } from '@/types'
import { useForm } from '@inertiajs/vue3'

/**
 * The parent registration form (plan §6 R1): today's Google Form fields plus the home satellite, and the family's
 * other parents and guardians (no email: only the parent registering signs in).
 * The form is a draft of RegisterFamilyInput (no `notes`: registration never asks), so server errors land on
 * the right control. On the kit (the Bold look): each part is a Card; "Register my family" is the one primary button.
 */
const props = defineProps<{
    action: string
    satellites: SatelliteOption[]
}>()

const blankChild = (): ChildDraft => ({
    first_name: '',
    middle_name: '',
    last_name: '',
    nickname: '',
    gender: '',
    date_of_birth: '',
    has_allergies: null,
    allergies: '',
    illnesses: '',
    dislikes: '',
    fears: '',
    guardian_notes: '',
})

const form = useForm<Omit<Draft<RegisterFamilyInput>, 'notes'>>({
    home_satellite_id: '',
    area_text: '',
    privacy_consented: false,
    fb_account: '',
    wants_fb_group: false,
    in_dgroup: false,
    dgroup_area_text: '',
    dgroup_leader_name: '',
    dgroup_leader_phone: '',
    wants_dgroup: false,
    lifestage: '',
    guardian: {
        first_name: '',
        last_name: '',
        relationship: 'mother',
        phone: '',
        email: '',
        ministries: [],
    },
    children: [blankChild()],
    guardians: [],
})

const errors = (): Record<string, string | undefined> =>
    form.errors as Record<string, string | undefined>

function addChild(): void {
    form.children.push(blankChild())
}

function removeChild(index: number): void {
    form.children.splice(index, 1)
}

function submit(): void {
    form.post(props.action, { preserveScroll: true })
}
</script>

<template>
    <form class="flex flex-col gap-6" novalidate @submit.prevent="submit">
        <Card as="section" class="flex flex-col gap-4">
            <SectionHeading>About you</SectionHeading>
            <GuardianFieldset v-model="form.guardian" :errors="errors()" with-email />
        </Card>

        <section class="flex flex-col gap-4">
            <SectionHeading>Other parents and guardians</SectionHeading>
            <p class="text-text-muted text-lg">
                Optional — a father, a grandparent… They do not need to sign in.
            </p>
            <OtherGuardiansFieldset v-model="form.guardians" :errors="errors()" />
        </section>

        <Card as="section" class="flex flex-col gap-4">
            <SectionHeading>Your family</SectionHeading>
            <FamilyFieldset v-model="form" :satellites="satellites" :errors="errors()" />
        </Card>

        <section class="flex flex-col gap-4">
            <SectionHeading>Your children</SectionHeading>
            <p v-if="errors()['children']" role="alert" class="text-danger font-semibold">
                {{ errors()['children'] }}
            </p>
            <Card v-for="(_, index) in form.children" :key="index">
                <ChildFieldset
                    v-model="form.children[index]!"
                    :index="index"
                    :errors="errors()"
                    :removable="form.children.length > 1"
                    @remove="removeChild(index)"
                />
            </Card>
            <AppButton
                variant="secondary"
                class="self-start"
                data-testid="add-child"
                @click="addChild"
            >
                + Add another child
            </AppButton>
        </section>

        <div class="flex flex-col gap-1.5">
            <CheckRow
                :model-value="form.privacy_consented === true"
                class="text-lg"
                data-testid="privacy"
                @update:model-value="form.privacy_consented = $event"
                >I agree that NxtGen may keep these details to care for my children.</CheckRow
            >
            <p v-if="errors()['privacy_consented']" role="alert" class="text-danger font-semibold">
                {{ errors()['privacy_consented'] }}
            </p>
        </div>

        <AppButton
            type="submit"
            size="lg"
            block
            :loading="form.processing"
            data-testid="register-submit"
        >
            Register my family
        </AppButton>
    </form>
</template>
