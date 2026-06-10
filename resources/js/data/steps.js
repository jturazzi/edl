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

const etat = [
    { value: 'bon',     label: '✅ Bon état' },
    { value: 'usure',   label: '⚠️ Usure normale' },
    { value: 'mauvais', label: '🔴 Mauvais état' },
]

const room = (key, icon, title, elements) => ({
    type: 'room', key, icon, title, elements, etat,
})

const inv = (key, icon, title, items, withDimension = false) => ({
    type: 'inventory', key, icon, title, items, withDimension,
})

export const steps = [
    { type: 'compteurs', key: 'compteurs', icon: '📊', title: 'Compteurs & Clés' },

    room('entree',   '🚪', 'Entrée',         ['Sol','Murs','Porte palière','Interphone','Chauffage','Fenêtre','Luminaires','Prises électrique']),
    room('couloir',  '🚶', 'Couloir',         ['Sol','Murs','Plafond','Porte','Chauffage','Fenêtre','Luminaires','Prises électrique']),
    room('cuisine',  '🍳', 'Cuisine',         ['Plafond','Sol','Murs','Aération','Évier','Robinetterie','Chaudière','Fenêtre','Luminaires','Prises électrique']),
    room('sejour',   '🛋️', 'Séjour',          ['Sol','Murs','Plafond','Chauffage','Fenêtre','Volet/Store','Meuble(s)','Luminaires','Prises électrique']),
    room('wc',       '🚽', 'WC',              ['Cuvette WC','Abattant',"Chasse d'eau",'Lavabo','Ouverture','Luminaires','Prises électrique']),
    room('sdb',      '🛁', 'Salle de bains',  [
        'Plafond','Murs','Sol','Porte','Chauffage','Fenêtre',
        'Colonne lavabo','Robinet/mélangeur Lavabo','Douche/baignoire',
        'Robinet/mélangeur douche','Inverseur douche/baignoire',
        'Flexible et douchette douche','Joint lavabo','Joint douche/baignoire',
        'Luminaires','Prises électrique',
    ]),
    room('chambre1', '🛏️', 'Chambre 1',       ['Sol','Murs','Plafond','Porte','Chauffage','Fenêtre','Volets/Stores','Luminaires','Prises électrique']),
    room('chambre2', '🛏️', 'Chambre 2',       ['Sol','Murs','Plafond','Porte','Chauffage','Fenêtre','Volets/Stores','Luminaires','Prises électrique']),
    room('chambre3', '🛏️', 'Chambre 3',       ['Sol','Murs','Plafond','Porte','Chauffage','Fenêtre','Volets/Stores','Luminaires','Prises électrique']),

    inv('vaisselle', '🍽️', 'Vaisselle', [
        'Passoire','Saladier','Plat four','Assiette plate','Assiette creuse',
        'Bol','Tasse','Verre','Fourchette','Couteau','Cuillère à soupe',
        'Cuillère à café','Couvercle','Poêle','Econome','Couteau service',
        'Ouvre-boîte','Spatule en bois','Casserole','Dessous de plat',
        'Range couvert','Balai','Carafe','Égouttoir vaisselle',
    ]),
    inv('petit_materiel', '🧹', 'Petit matériel', [
        'Balayette + pelle','Seau','Aspirateur','Cafetière',
        'Balai brosse','Poubelle','Poubelle salle de bain','Balayette WC','Table à repasser',
    ]),
    inv('literie', '🛏️', 'Literie & Lingerie', [
        'Sommier 90x190','Sommier 140x190','Matelas','Couverture',
        'Drap housse','Drap dessus','Alèze','Housse couette','Couette',
        'Oreillers',"Taie d'oreillers",'Dessus de lit','Rideaux + Tringles',
        'Torchon vaisselle','Nappe',
    ], true),
    inv('mobilier', '🪑', 'Mobilier', [
        'Cuisinière','Machine à laver','Bureau','Armoire','Téléphone',
        'Chevet','Meuble rangement vaisselle','Table de cuisine','Chaises',
        'Canapé','Table de salon','Meuble de TV','Commode','Chaise de bureau',
    ]),
    {
        type: 'inventory_multi', key: 'materiel_divers',
        icon: '💡', title: 'Matériel & Luminaires',
        sections: [
            { title: 'Matériel ménager',  groupKey: 'menager',   items: ['Fer à repasser','TV','Télécommande TV'] },
            { title: 'Électroménager',    groupKey: 'electro',   items: ['Réfrigérateur / Congélateur','Micro-ondes'] },
            { title: 'Luminaires',        groupKey: 'luminaire', items: [
                'Lampe de chevet (Salon)','Luminaire (Salon)','Lampe de salon',
                'Luminaire (Chambre)','Autre luminaire',
            ]},
        ],
    },
    {
        type: 'synthese', key: 'synthese', icon: '📝', title: 'Synthèse',
        fields: [
            { key: 'nettoyage_entreprise', label: 'Nettoyage entreprise spécialisée', description: 'Un nettoyage par une entreprise spécialisée est-il nécessaire ?' },
            { key: 'depot_garantie', label: 'Dépôt de garantie à restituer', description: 'Le dépôt de garantie doit-il être restitué à la personne concernée ?' },
        ],
    },
]
