<script setup lang="ts">
import ChildFieldset, { type ChildForm } from '@/components/molecules/ChildFieldset.vue'
import FamilyFieldset from '@/components/molecules/FamilyFieldset.vue'
import GuardianFieldset from '@/components/molecules/GuardianFieldset.vue'
import { useForm } from '@inertiajs/vue3'

/**
 * The parent registration form (plan §6 R1): today's Google Form fields plus the home satellite.
 * Field names match RegisterFamilyInput, so server errors land on the right control.
 */
type Option = { value: string; label: string }

const props = defineProps<{
    action: string
    satellites: { id: number; name: string }[]
    relationships: Option[]
    genders: Option[]
}>()

const blankChild = (): ChildForm => ({
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
    parent_notes: '',
})

const form = useForm({
    home_branch_id: '' as number | '',
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
        ministries: [] as string[],
    },
    children: [blankChild()],
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
        <section class="flex flex-col gap-4">
            <h2 class="text-text text-2xl font-semibold">About you</h2>
            <GuardianFieldset
                v-model="form.guardian"
                :relationships="relationships"
                :errors="errors()"
                with-email
            />
        </section>

        <section class="flex flex-col gap-4">
            <h2 class="text-text text-2xl font-semibold">Your family</h2>
            <FamilyFieldset v-model="form" :satellites="satellites" :errors="errors()" />
        </section>

        <section class="flex flex-col gap-4">
            <h2 class="text-text text-2xl font-semibold">Your children</h2>
            <p v-if="errors()['children']" role="alert" class="text-danger font-semibold">
                {{ errors()['children'] }}
            </p>
            <ChildFieldset
                v-for="(_, index) in form.children"
                :key="index"
                v-model="form.children[index]!"
                :index="index"
                :genders="genders"
                :errors="errors()"
                :removable="form.children.length > 1"
                @remove="removeChild(index)"
            />
            <button
                type="button"
                class="min-h-touch border-primary text-primary self-start rounded-md border px-4 text-lg font-semibold"
                data-testid="add-child"
                @click="addChild"
            >
                + Add another child
            </button>
        </section>

        <label class="text-text flex items-start gap-3 text-lg">
            <input
                v-model="form.privacy_consented"
                type="checkbox"
                class="mt-1 size-6"
                data-testid="privacy"
            />
            <span>I agree that NxtGen may keep these details to care for my children.</span>
        </label>
        <p v-if="errors()['privacy_consented']" role="alert" class="text-danger font-semibold">
            {{ errors()['privacy_consented'] }}
        </p>

        <button
            type="submit"
            :disabled="form.processing"
            class="min-h-touch bg-primary text-on-primary rounded-md px-4 text-lg font-semibold"
            data-testid="register-submit"
        >
            Register my family
        </button>
    </form>
</template>
