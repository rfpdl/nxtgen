<script setup lang="ts">
import ResendLinkButton from '@/components/atoms/ResendLinkButton.vue'
import ChurchShell from '@/components/layout/ChurchShell.vue'
import ChildAdminControls from '@/components/molecules/ChildAdminControls.vue'
import FamilyChildCard from '@/components/molecules/FamilyChildCard.vue'
import AddChildForm from '@/components/organisms/AddChildForm.vue'
import ChildPhotoForm from '@/components/organisms/ChildPhotoForm.vue'
import EditChildForm from '@/components/organisms/EditChildForm.vue'
import FamilyDetailsForm from '@/components/organisms/FamilyDetailsForm.vue'
import { useChurch } from '@/composables/useChurch'
import type {
    ChildStatus,
    CorrectFamilyEmailInput,
    FamilyAdmin,
    FamilyDetails,
    GuardianEdit,
    LevelOption,
    Note,
    SatelliteOption,
    UpdateChildLevelInput,
    UpdateChildStatusInput,
} from '@/types'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { ref } from 'vue'
import { route } from 'ziggy-js'

const props = defineProps<{
    family: FamilyAdmin
    /** What staff changed on this family and its children (system notes), newest first. Staff only. */
    notes: Note[]
    /** Only for someone who may edit this family (null otherwise). */
    details: FamilyDetails | null
    primary: GuardianEdit | null
    satellites: SatelliteOption[]
    /** Church admins only (plan §3.2): correct a child's Level outside Moving-Up. */
    can_set_level: boolean
    levels: LevelOption[]
}>()

const church = useChurch()

const emailForm = useForm<CorrectFamilyEmailInput>({ email: '' })

function correctEmail(): void {
    emailForm.put(route('church.families.email', [church.value.slug, props.family.id]), {
        preserveScroll: true,
        onSuccess: () => emailForm.reset(),
    })
}

/** A refused Level change comes back as a validation error on the page. */
const page = usePage<{ errors: Record<string, string | undefined> }>()

const lastLevelChild = ref<number | null>(null)

function setLevel(childId: number, level: number): void {
    lastLevelChild.value = childId
    const body: UpdateChildLevelInput = { level }
    router.put(route('church.children.level', [church.value.slug, childId]), body, {
        preserveScroll: true,
    })
}

function setStatus(childId: number, status: ChildStatus): void {
    const body: UpdateChildStatusInput = { status }
    router.put(route('church.children.status', [church.value.slug, childId]), body, {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head :title="family.primary_name" />
    <ChurchShell>
        <header class="flex flex-col gap-1">
            <h1 class="text-text text-2xl font-semibold">{{ family.primary_name }}</h1>
            <p class="text-text">
                {{ family.email }}
                <span v-if="!family.email_confirmed" class="text-warning ml-2 font-semibold"
                    >Email not confirmed</span
                >
            </p>
            <p class="text-text-muted">
                {{ family.satellite }}<span v-if="family.area"> · {{ family.area }}</span> ·
                registered by {{ family.registered_by }}
            </p>
            <ResendLinkButton v-if="!family.email_confirmed" :family-id="family.id" />
            <form
                v-if="family.can_edit && !family.email_confirmed"
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
                :key="`${g.first_name}-${g.last_name}`"
                class="text-text"
            >
                {{ g.first_name }} {{ g.last_name }} · {{ g.relationship_label
                }}<span v-if="g.phone"> · {{ g.phone }}</span>
            </p>
            <FamilyDetailsForm
                v-if="details && primary"
                :action="route('church.families.update', [church.slug, family.id])"
                :details="details"
                :guardian="primary"
                :satellites="satellites"
            />
        </section>
        <section class="flex flex-col gap-3">
            <h2 class="text-text text-xl font-semibold">Children</h2>
            <div v-for="child in family.children" :key="child.id" class="flex flex-col gap-2">
                <FamilyChildCard :child="child">
                    <ChildPhotoForm
                        :child="child"
                        who="staff"
                        :action="route('church.children.photo', [church.slug, child.id])"
                        :consent-action="
                            route('church.children.photo-consent', [church.slug, child.id])
                        "
                    />
                    <EditChildForm
                        v-if="family.can_edit"
                        :action="route('church.children.update', [church.slug, child.id])"
                        :child="child"
                    />
                </FamilyChildCard>
                <ChildAdminControls
                    :child="child"
                    :can-set-status="family.can_edit"
                    :can-set-level="can_set_level"
                    :levels="levels"
                    :level-error="lastLevelChild === child.id ? page.props.errors.level : undefined"
                    @status="setStatus"
                    @level="setLevel"
                />
            </div>
            <AddChildForm
                :action="route('church.families.children.store', [church.slug, family.id])"
            />
        </section>
        <section v-if="notes.length > 0" class="flex flex-col gap-2" data-testid="history">
            <h2 class="text-text text-xl font-semibold">History</h2>
            <ul class="flex flex-col gap-1">
                <li v-for="note in notes" :key="note.id" class="text-text">
                    {{ note.body }}
                    <span class="text-text-muted"> — {{ note.author }}, {{ note.at }}</span>
                </li>
            </ul>
        </section>
    </ChurchShell>
</template>
