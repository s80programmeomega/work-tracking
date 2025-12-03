#!/bin/bash

set -e  # Arrêter en cas d'erreur

echo "========================================="
echo "🚀 BUILD PRODUCTION - WORK TRACKING"
echo "========================================="
echo ""

# 1. Nettoyer complètement
echo "🧹 Nettoyage complet..."
rm -rf public/build
rm -rf node_modules
rm -rf vendor
php artisan cache:clear 2>/dev/null || true
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
echo "   ✅ Nettoyage terminé"
echo ""

# 2. Installer les dépendances Composer
echo "📥 Installation des dépendances Composer..."
composer install --no-dev --optimize-autoloader --no-interaction
echo "   ✅ Composer installé"
echo ""

# 3. Installer les dépendances NPM
echo "📥 Installation des dépendances NPM..."
npm ci
echo "   ✅ NPM installé"
echo ""

# 4. Build avec les variables de production
echo "🎨 Build du frontend (mode production)..."
NODE_ENV=production npm run build -- --mode production
echo "   ✅ Build terminé"
echo ""

# 5. Vérification du build
echo "🔍 Vérification du build..."

# Vérifier manifest.json (peut être dans .vite/ ou à la racine)
if [ -f "public/build/manifest.json" ]; then
    MANIFEST_PATH="public/build/manifest.json"
elif [ -f "public/build/.vite/manifest.json" ]; then
    MANIFEST_PATH="public/build/.vite/manifest.json"
    echo "   ⚠️  Manifest trouvé dans .vite/, déplacement..."
    mv public/build/.vite/manifest.json public/build/manifest.json
    MANIFEST_PATH="public/build/manifest.json"
else
    echo "   ❌ ERREUR: manifest.json manquant!"
    exit 1
fi

echo "   ✅ manifest.json présent : $MANIFEST_PATH"

# Vérifier les assets
if [ ! -d "public/build/assets" ]; then
    echo "   ❌ ERREUR: Dossier assets manquant!"
    exit 1
fi

asset_count=$(ls -1 public/build/assets | wc -l)
echo "   ✅ $asset_count fichiers dans assets/"

# Vérifier qu'il n'y a pas de localhost dans le build
if grep -rq "localhost\|127.0.0.1" public/build/; then
    echo "   ⚠️  WARNING: 'localhost' ou '127.0.0.1' trouvé dans le build!"
fi
echo ""

# 6. Optimisation Laravel
echo "⚡ Optimisation Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "   ✅ Optimisation terminée"
echo ""

# 7. Afficher les stats du build
echo "📊 Statistiques du build:"
echo "   Taille totale: $(du -sh public/build | cut -f1)"
echo "   Manifest.json:"
head -5 public/build/manifest.json
echo "   ..."
echo ""

# 8. Créer l'archive
echo "📦 Création de l'archive de déploiement..."
tar -czf deploy.tar.gz \
    --exclude='node_modules' \
    --exclude='.git' \
    --exclude='.env*' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='tests' \
    --exclude='.phpunit.result.cache' \
    --exclude='*.sh' \
    .

echo "   ✅ Archive créée: deploy.tar.gz"
echo "   📊 Taille: $(du -h deploy.tar.gz | cut -f1)"
echo ""

echo "========================================="
echo "✅ BUILD TERMINÉ AVEC SUCCÈS!"
echo "========================================="
echo ""
echo "📋 Prochaines étapes:"
echo "1. Uploadez deploy.tar.gz sur votre serveur"
echo "2. Extrayez dans domains/work-tracking.online/public_html/"
echo "3. Configurez le .env de production"
echo "4. Exécutez post-deploy.php"
echo ""
