# EDL

Application web de gestion des **États des Lieux** (entrants et sortants) avec authentification Microsoft 365.

---

## Fonctionnalités

- **Authentification Microsoft 365** — Connexion via Microsoft Entra ID (OAuth2)
- **Création d'EDL** — Recherche de commune par département (API Géo gouv.fr)
- **Formulaire multi-étapes** — Compteurs, clés, pièces (entrée, cuisine, séjour, WC, chambres, SDB), inventaire complet
- **Upload de photos** par pièce
- **Signature numérique** — Canvas SignaturePad
- **Génération PDF** — Automatique à la finalisation (DomPDF), couleur personnalisable
- **Envoi par email** — PDF joint au locataire et/ou à l'agent
- **Historique** — EDL avec pagination
- **PWA** — Installable sur mobile et bureau
- **Mode sombre** — Automatique ou manuel

---

## Stack technique

| Couche | Technologie |
|---|---|
| Backend | Laravel 13, PHP 8.5 |
| Frontend | Vue 3 + Vue Router 4, Tailwind CSS 4 |
| Auth | Laravel Socialite + socialiteproviders/microsoft |
| PDF | barryvdh/laravel-dompdf |
| Signature | signature_pad |
| Serveur | FrankenPHP (Caddy) |
| Base de données | SQLite |
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
| `APP_DEPARTEMENT` | Numéro de département pour la recherche de commune | `42` |
| `APP_URL` | URL publique de l'application | `https://edl.exemple.com` |
| `APP_KEY` | Clé de chiffrement Laravel (générer ci-dessous) | `base64:...` |
| `MICROSOFT_CLIENT_ID` | ID de l'application Entra ID | `xxxxxxxx-xxxx-...` |
| `MICROSOFT_CLIENT_SECRET` | Secret client Entra ID | `xxxxxxxxxxxxxxxxx` |
| `MICROSOFT_REDIRECT_URI` | URL de callback OAuth | `https://edl.exemple.com/auth/microsoft/callback` |
| `MICROSOFT_TENANT_ID` | ID du tenant Entra ID | `xxxxxxxx-xxxx-...` |
| `MAIL_HOST` | Serveur SMTP | `smtp.exemple.com` |
| `MAIL_PORT` | Port  | `25` |
| `MAIL_FROM_ADDRESS` | Adresse d'expédition des emails | `edl@exemple.com` |
| `MAIL_FROM_NAME` | Nom d'expéditeur affiché dans les emails | `"EDL"` |

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

---

## Modifier la configuration sans rebuild

Après un changement dans `.env`, redémarrer simplement le conteneur :

```bash
docker compose down && docker compose up -d
```

> Aucun rebuild de l'image ni de `npm run build` n'est nécessaire pour les variables `APP_*`.

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

Le workflow est défini dans `.github/workflows/docker.yml`. Aucune configuration supplémentaire n'est requise — le `GITHUB_TOKEN` est automatique.

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

---

## Licence

Logiciel libre — adaptable à toute organisation.
