<script setup lang="ts">
import FormField from '@/components/atoms/FormField.vue'
import ChildFieldset, { type ChildForm } from '@/components/molecules/ChildFieldset.vue'
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
    wants_dgroup: false,
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

const input = 'min-h-touch rounded-md border border-border bg-surface px-4 text-lg text-text'
</script>

<template>
    <form class="flex flex-col gap-6" novalidate @submit.prevent="submit">
        <section class="flex flex-col gap-4">
            <h2 class="text-text text-2xl font-semibold">About you</h2>
            <FormField label="Your first name" required :error="errors()['guardian.first_name']">
                <input
                    v-model="form.guardian.first_name"
                    :class="input"
                    autocomplete="given-name"
                    data-testid="guardian-first-name"
                />
            </FormField>
            <FormField label="Your last name" required :error="errors()['guardian.last_name']">
                <input
                    v-model="form.guardian.last_name"
                    :class="input"
                    autocomplete="family-name"
                    data-testid="guardian-last-name"
                />
            </FormField>
            <FormField
                label="You are the child's"
                required
                :error="errors()['guardian.relationship']"
            >
                <select
                    v-model="form.guardian.relationship"
                    :class="input"
                    data-testid="guardian-relationship"
                >
                    <option v-for="r in relationships" :key="r.value" :value="r.value">
                        {{ r.label }}
                    </option>
                </select>
            </FormField>
            <FormField label="Mobile number" required :error="errors()['guardian.phone']">
                <input
                    v-model="form.guardian.phone"
                    type="tel"
                    :class="input"
                    autocomplete="tel"
                    data-testid="guardian-phone"
                />
            </FormField>
            <FormField
                label="Email"
                required
                hint="We send a link here so you can sign in. No password needed."
                :error="errors()['guardian.email']"
            >
                <input
                    v-model="form.guardian.email"
                    type="email"
                    :class="input"
                    autocomplete="email"
                    data-testid="guardian-email"
                />
            </FormField>
        </section>

        <section class="flex flex-col gap-4">
            <h2 class="text-text text-2xl font-semibold">Your family</h2>
            <FormField
                label="Satellite you usually attend"
                required
                :error="errors()['home_branch_id']"
            >
                <select v-model="form.home_branch_id" :class="input" data-testid="home-branch">
                    <option value="">Choose…</option>
                    <option v-for="s in satellites" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
            </FormField>
            <FormField
                label="Area where you live"
                hint="For example: Al Barsha"
                :error="errors()['area_text']"
            >
                <input v-model="form.area_text" :class="input" data-testid="area" />
            </FormField>
            <FormField
                label="Facebook account"
                hint="To add you to the parents' group"
                :error="errors()['fb_account']"
            >
                <input v-model="form.fb_account" :class="input" />
            </FormField>
            <label class="min-h-touch text-text flex items-center gap-3 text-lg"
                ><input v-model="form.wants_fb_group" type="checkbox" class="size-6" /> Add me to
                the parents' FB group / chat</label
            >
            <label class="min-h-touch text-text flex items-center gap-3 text-lg"
                ><input v-model="form.in_dgroup" type="checkbox" class="size-6" /> I am in a
                DGroup</label
            >
            <FormField
                v-if="form.in_dgroup"
                label="DGroup leader's name"
                :error="errors()['dgroup_leader_name']"
            >
                <input v-model="form.dgroup_leader_name" :class="input" />
            </FormField>
            <label v-else class="min-h-touch text-text flex items-center gap-3 text-lg"
                ><input v-model="form.wants_dgroup" type="checkbox" class="size-6" /> I would like
                to join a DGroup</label
            >
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
