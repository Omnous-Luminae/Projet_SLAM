# Guide d'Installation - Système d'Archivage et de Cryptage

## Vue d'ensemble
Ce guide explique comment installer et configurer le système d'archivage et de cryptage des réservations passées dans HAP.

## Prérequis

- PHP 7.2 ou supérieur
- Extension OpenSSL activée
- Accès à la base de données MySQL
- Permissions de création de fichiers et dossiers

## Étape 1: Vérifier les prérequis PHP

```bash
php -i | grep -E "(OpenSSL|hash)"
```

Vous devez voir:
- `OpenSSL support => enabled`
- `hash => enabled`

## Étape 2: Créer les tables de base de données

### Option A: Utiliser le script SQL fourni

```bash
cd /chemin/vers/Projet_HAP
mysql -u root -p Project_HAP < sql/archive_reservations.sql
```

### Option B: Exécuter les requêtes manuellement

```sql
-- Ouvrir une session MySQL
mysql -u root -p

-- Utiliser la base de données
USE Project_HAP;

-- Créer les tables (voir le fichier sql/archive_reservations.sql)
CREATE TABLE IF NOT EXISTS Reservation_Archive ( ... );
CREATE TABLE IF NOT EXISTS Archive_Log ( ... );
```

## Étape 3: Créer les répertoires nécessaires

```bash
# Créer les répertoires pour les logs et la clé
mkdir -p /chemin/vers/Projet_HAP/logs
mkdir -p /chemin/vers/Projet_HAP/config

# Définir les permissions correctes
chmod 700 /chemin/vers/Projet_HAP/logs
chmod 700 /chemin/vers/Projet_HAP/config
```

## Étape 4: Générer la clé de cryptage

La clé est générée automatiquement au premier appel de `EncryptionManager`.

Pour la générer manuellement:

```php
<?php
require_once 'classes/EncryptionManager.php';

$encryption = new EncryptionManager($pdo);
echo "Clé de cryptage générée avec succès";
?>
```

Ou en ligne de commande:

```bash
php -r "
require_once 'config/db.php';
require_once 'classes/EncryptionManager.php';
\$encryption = new EncryptionManager(\$pdo);
echo 'Clé générée';
"
```

### Vérifier la clé

```bash
ls -la /chemin/vers/Projet_HAP/config/.encryption_key
```

Les permissions doivent être `600`:

```bash
chmod 600 /chemin/vers/Projet_HAP/config/.encryption_key
```

## Étape 5: Tester le système

```bash
php /chemin/vers/Projet_HAP/test_archive_system.php
```

Vous devriez voir:

```
=== TEST DU SYSTÈME D'ARCHIVAGE ===

Test 1: Initialisation de EncryptionManager... ✓ OK
Test 2: Cryptage et décryptage des données... ✓ OK
Test 3: Génération de clé dérivée... ✓ OK
Test 4: Initialisation de ReservationArchive... ✓ OK
Test 5: Vérification des tables... ✓ OK
Test 6: Vérification des réservations... ✓ OK
...

=== RÉSUMÉ DES TESTS ===
✓ Tous les tests essentiels sont passés avec succès!

Le système d'archivage est prêt à être utilisé.
```

## Étape 6: Configurer l'archivage automatique (optionnel)

### Sur Linux/Unix (utiliser Cron)

```bash
# Ouvrir l'éditeur crontab
crontab -e

# Ajouter cette ligne pour archiver chaque jour à 2h du matin
0 2 * * * /usr/bin/php /chemin/vers/Projet_HAP/cron_archive_reservations.php >> /chemin/vers/Projet_HAP/logs/cron.log 2>&1

# Ou pour exécuter toutes les 6 heures
0 */6 * * * /usr/bin/php /chemin/vers/Projet_HAP/cron_archive_reservations.php >> /chemin/vers/Projet_HAP/logs/cron.log 2>&1
```

Vérifier que le cron est configuré:

```bash
crontab -l
```

### Sur Windows (Planificateur de tâches)

1. **Ouvrir le Planificateur de tâches**
   - Presser `Win + R`
   - Taper `taskschd.msc` et appuyer sur Entrée

2. **Créer une nouvelle tâche**
   - Clic droit sur "Planificateur de tâches (local)"
   - Sélectionner "Créer une tâche..."

3. **Onglet "Général"**
   - Nom: `HAP - Archive Reservations`
   - Description: `Archive automatiquement les réservations passées`
   - Cocher "Exécuter avec les autorisations les plus élevées"

4. **Onglet "Déclencheurs"**
   - Clic sur "Nouveau"
   - Débuter la tâche: `Selon un planning`
   - Fréquence: `Quotidienne`
   - Heure: `02:00` (2h du matin)

