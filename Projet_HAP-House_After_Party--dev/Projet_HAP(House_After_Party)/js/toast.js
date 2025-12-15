/**
 * Système de Notifications Toast Élégantes
 * Usage: Incluez ce fichier JS, puis appelez showToast(message, type)
 */

// Créer le conteneur de toasts au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    if (!document.getElementById('toast-container')) {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
});

/**
 * Afficher une notification toast
 * @param {string} message - Le message à afficher
 * @param {string} type - Type: 'success', 'error', 'warning', 'info'
 * @param {number} duration - Durée en ms (défaut: 4000)
 */
function showToast(message, type = 'info', duration = 4000) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    // Icônes selon le type
    const icons = {
        success: '✓',
        error: '✗',
        warning: '⚠',
        info: 'ℹ'
    };
    
    toast.innerHTML = `
        <div class="toast-icon">${icons[type] || 'ℹ'}</div>
        <div class="toast-message">${message}</div>
        <button class="toast-close" onclick="closeToast(this)">&times;</button>
    `;
    
    container.appendChild(toast);
    
    // Animation d'entrée
    setTimeout(() => toast.classList.add('toast-show'), 10);
    
    // Auto-fermeture
    setTimeout(() => {
        closeToast(toast);
    }, duration);
}

/**
 * Fermer une notification
 */
function closeToast(element) {
    const toast = element.classList ? element : element.parentElement;
    toast.classList.remove('toast-show');
    toast.classList.add('toast-hide');
    
    setTimeout(() => {
        if (toast.parentElement) {
            toast.parentElement.removeChild(toast);
        }
    }, 300);
}

/**
 * Remplacer les alert() par des toasts
 */
window.alertToast = function(message, type = 'info') {
    showToast(message, type);
};

// Intercepter les formulaires pour afficher des toasts au lieu d'alertes
document.addEventListener('DOMContentLoaded', function() {
    // Convertir les messages PHP en toasts
    const successMsg = document.querySelector('.message.success, .alert-success');
    const errorMsg = document.querySelector('.message.error, .alert-error');
    
    if (successMsg) {
        const text = successMsg.textContent.trim();
        if (text) {
            showToast(text, 'success');
            successMsg.style.display = 'none';
        }
    }
    
    if (errorMsg) {
        const text = errorMsg.textContent.trim();
        if (text) {
            showToast(text, 'error', 6000);
            errorMsg.style.display = 'none';
        }
    }
});
