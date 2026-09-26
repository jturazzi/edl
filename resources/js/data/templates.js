/**
 * Modèles de logement : sélection de pièces / étapes proposée à la création d'un EDL.
 * Les clés sont celles de steps.json ; « compteurs » et « synthese » sont toujours ajoutées côté serveur.
 */

const BASE = ['compteurs', 'entree', 'cuisine', 'sejour', 'wc', 'sdb', 'volets', 'synthese']
const MEUBLE = ['vaisselle', 'petit_materiel', 'literie', 'mobilier', 'materiel_divers']

export const TEMPLATES = [
    { id: 'studio', label: 'Studio / T1', description: 'Une pièce de vie', steps: BASE },
    { id: 't2', label: 'T2', description: '1 chambre', steps: [...BASE, 'chambre1'] },
    { id: 't3', label: 'T3', description: '2 chambres + couloir', steps: [...BASE, 'couloir', 'chambre1', 'chambre2'] },
    { id: 't4', label: 'T4 et +', description: '3 chambres + couloir', steps: [...BASE, 'couloir', 'chambre1', 'chambre2', 'chambre3'] },
    { id: 'complet', label: 'Complet', description: 'Toutes les étapes', steps: null },
]

/** Étapes d'un modèle, avec ou sans l'inventaire du mobilier (logement meublé). `null` = toutes. */
export function templateSteps(template, meuble, allKeys) {
    if (!template || template.steps === null) return [...allKeys]
    return allKeys.filter((key) => template.steps.includes(key) || (meuble && MEUBLE.includes(key)))
}

export { MEUBLE }
