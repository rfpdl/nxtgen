<script setup lang="ts">
import Card from '@/components/atoms/Card.vue'
import EmailText from '@/components/atoms/EmailText.vue'
import PageHeader from '@/components/atoms/PageHeader.vue'
import Pill from '@/components/atoms/Pill.vue'
import ResendLinkButton from '@/components/atoms/ResendLinkButton.vue'
import RowList from '@/components/atoms/RowList.vue'
import SectionHeading from '@/components/atoms/SectionHeading.vue'
import GuardianList from '@/components/molecules/GuardianList.vue'
import ChurchShell from '@/components/layout/ChurchShell.vue'
import ChildAdminControls from '@/components/molecules/ChildAdminControls.vue'
import ChildAttendanceList from '@/components/molecules/ChildAttendanceList.vue'
import FamilyChildCard from '@/components/molecules/FamilyChildCard.vue'
import PossibleDuplicateCard from '@/components/organisms/PossibleDuplicateCard.vue'
import AddChildForm from '@/components/organisms/AddChildForm.vue'
import ChildCardRequest from '@/components/organisms/ChildCardRequest.vue'
import ChildPhotoForm from '@/components/organisms/ChildPhotoForm.vue'
import CorrectEmailForm from '@/components/organisms/CorrectEmailForm.vue'
import EditChildForm from '@/components/organisms/EditChildForm.vue'
import FamilyDetailsForm from '@/components/organisms/FamilyDetailsForm.vue'
import { useChurch } from '@/composables/useChurch'
import type {
    CardRequest,
    ChildAttendance,
    ChildCheckIn,
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
import { areaRoute } from '@/composables/useArea'

const props = defineProps<{
    family: FamilyAdmin
    /** Each child's attendance, newest first (slice 3). Staff only. */
    attendance: ChildAttendance[]
    /** Each child's open Card request (name cards), at most one per child. Staff only. */
    card_requests: CardRequest[]
    /** What staff changed on this family and its children (system notes), newest first. Staff only. */
    notes: Note[]
    /** Open possible duplicates this family is on, for an admin who may answer them (empty for everyone else). */
    duplicates: DuplicatePair[]
    /** Only for someone who may edit this family (null otherwise). */
    details: FamilyDetails | null
    primary: GuardianEdit | null
    other_guardians: GuardianEdit[]
    satellites: SatelliteOption[]
    /** Correct the email, set a child's status, delete a photo for good: an editor, in the Admin area only (Roy 2026-11-06). */
    admin_controls: boolean
    /** Church admins only (plan §3.2), in the Admin area: correct a child's Level outside Moving-Up. */
    can_set_level: boolean
    levels: LevelOption[]
}>()

const church = useChurch()

/** A child's Check-ins, newest first ([] = not checked in yet). */
function checkInsOf(childId: number): ChildCheckIn[] {
    return props.attendance.find((a) => a.child_id === childId)?.check_ins ?? []
}

/** A child's open Card request, or null (then "New card…"). */
function cardRequestOf(childId: number): CardRequest | null {
    return props.card_requests.find((r) => r.child_id === childId) ?? null
}

/** "CCF DXB · Al Barsha · registered by Lifeshaper" under the name. */
const sub = computed(() =>
    [props.family.satellite, props.family.area, `registered by ${props.family.registered_by}`]
        .filter(Boolean)
        .join(' · '),
)

/** A refused Level change comes back as a validation error on the page. */
const page = usePage<{ errors: Record<string, string | undefined> }>()

const lastLevelChild = ref<number | null>(null)

/** One of Admin's own changes to this family (Level, status, a duplicate), keeping the scroll. */
function adminPut(name: string, id: number, body: Record<string, string | number>): void {
    router.put(route(`nxtgen.admin.${name}`, [church.value.slug, id]), body, {
        preserveScroll: true,
    })
}

function setLevel(childId: number, level: number): void {
    lastLevelChild.value = childId
    adminPut('children.level', childId, { level } satisfies UpdateChildLevelInput)
}

function answerDuplicate(pairId: number, status: Exclude<DuplicateStatus, 'open'>): void {
    adminPut('duplicates.resolve', pairId, { status } satisfies ResolveDuplicateInput)
}

function setStatus(childId: number, status: ChildStatus): void {
    adminPut('children.status', childId, { status } satisfies UpdateChildStatusInput)
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
                v-if="admin_controls && !family.email_confirmed"
                :action="route('nxtgen.admin.families.email', [church.slug, family.id])"
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
            <GuardianList :guardians="family.guardians" />
            <FamilyDetailsForm
                v-if="details && primary"
                :action="areaRoute('families.update', [church.slug, family.id])"
                :details="details"
                :guardian="primary"
                :others="other_guardians"
                :satellites="satellites"
            />
        </section>
        <section class="flex flex-col gap-3">
            <SectionHeading>Children</SectionHeading>
            <FamilyChildCard v-for="child in family.children" :key="child.id" :child="child">
                <ChildAttendanceList :check-ins="checkInsOf(child.id)" />
                <ChildCardRequest
                    :request="cardRequestOf(child.id)"
                    :action="areaRoute('card-requests.store', [church.slug, child.id])"
                />
                <ChildAdminControls
                    :child="child"
                    :can-set-status="admin_controls"
                    :can-set-level="can_set_level"
                    :levels="levels"
                    :level-error="lastLevelChild === child.id ? page.props.errors.level : undefined"
                    @status="setStatus"
                    @level="setLevel"
                />
                <ChildPhotoForm
                    :child="child"
                    who="staff"
                    :action="areaRoute('children.photo', [church.slug, child.id])"
                    :consent-action="areaRoute('children.photo-consent', [church.slug, child.id])"
                    :delete-action="
                        admin_controls
                            ? route('nxtgen.admin.children.photo.destroy', [church.slug, child.id])
                            : undefined
                    "
                />
                <EditChildForm
                    v-if="family.can_edit"
                    :action="areaRoute('children.update', [church.slug, child.id])"
                    :child="child"
                />
            </FamilyChildCard>
            <AddChildForm
                :action="areaRoute('families.children.store', [church.slug, family.id])"
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
