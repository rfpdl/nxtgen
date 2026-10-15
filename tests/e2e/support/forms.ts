import type { Page } from '@playwright/test'

export type Child = {
    first: string
    last: string
    nickname: string
    gender: 'boy' | 'girl'
    dob: string
    allergies?: string
}

/** One child's fields (ChildFieldset). `index` = which child on the form. */
export async function fillChild(page: Page, index: number, child: Child): Promise<void> {
    await page.getByTestId(`child-${index}-first-name`).fill(child.first)
    await page.getByTestId(`child-${index}-last-name`).fill(child.last)
    await page.getByTestId(`child-${index}-nickname`).fill(child.nickname)
    await page.getByTestId(`child-${index}-gender`).selectOption(child.gender)
    await page.getByTestId(`child-${index}-dob`).fill(child.dob)
    // Always answered, yes or no — the form will not take a blank (review B6).
    if (child.allergies) {
        await page.getByTestId(`child-${index}-has-allergies-yes`).check()
        await page.getByTestId(`child-${index}-allergies`).fill(child.allergies)
    } else {
        await page.getByTestId(`child-${index}-has-allergies-no`).check()
    }
}

export type Parent = {
    first: string
    last: string
    email: string
    phone: string
    satellite: string
}

/** The registration form (RegisterFamilyForm) — the same form for parents and for staff. */
export async function fillFamily(page: Page, parent: Parent, children: Child[]): Promise<void> {
    await page.getByTestId('guardian-first-name').fill(parent.first)
    await page.getByTestId('guardian-last-name').fill(parent.last)
    await page.getByTestId('guardian-relationship').selectOption('mother')
    await page.getByTestId('guardian-phone').fill(parent.phone)
    await page.getByTestId('guardian-email').fill(parent.email)
    await page.getByTestId('home-branch').selectOption({ label: parent.satellite })
    await page.getByTestId('area').fill('Al Barsha')
    for (const [i, child] of children.entries()) {
        if (i > 0) await page.getByTestId('add-child').click()
        await fillChild(page, i, child)
    }
    await page.getByTestId('privacy').check()
}

/**
 * A parent registers on the public form and confirms from the email, landing on their family page. For
 * journeys that start from "a registered, signed-in parent".
 */
export async function registerAndConfirm(
    page: Page,
    parent: Omit<Parent, 'email'>,
    children: Child[],
): Promise<string> {
    const { uniqueEmail } = await import('./world')
    const { mailCount } = await import('./mailbox')
    const { openEmailedLink } = await import('./sign-in')
    const email = uniqueEmail(parent.first.toLowerCase())
    const before = mailCount(email)
    await page.goto('/ccf/register')
    await fillFamily(page, { ...parent, email }, children)
    await page.getByTestId('register-submit').click()
    await openEmailedLink(page, email, before)
    return email
}
