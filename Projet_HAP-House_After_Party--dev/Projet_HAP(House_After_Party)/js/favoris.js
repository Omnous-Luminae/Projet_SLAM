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
