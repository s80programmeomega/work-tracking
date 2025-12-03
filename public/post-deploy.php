<!-- public\post-deploy.php -->
<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

header('Content-Type: text/html; charset=utf-8');
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
</body>
</html>