<?php
/**
 * Script pour télécharger des images libres de droit pour les Points d'Intérêt
 * Images provenant de Unsplash (licence libre)
 */

require_once __DIR__ . '/../config/db.php';

// Images libres de droit d'Unsplash classées par type de point d'intérêt
$freeImages = [
    // Monuments historiques
    'Monument historique' => [
        'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=800&q=80', // Tour Eiffel
        'https://images.unsplash.com/photo-1499856871958-5b9627545d1a?w=800&q=80', // Paris monument
        'https://images.unsplash.com/photo-1478391679764-b2d8b3cd1e94?w=800&q=80', // Arc de Triomphe
        'https://images.unsplash.com/photo-1431274172761-fca41d930114?w=800&q=80', // Paris ville
        'https://images.unsplash.com/photo-1555992336-03a23c7b20ee?w=800&q=80', // Monument ancien
    ],
    // Musées
    'Musée' => [
        'https://images.unsplash.com/photo-1565060169194-19fabf63012e?w=800&q=80', // Musée intérieur
        'https://images.unsplash.com/photo-1554907984-15263bfd63bd?w=800&q=80', // Galerie art
        'https://images.unsplash.com/photo-1566127444979-b3d2b654e3d7?w=800&q=80', // Musée moderne
        'https://images.unsplash.com/photo-1518998053901-5348d3961a04?w=800&q=80', // Exposition
        'https://images.unsplash.com/photo-1536924940846-227afb31e2a5?w=800&q=80', // Art contemporain
    ],
    // Restaurants
    'Restaurant' => [
        'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=800&q=80', // Restaurant élégant
        'https://images.unsplash.com/photo-1552566626-52f8b828add9?w=800&q=80', // Restaurant terrasse
        'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=800&q=80', // Restaurant cosy
        'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800&q=80', // Plat gastronomique
        'https://images.unsplash.com/photo-1544148103-0773bf10d330?w=800&q=80', // Ambiance resto
    ],
    // Plages
    'Plage' => [
        'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=800&q=80', // Plage tropicale
        'https://images.unsplash.com/photo-1519046904884-53103b34b206?w=800&q=80', // Plage sable
        'https://images.unsplash.com/photo-1473116763249-2faaef81ccda?w=800&q=80', // Coucher soleil plage
        'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800&q=80', // Plage panoramique
        'https://images.unsplash.com/photo-1520942702018-0862200e6873?w=800&q=80', // Plage méditerranée
    ],
    // Parcs d'attractions
    "Parc d'attractions" => [
        'https://images.unsplash.com/photo-1513889961551-628c1e5e2ee9?w=800&q=80', // Parc attractions
        'https://images.unsplash.com/photo-1536768139911-e290a59011e4?w=800&q=80', // Grande roue
        'https://images.unsplash.com/photo-1560713781-d00f6c18f388?w=800&q=80', // Manèges
        'https://images.unsplash.com/photo-1569154941061-e231b4725ef1?w=800&q=80', // Parc thème
        'https://images.unsplash.com/photo-1551524164-687a55dd1126?w=800&q=80', // Carousel
    ],
    // Théâtre/Cinéma
    'Théâtre/Cinéma' => [
        'https://images.unsplash.com/photo-1503095396549-807759245b35?w=800&q=80', // Salle théâtre
        'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=800&q=80', // Cinéma
        'https://images.unsplash.com/photo-1517604931442-7e0c8ed2963c?w=800&q=80', // Salle cinéma
        'https://images.unsplash.com/photo-1524712245354-2c4e5e7121c0?w=800&q=80', // Spectacle
        'https://images.unsplash.com/photo-1460881680093-7b6c6a7c6c18?w=800&q=80', // Scène
    ],
    // Parcs naturels
    'Parc naturel' => [
        'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=800&q=80', // Forêt
        'https://images.unsplash.com/photo-1426604966848-d7adac402bff?w=800&q=80', // Nature
        'https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?w=800&q=80', // Paysage vert
        'https://images.unsplash.com/photo-1447752875215-b2761acb3c5d?w=800&q=80', // Parc arbres
        'https://images.unsplash.com/photo-1501854140801-50d01698950b?w=800&q=80', // Nature panorama
    ],
    // Châteaux
    'Château' => [
        'https://images.unsplash.com/photo-1518998053901-5348d3961a04?w=800&q=80', // Château
        'https://images.unsplash.com/photo-1533154683836-84ea7a0bc310?w=800&q=80', // Château France
        'https://images.unsplash.com/photo-1567605544219-c6c2e3fdd04b?w=800&q=80', // Château Loire
        'https://images.unsplash.com/photo-1555109307-f7d9da25c244?w=800&q=80', // Château médiéval
        'https://images.unsplash.com/photo-1590001155093-a3c66ab0c3ff?w=800&q=80', // Château jardin
    ],
    // Églises/Cathédrales
    'Église/Cathédrale' => [
        'https://images.unsplash.com/photo-1548625149-fc4a29cf7092?w=800&q=80', // Cathédrale
        'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=800&q=80', // Église intérieur
        'https://images.unsplash.com/photo-1543349689-9a4d426bee8e?w=800&q=80', // Notre-Dame style
        'https://images.unsplash.com/photo-1555992457-b8fefdd09069?w=800&q=80', // Vitraux
        'https://images.unsplash.com/photo-1520263115673-610416f52ab6?w=800&q=80', // Architecture religieuse
    ],
    // Zoo/Aquarium
    'Zoo/Aquarium' => [
        'https://images.unsplash.com/photo-1534567153574-2b12153a87f0?w=800&q=80', // Aquarium
        'https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=800&q=80', // Poissons
        'https://images.unsplash.com/photo-1564349683136-77e08dba1ef7?w=800&q=80', // Girafe zoo
        'https://images.unsplash.com/photo-1503918756811-975bd3397178?w=800&q=80', // Zoo
        'https://images.unsplash.com/photo-1535941339077-2dd1c7963098?w=800&q=80', // Animaux
    ],
    // Casino
    'Casino' => [
        'https://images.unsplash.com/photo-1596838132731-3301c3fd4317?w=800&q=80', // Casino
        'https://images.unsplash.com/photo-1606167668584-78701c57f13d?w=800&q=80', // Roulette
        'https://images.unsplash.com/photo-1511193311914-0346f16efe90?w=800&q=80', // Cartes
        'https://images.unsplash.com/photo-1518895949257-7621c3c786d7?w=800&q=80', // Machines
    ],
    // Vignoble
    'Vignoble' => [
        'https://images.unsplash.com/photo-1506377247377-2a5b3b417ebb?w=800&q=80', // Vignoble
        'https://images.unsplash.com/photo-1560493676-04071c5f467b?w=800&q=80', // Vigne
        'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?w=800&q=80', // Dégustation vin
        'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&q=80', // Cave vin
        'https://images.unsplash.com/photo-1474722883778-792e7990302f?w=800&q=80', // Château viticole
    ],
    // Marché local
    'Marché local' => [
        'https://images.unsplash.com/photo-1488459716781-31db52582fe9?w=800&q=80', // Marché fruits
        'https://images.unsplash.com/photo-1542838132-92c53300491e?w=800&q=80', // Marché légumes
        'https://images.unsplash.com/photo-1533900298318-6b8da08a523e?w=800&q=80', // Marché local
        'https://images.unsplash.com/photo-1578916171728-46686eac8d58?w=800&q=80', // Étals marché
    ],
    // Site archéologique
    'Site archéologique' => [
        'https://images.unsplash.com/photo-1539650116574-8efeb43e2750?w=800&q=80', // Ruines
        'https://images.unsplash.com/photo-1564769625905-50e93615e769?w=800&q=80', // Site ancien
        'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&q=80', // Archéologie
    ],
    // Galerie d'art
    "Galerie d'art" => [
        'https://images.unsplash.com/photo-1577720643272-265f09367456?w=800&q=80', // Galerie
        'https://images.unsplash.com/photo-1531243269054-5ebf6f34081e?w=800&q=80', // Art moderne
        'https://images.unsplash.com/photo-1544967082-d9d25d867d66?w=800&q=80', // Exposition art
    ],
    // Observatoire
    'Observatoire' => [
        'https://images.unsplash.com/photo-1516339901601-2e1b62dc0c45?w=800&q=80', // Télescope
        'https://images.unsplash.com/photo-1507400492013-162706c8c05e?w=800&q=80', // Observatoire nuit
        'https://images.unsplash.com/photo-1419242902214-272b3f66ee7a?w=800&q=80', // Étoiles
    ],
    // Centre commercial
    'Centre commercial' => [
        'https://images.unsplash.com/photo-1519567241046-7f570eee3ce6?w=800&q=80', // Shopping
        'https://images.unsplash.com/photo-1555529669-e69e7aa0ba9a?w=800&q=80', // Centre commercial
        'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=800&q=80', // Boutiques
    ],
    // Randonnée
    'Randonnée' => [
        'https://images.unsplash.com/photo-1551632811-561732d1e306?w=800&q=80', // Randonnée montagne
        'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=800&q=80', // Montagne
        'https://images.unsplash.com/photo-1486870591958-9b9d0d1dda99?w=800&q=80', // Sentier
        'https://images.unsplash.com/photo-1454496522488-7a8e488e8606?w=800&q=80', // Sommet
    ],
    // Site naturel
    'Site naturel' => [
        'https://images.unsplash.com/photo-1433086966358-54859d0ed716?w=800&q=80', // Cascade
        'https://images.unsplash.com/photo-1439066615861-d1af74d74000?w=800&q=80', // Lac
        'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800&q=80', // Paysage
        'https://images.unsplash.com/photo-1472214103451-9374bd1c798e?w=800&q=80', // Nature
    ],
    // Activité sportive
    'Activité sportive' => [
        'https://images.unsplash.com/photo-1461896836934- voices77d7a7?w=800&q=80', // Sport
        'https://images.unsplash.com/photo-1530549387789-4c1017266635?w=800&q=80', // Natation
        'https://images.unsplash.com/photo-1551698618-1dfe5d97d256?w=800&q=80', // Ski
        'https://images.unsplash.com/photo-1502680390469-be75c86b636f?w=800&q=80', // Surf
    ],
    // Par défaut
    'default' => [
        'https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=800&q=80', // Nature générale
        'https://images.unsplash.com/photo-1501785888041-af3ef285b470?w=800&q=80', // Paysage
        'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=800&q=80', // Voyage
        'https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=800&q=80', // Destination
    ],
];

