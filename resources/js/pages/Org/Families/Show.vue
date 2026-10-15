<script setup lang="ts">
import OrgShell from '@/components/layout/OrgShell.vue'
import FamilyChildCard from '@/components/molecules/FamilyChildCard.vue'
import AddChildForm from '@/components/organisms/AddChildForm.vue'
import type { FamilyAdmin } from '@/types'
import { Head, router, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

const props = defineProps<{
    church: { name: string; slug: string }
    family: FamilyAdmin
    genders: { value: string; label: string }[]
}>()

function resend(): void {
    router.post(
        route('org.families.resend', [props.church.slug, props.family.id]),
        {},
        { preserveScroll: true },
    )
}

const emailForm = useForm({ email: '' })

function correctEmail(): void {
    emailForm.put(route('org.families.email', [props.church.slug, props.family.id]), {
        preserveScroll: true,
        onSuccess: () => emailForm.reset(),
    })
}

function setStatus(childId: number, status: string): void {
    router.put(
        route('org.children.status', [props.church.slug, childId]),
        { status },
        { preserveScroll: true },
    )
}
</script>

<template>
    <Head :title="family.primaryName" />
    <OrgShell :church="church">
        <header class="flex flex-col gap-1">
            <h1 class="text-text text-2xl font-semibold">{{ family.primaryName }}</h1>
            <p class="text-text">
                {{ family.email }}
                <span v-if="!family.emailConfirmed" class="text-warning ml-2 font-semibold"
                    >Email not confirmed</span
                >
            </p>
            <p class="text-text-muted">
                {{ family.satellite }}<span v-if="family.area"> · {{ family.area }}</span> ·
                registered by {{ family.registeredBy }}
            </p>
            <button
                v-if="!family.emailConfirmed"
                type="button"
                class="min-h-touch border-primary text-primary self-start rounded-md border px-4 text-lg font-semibold"
                data-testid="resend"
                @click="resend"
            >
                Send the confirmation link again
            </button>
            <form
                v-if="family.canEdit && !family.emailConfirmed"
                class="flex flex-wrap items-end gap-2"
                @submit.prevent="correctEmail"
            >
                <label class="text-text flex flex-col gap-1">
                    Typed the email wrong? Correct it:
                    <input
                        v-model="emailForm.email"
                        type="email"
                        class="min-h-touch border-border bg-surface text-text rounded-md border px-3"
                        data-testid="correct-email"
                    />
                </label>
                <button
                    type="submit"
                    class="min-h-touch border-border bg-surface text-text rounded-md border px-4"
                >
                    Save email
                </button>
                <span
                    v-if="emailForm.errors.email"
                    role="alert"
                    class="text-danger font-semibold"
                    >{{ emailForm.errors.email }}</span
                >
            </form>
        </header>
        <section class="flex flex-col gap-2">
            <h2 class="text-text text-xl font-semibold">Parents and guardians</h2>
            <p
                v-for="g in family.guardians"
                :key="`${g.firstName}-${g.lastName}`"
                class="text-text"
            >
                {{ g.firstName }} {{ g.lastName }} · {{ g.relationship
                }}<span v-if="g.phone"> · {{ g.phone }}</span>
            </p>
        </section>
        <section class="flex flex-col gap-3">
            <h2 class="text-text text-xl font-semibold">Children</h2>
            <div v-for="child in family.children" :key="child.id" class="flex flex-col gap-2">
                <FamilyChildCard :child="child" />
                <label v-if="family.canEdit" class="text-text flex items-center gap-2">
                    Status
                    <select
                        class="min-h-touch border-border bg-surface text-text rounded-md border px-3"
                        :data-testid="`status-${child.id}`"
                        @change="setStatus(child.id, ($event.target as HTMLSelectElement).value)"
                    >
                        <option value="" selected disabled>{{ child.status }}</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="graduated">Graduated</option>
                    </select>
                </label>
            </div>
            <AddChildForm
                :action="route('org.families.children.store', [church.slug, family.id])"
                :genders="genders"
            />
        </section>
    </OrgShell>
</template>
