/**
 * Module JavaScript pour le système de favoris
 * Ajoute les fonctionnalités de favoris aux cartes de biens
 */

const FavorisManager = {
    init: function() {
        // Initialiser tous les boutons favoris sur la page
        document.querySelectorAll('.favorite-btn, .heart-btn').forEach(btn => {
            const bienId = btn.dataset.bienId;
            if (bienId) {
                this.checkFavoriteStatus(bienId, btn);
            }
        });
    },

    checkFavoriteStatus: function(bienId, button) {
        fetch(`../api/favoris.php?action=check&bien_id=${bienId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.is_favorite) {
                    button.classList.add('active');
                    button.innerHTML = '❤️';
                }
            })
            .catch(error => console.error('Erreur vérification favori:', error));
    },

    toggle: function(bienId, button) {
        fetch('../api/favoris.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=toggle&bien_id=${bienId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.is_favorite) {
                    button.classList.add('active');
                    button.innerHTML = '❤️';
                    this.showToast('Ajouté aux favoris ❤️');
                } else {
                    button.classList.remove('active');
                    button.innerHTML = '🤍';
                    this.showToast('Retiré des favoris');
                }
                // Animation du bouton
                button.style.transform = 'scale(1.3)';
                setTimeout(() => {
                    button.style.transform = 'scale(1)';
                }, 200);
                // Rafraîchir la liste des favoris dans le profil si présente
                if (document.getElementById('tab-favoris')) {
                    FavorisManager.refreshProfileFavoris();
                }
            } else if (data.login_required) {
                // Rediriger vers la connexion
                if (confirm('Vous devez être connecté pour ajouter des favoris. Voulez-vous vous connecter ?')) {
                    window.location.href = '../auth/connexion.php?redirect=' + encodeURIComponent(window.location.href);
                }
            } else {
                this.showToast('Erreur: ' + data.error, true);
            }
        })
        .catch(error => {
            this.showToast('Erreur de connexion', true);
        });
    },

    refreshProfileFavoris: function() {
        // Appel AJAX pour récupérer la liste à jour
        fetch('../api/favoris.php?action=list')
            .then(response => response.json())
            .then(data => {
                if (data.success && document.getElementById('tab-favoris')) {
                    const container = document.querySelector('#tab-favoris .favoris-grid');
                    if (!container) return;
                    // Nettoyer
                    container.innerHTML = '';
                    if (data.favoris.length === 0) {
                        container.innerHTML = '<div class="empty-state"><div class="icon">💔</div><p>Vous n\'avez pas encore de favoris.<br>Explorez les annonces et ajoutez vos coups de cœur !</p><a href="../forms/Annonce.form.php" class="view-all-link">🏠 Découvrir les biens</a></div>';
                    } else {
                        data.favoris.slice(0, 6).forEach(function(bien) {
                            const card = document.createElement('div');
                            card.className = 'favori-card';
                            card.innerHTML = `
                                <div class="image-container">
                                    ${bien.photo ? `<img src="${bien.photo}" alt="${bien.nom_biens}">` : '<div class="no-image">🏠</div>'}
                                    ${bien.type_bien ? `<span class="badge">${bien.type_bien}</span>` : ''}
                                    <span class="heart-icon">❤️</span>
                                </div>
                                <div class="content">
                                    <h4 class="title"><a href="../forms/annonce_detail.php?id=${bien.id_biens}">${bien.nom_biens}</a></h4>
                                    <div class="location">📍 ${bien.nom_commune || 'Non spécifié'}${bien.code_postal ? ` (${bien.code_postal})` : ''}</div>
                                    <div class="rating">${'⭐'.repeat(Math.round(bien.note_moyenne || 0))}${'☆'.repeat(5 - Math.round(bien.note_moyenne || 0))} <span style="color: var(--text-secondary); font-size: 0.85em;">(${bien.nb_avis || 0} avis)</span></div>
                                </div>
                            `;
                            container.appendChild(card);
                        });
                    }
                }
            });
    },

    showToast: function(message, isError = false) {
        // Créer le toast s'il n'existe pas
        let toast = document.getElementById('favoris-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'favoris-toast';
            toast.style.cssText = `
                position: fixed;
                bottom: 30px;
                right: 30px;
                background: var(--bg-card, #fff);
                color: var(--text-primary, #1e293b);
                padding: 15px 25px;
                border-radius: 12px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.15);
                transform: translateY(100px);
                opacity: 0;
                transition: all 0.3s;
                z-index: 10000;
                font-family: 'Montserrat', sans-serif;
            `;
            document.body.appendChild(toast);
        }
        
        toast.textContent = message;
        toast.style.borderLeft = isError ? '4px solid #ef4444' : '4px solid #10b981';
        toast.style.transform = 'translateY(0)';
        toast.style.opacity = '1';
        
        setTimeout(() => {
            toast.style.transform = 'translateY(100px)';
            toast.style.opacity = '0';
        }, 3000);
    },

    getFavoritesCount: function(callback) {
        fetch('../api/favoris.php?action=count')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    callback(data.count);
                }
            })
            .catch(error => console.error('Erreur comptage favoris:', error));
    }
};

// Initialiser automatiquement au chargement
document.addEventListener('DOMContentLoaded', function() {
    FavorisManager.init();
});

// Exporter pour utilisation globale
window.FavorisManager = FavorisManager;
