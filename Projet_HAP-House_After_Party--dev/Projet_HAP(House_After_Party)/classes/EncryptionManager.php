<?php

/**
 * Classe EncryptionManager
 * Gère le cryptage et décryptage des données sensibles des réservations
 */
class EncryptionManager {
    
    private $encryption_algorithm = 'AES-256-CBC';
    private $hash_algorithm = 'sha256';
    private $pdo;
    private $encryption_key;
    
    /**
     * Constructeur
     * @param PDO $pdo Connexion à la base de données
     * @param string $encryption_key Clé de cryptage principale
     */
    public function __construct($pdo, $encryption_key = null) {
        $this->pdo = $pdo;
        
        // Si aucune clé n'est fournie, on la génère ou on la récupère
        if ($encryption_key === null) {
            $encryption_key = $this->getOrCreateEncryptionKey();
        }
        
        // Valider et normaliser la clé
        $this->encryption_key = hash($this->hash_algorithm, $encryption_key, true);
    }
    
    /**
     * Récupère ou crée la clé de cryptage principale
     * @return string
     */
    private function getOrCreateEncryptionKey() {
        // Clé par défaut (à remplacer par une clé sécurisée en production)
        $key_file = __DIR__ . '/../../config/.encryption_key';
        
        if (file_exists($key_file)) {
            return file_get_contents($key_file);
        } else {
            // Générer une nouvelle clé - compatible PHP 5.3+
            $new_key = bin2hex(openssl_random_pseudo_bytes(32));
            @mkdir(dirname($key_file), 0700, true);
            @file_put_contents($key_file, $new_key, LOCK_EX);
            @chmod($key_file, 0600);
            return $new_key;
        }
    }
    
    /**
     * Crypte les données
     * @param array $donnees Données à crypter
     * @return array ['encrypted' => données_cryptées, 'iv' => vecteur_initialisation, 'key_hash' => hash_clé]
     */
    public function encrypt($donnees) {
        // Convertir les données en JSON
        $json_data = json_encode($donnees, JSON_UNESCAPED_UNICODE);
        
        // Générer un vecteur d'initialisation unique
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->encryption_algorithm));
        
        // Crypter les données
        $encrypted = openssl_encrypt(
            $json_data,
            $this->encryption_algorithm,
            $this->encryption_key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        // Générer un HMAC pour vérifier l'intégrité
        $hmac = hash_hmac($this->hash_algorithm, $encrypted, $this->encryption_key, true);
        
        // Combiner IV + HMAC + données cryptées
        $final_encrypted = base64_encode($iv . $hmac . $encrypted);
        
        return [
            'encrypted' => $final_encrypted,
            'iv' => bin2hex($iv),
            'key_hash' => hash($this->hash_algorithm, $this->encryption_key)
        ];
    }
    
    /**
     * Décrypte les données
     * @param string $encrypted_data Données cryptées (format base64)
     * @return array|false Données décryptées ou false si erreur
     */
    public function decrypt($encrypted_data) {
        try {
            // Décoder le base64
            $raw = base64_decode($encrypted_data, true);
            if ($raw === false) {
                return false;
            }
            
            // Extraire les composants
            $iv_length = openssl_cipher_iv_length($this->encryption_algorithm);
            $hash_length = 32; // sha256
            
            $iv = substr($raw, 0, $iv_length);
            $hmac = substr($raw, $iv_length, $hash_length);
            $encrypted = substr($raw, $iv_length + $hash_length);
            
            // Vérifier l'intégrité avec HMAC
            $computed_hmac = hash_hmac($this->hash_algorithm, $encrypted, $this->encryption_key, true);
            if (!hash_equals($hmac, $computed_hmac)) {
                return false; // Les données ont été altérées
            }
            
            // Décrypter
            $decrypted = openssl_decrypt(
                $encrypted,
                $this->encryption_algorithm,
                $this->encryption_key,
                OPENSSL_RAW_DATA,
                $iv
            );
            
            if ($decrypted === false) {
                return false;
            }
            
            // Décoder le JSON
            return json_decode($decrypted, true);
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Hash une donnée sensible
     * @param string $data
     * @return string
     */
    public function hash($data) {
        return hash($this->hash_algorithm, $data);
    }
    
    /**
     * Génère une clé dérivée pour chaque réservation
     * @param int $reservation_id
     * @return string
     */
    public function generateDerivedKey($reservation_id) {
        return hash_hmac($this->hash_algorithm, 'reservation_' . $reservation_id, $this->encryption_key);
    }
}

?>
