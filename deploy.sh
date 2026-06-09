#!/usr/bin/env bash
#
# deploy.sh — Déploiement production (VPS / serveur avec accès shell + systemd).
#
# Couvre le cycle backend complet : mode maintenance, dépendances, build front,
# migrations, lien de stockage, caches, (ré)indexation Typesense optionnelle,
# redémarrage des workers (Horizon) et du serveur temps réel (Reverb).
#
# Pour l'hébergement mutualisé Hostinger (sans systemd / sans worker long),
# voir plutôt deploy-hostinger.sh + public/post-deploy.php.
#
# Usage :
#   ./deploy.sh                 # déploiement standard (git pull + tout le cycle)
#   SKIP_GIT=1 ./deploy.sh      # ne pas faire de git pull (build à partir du checkout courant)
#   SKIP_BUILD=1 ./deploy.sh    # ne pas reconstruire le frontend
#   REINDEX=1 ./deploy.sh       # (ré)importer les collections Typesense (Scout)
#   BRANCH=jonas ./deploy.sh    # branche à déployer (défaut: jonas)
#
# Variables d'environnement optionnelles :
#   PHP=php  COMPOSER=composer  NPM=npm   # binaires (si chemins personnalisés)
#   REVERB_RESTART_CMD="sudo systemctl restart reverb"   # commande de restart Reverb
#
# Prérequis serveur : PHP 8.1+ (+phpredis), Composer, Node/npm, MySQL, Redis,
# Typesense, et Horizon supervisé (systemd/supervisor) écoutant queue:restart.

set -Eeuo pipefail

# ── Réglages ──────────────────────────────────────────────────────────────────
BRANCH="${BRANCH:-jonas}"
PHP="${PHP:-php}"
COMPOSER="${COMPOSER:-composer}"
NPM="${NPM:-npm}"
SKIP_GIT="${SKIP_GIT:-0}"
SKIP_BUILD="${SKIP_BUILD:-0}"
REINDEX="${REINDEX:-0}"

# Modèles Scout à (ré)indexer si REINDEX=1.
SCOUT_MODELS=(
  "App\\Models\\Projet"
  "App\\Models\\Activite"
  "App\\Models\\Tache"
  "App\\Models\\SousTache"
  "App\\Models\\Document"
  "App\\Models\\User"
  "App\\Models\\TeamMessage"
  "App\\Models\\Notification"
  "App\\Models\\HelpArticle"
)

# ── Helpers ─────────────────────────────────────────────────────────────────
log()  { printf '\n\033[1;34m▶ %s\033[0m\n' "$*"; }
ok()   { printf '\033[1;32m  ✓ %s\033[0m\n' "$*"; }
warn() { printf '\033[1;33m  ⚠ %s\033[0m\n' "$*"; }

# Toujours sortir du mode maintenance, même en cas d'erreur.
cleanup_on_error() {
  warn "Échec du déploiement — tentative de sortie du mode maintenance."
  "$PHP" artisan up >/dev/null 2>&1 || true
}
trap cleanup_on_error ERR

cd "$(dirname "$0")"

# ── 0. Garde-fous ─────────────────────────────────────────────────────────────
log "Vérifications préalables"
[ -f artisan ] || { echo "artisan introuvable — lancez depuis la racine du projet."; exit 1; }
[ -f .env ] || { echo ".env introuvable en production."; exit 1; }

APP_ENV="$("$PHP" artisan tinker --no-interaction --execute='echo config("app.env");' 2>/dev/null | tail -1 || true)"
ok "Environnement applicatif : ${APP_ENV:-inconnu}"
if [ "${APP_ENV:-}" != "production" ]; then
  warn "APP_ENV n'est pas 'production' (= ${APP_ENV:-vide}). Poursuite quand même."
fi

# ── 1. Récupérer le code ──────────────────────────────────────────────────────
if [ "$SKIP_GIT" != "1" ]; then
  log "Récupération du code (branche $BRANCH)"
  git fetch --all --prune
  git checkout "$BRANCH"
  git pull --ff-only origin "$BRANCH"
  ok "Code à jour : $(git rev-parse --short HEAD)"
