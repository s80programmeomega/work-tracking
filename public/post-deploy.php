<!-- public\post-deploy.php -->
<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

header('Content-Type: text/html; charset=utf-8');
// $kernel->call('storage:link');
// var_dump(symlink('/home/.../storage/app/public', '/home/.../public_html/storage'));

var_dump(function_exists('symlink'));

?>

<!DOCTYPE html>
<html>
<head>
    <title>Post-Deployment - Work Tracking</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #0f0; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .warning { color: #ff0; }
        .section { margin: 20px 0; padding: 10px; border: 1px solid #333; }
    </style>
</head>
<body>
    <h1>🚀 POST-DEPLOYMENT WORK TRACKING</h1>
    
    <div class="section">
        <h2>🧹 Nettoyage des caches...</h2>
        <?php
        try {
            $kernel->call('config:clear');
            echo "<p class='success'>✅ config:clear</p>";
            
            $kernel->call('cache:clear');
            echo "<p class='success'>✅ cache:clear</p>";
            
            $kernel->call('view:clear');
            echo "<p class='success'>✅ view:clear</p>";
            
            $kernel->call('route:clear');
            echo "<p class='success'>✅ route:clear</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Erreur: ".$e->getMessage()."</p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>⚡ Optimisation...</h2>
        <?php
        try {
            $kernel->call('config:cache');
            echo "<p class='success'>✅ config:cache</p>";
            
            $kernel->call('route:cache');
            echo "<p class='success'>✅ route:cache</p>";
            
            $kernel->call('view:cache');
            echo "<p class='success'>✅ view:cache</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Erreur: ".$e->getMessage()."</p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>📦 Vérification du build...</h2>
        <?php
        $buildDir = __DIR__.'/build';
        $manifestFile = $buildDir.'/manifest.json';
        
        if (is_dir($buildDir)) {
            echo "<p class='success'>✅ Dossier build existe</p>";
            
            if (file_exists($manifestFile)) {
                echo "<p class='success'>✅ manifest.json présent</p>";
                
                $manifest = json_decode(file_get_contents($manifestFile), true);
                echo "<p>📄 Nombre d'entrées: ".count($manifest)."</p>";
                
                // Afficher quelques clés
                $keys = array_keys($manifest);
                echo "<p>🔑 Premières entrées:</p><ul>";
                foreach (array_slice($keys, 0, 5) as $key) {
                    echo "<li>$key</li>";
                }
                echo "</ul>";
            } else {
                echo "<p class='error'>❌ manifest.json MANQUANT!</p>";
            }
            
            // Vérifier assets
            $assetsDir = $buildDir.'/assets';
            if (is_dir($assetsDir)) {
                $files = scandir($assetsDir);
                $count = count($files) - 2; // Enlever . et ..
                echo "<p class='success'>✅ Dossier assets: $count fichiers</p>";
            }
        } else {
            echo "<p class='error'>❌ Dossier build MANQUANT!</p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>🔐 Vérification des permissions...</h2>
        <?php
        $dirs = [
            'storage' => __DIR__.'/../storage',
            'bootstrap/cache' => __DIR__.'/../bootstrap/cache',
            'public/build' => __DIR__.'/build',
        ];
        
        foreach ($dirs as $name => $dir) {
            if (is_writable($dir)) {
                echo "<p class='success'>✅ $name (accessible en écriture)</p>";
            } else {
                echo "<p class='error'>❌ $name (non accessible en écriture)</p>";
            }
        }
        ?>
    </div>

    <div class="section">
        <h2>🌍 Configuration environnement...</h2>
        <?php
        echo "<p>APP_ENV: <strong>".env('APP_ENV')."</strong></p>";
        echo "<p>APP_URL: <strong>".env('APP_URL')."</strong></p>";
        echo "<p>ASSET_URL: <strong>".env('ASSET_URL', 'non défini')."</strong></p>";
        echo "<p>SESSION_DOMAIN: <strong>".env('SESSION_DOMAIN')."</strong></p>";
        ?>
    </div>

    <div class="section">
        <h2 class="success">✅ DÉPLOIEMENT TERMINÉ</h2>
        <p>🌐 Accédez à votre application: <a href="https://work-tracking.online" style="color: #0ff;">https://work-tracking.online</a></p>
        <p class="warning">⚠️  N'oubliez pas de supprimer ce fichier après vérification!</p>
    </div>
    
        <?php
    /**
     * Script de post-déploiement pour Hostinger
     * À exécuter via : https://work-tracking.online/post-deploy.php
     * 
     * ⚠️ SÉCURITÉ: Supprimer ce fichier après exécution !
     */
    
    // Activer l'affichage des erreurs
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    echo "<h1>🚀 Post-Déploiement Work Tracking</h1>";
    echo "<pre>";
    
    // Chemin de base (ajustez selon votre hébergement)
    $base_path = __DIR__;
    $storage_path = $base_path . '/storage/app/public';
    $public_storage = $base_path . '/public/storage';
    
    echo "===========================================\n";
    echo "📁 Chemins détectés\n";
    echo "===========================================\n";
    echo "Base       : $base_path\n";
    echo "Storage    : $storage_path\n";
    echo "Public/Storage : $public_storage\n\n";
    
    // ========================================
    // 1. PERMISSIONS
    // ========================================
    echo "===========================================\n";
    echo "🔐 Configuration des permissions\n";
    echo "===========================================\n";
    
    $dirs_to_chmod = [
        'storage',
        'storage/app',
        'storage/app/public',
        'storage/framework',
        'storage/framework/cache',
        'storage/framework/sessions',
        'storage/framework/views',
        'storage/logs',
        'bootstrap/cache',
        'public/storage'
    ];
    
    foreach ($dirs_to_chmod as $dir) {
        $full_path = $base_path . '/' . $dir;
        
        if (!is_dir($full_path)) {
            mkdir($full_path, 0755, true);
            echo "✅ Créé : $dir\n";
        }
        
        if (chmod($full_path, 0755)) {
            echo "✅ Permissions 755 : $dir\n";
        } else {
            echo "❌ Échec permissions : $dir\n";
        }
    }
     
    
    // ========================================
    // 3. VÉRIFICATIONS
    // ========================================
    echo "===========================================\n";
    echo "🔍 Vérifications\n";
    echo "===========================================\n";
    
    // Vérifier que le lien fonctionne
    if (is_dir($public_storage) || is_link($public_storage)) {
        echo "✅ public/storage est accessible\n";
        
        // Lister quelques fichiers
        $files = scandir($public_storage);
        $file_count = count($files) - 2; // Exclure . et ..
        echo "✅ Fichiers détectés : $file_count\n";
        
        if ($file_count > 0) {
            echo "   Exemples : \n";
            $shown = 0;
            foreach ($files as $file) {
                if ($file != '.' && $file != '..' && $shown < 5) {
                    echo "   - $file\n";
                    $shown++;
                }
            }
        }
    } else {
        echo "❌ public/storage n'est pas accessible\n";
    }
    
    echo "\n";
    
    // ========================================
    // 4. CACHE LARAVEL
    // ========================================
    echo "===========================================\n";
    echo "⚡ Nettoyage du cache\n";
    echo "===========================================\n";
    
    // Vider les caches manuellement
    $cache_dirs = [
        'bootstrap/cache/config.php',
        'bootstrap/cache/routes.php',
        'bootstrap/cache/packages.php',
        'bootstrap/cache/services.php',
    ];
    
    foreach ($cache_dirs as $cache_file) {
        $full_path = $base_path . '/' . $cache_file;
        if (file_exists($full_path)) {
            unlink($full_path);
            echo "🗑️  Supprimé : $cache_file\n";
        }
    }
    
    echo "\n";
    
    // ========================================
    // 5. TEST URL
    // ========================================
    echo "===========================================\n";
    echo "🌐 Test d'URL d'image\n";
    echo "===========================================\n";
    
    // Créer un fichier de test
    $test_file = $storage_path . '/test-image.txt';
    file_put_contents($test_file, 'Test OK - ' . date('Y-m-d H:i:s'));
    
    $test_url = 'https://work-tracking.online/storage/test-image.txt';
    echo "📝 Fichier test créé : $test_file\n";
    echo "🔗 URL de test : $test_url\n";
    echo "👉 Ouvrez cette URL dans votre navigateur pour vérifier\n";
    
    echo "\n";
    echo "===========================================\n";
    echo "✅ POST-DÉPLOIEMENT TERMINÉ\n";
    echo "===========================================\n";
    echo "\n";
    echo "⚠️  IMPORTANT : Supprimez ce fichier maintenant !\n";
    echo "   rm post-deploy.php\n";
    echo "\n";
    echo "</pre>";
    
    // ========================================
    // BONUS : Informations système
    // ========================================
    echo "<h2>📊 Informations système</h2>";
    echo "<pre>";
    echo "PHP Version    : " . phpversion() . "\n";
    echo "Laravel Version: " . (file_exists($base_path . '/artisan') ? 'Détecté' : 'Non trouvé') . "\n";
    echo "Server         : " . $_SERVER['SERVER_SOFTWARE'] ?? 'Inconnu' . "\n";
    echo "User           : " . get_current_user() . "\n";
    echo "Open basedir   : " . ini_get('open_basedir') . "\n";
    echo "</pre>";
    ?>
</body>
</html>