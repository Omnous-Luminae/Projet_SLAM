<?php
/**
 * Script pour télécharger des images libres de droit et les associer aux biens
 * Images provenant de Unsplash (licence libre)
 */

require_once __DIR__ . '/../config/db.php';

// Images libres de droit d'Unsplash (locations de vacances, maisons, appartements)
$freeImages = [
    // Maisons
    'house' => [
        'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=800&q=80', // Maison moderne
        'https://images.unsplash.com/photo-1570129477492-45c003edd2be?w=800&q=80', // Maison avec jardin
        'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=800&q=80', // Villa luxe
        'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800&q=80', // Maison contemporaine
        'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=800&q=80', // Maison élégante
    ],
    // Appartements
    'apartment' => [
        'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800&q=80', // Salon moderne
        'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=800&q=80', // Appartement lumineux
        'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800&q=80', // Chambre design
        'https://images.unsplash.com/photo-1493809842364-78817add7ffb?w=800&q=80', // Intérieur cosy
        'https://images.unsplash.com/photo-1484154218962-a197022b5858?w=800&q=80', // Cuisine moderne
    ],
    // Chalets / Montagne
    'chalet' => [
        'https://images.unsplash.com/photo-1542718610-a1d656d1884c?w=800&q=80', // Chalet bois
        'https://images.unsplash.com/photo-1520984032042-162d526883e0?w=800&q=80', // Chalet neige
        'https://images.unsplash.com/photo-1605146769289-440113cc3d00?w=800&q=80', // Chalet montagne
        'https://images.unsplash.com/photo-1449158743715-0a90ebb6d2d8?w=800&q=80', // Cabane cosy
        'https://images.unsplash.com/photo-1518780664697-55e3ad937233?w=800&q=80', // Maison bois
    ],
    // Villas avec piscine
    'villa' => [
        'https://images.unsplash.com/photo-1613490493576-7fde63acd811?w=800&q=80', // Villa piscine
        'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=800&q=80', // Villa moderne
        'https://images.unsplash.com/photo-1580587771525-78b9dba3b914?w=800&q=80', // Villa luxueuse
        'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?w=800&q=80', // Villa jardin
        'https://images.unsplash.com/photo-1600047509807-ba8f99d2cdde?w=800&q=80', // Villa terrasse
    ],
    // Intérieurs généraux
    'interior' => [
        'https://images.unsplash.com/photo-1616137466211-f939a420be84?w=800&q=80', // Salon chaleureux
        'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?w=800&q=80', // Chambre élégante
        'https://images.unsplash.com/photo-1600121848594-d8644e57abab?w=800&q=80', // Salle de bain
        'https://images.unsplash.com/photo-1583608205776-bfd35f0d9f83?w=800&q=80', // Entrée maison
        'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=800&q=80', // Cuisine équipée
    ],
];

$uploadDir = __DIR__ . '/../images/uploads/';

// Créer le dossier s'il n'existe pas
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

echo "=== Telechargement d'images libres de droit ===\n\n";

// Récupérer tous les biens
$biens = $pdo->query('SELECT id_biens, nom_biens FROM Biens')->fetchAll(PDO::FETCH_ASSOC);

if (empty($biens)) {
    echo "Aucun bien trouve dans la base de donnees.\n";
    exit;
}

echo "Biens trouves : " . count($biens) . "\n\n";

// Compter les images par bien existantes
$existingPhotos = $pdo->query('SELECT id_biens, COUNT(*) as cnt FROM Photos GROUP BY id_biens')->fetchAll(PDO::FETCH_KEY_PAIR);

$imagesDownloaded = 0;
$errors = 0;

// Configurer le contexte pour le téléchargement
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n",
        'timeout' => 30
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ]
]);

foreach ($biens as $bien) {
    $id_biens = $bien['id_biens'];
    $nom = $bien['nom_biens'];
    
    // Vérifier si le bien a déjà des photos
    $hasPhotos = isset($existingPhotos[$id_biens]) && $existingPhotos[$id_biens] > 0;
    
    if ($hasPhotos) {
        echo "[$id_biens] $nom - A deja " . $existingPhotos[$id_biens] . " photo(s), ignore\n";
        continue;
    }
    
    echo "[$id_biens] $nom - Telechargement d'images...\n";
    
    // Déterminer le type d'images à télécharger selon le nom
    $nomLower = strtolower($nom);
    $category = 'interior'; // Par défaut
    
    if (strpos($nomLower, 'chalet') !== false || strpos($nomLower, 'montagne') !== false || strpos($nomLower, 'alp') !== false) {
        $category = 'chalet';
    } elseif (strpos($nomLower, 'villa') !== false || strpos($nomLower, 'piscine') !== false || strpos($nomLower, 'luxe') !== false) {
        $category = 'villa';
    } elseif (strpos($nomLower, 'appartement') !== false || strpos($nomLower, 'appart') !== false || strpos($nomLower, 'studio') !== false) {
        $category = 'apartment';
    } elseif (strpos($nomLower, 'maison') !== false || strpos($nomLower, 'house') !== false || strpos($nomLower, 'pavillon') !== false) {
        $category = 'house';
    }
    
    // Télécharger 2-3 images par bien
    $imagesToDownload = array_slice($freeImages[$category], 0, 3);
    
    // Ajouter une image d'intérieur
    $imagesToDownload[] = $freeImages['interior'][array_rand($freeImages['interior'])];
    
    foreach ($imagesToDownload as $index => $imageUrl) {
        try {
            echo "  Telechargement depuis: " . substr($imageUrl, 0, 60) . "...\n";
            
            // Télécharger l'image avec cURL si disponible
            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $imageUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                $imageData = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);
                
                if ($httpCode !== 200 || empty($imageData)) {
                    echo "  ! Erreur HTTP $httpCode: $error\n";
                    $errors++;
                    continue;
                }
            } else {
                // Fallback sur file_get_contents
                $imageData = @file_get_contents($imageUrl, false, $context);
                
                if ($imageData === false) {
                    echo "  ! Erreur telechargement\n";
                    $errors++;
                    continue;
                }
            }
            
            // Générer un nom unique
            $fileName = 'bien_' . $id_biens . '_' . uniqid() . '.jpg';
            $filePath = $uploadDir . $fileName;
            
            // Sauvegarder le fichier
            if (file_put_contents($filePath, $imageData)) {
                // Enregistrer en base
                $lienPhoto = 'Projet_HAP(House_After_Party)/images/uploads/' . $fileName;
                $nomPhoto = $nom . ' - Image ' . ($index + 1);
                
                $stmt = $pdo->prepare('INSERT INTO Photos (nom_photos, lien_photo, id_biens) VALUES (?, ?, ?)');
                $stmt->execute([$nomPhoto, $lienPhoto, $id_biens]);
                
                echo "  OK Image " . ($index + 1) . " telechargee (" . round(strlen($imageData)/1024) . " KB)\n";
                $imagesDownloaded++;
            }
        } catch (Exception $e) {
            echo "  X Erreur: " . $e->getMessage() . "\n";
            $errors++;
        }
        
        // Pause pour éviter le rate limiting
        usleep(500000); // 0.5 seconde
    }
    
    echo "\n";
}

echo "=== Termine ===\n";
echo "Images telechargees : $imagesDownloaded\n";
echo "Erreurs : $errors\n";