else
  warn "SKIP_GIT=1 — pas de git pull."
fi

# ── 2. Mode maintenance ───────────────────────────────────────────────────────
log "Activation du mode maintenance"
"$PHP" artisan down --render="errors::503" --retry=15 || "$PHP" artisan down || true
ok "Mode maintenance activé"

# ── 3. Dépendances PHP ────────────────────────────────────────────────────────
log "Installation des dépendances PHP (prod)"
"$COMPOSER" install --no-dev --optimize-autoloader --no-interaction --prefer-dist
ok "Composer OK"

# ── 4. Frontend ───────────────────────────────────────────────────────────────
if [ "$SKIP_BUILD" != "1" ]; then
  log "Build du frontend"
  "$NPM" ci
  "$NPM" run build
  ok "Build frontend OK"
else
  warn "SKIP_BUILD=1 — frontend non reconstruit."
fi

# ── 5. Base de données ────────────────────────────────────────────────────────
log "Migrations"
"$PHP" artisan migrate --force
ok "Migrations appliquées"

# ── 6. Lien de stockage (avatars, documents → /storage) ───────────────────────
log "Lien de stockage public"
if [ -L public/storage ]; then
  ok "public/storage déjà présent"
else
  "$PHP" artisan storage:link
  ok "public/storage créé"
fi

# ── 7. Caches (config / routes / events / views) ──────────────────────────────
log "Mise en cache de configuration"
"$PHP" artisan config:clear
"$PHP" artisan optimize        # config:cache + route:cache + ...
"$PHP" artisan event:cache || true
"$PHP" artisan view:cache || true
ok "Caches régénérés"

# ── 8. Recherche Typesense (optionnel) ────────────────────────────────────────
if [ "$REINDEX" = "1" ]; then
  log "(Ré)indexation Typesense (Scout)"
  for model in "${SCOUT_MODELS[@]}"; do
    "$PHP" artisan scout:import "$model" || warn "Import échoué pour $model (collection absente ?)"
  done
  ok "Indexation lancée (peut se poursuivre en file d'attente si SCOUT_QUEUE=true)"
else
  warn "REINDEX non demandé — index Typesense inchangé (utilisez REINDEX=1 si schéma modifié)."
fi

# ── 9. Workers de file (Horizon) ──────────────────────────────────────────────
# Demande aux workers Horizon de se terminer proprement ; le superviseur
# (systemd/supervisor) les relance avec le nouveau code. Ne PAS lancer
# 'php artisan horizon' ici (ce script n'est pas le superviseur).
log "Redémarrage gracieux des workers"
"$PHP" artisan horizon:terminate 2>/dev/null && ok "Horizon: signal de redémarrage envoyé" \
  || { "$PHP" artisan queue:restart >/dev/null 2>&1 && ok "queue:restart envoyé" \
       || warn "Aucun worker signalé (Horizon/queue non actif ?)."; }

# ── 10. Serveur temps réel (Reverb) ───────────────────────────────────────────
log "Redémarrage du serveur temps réel (Reverb)"
if [ -n "${REVERB_RESTART_CMD:-}" ]; then
  eval "$REVERB_RESTART_CMD" && ok "Reverb redémarré via REVERB_RESTART_CMD" \
    || warn "Échec de REVERB_RESTART_CMD."
else
  warn "REVERB_RESTART_CMD non défini — redémarrez Reverb manuellement (ex: sudo systemctl restart reverb)."
fi

# ── 11. Sortie de maintenance ─────────────────────────────────────────────────
log "Désactivation du mode maintenance"
"$PHP" artisan up
ok "Application en ligne"

trap - ERR
printf '\n\033[1;32m✅ Déploiement terminé (%s @ %s)\033[0m\n' "$BRANCH" "$(git rev-parse --short HEAD 2>/dev/null || echo '?')"
