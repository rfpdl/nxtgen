<script setup lang="ts">
import Card from '@/components/atoms/Card.vue'
import EmailText from '@/components/atoms/EmailText.vue'
import PageHeader from '@/components/atoms/PageHeader.vue'
import Pill from '@/components/atoms/Pill.vue'
import ResendLinkButton from '@/components/atoms/ResendLinkButton.vue'
import RowList from '@/components/atoms/RowList.vue'
import SectionHeading from '@/components/atoms/SectionHeading.vue'
import ChurchShell from '@/components/layout/ChurchShell.vue'
import ChildAdminControls from '@/components/molecules/ChildAdminControls.vue'
import FamilyChildCard from '@/components/molecules/FamilyChildCard.vue'
import PossibleDuplicateCard from '@/components/organisms/PossibleDuplicateCard.vue'
import AddChildForm from '@/components/organisms/AddChildForm.vue'
import ChildPhotoForm from '@/components/organisms/ChildPhotoForm.vue'
import CorrectEmailForm from '@/components/organisms/CorrectEmailForm.vue'
import EditChildForm from '@/components/organisms/EditChildForm.vue'
import FamilyDetailsForm from '@/components/organisms/FamilyDetailsForm.vue'
import { useChurch } from '@/composables/useChurch'
import type {
    ChildStatus,
    DuplicatePair,
    DuplicateStatus,
    FamilyAdmin,
    FamilyDetails,
    GuardianEdit,
    LevelOption,
    Note,
    ResolveDuplicateInput,
    SatelliteOption,
    UpdateChildLevelInput,
    UpdateChildStatusInput,
} from '@/types'
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { route } from 'ziggy-js'

const props = defineProps<{
    family: FamilyAdmin
    /** What staff changed on this family and its children (system notes), newest first. Staff only. */
    notes: Note[]
    /** Open possible duplicates this family is on, for an admin who may answer them (empty for everyone else). */
    duplicates: DuplicatePair[]
    /** Only for someone who may edit this family (null otherwise). */
    details: FamilyDetails | null
    primary: GuardianEdit | null
    other_guardians: GuardianEdit[]
    satellites: SatelliteOption[]
    /** Church admins only (plan §3.2): correct a child's Level outside Moving-Up. */
    can_set_level: boolean
    levels: LevelOption[]
}>()

const church = useChurch()

/** "CCF DXB · Al Barsha · registered by Lifeshaper" under the name. */
const sub = computed(() =>
    [props.family.satellite, props.family.area, `registered by ${props.family.registered_by}`]
        .filter(Boolean)
        .join(' · '),
)

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

function answerDuplicate(pairId: number, status: Exclude<DuplicateStatus, 'open'>): void {
    const body: ResolveDuplicateInput = { status }
    router.put(route('church.duplicates.resolve', [church.value.slug, pairId]), body, {
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
        <PageHeader :title="family.primary_name" :sub="sub" />
        <Card class="flex flex-col gap-3">
            <p class="text-text flex flex-wrap items-center gap-2 text-lg">
                <EmailText :email="family.email" />
                <Pill v-if="!family.email_confirmed" variant="warning">Email not confirmed</Pill>
            </p>
            <ResendLinkButton v-if="!family.email_confirmed" :family-id="family.id" />
            <CorrectEmailForm
                v-if="family.can_edit && !family.email_confirmed"
                :action="route('church.families.email', [church.slug, family.id])"
            />
        </Card>
        <section v-if="duplicates.length > 0" class="flex flex-col gap-3">
            <SectionHeading>Possible duplicates</SectionHeading>
            <PossibleDuplicateCard
                v-for="pair in duplicates"
                :key="pair.id"
                :pair="pair"
                @answer="answerDuplicate"
            />
        </section>
        <section class="flex flex-col gap-3">
            <SectionHeading>Parents and guardians</SectionHeading>
            <RowList :items="family.guardians" :item-key="(g) => `${g.first_name}-${g.last_name}`">
                <template #default="{ item: g }">
                    <p class="text-text text-lg">
                        {{ g.first_name }} {{ g.last_name }} · {{ g.relationship_label
                        }}<span v-if="g.phone"> · {{ g.phone }}</span>
                    </p>
                </template>
            </RowList>
            <FamilyDetailsForm
                v-if="details && primary"
                :action="route('church.families.update', [church.slug, family.id])"
                :details="details"
                :guardian="primary"
                :others="other_guardians"
                :satellites="satellites"
            />
        </section>
        <section class="flex flex-col gap-3">
            <SectionHeading>Children</SectionHeading>
            <FamilyChildCard v-for="child in family.children" :key="child.id" :child="child">
                <ChildAdminControls
                    :child="child"
                    :can-set-status="family.can_edit"
                    :can-set-level="can_set_level"
                    :levels="levels"
                    :level-error="lastLevelChild === child.id ? page.props.errors.level : undefined"
                    @status="setStatus"
                    @level="setLevel"
                />
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
            <AddChildForm
                :action="route('church.families.children.store', [church.slug, family.id])"
            />
        </section>
        <section v-if="notes.length > 0" class="flex flex-col gap-3" data-testid="history">
            <SectionHeading>History</SectionHeading>
            <RowList :items="notes" :item-key="(note) => note.id">
                <template #default="{ item: note }">
                    <p class="text-text">
                        {{ note.body }}
                        <span class="text-text-muted"> — {{ note.author }}, {{ note.at }}</span>
                    </p>
                </template>
            </RowList>
        </section>
    </ChurchShell>
</template>
