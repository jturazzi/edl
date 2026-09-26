import { test, expect } from '@playwright/test'
import { loginAs, createEdl, drawSignature } from './helpers.js'

/** Crée et valide un EDL avec le compte connecté ; renvoie son id. */
async function createSignedEdl(page) {
    const id = await createEdl(page)
    await page.getByRole('button', { name: 'Signer', exact: true }).click()
    await page.waitForURL(/\/signature$/)
    await drawSignature(page, 0)
    await page.getByLabel(/Locataire absent/).check()
    await page.getByRole('button', { name: /Valider et générer le PDF/ }).click()
    await page.waitForURL(new RegExp(`/edl/${id}$`), { timeout: 30_000 })
    return id
}

test.describe.serial('Rôles', () => {
    let edlId

    test('un technicien crée un EDL et n\'a ni administration, ni suppression, ni archivage', async ({ page }) => {
        await loginAs(page, 'technicien')
        edlId = await createSignedEdl(page)

        await expect(page.getByRole('link', { name: 'Administration' })).toHaveCount(0)
        await expect(page.getByRole('button', { name: 'Supprimer' })).toHaveCount(0)
        await expect(page.getByRole('button', { name: 'Archiver' })).toHaveCount(0)

        await page.goto('/historique')
        await page.getByRole('button', { name: 'Autres actions' }).first().click()
        await expect(page.locator('.q-menu .q-item').filter({ hasText: /Dupliquer/ })).toBeVisible()
        await expect(page.locator('.q-menu .q-item').filter({ hasText: /Supprimer/ })).toHaveCount(0)
        await expect(page.locator('.q-menu .q-item').filter({ hasText: /Archiver/ })).toHaveCount(0)
    })

    test('un autre technicien voit l\'EDL mais ne peut pas le modifier', async ({ page, browser }) => {
        const ctx = await browser.newContext()
        const other = await ctx.newPage()
        await loginAs(other, 'technicien2') // second compte technicien, distinct de l'auteur
        await other.goto('/historique')
        await expect(other.getByText('12 Rue de la Paix').first()).toBeVisible()

        const result = await other.evaluate(async (id) => {
            const xsrf = decodeURIComponent(document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN=')).split('=')[1])
            const headers = { Accept: 'application/json', 'X-XSRF-TOKEN': xsrf }
            const show = await (await fetch(`/api/edls/${id}`, { headers })).json()
            const del = await fetch(`/api/edls/${id}`, { method: 'DELETE', headers })
            return { canEdit: show.can_edit, deleteStatus: del.status }
        }, edlId)
        expect(result).toEqual({ canEdit: false, deleteStatus: 403 })
        await ctx.close()
    })

    test('un administrateur archive puis retrouve l\'EDL', async ({ page }) => {
        await loginAs(page, 'admin')
        await page.goto('/historique')
        await page.getByRole('button', { name: 'Autres actions' }).first().click()
        await page.locator('.q-menu .q-item').filter({ hasText: /Archiver/ }).click()
        await expect(page.getByText('12 Rue de la Paix')).toHaveCount(0)

        await page.getByText('Archivés').click()
        await expect(page.getByText('12 Rue de la Paix').first()).toBeVisible()
        await expect(page.getByText('Archivé', { exact: true })).toBeVisible()
    })
})
