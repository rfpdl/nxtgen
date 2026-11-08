import { navTo } from '../support/menu'
import { latestMail, mailCount } from '../support/mailbox'
import { openEmailedLink } from '../support/sign-in'
import { expect, test } from '../support/test'
import { DUBAI, CLASSES, SATELLITES, sessionFile, uniqueEmail } from '../support/world'

/*
 * A class admin (TODO #9; plan §3.2 "Class admin (S)"): an admin of CCF Dubai makes someone Admin of CCF Dubai · 1PM on
 * People; they sign in from the email and find their class at the door and on Classes — and no other.
 */
test('an admin makes someone class admin of 1PM; they see only 1PM at the door and on Classes @story#180 @story#182 @story#183', async ({
    page,
    browser,
}) => {
    const email = uniqueEmail('classadmin')
    const before = mailCount(email)
    const role = `Admin · ${SATELLITES.dxb} · ${CLASSES.onePm.name}`

    const admin = await browser.newContext({ storageState: sessionFile('admin') })
    const office = await admin.newPage()
    await office.goto(`/${DUBAI}/admin/people`)
    await office.getByTestId('staff-name').fill('Cleo Class')
    await office.getByTestId('staff-email').fill(email)
    await office.getByTestId('staff-role').selectOption({ label: role })
    await office.getByTestId('add-staff-submit').click()
    await expect(office.getByTestId('flash-success')).toContainText(`${email} is now ${role}`)
    await admin.close()
    expect((await latestMail(email, before)).subject).toContain('You have been added')

    // Cleo, on her own device: the church's address opens the door — 1PM only.
    await openEmailedLink(page, email, before)
    await expect(page).toHaveURL(new RegExp(`/${DUBAI}/serve/door`))
    const classes = page.getByTestId('door-class').getByRole('radio')
    await expect(classes).toHaveCount(1)
    await expect(classes).toContainText(CLASSES.onePm.name)

    // Admin: her Classes — 1PM, no "+ Add a class" — and nothing else of Admin.
    await navTo(page, 'Admin')
    await expect(page).toHaveURL(new RegExp(`/${DUBAI}/admin/classes`))
    await expect(page.getByTestId('schedule').getByRole('heading')).toHaveText([CLASSES.onePm.name])
    await expect(page.getByTestId('open-add-schedule')).toHaveCount(0)
    await page.goto(`/${DUBAI}/admin/families`)
    // A class admin opens only Classes and Attendance in Admin: anything else sends them to Serve (spec item 10).
    await expect(page).toHaveURL(new RegExp(`/${DUBAI}/serve/door$`))
})
