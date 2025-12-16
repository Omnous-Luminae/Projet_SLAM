/**
 * Loader JavaScript - Gestionnaire de loading
 */

const Loader = {
    overlayId: 'global-loading-overlay',
    
    /**
     * Créer l'overlay si inexistant
     */
    createOverlay: function(style = 'circle', text = 'Chargement') {
        if (document.getElementById(this.overlayId)) return;
        
        let spinnerHtml = '';
        switch(style) {
            case 'dots':
                spinnerHtml = '<div class="spinner spinner-dots"><span></span><span></span><span></span></div>';
                break;
            case 'pulse':
                spinnerHtml = '<div class="spinner spinner-pulse"></div>';
                break;
            case 'ripple':
                spinnerHtml = '<div class="spinner spinner-ripple"><span></span><span></span></div>';
                break;
            case 'house':
                spinnerHtml = '<div class="spinner spinner-house">🏠</div>';
                break;
            default:
                spinnerHtml = '<div class="spinner spinner-circle"></div>';
        }
        
        const overlay = document.createElement('div');
        overlay.id = this.overlayId;
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="loading-container">
                ${spinnerHtml}
                <div class="loading-text">${text}</div>
                <div class="progress-bar"><div class="progress-bar-fill"></div></div>
            </div>
        `;
        document.body.appendChild(overlay);
    },
    
    /**
     * Afficher le loader global
     */
    show: function(style = 'circle', text = 'Chargement') {
        this.createOverlay(style, text);
        const overlay = document.getElementById(this.overlayId);
        setTimeout(() => overlay.classList.add('active'), 10);
    },
    
    /**
     * Cacher le loader global
     */
    hide: function() {
        const overlay = document.getElementById(this.overlayId);
        if (overlay) {
            overlay.classList.remove('active');
            setTimeout(() => overlay.remove(), 300);
        }
    },
    
    /**
     * Ajouter un loader à un élément
     */
    addTo: function(element, style = 'circle') {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        if (!element) return;
        
        element.style.position = 'relative';
        element.style.minHeight = '100px';
        
        const loader = document.createElement('div');
        loader.className = 'element-loader loading-container';
        loader.innerHTML = `<div class="spinner spinner-${style}"></div>`;
        loader.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--bg-primary, rgba(248, 250, 252, 0.9));
            z-index: 10;
        `;
        element.appendChild(loader);
    },
    
    /**
     * Retirer le loader d'un élément
     */
    removeFrom: function(element) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        if (!element) return;
        
        const loader = element.querySelector('.element-loader');
        if (loader) loader.remove();
    },
    
    /**
     * Mettre un bouton en état de chargement
     */
    buttonLoading: function(button, loading = true) {
        if (typeof button === 'string') {
            button = document.querySelector(button);
        }
        if (!button) return;
        
        if (loading) {
            button.classList.add('btn-loading');
            button.disabled = true;
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = `<span class="btn-text">${button.innerHTML}</span>`;
        } else {
            button.classList.remove('btn-loading');
            button.disabled = false;
            if (button.dataset.originalText) {
                button.innerHTML = button.dataset.originalText;
            }
        }
    },
    
    /**
     * Créer un skeleton loading
     */
    skeleton: function(element, options = {}) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        if (!element) return;
        
        const defaults = {
            lines: 3,
            hasImage: false,
            hasTitle: true
        };
        const opts = { ...defaults, ...options };
        
        let html = '';
        
        if (opts.hasImage) {
            html += '<div class="skeleton skeleton-image"></div>';
        }
        
        if (opts.hasTitle) {
            html += '<div class="skeleton skeleton-title"></div>';
        }
        
        for (let i = 0; i < opts.lines; i++) {
            const size = i === opts.lines - 1 ? 'short' : (i % 2 === 0 ? 'long' : 'medium');
            html += `<div class="skeleton skeleton-text ${size}"></div>`;
        }
        
        element.innerHTML = html;
    },
    
    /**
     * Wrapper pour fetch avec loader
     */
    fetch: function(url, options = {}, loaderStyle = 'circle') {
        const self = this;
        self.show(loaderStyle);
        return window.fetch(url, options)
            .then(function(response) {
                return response;
            })
            .finally(function() {
                self.hide();
            });
    }
};

// Auto-loading pour les formulaires
document.addEventListener('submit', function(e) {
    const form = e.target;
    const submitBtn = form.querySelector('[type="submit"]');
    if (submitBtn && !form.dataset.noLoader) {
        Loader.buttonLoading(submitBtn, true);
    }
});

// Exporter
window.Loader = Loader;
