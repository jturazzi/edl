import { expect } from '@playwright/test'

/** Ouvre une session sans passer par Microsoft (route disponible uniquement en environnement `e2e`). */
export async function loginAs(page, role) {
    await page.goto(`/__e2e/login/${role}`)
    await expect(page.getByRole('heading', { name: /Bonjour/ })).toBeVisible()
}

/** Le service d'adresses public est remplacé par une réponse fixe : tests stables et sans réseau. */
export async function mockAddressApi(page) {
    await page.route('https://api-adresse.data.gouv.fr/**', (route) => route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({
            features: [{
                properties: { id: 'x1', name: '12 Rue de la Paix', postcode: '75002', city: 'Paris', context: '75, Paris, Île-de-France' },
            }],
        }),
    }))
}

/** Trace un trait dans une zone de signature (souris). */
export async function drawSignature(page, index) {
    const canvas = page.locator('canvas.signature-canvas').nth(index)
    await canvas.scrollIntoViewIfNeeded()
    const box = await canvas.boundingBox()
    await page.mouse.move(box.x + 30, box.y + 40)
    await page.mouse.down()
    await page.mouse.move(box.x + box.width / 2, box.y + box.height - 40, { steps: 8 })
    await page.mouse.move(box.x + box.width - 30, box.y + 40, { steps: 8 })
    await page.mouse.up()
}

/** Crée un EDL via l'interface et renvoie son identifiant (l'utilisateur doit être connecté). */
export async function createEdl(page, { type = 'Entrant' } = {}) {
    await mockAddressApi(page)
    await page.goto('/nouveau')
    await page.getByRole('combobox').fill('12 rue de la paix')
    await page.getByRole('option', { name: /12 Rue de la Paix/ }).click()
    await expect(page.getByLabel('Code postal et ville *')).toHaveValue('75002 Paris')
    await page.getByRole('radio', { name: new RegExp(type) }).click()
    await page.getByRole('button', { name: /Commencer l'état des lieux/ }).click()
    await page.waitForURL(/\/edl\/\d+\/formulaire/)
    return Number(page.url().match(/\/edl\/(\d+)\//)[1])
}
