# 🎵 House After Party (HAP)

> Plateforme de location de logements à proximité des lieux festifs

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql&logoColor=white)
![XAMPP](https://img.shields.io/badge/XAMPP-Apache-FB7A24?logo=xampp&logoColor=white)

---

## 📋 Table des matières

1. [Installation](#-installation)
2. [Configuration](#-configuration)
3. [Guide Utilisateur](#-guide-utilisateur)
4. [Guide Administrateur](#-guide-administrateur)
5. [Structure du projet](#-structure-du-projet)
6. [Fonctionnalités](#-fonctionnalités)
7. [Sécurité](#-sécurité)
8. [FAQ](#-faq)

---

## 🚀 Installation

### Prérequis

- **XAMPP** (ou équivalent) avec PHP 8.0+ et MySQL 8.0+
- Navigateur web moderne (Chrome, Firefox, Edge, Safari)

### Étapes d'installation

1. **Cloner/Copier le projet** dans le dossier `htdocs` de XAMPP :
   ```
   C:\xampp\htdocs\HAP\
   ```

2. **Démarrer XAMPP** :
   - Lancer Apache
   - Lancer MySQL

3. **Créer la base de données** :
   - Ouvrir phpMyAdmin : http://localhost/phpmyadmin
   - Créer une base de données nommée `project_hap`
   - Importer le fichier SQL :
     ```
     Projet_HAP(House_After_Party)/sql/base.sql
     ```
   - (Optionnel) Importer les données de test :
     ```
     Projet_HAP(House_After_Party)/sql/donnees_test_completes.sql
     ```

4. **Configurer la connexion** dans `Projet_HAP(House_After_Party)/config/db.php` :
   ```php
   $host = 'localhost';
   $dbname = 'project_hap';
   $username = 'root';
   $password = '';
   ```

5. **Accéder au site** : http://localhost/HAP/

---

## ⚙️ Configuration

### Base de données (`config/db.php`)

```php
$host = 'localhost';      // Hôte MySQL
$dbname = 'project_hap';  // Nom de la base
$username = 'root';       // Utilisateur MySQL
$password = '';           // Mot de passe MySQL
```

### Sécurité Admin (`config/admin_security.php`)

```php
// Clé secrète pour inscription admin (à changer !)
define('ADMIN_SECRET_KEY', 'VOTRE_CLE_SECRETE');

// Tentatives de connexion max
define('MAX_LOGIN_ATTEMPTS', 5);

// Durée de blocage (en secondes)
define('LOGIN_LOCKOUT_TIME', 900);
```

### Anti-spam (`config/spam_limits.php`)

Configuration des limites de soumission pour éviter le spam.

---

## 👤 Guide Utilisateur

### Inscription et Connexion

1. **S'inscrire** : Cliquer sur "Se connecter" → "Créer un compte"
   - Remplir le formulaire (nom, prénom, email, mot de passe)
   - Choisir le type de compte : Personne Physique ou Personne Morale

2. **Se connecter** : Entrer email et mot de passe

3. **Mot de passe oublié** : Cliquer sur "Mot de passe oublié" pour réinitialiser

### Navigation

| Page | Description |
|------|-------------|
| 🏠 **Accueil** | Présentation du site, témoignages, galerie |
| 📅 **Annonces** | Liste des logements disponibles |
| 🗺️ **Carte** | Carte interactive des logements |
| 🎵 **Points d'Intérêt** | Boîtes de nuit, bars, restaurants à proximité |
| 📝 **Blog** | Articles et avis des utilisateurs |
| 📞 **Contact** | Formulaire de contact et support |

### Réserver un logement

1. Parcourir les **Annonces**
2. Cliquer sur une annonce pour voir les détails
3. Consulter le **calendrier de disponibilité**
4. Sélectionner les dates et cliquer sur **Réserver**
5. Confirmer la réservation

### Laisser un avis

1. Aller sur la page **Blog**
2. Cliquer sur "Laisser un avis"
3. Sélectionner le bien concerné
4. Donner une note (1-5 étoiles) et écrire un commentaire
5. L'avis sera visible après validation par un administrateur

### Contacter le support

1. Aller sur la page **Contact**
2. Choisir le type de demande :
   - ❓ **Question** : Questions générales
   - 🚨 **Signalement** : Signaler un contenu ou utilisateur
   - 🐛 **Bug/Erreur** : Problème technique
   - 💡 **Suggestion** : Idée d'amélioration
   - 📝 **Autre** : Autre demande
3. Définir la priorité (Basse → Urgente)
4. Remplir le formulaire et envoyer
5. Un numéro de ticket vous sera attribué

### Thème clair/sombre

Cliquer sur le bouton 🌙/☀️ en bas à droite pour basculer entre les thèmes.

---

## 🛠️ Guide Administrateur

### Accès au Dashboard

1. Se connecter avec un compte **Animateur**
2. Cliquer sur "🛠️ Dashboard Admin" dans le header

### Inscription Admin

1. Aller sur `/Projet_HAP(House_After_Party)/auth/inscription_admin.php`
2. Entrer la **clé secrète admin** (définie dans `admin_security.php`)
3. Remplir le formulaire d'inscription

### Fonctionnalités Admin

| Module | Description |
|--------|-------------|
| 🏠 **Gestion des Biens** | Ajouter, modifier, supprimer des logements |
| 🔍 **Validation des Biens** | Valider/refuser les nouveaux biens |
| 🎭 **Validation des Avis** | Modérer les avis des utilisateurs |
| 👥 **Gestion des Locataires** | Gérer les comptes utilisateurs |
| 📅 **Gestion des Réservations** | Suivre et gérer les réservations |
| 🎉 **Gestion des Événements** | Organiser des événements |
| 🎵 **Points d'Intérêt** | Gérer les lieux (boîtes, bars, etc.) |
| 💰 **Gestion des Tarifs** | Définir les prix par saison |
| 📢 **Gestion des Annonces** | Créer/modifier les annonces |
| 🗓️ **Gestion des Saisons** | Définir les périodes (haute/basse saison) |
| 📬 **Messages & Support** | Répondre aux tickets utilisateurs |
| 🔐 **Archives** | Consulter les réservations archivées |

### Gestion des Messages/Tickets

1. Aller dans **Messages & Support**
2. Filtrer par statut, type ou priorité
3. Actions disponibles :
   - Changer le statut (Nouveau → En cours → Résolu → Fermé)
   - **Répondre** au ticket (la réponse sera visible par l'utilisateur)
   - Supprimer le ticket

### Sélecteur rapide

Utiliser le menu déroulant "Aller directement à un formulaire" pour accéder rapidement à n'importe quel module.

---

## 📁 Structure du projet

```
HAP/
├── index.php                    # Page d'accueil
├── contact.php                  # Page de contact
├── apropos.php                  # Dashboard Admin
├── about.php                    # À propos
├── theme_toggle.php             # Bouton thème clair/sombre
│
└── Projet_HAP(House_After_Party)/
    ├── api/                     # Endpoints API (AJAX)
    │   ├── search_biens.php
    │   ├── get_reservations.php
    │   └── ...
    │
    ├── auth/                    # Authentification
    │   ├── connexion.php        # Connexion utilisateur
    │   ├── inscription.php      # Inscription utilisateur
    │   ├── connexion_admin.php  # Connexion admin
    │   ├── inscription_admin.php# Inscription admin
    │   ├── profile.php          # Profil utilisateur
    │   ├── forgot_password.php  # Mot de passe oublié
    │   └── logout.php           # Déconnexion
    │
    ├── classes/                 # Classes PHP (POO)
    │   ├── Biens/
    │   ├── Locataire/
    │   ├── Reservation/
    │   └── ...
    │
    ├── config/                  # Configuration
    │   ├── db.php               # Connexion BDD
    │   ├── admin_security.php   # Sécurité admin
    │   └── spam_limits.php      # Limites anti-spam
    │
    ├── Css/                     # Feuilles de style
    │   ├── style.css            # Style principal
    │   ├── dashboard.css        # Style dashboard
    │   ├── forms.css            # Style formulaires
    │   └── ...
    │
    ├── forms/                   # Formulaires de gestion
    │   ├── Annonce.form.php     # Gestion annonces
    │   ├── Bien.form.php        # Gestion biens
    │   ├── Reservation.form.php # Gestion réservations
    │   ├── validate_biens.php   # Validation biens
    │   ├── validate_reviews.php # Validation avis
    │   ├── manage_contacts.php  # Gestion tickets
    │   ├── blog.php             # Blog/Avis
    │   └── ...
    │
    ├── images/                  # Images
    │   ├── uploads/             # Photos uploadées
    │   │   └── poi/             # Photos points d'intérêt
    │   └── upload.php           # Script d'upload
    │
    ├── js/                      # Scripts JavaScript
    │   ├── autocomplete.js
    │   ├── validation.js
    │   └── ...
    │
    └── sql/                     # Scripts SQL
        ├── base.sql             # Structure BDD
        └── donnees_test_completes.sql
```

---

## ✨ Fonctionnalités

### Utilisateurs
- ✅ Inscription/Connexion (Personne Physique ou Morale)
- ✅ Profil utilisateur modifiable
- ✅ Réinitialisation mot de passe
- ✅ Système de favoris
- ✅ Historique des réservations
- ✅ Système d'avis avec notes
- ✅ Suivi des tickets de support

### Recherche & Navigation
- ✅ Recherche de biens avec filtres
- ✅ Carte interactive
- ✅ Points d'intérêt à proximité
- ✅ Galerie photos avec lightbox
- ✅ Thème clair/sombre

### Réservations
- ✅ Calendrier de disponibilité
- ✅ Calcul automatique du tarif
- ✅ Gestion des saisons (haute/basse)
- ✅ Confirmation par email

### Administration
- ✅ Dashboard avec statistiques
- ✅ Validation des biens et avis
- ✅ Gestion complète CRUD
- ✅ Système de tickets/support
- ✅ Archives cryptées

### Sécurité
- ✅ Protection CSRF
- ✅ Hashage des mots de passe (bcrypt)
- ✅ Protection contre les injections SQL (PDO)
- ✅ Limitation des tentatives de connexion
- ✅ Clé secrète pour inscription admin

---

## 🔒 Sécurité

### Bonnes pratiques

1. **Changer la clé admin** dans `config/admin_security.php`
2. **Ne jamais committer** le fichier de configuration en production
3. **Utiliser HTTPS** en production
4. **Sauvegarder** régulièrement la base de données
5. **Mettre à jour** PHP et MySQL régulièrement

### Fichiers sensibles à protéger

```
config/db.php              # Identifiants BDD
config/admin_security.php  # Clé admin
```

### En production

- Activer `ADMIN_EMAIL_VERIFICATION`
- Restreindre `ADMIN_ALLOWED_IPS`
- Activer les logs `ADMIN_LOG_ENABLED`

---

## ❓ FAQ

### Comment devenir administrateur ?

1. Obtenir la clé secrète admin auprès d'un admin existant
2. Aller sur `/Projet_HAP(House_After_Party)/auth/inscription_admin.php`
3. Entrer la clé et créer votre compte

### Je ne peux pas me connecter

- Vérifier email/mot de passe
- Après 5 tentatives échouées, attendre 15 minutes
- Utiliser "Mot de passe oublié"

### Comment ajouter un bien ?

1. Se connecter en tant qu'admin
2. Dashboard → Gestion des Biens
3. Remplir le formulaire et ajouter des photos
4. Le bien sera visible après validation

### Les images ne s'affichent pas

- Vérifier les permissions du dossier `images/uploads/`
- S'assurer que les chemins sont corrects dans la BDD

### Comment changer le thème ?

Cliquer sur l'icône 🌙/☀️ en bas à droite de l'écran.

---

## 📞 Support

Pour toute question ou problème :
- **Email** : contact@hap.fr
- **Formulaire** : Page Contact du site
- **Documentation** : Ce fichier README

---

## 📝 Licence

© 2025 House After Party - Tous droits réservés.

---

*Fait avec ❤️ pour les amoureux des nuits blanches*
