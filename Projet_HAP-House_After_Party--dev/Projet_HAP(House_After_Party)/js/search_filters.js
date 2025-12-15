/**
 * Script de gestion des filtres de recherche avancés pour les annonces
 * Permet de filtrer les biens par multiple critères
 */

// Fonction pour basculer l'affichage des filtres
function toggleFilters() {
    const filterRows = document.querySelectorAll('.filter-row');
    const toggleIcon = document.getElementById('toggle-icon');
    const toggleText = document.getElementById('toggle-text');
    
    if (!filterRows.length) return;
    
    const isHidden = filterRows[0].style.display === 'none';
    
    filterRows.forEach(row => {
        row.style.display = isHidden ? 'grid' : 'none';
    });
    
    if (isHidden) {
        toggleIcon.textContent = '▼';
        toggleText.textContent = 'Masquer les filtres';
        localStorage.setItem('filtersVisible', 'true');
    } else {
        toggleIcon.textContent = '▶';
        toggleText.textContent = 'Afficher les filtres';
        localStorage.setItem('filtersVisible', 'false');
    }
}

// Fonction pour réinitialiser tous les filtres
function resetAllFilters() {
    const form = document.querySelector('.advanced-search-form');
    if (!form) return;
    
    // Réinitialiser tous les inputs et selects
    const inputs = form.querySelectorAll('input[type="text"], input[type="number"], select');
    inputs.forEach(input => {
        if (input.type === 'text' || input.type === 'number') {
            input.value = '';
        } else if (input.tagName === 'SELECT') {
            input.selectedIndex = 0;
        }
    });
    
    // Réinitialiser les champs cachés
    const hiddenInputs = form.querySelectorAll('input[type="hidden"]');
    hiddenInputs.forEach(input => {
        input.value = '';
    });
}

// Fonction pour compter les filtres actifs
function countActiveFilters() {
    const form = document.querySelector('.advanced-search-form');
    if (!form) return 0;
    
    let count = 0;
    const inputs = form.querySelectorAll('input[type="text"], input[type="number"], select');
    
    inputs.forEach(input => {
        if (input.name && input.value && input.value !== '' && input.value !== '-1') {
            // Ne pas compter les champs cachés comme search_commune_id
            if (input.type !== 'hidden') {
                count++;
            }
        }
    });
    
    return count;
}

// Fonction pour afficher le nombre de filtres actifs
function updateFilterBadge() {
    const count = countActiveFilters();
    const badge = document.getElementById('filter-count-badge');
    
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    }
}

// Fonction pour sauvegarder l'état des filtres dans localStorage
function saveFiltersState() {
    const form = document.querySelector('.advanced-search-form');
    if (!form) return;
    
    const filters = {};
    const inputs = form.querySelectorAll('input, select');
    
    inputs.forEach(input => {
        if (input.name && input.value) {
            filters[input.name] = input.value;
        }
    });
    
    localStorage.setItem('savedFilters', JSON.stringify(filters));
}

// Fonction pour restaurer l'état des filtres depuis localStorage
function restoreFiltersState() {
    const savedFilters = localStorage.getItem('savedFilters');
    if (!savedFilters) return;
    
    try {
        const filters = JSON.parse(savedFilters);
        const form = document.querySelector('.advanced-search-form');
        if (!form) return;
        
        Object.keys(filters).forEach(name => {
            const input = form.querySelector(`[name="${name}"]`);
            if (input && !input.value) {
                input.value = filters[name];
            }
        });
    } catch (e) {
        console.error('Erreur lors de la restauration des filtres:', e);
    }
}

// Fonction pour vérifier si des filtres sont actifs
function hasActiveFilters() {
    const urlParams = new URLSearchParams(window.location.search);
    const filterParams = [
        'search_nom_bien', 'search_commune_id', 'search_type_bien',
        'search_prix_min', 'search_prix_max', 'search_couchage_min',
        'search_couchage_max', 'search_superficie_min', 'search_superficie_max',
        'search_animaux', 'search_note'
    ];
    
    return filterParams.some(param => {
        const value = urlParams.get(param);
        return value && value !== '' && value !== '-1' && value !== '0';
    });
}

