# 🎯 Guide d'utilisation - Nouvelles fonctionnalités HAP

## 🌓 Mode Sombre/Clair

### Activation
1. Cliquer sur le bouton flottant en bas à droite de n'importe quelle page
2. Icône 🌙 = Mode clair actif → Cliquer pour passer en mode sombre
3. Icône ☀️ = Mode sombre actif → Cliquer pour passer en mode clair

### Persistance
- Votre choix est automatiquement sauvegardé
- Le thème persiste même après fermeture du navigateur
- Fonctionne sur toutes les pages du site

### Pages supportées
✅ Page d'accueil (index.php)
✅ Dashboard admin (apropos.php)
✅ Tous les formulaires admin
✅ Profil utilisateur
✅ Blog/Avis
✅ Galerie photos
✅ Gestion des locataires
✅ Annonces

---

## 🔍 Validation des Biens (Admin uniquement)

### Accès
1. Se connecter en tant qu'administrateur (animateur)
2. Aller sur le **Dashboard Administrateur**
3. Cliquer sur **"🔍 Validation des Biens"**

### Onglet "En attente"
Affiche tous les biens qui attendent validation.

**Informations affichées:**
- Nom du bien
- Type (maison, appartement, etc.)
- Propriétaire (nom/raison sociale)
- Email du propriétaire
- Commune et adresse complète
- Superficie en m²
- Nombre de couchages
- Autorisation d'animaux
- Description complète

**Actions possibles:**
- ✅ **Valider**: Approuve le bien (il devient visible publiquement)
- ❌ **Refuser**: Supprime définitivement le bien

### Onglet "Validés"
Historique des 20 derniers biens validés.

**Informations affichées:**
- Nom du bien
- Type
- Propriétaire
- Commune
- Nom de l'admin qui a validé
- Date et heure de validation

### Workflow recommandé
1. Un utilisateur crée une nouvelle annonce
2. Le bien est automatiquement en attente (`validated = FALSE`)
3. L'admin reçoit une notification (à implémenter)
4. L'admin vérifie les informations
5. L'admin valide ou refuse
6. Si validé, le bien apparaît dans les recherches publiques

---

## ⚽ Nouvelles Prestations Sportives

### Accès
1. Dashboard Admin → **"🎭 Gestion des Prestations"**

### 20 équipements disponibles
1. Terrain de football ⚽
2. Terrain de tennis 🎾
3. Terrain de basketball 🏀
4. Piscine privée 🏊
5. Jacuzzi 🛁
6. Sauna 🧖
7. Salle de sport 💪
8. Terrain de pétanque 🎯
9. Table de ping-pong 🏓
10. Baby-foot ⚽
11. Billard 🎱
12. Salle de jeux 🎮
13. Home cinéma 🎬
14. Studio de musique 🎵
15. Espace barbecue 🍖
16. Terrain de volley 🏐
17. Court de badminton 🏸
18. Piste de danse 💃
19. Bar privé 🍹
20. Cave à vin 🍷

### Ajouter une prestation personnalisée
1. Aller sur "Gestion des Prestations"
2. Taper le nom (ex: "Trampoline", "Salle de cinéma")
3. Cliquer sur **"Ajouter"**
4. La prestation est immédiatement disponible

---

## ⚙️ Gestion des Compositions par Bien

### Méthode 1: Navigation directe
1. Aller sur **"Gestion des Biens"**
2. Trouver le bien à configurer
3. Cliquer sur **"⚙️ Composition"** pour ce bien
4. Vous arrivez sur la page de composition filtrée
5. Seules les prestations de ce bien sont affichées

### Méthode 2: Filtre manuel
1. Aller sur **"Gestion des Compositions"**
2. Utiliser le menu déroulant en haut de page
3. Sélectionner un bien
4. La page se recharge avec uniquement ce bien

### Ajouter une prestation à un bien
1. Filtrer par bien (méthode 1 ou 2)
2. Sélectionner le bien dans le formulaire
3. Sélectionner la prestation (ex: "Piscine privée")
4. Entrer la quantité (ex: 1)
5. Cliquer sur **"Ajouter"**

