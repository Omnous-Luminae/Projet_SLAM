# 🎵 House After Party (HAP)

Plateforme web complète et collaborative de location de logements à proximité des lieux festifs, pensée pour les fêtards, organisateurs d’événements, propriétaires et gestionnaires. HAP centralise la recherche, la réservation, la gestion, la sécurisation des séjours, la modération communautaire et l’administration avancée.

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql&logoColor=white)
![XAMPP](https://img.shields.io/badge/XAMPP-Apache-FB7A24?logo=xampp&logoColor=white)

---

## 📖 Introduction détaillée

**House After Party** (HAP) est une solution tout-en-un pour faciliter la location de logements lors d’événements festifs. Elle vise à :

- Offrir un moteur de recherche puissant pour trouver un logement adapté à la fête (proximité, capacité, équipements, ambiance, etc.).
- Sécuriser les transactions et la gestion des réservations.
- Permettre aux propriétaires de valoriser leurs biens auprès d’une clientèle ciblée.
- Proposer un espace communautaire (avis, blog, favoris, support).
- Fournir aux administrateurs des outils de gestion, de modération et d’archivage avancés.

Le site est conçu pour être :
- **Intuitif** : navigation fluide, interface responsive, thème clair/sombre.
- **Sécurisé** : protection CSRF, hashage, validation, gestion des accès.
- **Modulaire** : chaque fonctionnalité est pensée comme un module indépendant et réutilisable.
- **Scalable** : architecture prête pour l’évolution (ajout de modules, API REST, etc.).

---

## 📋 Sommaire

1. [Présentation du site](#présentation-du-site)
2. [Fonctionnement général](#fonctionnement-général)
3. [Cas d’usage & scénarios](#cas-dusage--scénarios)
4. [Installation & Configuration](#installation--configuration)
5. [Navigation & Modules](#navigation--modules)
6. [Architecture technique](#architecture-technique)
7. [Déploiement & Conseils](#déploiement--conseils)
8. [Sécurité & Bonnes pratiques](#sécurité--bonnes-pratiques)
9. [FAQ enrichie](#faq-enrichie)
10. [Support & Licence](#support--licence)

---

## Présentation du site

**House After Party** est une plateforme de location de logements axée sur la proximité des lieux festifs (clubs, bars, salles de concert, festivals, etc.).

### Objectifs principaux

- Simplifier la recherche et la réservation de logements pour les participants à des événements festifs.
- Offrir un espace sécurisé et communautaire pour les utilisateurs et propriétaires.
- Centraliser la gestion, la modération et l’archivage pour les administrateurs.

### Valeurs ajoutées

- **Recherche intelligente** : suggestions, filtres avancés, carte interactive.
- **Réservation rapide** : calendrier dynamique, calcul automatique des tarifs, confirmation instantanée.
- **Communauté** : avis, blog, favoris, support réactif.
- **Administration avancée** : dashboard, validation, modération, archives chiffrées.

---

## Fonctionnement général

### Utilisateurs

- **Inscription/Connexion** :
   - Création de compte (personne physique ou morale)
   - Connexion sécurisée (limitation brute-force, récupération de mot de passe)
   - Gestion du profil, historique, favoris

- **Recherche & Navigation** :
   - Filtres avancés (prix, capacité, type, distance, équipements, ambiance)
   - Carte interactive (affichage des biens sur une carte, POI à proximité)
   - Galerie photos, favoris, blog

- **Réservation** :
   - Consultation du calendrier de disponibilité
   - Calcul automatique du tarif selon la saison, le nombre de nuits, les options
   - Réservation en ligne, confirmation par email, historique des réservations

- **Avis & Blog** :
   - Système d’avis notés (1 à 5 étoiles), modération par admin
   - Blog communautaire (partage d’expériences, conseils, retours)

- **Support** :
   - Système de tickets, priorisation, suivi des demandes, notifications par email

### Propriétaires

- **Gestion des annonces** :
   - Ajout, modification, suppression de biens
   - Gestion des photos, calendrier de disponibilité, tarifs

- **Suivi des réservations** :
   - Validation, historique, gestion des tarifs et saisons

### Administrateurs

- **Dashboard** :
   - Statistiques globales (utilisateurs, réservations, avis, tickets)
   - Accès rapide à tous les modules

- **Validation/Modération** :
   - Biens, avis, utilisateurs, réservations

- **Gestion des tickets** :
   - Filtrage, réponse, clôture, historique

- **Archives** :
   - Archivage sécurisé et crypté des réservations, consultation avancée

---

## Cas d’usage & scénarios

### 1. Utilisateur lambda (Paul, 23 ans)

Paul souhaite participer à un festival. Il utilise HAP pour :
- Rechercher un logement proche du festival avec 4 couchages et parking.
- Consulter les avis, voir la galerie photo, ajouter le bien à ses favoris.
- Réserver pour 2 nuits, payer en ligne, recevoir la confirmation par email.
- Laisser un avis après son séjour.

### 2. Propriétaire (Sophie, 35 ans)

Sophie possède un appartement en centre-ville :
- Elle crée une annonce, ajoute des photos, définit les tarifs selon la saison.
- Elle valide les réservations reçues, consulte l’historique, modifie le calendrier.
- Elle répond aux avis et gère ses biens via son espace propriétaire.

### 3. Administrateur (Julien, 29 ans)

Julien supervise la plateforme :
- Il valide les nouveaux biens, modère les avis, gère les utilisateurs.
- Il répond aux tickets de support, archive les réservations terminées.
- Il consulte les statistiques et exporte les données pour analyse.

---

## Installation & Configuration

### Prérequis

- XAMPP (ou équivalent) avec PHP 8.0+ et MySQL 8.0+
- Navigateur web moderne (Chrome, Firefox, Edge, Safari)

### Installation pas à pas

1. Copier le projet dans `C:\xampp\htdocs\HAP\`
2. Démarrer Apache et MySQL via XAMPP
3. Créer la base de données `project_hap` via phpMyAdmin
4. Importer le script SQL principal :
    - `Projet_HAP(House_After_Party)/sql/projet_hap.sql`
    - (optionnel) Importer les données de test : `Projet_HAP(House_After_Party)/sql/donnees_test_completes.sql`
5. Configurer la connexion BDD dans `Projet_HAP(House_After_Party)/config/db.php`
6. Accéder au site : http://localhost/HAP/

### Configuration détaillée

- **Base de données** : Modifier les identifiants dans `config/db.php`
- **Sécurité admin** : Définir la clé secrète, les limites de connexion, etc. dans `config/admin_security.php`
- **Anti-spam** : Ajuster les limites dans `config/spam_limits.php`
- **Environnement** : Adapter les variables dans `archive_config.env.php` si besoin

---

## Navigation & Modules

### Pages principales

| Page | Rôle | Description |
|------|------|-------------|
| Accueil | Public | Présentation, témoignages, galerie |
| Annonces | Public | Liste filtrable des logements |
| Carte | Public | Carte interactive des biens |
| Points d’intérêt | Public | Clubs, bars, restaurants à proximité |
| Blog | Public | Avis, articles, retours d’expérience |
| Contact | Public | Formulaire de support/ticket |
| Profil | Utilisateur | Gestion du compte, favoris, historique |
| Dashboard | Admin | Supervision, statistiques, accès modules |

### Modules fonctionnels

- **Recherche avancée** :
   - Filtres (prix, capacité, type, distance, équipements, ambiance)
   - Suggestions automatiques, autocomplete, recherche par carte

- **Réservation** :
   - Sélection de dates, affichage dynamique du tarif
   - Gestion des conflits de réservation, confirmation par email

- **Favoris** :
   - Ajout/suppression rapide, consultation dans le profil

- **Avis** :
   - Attribution de notes, commentaires, validation par admin avant publication

- **Support** :
   - Système de tickets avec priorisation, suivi, réponse par email

- **Thème clair/sombre** :
   - Basculer à tout moment via le bouton dédié

### Administration

- **Gestion des biens** : CRUD complet, validation, archivage
- **Gestion des utilisateurs** : Validation, blocage, réinitialisation
- **Gestion des avis** : Modération, suppression, publication différée
- **Gestion des réservations** : Suivi, validation, archivage
- **Gestion des tarifs/saisons** : Définition des périodes, ajustement dynamique des prix
- **Gestion des points d’intérêt** : Ajout, modification, suppression de POI
- **Gestion des tickets/support** : Filtrage, réponse, clôture, historique
- **Archives** : Chiffrement et consultation des réservations archivées

---

## Architecture technique

### Structure du projet

```
HAP/
├── index.php                # Accueil
├── contact.php              # Contact/support
├── apropos.php              # Dashboard admin
├── about.php                # À propos
├── theme_toggle.php         # Thème clair/sombre
└── Projet_HAP(House_After_Party)/
      ├── api/                 # Endpoints AJAX/API
      ├── auth/                # Authentification (utilisateur/admin)
      ├── classes/             # Classes PHP (POO)
      ├── config/              # Fichiers de configuration
      ├── Css/                 # Styles CSS
      ├── forms/               # Formulaires de gestion
      ├── images/              # Images et uploads
      ├── includes/            # Fichiers inclusions PHP
      ├── js/                  # Scripts JavaScript
      ├── scripts/             # Scripts d’automatisation
      └── sql/                 # Scripts SQL
```

#### Schéma textuel des modules principaux

```
Utilisateur
    │
    ├──> Recherche (API/search_biens.php)
    │         │
    │         └──> Affichage Annonces (forms/Annonce.form.php)
    │
    ├──> Réservation (API/update_reservation.php)
    │         │
    │         └──> Paiement/Confirmation (email)
    │
    ├──> Avis (forms/blog.php, API/validate_reviews.php)
    │
    └──> Support (forms/manage_contacts.php)

Propriétaire
    │
    ├──> Gestion Biens (forms/Bien.form.php)
    ├──> Gestion Calendrier (forms/Reservation.form.php)
    └──> Suivi Réservations (forms/manage_archives.php)

Admin
    │
    ├──> Dashboard (forms/dashboard.php)
    ├──> Validation (forms/validate_biens.php, forms/validate_reviews.php)
    ├──> Gestion Utilisateurs (forms/Locataires.form.php)
    ├──> Gestion Tickets (forms/manage_contacts.php)
    └──> Archives (classes/ReservationArchive.php)
```

### Logique technique

- **PHP 8+** : Backend, logique métier, sécurité, gestion des sessions, API.
- **MySQL 8+** : Stockage des données, relations, transactions.
- **JavaScript** : Interactivité, AJAX, validation côté client, carte interactive.
- **CSS** : Thème responsive, animations, dark/light mode.
- **Sécurité** : CSRF, hashage bcrypt, PDO, limitation brute-force, validation serveur.

### Points clés

- **POO** : Toutes les entités principales sont modélisées en classes (Biens, Locataires, Réservations, etc.).
- **API interne** : Les appels AJAX passent par des endpoints sécurisés (dossier `api/`).
- **Séparation des rôles** : Utilisateur, Propriétaire, Admin, Animateur.
- **Archivage** : Les réservations archivées sont chiffrées et consultables uniquement par les admins.

---

## Déploiement & Conseils

### Déploiement local (XAMPP)

1. Copier le dossier dans `htdocs`.
2. Vérifier les droits d’écriture sur `images/uploads/`.
3. Adapter les chemins dans les fichiers de config si besoin.
4. Tester toutes les fonctionnalités (inscription, réservation, avis, support).

### Déploiement production

- Utiliser un hébergement compatible PHP 8+ et MySQL 8+.
- Activer HTTPS (SSL/TLS).
- Protéger les fichiers sensibles (`config/`, `.env`, etc.).
- Mettre à jour la clé admin et les variables d’environnement.
- Activer les logs et la surveillance.
- Sauvegarder régulièrement la base de données.

### Conseils d’optimisation

- Activer la compression GZIP côté serveur.
- Optimiser les images (taille, format WebP).
- Utiliser un cache navigateur pour les assets statiques.
- Sécuriser les permissions des dossiers d’upload.

---

## Sécurité & Bonnes pratiques

- **Clé admin** : À personnaliser dans `config/admin_security.php`.
- **Jamais committer les fichiers sensibles** (`db.php`, `admin_security.php`) en production.
- **HTTPS** : Obligatoire en production.
- **Logs** : Activer la journalisation des actions admin.
- **Sauvegardes** : Régulières de la BDD.
- **Mises à jour** : PHP/MySQL et dépendances à jour.
- **Protection CSRF** : Jetons sur tous les formulaires sensibles.
- **Hashage** : Mots de passe via bcrypt.
- **PDO** : Toutes les requêtes SQL sont préparées.
- **Limitation brute-force** : Blocage temporaire après X tentatives.
- **Validation serveur** : Toutes les entrées utilisateurs sont validées côté serveur.
- **Séparation des rôles** : Accès restreint selon le type de compte.

---

## FAQ enrichie

### Comment devenir administrateur ?

1. Obtenir la clé secrète admin auprès d’un admin existant.
2. Aller sur `/Projet_HAP(House_After_Party)/auth/inscription_admin.php`.
3. Entrer la clé et créer votre compte.

### Je ne peux pas me connecter

- Vérifier email/mot de passe
- Après 5 tentatives échouées, attendre 15 minutes
- Utiliser "Mot de passe oublié"
- Vérifier que votre compte est validé par un admin

### Comment ajouter un bien ?

1. Se connecter en tant qu’admin ou propriétaire
2. Dashboard → Gestion des Biens
3. Remplir le formulaire et ajouter des photos
4. Le bien sera visible après validation

### Les images ne s’affichent pas

- Vérifier les permissions du dossier `images/uploads/`
- S’assurer que les chemins sont corrects dans la BDD
- Vider le cache navigateur

### Comment changer le thème ?

Cliquer sur l’icône 🌙/☀️ en bas à droite de l’écran.

### Comment contacter le support ?

1. Aller sur la page Contact
2. Remplir le formulaire selon le type de demande (question, bug, suggestion, etc.)
3. Un ticket sera créé et suivi par l’équipe

### Comment fonctionne la validation des avis ?

Les avis sont soumis à validation par un administrateur avant publication pour garantir la qualité et la pertinence.

### Comment sont gérées les saisons et tarifs ?

L’admin définit les périodes de haute/basse saison et ajuste dynamiquement les tarifs selon la demande.

### Peut-on exporter les données ?

Oui, les administrateurs peuvent exporter les réservations, avis, utilisateurs au format CSV depuis le dashboard.

### Peut-on intégrer d’autres moyens de paiement ?

Le système est conçu pour être extensible (API REST, modules de paiement additionnels).

---

## Support & Licence

Pour toute question ou problème :

- **Email** : contact@hap.fr (ceci est un faux email)
- **Formulaire** : Page Contact du site
- **Documentation** : Ce fichier README

---

© 2025 House After Party - Tous droits réservés.

*Fait avec ❤️ pour les amoureux des nuits blanches*
