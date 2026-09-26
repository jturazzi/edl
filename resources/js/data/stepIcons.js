/** Icône (Material Design Icons) associée à chaque étape du formulaire. */
export const STEP_ICONS = {
    compteurs: 'mdi-gauge', entree: 'mdi-door', couloir: 'mdi-shoe-print', cuisine: 'mdi-pot-steam',
    sejour: 'mdi-sofa', wc: 'mdi-toilet', sdb: 'mdi-bathtub', chambre1: 'mdi-bed', chambre2: 'mdi-bed',
    chambre3: 'mdi-bed', volets: 'mdi-blinds', vaisselle: 'mdi-silverware-fork-knife', petit_materiel: 'mdi-broom',
    literie: 'mdi-bed-empty', mobilier: 'mdi-seat', materiel_divers: 'mdi-package-variant', synthese: 'mdi-clipboard-check-outline',
}

export const stepIcon = (key) => STEP_ICONS[key] || 'mdi-file-document-outline'
