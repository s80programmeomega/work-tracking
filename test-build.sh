#!/bin/bash

echo "🔍 TEST DU BUILD LOCAL"
echo "======================"
echo ""

# 1. Vérifier la structure
echo "📁 Structure du build:"
if [ -d "public/build" ]; then
    echo "   ✅ Dossier public/build existe"
    
    if [ -f "public/build/manifest.json" ]; then
        echo "   ✅ manifest.json présent"
        echo ""
        echo "   📄 Contenu (10 premières lignes):"
        head -10 public/build/manifest.json | sed 's/^/      /'
    else
        echo "   ❌ manifest.json MANQUANT"
        exit 1
    fi
    
    if [ -d "public/build/assets" ]; then
        asset_count=$(ls -1 public/build/assets | wc -l)
        echo "   ✅ Dossier assets: $asset_count fichiers"
    else
        echo "   ❌ Dossier assets MANQUANT"
        exit 1
    fi
else
    echo "   ❌ Dossier public/build MANQUANT"
    echo "   👉 Exécutez d'abord: npm run build"
    exit 1
fi

echo ""
echo "🔍 Recherche de problèmes..."

# 2. Chercher localhost
echo ""
echo "Recherche de 'localhost':"
if grep -r "localhost" public/build/ 2>/dev/null | grep -v ".map" | head -3; then
    echo "   ⚠️  WARNING: localhost trouvé!"
else
    echo "   ✅ Aucun localhost"
fi

# 3. Chercher 127.0.0.1
echo ""
echo "Recherche de '127.0.0.1':"
if grep -r "127.0.0.1" public/build/ 2>/dev/null | grep -v ".map" | head -3; then
    echo "   ⚠️  WARNING: 127.0.0.1 trouvé!"
else
    echo "   ✅ Aucun 127.0.0.1"
fi

# 4. Vérifier la config vite
echo ""
echo "🔍 Vérification vite.config.js..."
if grep -q "base.*'/build/'" vite.config.js; then
    echo "   ❌ PROBLÈME: base contient '/build/'"
    echo "   👉 Changez en: base: '/'"
else
    echo "   ✅ Configuration base correcte"
fi

# 5. Tailles
echo ""
echo "📊 Tailles des fichiers:"
echo "   Build total: $(du -sh public/build | cut -f1)"
echo "   Assets: $(du -sh public/build/assets | cut -f1)"

echo ""
echo "✅ Test terminé"