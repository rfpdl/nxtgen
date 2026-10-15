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
    if (child.allergies) await page.getByTestId(`child-${index}-allergies`).fill(child.allergies)
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