$uploadDir = __DIR__ . '/../images/uploads/poi/';

// Créer le dossier s'il n'existe pas
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
    echo "Dossier cree: $uploadDir\n";
}

echo "=== Telechargement d'images pour les Points d'Interet ===\n\n";

// Récupérer tous les points d'intérêt avec leur type
$sql = "SELECT p.id_pts_interet, p.lib_pts_interet, t.lib_type_points_interet as type_poi
        FROM Pts_Interet p
        JOIN Type_Pts_Interet t ON p.id_type_points_interet = t.id_type_points_interet
        ORDER BY p.id_pts_interet";
$pois = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

if (empty($pois)) {
    echo "Aucun point d'interet trouve dans la base de donnees.\n";
    exit;
}

echo "Points d'interet trouves : " . count($pois) . "\n\n";

// Compter les images par POI existantes
$existingPhotos = $pdo->query('SELECT id_pts_interet, COUNT(*) as cnt FROM Photos_PtsInteret GROUP BY id_pts_interet')->fetchAll(PDO::FETCH_KEY_PAIR);

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

foreach ($pois as $poi) {
    $id_poi = $poi['id_pts_interet'];
    $nom = $poi['lib_pts_interet'];
    $type = $poi['type_poi'];
    
    // Compter les photos existantes
    $currentPhotoCount = isset($existingPhotos[$id_poi]) ? $existingPhotos[$id_poi] : 0;
    
    // Ajouter des images même si le POI en a déjà (max 3 par POI)
    if ($currentPhotoCount >= 3) {
        echo "[$id_poi] $nom - A deja $currentPhotoCount photo(s), maximum atteint\n";
        continue;
    }
    
    echo "[$id_poi] $nom (Type: $type) - Ajout d'images...\n";
    
    // Sélectionner les images selon le type
    $category = isset($freeImages[$type]) ? $type : 'default';
    $availableImages = $freeImages[$category];
    
    // Télécharger jusqu'à 3 images par POI
    $maxToDownload = 3 - $currentPhotoCount;
    $imagesToDownload = array_slice($availableImages, 0, min(2, $maxToDownload));
    
    // Ajouter une image par défaut si on a de la place
    if (count($imagesToDownload) < $maxToDownload && $category !== 'default') {
        $imagesToDownload[] = $freeImages['default'][array_rand($freeImages['default'])];
    }
    
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
            $fileName = 'poi_' . $id_poi . '_' . uniqid() . '.jpg';
            $filePath = $uploadDir . $fileName;
            
            // Sauvegarder le fichier
            if (file_put_contents($filePath, $imageData)) {
                // Enregistrer en base
                $lienPhoto = 'Projet_HAP(House_After_Party)/images/uploads/poi/' . $fileName;
                
                $stmt = $pdo->prepare('INSERT INTO Photos_PtsInteret (lien_photo_pts, id_pts_interet) VALUES (?, ?)');
                $stmt->execute([$lienPhoto, $id_poi]);
                
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

// Afficher un résumé
$summary = $pdo->query('SELECT COUNT(*) FROM Photos_PtsInteret')->fetchColumn();
echo "\nTotal photos POI en base : $summary\n";