5. **Onglet "Actions"**
   - Clic sur "Nouveau"
   - Action: `Démarrer un programme`
   - Programme: `C:\xampp\php\php.exe`
   - Arguments: `C:\xampp\php\Projet_HAP\cron_archive_reservations.php`

6. **Cliquer OK**

## Étape 7: Tester l'archivage manuel

Accédez à l'interface web:

```
http://localhost/Projet_HAP/forms/manage_archives.php
```

Vérifier que:
- La page se charge correctement
- Les réservations passées s'affichent
- Le bouton "Archiver Tous les Anciens" fonctionne

## Étape 8: Vérifier les archives en base de données

```sql
SELECT * FROM Reservation_Archive;
SELECT * FROM Archive_Log;
```

## Configuration avancée

### Modifier la politique de rétention

Éditer `config/archive_config.php`:

```php
'retention' => [
    'keep_archives_days' => 2555,    // Changer la durée de conservation
    'auto_delete_old' => true,       // Activer la suppression automatique
],
```

### Personnaliser les champs archivés

Éditer `config/archive_config.php`:

```php
'archived_fields' => [
    'locataire' => [
        // Ajouter/retirer des champs
    ],
    'bien' => [
        // Ajouter/retirer des champs
    ],
],
```

## Dépannage

### Problème: "OpenSSL not available"

**Solution:**
```bash
# Vérifier que OpenSSL est installé
php -m | grep openssl

# Sur Ubuntu/Debian
sudo apt-get install php-openssl

# Sur CentOS
sudo yum install php-openssl
```

### Problème: "Permission denied" sur la clé

**Solution:**
```bash
chmod 600 config/.encryption_key
```

### Problème: Le cronjob ne s'exécute pas

**Solution:**
1. Vérifier que le cron est actif
2. Vérifier les droits du fichier PHP
3. Consulter les logs du système

```bash
# Voir les logs du cron
sudo tail -f /var/log/syslog | grep cron

# Tester l'exécution manuelle
php /chemin/vers/Projet_HAP/cron_archive_reservations.php
```

### Problème: Les archives ne se créent pas

**Solution:**
1. Vérifier les permissions sur les tables
2. Vérifier les logs PHP
3. S'assurer que des réservations passées existent

```bash
# Vérifier les erreurs PHP
tail -f /var/log/php-fpm.log
```

## Maintenance régulière

### Vérifier la santé du système

```bash
# Consulter les logs
cat logs/archive.log
cat logs/archive_errors.log
cat logs/cron.log

# Compter les archives
mysql -u root -p Project_HAP -e "SELECT COUNT(*) FROM Reservation_Archive;"

# Vérifier l'espace disque
du -sh *
```

### Sauvegarder les archives

```bash
# Exporter les archives en SQL
mysqldump -u root -p Project_HAP Reservation_Archive > backup_archives.sql

# Compresser
gzip backup_archives.sql
```

## Sécurité

### Points de sécurité importants

1. **Clé de cryptage**
   - Ne pas partager le fichier `.encryption_key`
   - Utiliser des permissions strictes (600)
   - Sauvegarder régulièrement en lieu sûr

2. **Accès à l'interface**
   - Restreindre à l'administrateur
   - Implémenter l'authentification
   - Enregistrer tous les accès

3. **Logs**
   - Réviser régulièrement les logs
   - Archiver les anciens logs
   - Protéger les fichiers de log

## Support

En cas de problème:

1. Consulter les logs: `logs/archive*.log`
2. Lancer les tests: `php test_archive_system.php`
3. Vérifier la configuration: `config/archive_config.php`
4. Consulter la documentation: `ARCHIVE_SYSTEM_README.md`

## Fichiers créés/modifiés

- ✓ `sql/archive_reservations.sql` - Schéma de base de données
- ✓ `classes/EncryptionManager.php` - Gestion du cryptage
- ✓ `classes/ReservationArchive.php` - Gestion des archives
- ✓ `config/archive_config.php` - Configuration
- ✓ `forms/manage_archives.php` - Interface de gestion
- ✓ `api/get_archive_details.php` - API pour consulter les archives
- ✓ `cron_archive_reservations.php` - Tâche programmée
- ✓ `test_archive_system.php` - Tests du système
- ✓ `ARCHIVE_SYSTEM_README.md` - Documentation
- ✓ `INSTALL_GUIDE.md` - Ce fichier

## Étapes suivantes

1. ✓ Installation complétée
2. Tester l'interface: `forms/manage_archives.php`
3. Configurer le cronjob pour l'archivage automatique
4. Mettre à jour les backups régulièrement
5. Réviser les logs mensuellement

---

**Date d'installation:** <?php echo date('Y-m-d H:i:s'); ?>