// Validation des plages de valeurs
function validateRanges() {
    const form = document.querySelector('.advanced-search-form');
    if (!form) return true;
    
    // Validation prix
    const prixMin = parseFloat(form.search_prix_min?.value || 0);
    const prixMax = parseFloat(form.search_prix_max?.value || 0);
    if (prixMin > 0 && prixMax > 0 && prixMin > prixMax) {
        alert('Le prix minimum ne peut pas être supérieur au prix maximum.');
        return false;
    }
    
    // Validation couchages
    const couchageMin = parseInt(form.search_couchage_min?.value || 0);
    const couchageMax = parseInt(form.search_couchage_max?.value || 0);
    if (couchageMin > 0 && couchageMax > 0 && couchageMin > couchageMax) {
        alert('Le nombre de couchages minimum ne peut pas être supérieur au maximum.');
        return false;
    }
    
    // Validation superficie
    const superficieMin = parseInt(form.search_superficie_min?.value || 0);
    const superficieMax = parseInt(form.search_superficie_max?.value || 0);
    if (superficieMin > 0 && superficieMax > 0 && superficieMin > superficieMax) {
        alert('La superficie minimum ne peut pas être supérieure au maximum.');
        return false;
    }
    
    return true;
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si des filtres sont actifs dans l'URL
    const filtersActive = hasActiveFilters();
    
    // Restaurer la visibilité des filtres depuis localStorage
    const savedVisibility = localStorage.getItem('filtersVisible');
    const shouldShowFilters = filtersActive || savedVisibility === 'true';
    
    // Appliquer la visibilité
    const filterRows = document.querySelectorAll('.filter-row');
    const toggleIcon = document.getElementById('toggle-icon');
    const toggleText = document.getElementById('toggle-text');
    
    if (!shouldShowFilters && filterRows.length > 0) {
        filterRows.forEach(row => {
            row.style.display = 'none';
        });
        if (toggleIcon) toggleIcon.textContent = '▶';
        if (toggleText) toggleText.textContent = 'Afficher les filtres';
    }
    
    // Ajouter la validation avant soumission
    const form = document.querySelector('.advanced-search-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateRanges()) {
                e.preventDefault();
                return false;
            }
            saveFiltersState();
        });
    }
    
    // Mettre à jour le badge de compteur de filtres
    updateFilterBadge();
    
    // Ajouter des écouteurs pour les changements de filtres
    const inputs = document.querySelectorAll('.advanced-search-form input, .advanced-search-form select');
    inputs.forEach(input => {
        input.addEventListener('change', updateFilterBadge);
    });
});

// Fonction pour afficher un résumé des filtres actifs
function showActiveFiltersummary() {
    const form = document.querySelector('.advanced-search-form');
    if (!form) return;
    
    const summaryContainer = document.getElementById('active-filters-summary');
    if (!summaryContainer) return;
    
    const activeFilters = [];
    
    // Collecter tous les filtres actifs
    const inputs = form.querySelectorAll('input[type="text"], input[type="number"], select');
    inputs.forEach(input => {
        if (input.value && input.value !== '' && input.value !== '-1' && input.type !== 'hidden') {
            const label = form.querySelector(`label[for="${input.id}"]`)?.textContent || input.name;
            activeFilters.push({ label, value: input.value });
        }
    });
    
    // Afficher le résumé
    if (activeFilters.length > 0) {
        let html = '<div class="active-filters-tags">';
        activeFilters.forEach(filter => {
            html += `<span class="filter-tag">${filter.label}: ${filter.value}</span>`;
        });
        html += '</div>';
        summaryContainer.innerHTML = html;
        summaryContainer.style.display = 'block';
    } else {
        summaryContainer.style.display = 'none';
    }
}
