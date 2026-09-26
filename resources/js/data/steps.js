/**
 * Équivalent JS de Str::slug($str, '_') de Laravel.
 */
export function slugify(str) {
    return str
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_|_$/g, '')
}

import steps from './steps.json'

/**
 * La structure des étapes vit dans steps.json : elle est partagée avec le backend
 * (comparaison entrant/sortant, PDF) pour n'avoir qu'une seule source de vérité.
 */
export { steps }