**Exemples de quantités:**
- Piscine privée: 1
- Terrain de tennis: 2 (s'il y en a deux)
- Table de ping-pong: 1
- Terrain de football: 1

### Modifier une quantité
1. Trouver la composition dans la liste
2. Cliquer sur **"Modifier"**
3. Changer la quantité
4. Cliquer sur **"Enregistrer"**

### Supprimer une prestation
1. Trouver la composition dans la liste
2. Cliquer sur **"Supprimer"**
3. Confirmer la suppression

### Réinitialiser le filtre
Cliquer sur le bouton **"Réinitialiser le filtre"** pour voir toutes les compositions

---

## 🎨 Personnalisation du Thème (Développeurs)

### Structure des variables CSS

Chaque fichier CSS utilise des variables CSS pour le thème:

```css
:root {
    --color-name: #value-light;
}

[data-theme="dark"] {
    --color-name: #value-dark;
}

.element {
    background: var(--color-name);
}
```

### Ajouter le support dark mode à un nouveau fichier

1. Définir les variables en haut du fichier:
```css
:root {
    --bg: #ffffff;
    --text: #333333;
    --accent: #a100b8;
}

[data-theme="dark"] {
    --bg: #1e1e1e;
    --text: #bb86fc;
    --accent: #bb86fc;
}
```

2. Utiliser les variables dans les styles:
```css
body {
    background: var(--bg);
    color: var(--text);
}
```

3. Inclure `theme_toggle.php` dans la page:
```php
<?php include 'theme_toggle.php'; ?>
```

---

## 📱 Interface Mobile

### Mode sombre
Le bouton de thème est adaptatif:
- Desktop: Fixé en bas à droite
- Mobile: Réduit en taille, toujours accessible

### Compositions
Le filtre par bien fonctionne sur mobile:
- Menu déroulant tactile
- Navigation simplifiée

### Validation
Interface responsive:
- Cartes empilées sur petit écran
- Boutons adaptés au toucher

---

## 🔔 Notifications (À venir)

### Suggestions d'amélioration
1. **Email aux admins** quand un nouveau bien attend validation
2. **Badge de notification** sur le dashboard (nombre de biens en attente)
3. **Historique complet** de validation (journal d'audit)

---

## 🆘 Dépannage

### Le thème ne change pas
1. Vider le cache du navigateur (Ctrl + Shift + R)
2. Vérifier la console JavaScript (F12)
3. Vérifier que `theme_toggle.php` est inclus dans la page

### Les biens n'apparaissent pas après validation
1. Vérifier que `validated = TRUE` dans la base de données
2. Rafraîchir la page des annonces
3. Vérifier les filtres de recherche

### Les compositions ne s'affichent pas
1. Vérifier que le bien a au moins une prestation
2. Vérifier le paramètre `?id_bien=X` dans l'URL
3. Rafraîchir la page

### Erreur lors de l'ajout de prestation
1. Vérifier que les migrations SQL ont été exécutées
2. Vérifier la connexion à la base de données
3. Consulter les logs d'erreur PHP

---

## 📊 Statistiques et Rapports

### Dashboard Admin
- Total de biens validés
- Total de biens en attente
- Total de prestations disponibles
- Total de compositions configurées

### Requête SQL pour statistiques avancées

```sql
-- Biens par statut de validation
SELECT 
    CASE WHEN validated = TRUE THEN 'Validé' ELSE 'En attente' END as statut,
    COUNT(*) as nombre
FROM Biens
GROUP BY validated;

-- Top 10 prestations les plus utilisées
SELECT 
    p.lib_prestation,
    COUNT(*) as nb_biens,
    SUM(c.quantite) as quantite_totale
FROM Compose c
JOIN Prestation p ON c.id_prestation = p.id_prestation
GROUP BY p.id_prestation
ORDER BY nb_biens DESC
LIMIT 10;

-- Admins les plus actifs (validations)
SELECT 
    a.nom_animateur,
    COUNT(*) as nb_validations
FROM Biens b
JOIN Animateur a ON b.validated_by = a.id_animateur
WHERE b.validated = TRUE
GROUP BY a.id_animateur
ORDER BY nb_validations DESC;
```

---

## 🎓 Formation Admin

### Checklist onboarding nouvel admin

- [ ] Se connecter avec compte animateur
- [ ] Explorer le dashboard
- [ ] Tester le bouton de thème
- [ ] Créer une prestation de test
- [ ] Ajouter une composition à un bien
- [ ] Valider un bien en attente
- [ ] Consulter l'historique des validations
- [ ] Tester le filtre par bien

### Bonnes pratiques

1. **Validation des biens**: Vérifier toutes les informations avant validation
2. **Prestations**: Utiliser des noms clairs et cohérents
3. **Compositions**: Mettre des quantités réalistes
4. **Thème**: Respecter le choix de l'utilisateur (ne pas forcer)

---

## 📞 Support Technique

### Fichiers de documentation
- `README.md` - Documentation générale
- `TODO.md` - Liste des tâches (30/30 ✅)
- `DEPLOYMENT.md` - Instructions de déploiement
- `SUMMARY.md` - Résumé complet
- `GUIDE_UTILISATION.md` - Ce fichier

### Contact développeur
En cas de problème technique, contacter le développeur avec:
- Description du problème
- Capture d'écran
- Message d'erreur (si applicable)
- Navigateur utilisé
- Étapes pour reproduire

---

## 🎉 Félicitations !

Vous êtes maintenant prêt à utiliser toutes les nouvelles fonctionnalités du projet HAP.

**Bon courage et bonne gestion ! 🚀**
