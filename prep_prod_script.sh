#!/bin/bash

echo "🚀 Préparation pour la production..."

# 1. Nettoyer les caches
echo "📦 Nettoyage des caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 2. Installer les dépendances de production (sans dev)
echo "📥 Installation des dépendances..."
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Build du frontend AVEC les variables de production
echo "🎨 Build du frontend Vue.js..."
npm ci

# ✅ CRITIQUE : Build avec le bon environnement
NODE_ENV=production npm run build -- --mode production

# 4. Optimiser Laravel
echo "⚡ Optimisation Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Vérifier le build
echo "🔍 Vérification du build..."
if [ ! -f "public/build/manifest.json" ]; then
    echo "❌ ERREUR: manifest.json manquant!"
    exit 1
fi

echo "✅ Build vérifié"

# 6. Créer l'archive pour upload
echo "📦 Création de l'archive..."
tar -czf deploy.tar.gz \
    --exclude='node_modules' \
    --exclude='.git' \
    --exclude='.env' \
    --exclude='.env.local' \
    --exclude='.env.production' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='tests' \
    --exclude='.phpunit.result.cache' \
    .

echo "✅ Archive créée: deploy.tar.gz"
echo "📊 Taille de l'archive:"
du -h deploy.tar.gz

echo ""
echo "🎉 Préparation terminée!"
echo "Vous pouvez maintenant uploader deploy.tar.gz sur Hostinger"