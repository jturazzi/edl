import { test, expect } from '@playwright/test'
import { loginAs, createEdl, drawSignature } from './helpers.js'

test.describe('Parcours complet', () => {
    test('un technicien crée, remplit, signe et valide un EDL', async ({ page, request }) => {
        await loginAs(page, 'technicien')
        const id = await createEdl(page)

        // Formulaire : un relevé de compteur, sauvegardé automatiquement
        await page.getByLabel('Eau (m³)').fill('123.456')

        // Sauvegarde automatique : les réponses arrivent côté serveur sans clic
        const fetchEdl = (edlId) => page.evaluate(async (i) => (await fetch(`/api/edls/${i}`, { headers: { Accept: 'application/json' } })).json(), edlId)
        await expect.poll(async () => (await fetchEdl(id)).survey_data?.compteur_eau, { timeout: 10_000 }).toBe('123.456')

        // Signature : technicien + locataire absent
        await page.getByRole('button', { name: 'Signer', exact: true }).click()
        await page.waitForURL(/\/signature$/)
        await expect(page.getByRole('button', { name: /Valider et générer le PDF/ })).toBeDisabled()
        await drawSignature(page, 0)
        await page.getByLabel(/Locataire absent/).check()
        await page.getByRole('button', { name: /Valider et générer le PDF/ }).click()

        // Récapitulatif + PDF + intégrité
        await page.waitForURL(new RegExp(`/edl/${id}$`), { timeout: 30_000 })
        await expect(page.getByText('EDL validé avec succès')).toBeVisible()
        await page.getByRole('button', { name: 'Vérifier' }).click()
        await expect(page.getByText('Le PDF est identique à celui généré à la validation.')).toBeVisible()

        const pdf = await page.request.get(`/edl/${id}/pdf/view`)
        expect(pdf.ok()).toBeTruthy()
        expect(pdf.headers()['content-type']).toContain('application/pdf')
        expect((await pdf.body()).subarray(0, 4).toString()).toBe('%PDF')

        // L'EDL signé est figé : son formulaire redirige vers le récapitulatif
        await page.goto(`/edl/${id}/formulaire`)
        await page.waitForURL(new RegExp(`/edl/${id}$`))
    })

    test('une signature vide est refusée par le serveur', async ({ page }) => {
        await loginAs(page, 'technicien')
        const id = await createEdl(page)

        const status = await page.evaluate(async (edlId) => {
            const xsrf = decodeURIComponent(document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN=')).split('=')[1])
            const blank = document.createElement('canvas'); blank.width = 100; blank.height = 50
            const ctx = blank.getContext('2d'); ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, 100, 50)
            const res = await fetch(`/api/edls/${edlId}/finalize`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': xsrf },
                body: JSON.stringify({ signature_technicien: blank.toDataURL('image/png'), locataire_absent: true }),
            })
            return res.status
        }, id)

        expect(status).toBe(422)
    })

    test('un visiteur non connecté est renvoyé vers la connexion', async ({ page }) => {
        await page.goto('/')
        await page.waitForURL(/\/login/)
        await expect(page.getByText(/Microsoft/i).first()).toBeVisible()
    })
})
