# EDL

[![Licence: MIT](https://img.shields.io/badge/licence-MIT-blue.svg)](LICENSE)
[![Docker Build](https://github.com/jturazzi/edl/actions/workflows/docker.yml/badge.svg)](https://github.com/jturazzi/edl/actions/workflows/docker.yml)
[![PHP](https://img.shields.io/badge/PHP-%5E8.3-777bb4.svg)](composer.json)

Application web de gestion des **États des Lieux** (entrants et sortants) avec authentification Microsoft 365.

---

## Sommaire

- [Fonctionnalités](#fonctionnalités)
- [Stack technique](#stack-technique)
- [Démarrage rapide (Docker)](#démarrage-rapide-docker)
- [Mise à jour](#mise-à-jour)
- [Rôles et droits](#rôles-et-droits)
- [Sécurité et traçabilité](#sécurité-et-traçabilité)
- [Suivi des erreurs (Sentry)](#suivi-des-erreurs-sentry)
- [Modifier la configuration sans rebuild](#modifier-la-configuration-sans-rebuild)
- [Reverse Proxy](#reverse-proxy)
- [CI/CD (GitHub Actions)](#cicd-github-actions)
- [Configuration Microsoft Entra ID](#configuration-microsoft-entra-id)
- [Développement local](#développement-local)
- [Licence](#licence)

---

## Fonctionnalités

**Création et saisie**
- **Authentification Microsoft 365** - Connexion via Microsoft Entra ID (OAuth2)
- **Adresse assistée** - Suggestions de la Base Adresse Nationale (api-adresse.data.gouv.fr), saisie libre possible
- **Modèles de logement** - Studio, T2, T3, T4 et +, complet, avec option meublé ; pièces personnalisables à la création et modifiables ensuite
- **Formulaire multi-étapes** - Compteurs, clés, pièces, inventaire, synthèse ; sauvegarde automatique
- **Photos** - Par pièce ou par élément, légendes, visionneuse plein écran, suppression ; réduites dans le navigateur avant l'envoi
- **Conflits entre appareils** - Si un EDL est modifié ailleurs, choix entre fusionner, garder sa version ou prendre l'autre

**Entrée / sortie**
- **EDL sortant lié à l'entrant** - Création en un clic, reprise des informations
- **Comparaison entrée / sortie** - Dégradations, manquants, améliorations, consommations de compteurs
- **Bilan des retenues** - Montants estimés par dégradation, total, impression
- **Historique d'un logement** - Tous les EDL d'une même adresse, du plus récent au plus ancien
- **Duplication** - Nouvel EDL pour le même logement (nouveau locataire, nouvelle visite)

**Signature et documents**
- **Double signature** - Technicien et locataire (ou « locataire absent ») ; signature vide refusée
- **PDF** - Généré à la validation (DomPDF), couleur personnalisable, signatures horodatées, comparatif pour un sortant
- **Intégrité** - Empreinte SHA-256 du PDF enregistrée à la validation, vérifiable depuis la page de l'EDL
- **EDL verrouillé** - Un EDL signé n'est plus modifiable
- **Envoi par email** - PDF joint au locataire et/ou à l'agent

**Suivi**
- **Tableau de bord** - Chiffres du mois, EDL à reprendre, rappel des EDL en retard
- **Historique** - Recherche, filtres (type, statut, technicien, période), tri, export CSV
- **Archivage** - EDL archivés masqués des listes, consultables via un filtre (administrateurs)
- **Administration** - Utilisateurs et rôles, journal d'activité (EDL, connexions, changements de rôle)

**Confort**
- **Thème clair / sombre / automatique**
- **Accessibilité** - Lien d'évitement, navigation clavier, noms accessibles, contrastes, réduction des animations
- **PWA** - Installable sur mobile et bureau (l'application nécessite une connexion)

---

## Stack technique

| Couche | Technologie |
|---|---|
| Backend | Laravel 13, PHP 8.5 |
| Frontend | Vue 3 + Vue Router 5, Quasar 2, Tailwind CSS 4 (utilitaires) |
| Auth | Laravel Socialite + socialiteproviders/microsoft |
| PDF | barryvdh/laravel-dompdf |
| Signature | signature_pad |
| Serveur | FrankenPHP (Caddy) |
| Base de données | SQLite |
| Adresses | API Base Adresse Nationale (service public, sans clé) |
| Suivi d'erreurs | Sentry (serveur et navigateur, optionnel) |
| Tests | PHPUnit, Playwright (navigateur) |
| Analyse statique | PHPStan (Larastan) |
| Conteneur | Docker (build multi-stage) |

---

## Démarrage rapide (Docker)

### Prérequis

- Docker & Docker Compose
- Une application Microsoft Entra ID ([guide](#configuration-microsoft-entra-id))

### 1. Créer un `docker-compose.yml`

```yaml
services:
  edl:
    container_name: edl
    image: ghcr.io/jturazzi/edl:latest
    restart: unless-stopped
    ports:
      - "8080:8080"
    volumes:
      - storage-data:/var/www/html/storage
    env_file:
      - .env
    command: ["frankenphp", "run"]
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:8080/up"]
      interval: 10s
      timeout: 5s
      retries: 3
      start_period: 60s

volumes:
  storage-data:
    driver: local
```

### 2. Créer et configurer le `.env`

```bash
curl -o .env https://raw.githubusercontent.com/jturazzi/edl/main/.env.example
```

| Variable | Description | Exemple |
|---|---|---|
| `APP_NAME` | Nom affiché dans toute l'application | `"Etat des lieux Compagnie"` |
| `APP_LOGO` | URL HTTPS du logo (navbar, login, PDF) | `https://exemple.com/logo.png` |
| `APP_PDF_COLOR` | Couleur principale des PDF (hex) | `#33CCFF` |
| `APP_URL` | URL publique de l'application | `https://edl.exemple.com` |
| `APP_KEY` | Clé de chiffrement Laravel (générer ci-dessous) | `base64:...` |
| `MICROSOFT_CLIENT_ID` | ID de l'application Entra ID | `xxxxxxxx-xxxx-...` |
| `MICROSOFT_CLIENT_SECRET` | Secret client Entra ID | `xxxxxxxxxxxxxxxxx` |
| `MICROSOFT_REDIRECT_URI` | URL de callback OAuth | `https://edl.exemple.com/auth/microsoft/callback` |
| `MICROSOFT_TENANT_ID` | ID du tenant Entra ID | `xxxxxxxx-xxxx-...` |
| `MAIL_HOST` | Serveur SMTP | `smtp.exemple.com` |
| `MAIL_PORT` | Port SMTP | `25` |
| `MAIL_FROM_ADDRESS` | Adresse d'expédition des emails | `edl@exemple.com` |
| `MAIL_FROM_NAME` | Nom d'expéditeur affiché dans les emails | `"EDL"` |
| `ADMIN_EMAILS` | Adresses promues **administrateur** à la connexion (séparées par des virgules) | `jean@exemple.com,marie@exemple.com` |
| `SENTRY_LARAVEL_DSN` | DSN Sentry côté serveur (optionnel) | `https://xxx@sentry.io/1` |
| `SENTRY_JS_DSN` | DSN Sentry côté navigateur (optionnel, par défaut celui du serveur) | `https://xxx@sentry.io/2` |

> **Important** : `MICROSOFT_REDIRECT_URI` doit correspondre exactement à l'URI configurée dans Entra ID.

### 3. Générer la clé `APP_KEY`

```bash
docker run --rm ghcr.io/jturazzi/edl:latest php artisan key:generate --show
```

Copier la valeur et la renseigner dans le `.env`.

### 4. Démarrer

```bash
docker compose up -d
```

Les migrations sont exécutées automatiquement au démarrage.

---

## Mise à jour

```bash
docker compose pull
docker compose up -d
```

Les migrations s'exécutent au démarrage. Points d'attention :

- **Premier passage aux rôles** : la migration passe tous les utilisateurs existants en **administrateur** pour ne bloquer personne. Rétrogradez ensuite les techniciens depuis la page *Administration*, ou définissez `ADMIN_EMAILS` avant que d'autres personnes ne se connectent.
- La suppression des catégories (ancienne fonctionnalité) supprime la table `categories` : **sauvegardez la base avant la mise à jour**.
- Après un changement de `.env`, videz le cache de configuration si vous l'utilisez (`php artisan config:clear`).
- Le dossier `storage` (base SQLite, photos, PDF) est le seul volume à sauvegarder.

---

## Modifier la configuration sans rebuild

Après un changement dans `.env`, redémarrer simplement le conteneur :

```bash
docker compose down && docker compose up -d
```

> Aucun rebuild de l'image ni de `npm run build` n'est nécessaire pour les variables `APP_*`.

---

## Rôles et droits

| | Technicien | Administrateur |
|---|---|---|
| Consulter tous les EDL, PDF, photos, comparaisons | oui | oui |
| Créer un EDL, dupliquer, créer un sortant | oui | oui |
| Modifier / signer un EDL | les siens | tous |
| Supprimer ou archiver un EDL | non | oui |
| Utilisateurs, rôles, journal d'activité | non | oui |

Un nouvel utilisateur est **technicien** par défaut. Pour promouvoir quelqu'un :

- via `ADMIN_EMAILS` dans le `.env` (appliqué à la connexion) ;
- via la page *Administration* (carte « Utilisateurs »), en gardant toujours au moins un administrateur ;
- en ligne de commande : `php artisan user:role adresse@exemple.com admin`.

Un EDL signé est **verrouillé** pour tout le monde : réponses, pièces et photos ne changent plus (seul le bilan des retenues d'un sortant reste modifiable).

---

## Sécurité et traçabilité

- **Journal d'activité** : validation, suppression, archivage, duplication, changement de rôle, connexions, déconnexions et échecs de connexion (avec l'adresse IP), consultable dans *Administration*.
- **Limitation des requêtes** : 240/min par utilisateur pour l'API, 60/min pour les photos, 10/min pour les validations, 30/min par IP pour la connexion. Les compteurs sont stockés dans des fichiers (`CACHE_LIMITER_STORE`, `file` par défaut) pour ne pas surcharger la base SQLite.
- **SQLite** : attente de 5 s sur un verrou (`DB_BUSY_TIMEOUT`) au lieu d'une erreur immédiate en cas d'accès simultanés.
- **Intégrité des PDF** : empreinte SHA-256 enregistrée à la validation, bouton *Vérifier* sur la page de l'EDL.
- **Signatures** : jamais renvoyées dans les réponses JSON ; image PNG réellement tracée exigée.

> Tous les techniciens peuvent **lire** tous les EDL (adresses, locataires). Pensez à l'information des personnes concernées et à la durée de conservation des données (RGPD).

---

## Suivi des erreurs (Sentry)

Renseigner `SENTRY_LARAVEL_DSN` (erreurs PHP) et, si besoin, `SENTRY_JS_DSN` (erreurs du navigateur ; identique par défaut). Sans DSN, Sentry reste désactivé. Le DSN du navigateur est lu au chargement de la page : aucun rebuild n'est nécessaire.

---

## Reverse Proxy

L'application écoute sur le port **8080** (HTTP). Placer un reverse proxy devant pour HTTPS.

<details>
<summary>Exemple Nginx</summary>

```nginx
server {
    listen 443 ssl http2;
    server_name edl.exemple.com;

    ssl_certificate     /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

</details>

<details>
<summary>Exemple labels Traefik</summary>

```yaml
services:
  edl:
    # ...
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.edl.rule=Host(`edl.exemple.com`)"
      - "traefik.http.routers.edl.tls.certresolver=letsencrypt"
      - "traefik.http.services.edl.loadbalancer.server.port=8080"
```

</details>

---

## CI/CD (GitHub Actions)

L'image Docker est automatiquement construite et publiée sur **GitHub Container Registry** (`ghcr.io`) à chaque push sur `main` ou création d'un tag de version.

Le workflow est défini dans `.github/workflows/docker.yml`. Aucune configuration supplémentaire n'est requise - le `GITHUB_TOKEN` est automatique.

Les tags générés :

| Déclencheur | Tag(s) produits |
|---|---|
| Push sur `main` | `latest`, `sha-xxxxxxx` |
| Tag `v1.2.3` | `1.2.3`, `1.2`, `latest` |

---

## Configuration Microsoft Entra ID

1. Aller sur [Microsoft Entra ID](https://entra.microsoft.com) → **Inscriptions d'applications** → **Nouvelle inscription**
2. **Nom** : `EDL` (ou autre)
3. **Types de comptes pris en charge** : Comptes dans cet annuaire d'organisation uniquement (locataire unique)
4. **URI de redirection** : Plateforme `Web` → `https://edl.exemple.com/auth/microsoft/callback`
5. Noter l'**ID d'application (client)** et l'**ID de l'annuaire (locataire)**
6. **Certificats et secrets** → **Nouveau secret client** → copier la **Valeur**
7. **Autorisations d'API** → vérifier que `User.Read` (Microsoft Graph) est accordé

---

## Développement local

### Prérequis

- PHP 8.3+ avec Composer 2
- Node.js 20+ avec npm

```bash
git clone https://github.com/jturazzi/edl.git
cd edl

cp .env.example .env
# Renseigner les variables MICROSOFT_* et APP_* dans .env

composer install
npm install
php artisan key:generate
php artisan migrate

# Démarrer le serveur de développement
php artisan serve &
npm run dev
```

### Analyse statique et audit des dépendances

```bash
composer analyse  # PHPStan (Larastan)
composer audit     # Vulnérabilités connues des dépendances PHP
npm audit          # Vulnérabilités connues des dépendances JS
```

Le script [update-composer-npm.sh](update-composer-npm.sh) met à jour les dépendances puis lance ces deux audits.

### Tests

```bash
php artisan test        # tests PHP (PHPUnit)

npm run build           # les tests navigateur utilisent les assets compilés
npx playwright install chromium   # une seule fois
npm run test:e2e        # tests navigateur (Playwright)
```

Les tests navigateur ([tests/e2e/](tests/e2e/)) lancent leur propre serveur sur le port 8799 avec
l'environnement `e2e` ([.env.e2e](.env.e2e)) et une base SQLite jetable (`database/e2e.sqlite`) : votre base de
développement n'est jamais touchée. Comme la connexion Microsoft ne s'automatise pas, l'environnement `e2e`
expose une route `/__e2e/login/{role}` qui n'existe dans aucun autre environnement (un test PHP le vérifie).
Le service d'adresses public est simulé : les tests ne nécessitent aucun accès réseau.

### Thème

L'interface suit le thème clair ou sombre de l'appareil ; le bouton en bas de la barre latérale permet de forcer
l'un ou l'autre (choix mémorisé dans le navigateur).

---

## Licence

[MIT](LICENSE)
