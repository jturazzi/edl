#!/bin/bash

set -e

echo "🚀 Début du déploiement de EDL..."

# Couleurs pour les messages
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[0;33m'
NC='\033[0m' # No Color

# Vérifier la présence du fichier .env
if [ ! -f .env ]; then
    echo -e "${RED}❌ ERREUR : Le fichier .env est manquant !${NC}"
    echo -e "${YELLOW}⚠️  Veuillez créer un fichier .env avant de continuer.${NC}"
    echo -e "${BLUE}ℹ️  Vous pouvez copier .env.example : cp .env.example .env${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Fichier .env trouvé${NC}"

# Fonction pour afficher les messages
log_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# Vérifier si le service app existe et est en cours d'exécution
check_app_running() {
    if docker compose ps app --format json 2>/dev/null | grep -q '"State":"running"'; then
        return 0
    else
        return 1
    fi
}

# Étape 1 : Récupération du code source
log_info "Étape 1/7 : Pull du code depuis Git..."
git pull origin main
log_success "Code source à jour"

# Étape 2 : Activation du mode maintenance
if check_app_running; then
    log_info "Étape 2/7 : Activation du mode maintenance..."
    docker compose exec -T app php artisan down --render="errors::503" --retry=60 || true
    log_success "Mode maintenance activé"
else
    log_warning "Étape 2/7 : App non démarrée, mode maintenance ignoré"
fi

# Étape 3 : Build de l'image Docker
log_info "Étape 3/7 : Build de l'image Docker..."
docker compose build
log_success "Image Docker construite"

# Étape 4 : Redémarrage des conteneurs
log_info "Étape 4/7 : Arrêt des conteneurs..."
docker compose down
log_success "Conteneurs arrêtés"

log_info "Étape 5/7 : Démarrage des conteneurs..."
docker compose up -d --remove-orphans
log_success "Conteneurs démarrés"

# Étape 6 : Vérification de la santé des services
log_info "Étape 6/7 : Attente que les services soient prêts..."
TIMEOUT=60
ELAPSED=0
while [ $ELAPSED -lt $TIMEOUT ]; do
    APP_STATUS=$(docker compose ps app --format json 2>/dev/null | grep -o '"Health":"[^"]*"' | cut -d'"' -f4 || echo "")
    if [ "$APP_STATUS" = "healthy" ]; then
        log_success "Services prêts"
        break
    fi
    sleep 2
    ELAPSED=$((ELAPSED + 2))
    echo -n "."
done
echo ""

if [ $ELAPSED -ge $TIMEOUT ]; then
    log_error "Timeout: les services ne sont pas prêts après ${TIMEOUT}s"
    log_info "Vérification des logs..."
    docker compose logs --tail=50 app
    exit 1
fi

# Étape 7 : Désactivation du mode maintenance
log_info "Étape 7/7 : Désactivation du mode maintenance..."
if check_app_running; then
    # Attendre quelques secondes que l'application soit complètement prête
    sleep 5
    docker compose exec -T app php artisan up
    log_success "Mode maintenance désactivé"
else
    log_error "Le service app n'est pas démarré correctement"
    log_info "Vérification des logs..."
    docker compose logs --tail=50 app
    exit 1
fi

# Vérification finale de l'état des conteneurs
log_info "Vérification finale de l'état des conteneurs..."
docker compose ps

echo ""
log_success "🎉 Déploiement terminé avec succès!"
echo ""