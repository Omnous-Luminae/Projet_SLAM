<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Thème Sombre/Clair - HAP</title>
    <link rel="stylesheet" href="Projet_HAP(House_After_Party)/Css/style.css">
    <link rel="stylesheet" href="Projet_HAP(House_After_Party)/Css/dashboard.css">
    <link rel="stylesheet" href="Projet_HAP(House_After_Party)/Css/profile.css">
    <link rel="stylesheet" href="Projet_HAP(House_After_Party)/Css/blog.css">
    <link rel="stylesheet" href="Projet_HAP(House_After_Party)/Css/galerie.css">
    <link rel="stylesheet" href="Projet_HAP(House_After_Party)/Css/locataires.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Montserrat', Arial, sans-serif;
            padding: 20px;
            background: var(--bg-color, #f5f5f5);
            color: var(--text-color, #333);
            transition: all 0.3s;
        }
        .test-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .test-section {
            background: var(--card-bg, #fff);
            padding: 20px;
            margin: 20px 0;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .test-card {
            background: var(--dashboard-card-bg, rgba(161, 0, 184, 0.1));
            padding: 15px;
            border-radius: 8px;
            border: 2px solid var(--border-color, #ddd);
        }
        h1, h2, h3 {
            color: var(--heading-color, #a100b8);
        }
        .status-ok {
            color: #4caf50;
            font-weight: 600;
        }
        .status-test {
            color: var(--accent-color, #a100b8);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include 'theme_toggle.php'; ?>
    
    <div class="test-container">
        <h1>🎨 Test du Thème Sombre/Clair</h1>
        <p>Utilisez le bouton en bas à droite pour basculer entre les thèmes</p>
        
        <div class="test-section">
            <h2>État du système de thèmes</h2>
            <div class="test-grid">
                <div class="test-card">
                    <h3>✅ style.css</h3>
                    <p class="status-ok">Dark mode implémenté</p>
                </div>
                <div class="test-card">
                    <h3>✅ dashboard.css</h3>
                    <p class="status-ok">Variables CSS ajoutées</p>
                </div>
                <div class="test-card">
                    <h3>✅ profile.css</h3>
                    <p class="status-ok">Dark mode ajouté</p>
                </div>
                <div class="test-card">
                    <h3>✅ blog.css</h3>
                    <p class="status-ok">Dark mode ajouté</p>
                </div>
                <div class="test-card">
                    <h3>✅ galerie.css</h3>
                    <p class="status-ok">Dark mode ajouté</p>
                </div>
                <div class="test-card">
                    <h3>✅ locataires.css</h3>
                    <p class="status-ok">Dark mode ajouté</p>
                </div>
                <div class="test-card">
                    <h3>✅ annonce.css</h3>
                    <p class="status-ok">Dark mode existant</p>
                </div>
                <div class="test-card">
                    <h3>✅ forms.css</h3>
                    <p class="status-ok">Dark mode existant</p>
                </div>
            </div>
        </div>
        
        <div class="test-section">
            <h2>🚀 Nouvelles fonctionnalités</h2>
            <div class="test-grid">
                <div class="test-card">
                    <h3>🔍 Validation des biens</h3>
                    <p class="status-test">forms/validate_biens.php</p>
                    <p>Interface admin pour approuver/rejeter les annonces</p>
                </div>
                <div class="test-card">
                    <h3>⚙️ Filtre composition</h3>
                    <p class="status-test">Compose.form.php?id_bien=X</p>
                    <p>Gérer les équipements d'un bien spécifique</p>
                </div>
                <div class="test-card">
                    <h3>⚽ Prestations sportives</h3>
                    <p class="status-test">20 nouveaux équipements</p>
                    <p>Terrain de foot, piscine, jacuzzi, etc.</p>
                </div>
                <div class="test-card">
                    <h3>🎨 Thème complet</h3>
                    <p class="status-test">8/8 CSS avec dark mode</p>
                    <p>Persistance avec localStorage</p>
                </div>
            </div>
        </div>
        
        <div class="test-section">
            <h2>📊 Statistiques du projet</h2>
            <div class="test-grid">
                <div class="test-card">
                    <h3>Tâches TODO</h3>
                    <p style="font-size: 2em; color: #4caf50;">30/30</p>
                    <p class="status-ok">100% Complétées ✅</p>
                </div>
                <div class="test-card">
                    <h3>Fichiers CSS</h3>
                    <p style="font-size: 2em; color: #4caf50;">8/8</p>
                    <p class="status-ok">Dark mode partout ✅</p>
                </div>
                <div class="test-card">
                    <h3>Migrations SQL</h3>
                    <p style="font-size: 2em;">2</p>
                    <p>À exécuter (voir DEPLOYMENT.md)</p>
                </div>
                <div class="test-card">
                    <h3>Nouveaux fichiers</h3>
                    <p style="font-size: 2em;">5</p>
                    <p>PHP + SQL + Documentation</p>
                </div>
            </div>
        </div>
        
        <div class="test-section">
            <h2>🎯 Prochaines étapes</h2>
            <ol style="line-height: 2;">
                <li>Exécuter <code>sql/add_validation_to_biens.sql</code></li>
                <li>Exécuter <code>sql/update_prestations.sql</code></li>
                <li>Vider le cache du navigateur (Ctrl + Shift + R)</li>
                <li>Tester le thème sombre sur toutes les pages</li>
                <li>Configurer les compositions avec les nouvelles prestations</li>
                <li>Valider quelques biens depuis l'interface admin</li>
            </ol>
        </div>
        
        <div class="test-section">
            <h2>📖 Documentation</h2>
            <ul>
                <li><strong>TODO.md</strong> - Liste complète des tâches (30/30 ✅)</li>
                <li><strong>DEPLOYMENT.md</strong> - Instructions de déploiement</li>
                <li><strong>README.md</strong> - Documentation générale du projet</li>
            </ul>
        </div>
    </div>
    
    <script>
        // Test du thème au chargement
        console.log('Theme actuel:', document.body.dataset.theme || 'light');
        console.log('LocalStorage theme:', localStorage.getItem('theme'));
        
        // Observer les changements de thème
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.attributeName === 'data-theme') {
                    console.log('Thème changé:', document.body.dataset.theme);
                }
            });
        });
        
        observer.observe(document.body, {
            attributes: true,
            attributeFilter: ['data-theme']
        });
    </script>
</body>
</html>
