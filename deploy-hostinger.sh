#!/bin/bash

set -e

echo "========================================="
echo "🚀 BUILD PRODUCTION - WORK TRACKING"
echo "========================================="
echo ""

# 1. Vérifier que .env.production existe
if [ ! -f ".env.production" ]; then
    echo "❌ ERREUR: .env.production manquant!"
    echo ""
    echo "Créez le fichier .env.production avec:"
    echo "VITE_API_URL=https://work-tracking.online/api"
    exit 1
fi

echo "✅ .env.production détecté"
echo "   API URL: $(grep VITE_API_URL .env.production | cut -d'=' -f2)"
echo ""

# 2. Nettoyer complètement
echo "🧹 Nettoyage total..."
rm -rf public/build
rm -rf node_modules/.vite
php artisan cache:clear 2>/dev/null || true
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
echo "   ✅ Nettoyage terminé"

# 3. Installer NPM
echo ""
echo "📥 Installation NPM..."
npm ci
echo "   ✅ NPM installé"

# 4. Build avec .env.production
echo ""
echo "🎨 Build du frontend (mode production)..."
echo "   Variables:"
echo "   - NODE_ENV=production"
echo "   - Mode Vite: production"

# ✅ CORRECTION: Utiliser --mode production pour charger .env.production
NODE_ENV=production npm run build -- --mode production

if [ $? -ne 0 ]; then
    echo "   ❌ ERREUR lors du build!"
    exit 1
fi

echo "   ✅ Build terminé"

# 5. Vérification stricte
echo ""
echo "🔍 Vérification du build..."

# Vérifier manifest.json
if [ ! -f "public/build/manifest.json" ]; then
    if [ -f "public/build/.vite/manifest.json" ]; then
        echo "   📦 Déplacement de .vite/manifest.json..."
        cp public/build/.vite/manifest.json public/build/manifest.json
    else
        echo "   ❌ manifest.json introuvable!"
        ls -la public/build/
        exit 1
    fi
fi

echo "   ✅ manifest.json présent"

# Vérifier assets
if [ ! -d "public/build/assets" ]; then
    echo "   ❌ Dossier assets manquant!"
    exit 1
fi

asset_count=$(find public/build/assets -type f -name "*.js" -o -name "*.css" | wc -l)
echo "   ✅ Assets générés: $asset_count fichiers"

# ⚠️ VÉRIFICATION CRITIQUE: localhost
echo ""
echo "🚨 Vérification CRITIQUE (localhost):"
localhost_count=$(grep -r "localhost" public/build/assets/*.js 2>/dev/null | grep -v ".map" | wc -l)

if [ $localhost_count -gt 0 ]; then
    echo "   ❌ ERREUR: 'localhost' trouvé $localhost_count fois!"
    echo ""
    echo "   Occurrences:"
    grep -n "localhost" public/build/assets/*.js | grep -v ".map" | head -5
    echo ""
    echo "   🔧 Causes possibles:"
    echo "   1. .env.production mal configuré"
    echo "   2. Cache Vite non nettoyé (node_modules/.vite)"
    echo "   3. Variables d'environnement non chargées"
    echo ""
    exit 1
else
    echo "   ✅ Aucun localhost dans le build"
fi

# Vérifier l'API URL dans le build
api_url=$(grep -o "https://work-tracking.online/api" public/build/assets/*.js | head -1)
if [ -n "$api_url" ]; then
    echo "   ✅ API URL correcte trouvée: $api_url"
else
    echo "   ⚠️  API URL production non trouvée (peut être obfusquée)"
fi

# 6. Installer Composer
echo ""
echo "📥 Installation Composer..."
composer install --no-dev --optimize-autoloader --no-interaction
echo "   ✅ Composer installé"

# 7. Optimiser Laravel
echo ""
echo "⚡ Optimisation Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "   ✅ Laravel optimisé"

# 8. Statistiques
echo ""
echo "📊 Statistiques du build:"
echo "   Taille totale: $(du -sh public/build | cut -f1)"
echo "   Fichiers JS: $(find public/build/assets -name "*.js" | wc -l)"
echo "   Fichiers CSS: $(find public/build/assets -name "*.css" | wc -l)"

# 9. Afficher les premiers fichiers
echo ""
echo "📄 Premiers fichiers générés:"
ls -lh public/build/assets/*.{js,css} 2>/dev/null | head -5

# 10. Créer l'archive
echo ""
echo "📦 Création de l'archive..."

tar -czf deploy-production.tar.gz \
    --exclude='node_modules' \
    --exclude='.git' \
    --exclude='.env' \
    --exclude='.env.local' \
    --exclude='.env.production' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/data/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='tests' \
    --exclude='.phpunit.result.cache' \
    --exclude='*.sh' \
    --exclude='*.md' \
    --exclude='work-tracking' \
    .

echo "   ✅ Archive créée: deploy-production.tar.gz"
echo "   📊 Taille: $(du -h deploy-production.tar.gz | cut -f1)"

echo ""
echo "========================================="
echo "✅ BUILD TERMINÉ AVEC SUCCÈS!"
echo "========================================="
echo ""
echo "📤 Prochaines étapes:"
echo "1. Uploadez deploy-production.tar.gz sur Hostinger"
echo "2. Extrayez dans domains/work-tracking.online/public_html/"
echo "3. Créez le .env avec les vraies credentials"
echo "4. Permissions: chmod 755 storage bootstrap/cache -R"
echo "5. Testez: https://work-tracking.online"
echo ""