/**
 * Validation en temps réel des formulaires
 * Valide les champs pendant la saisie et affiche des messages d'aide
 */

// Configuration des validateurs
const validators = {
    email: {
        pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
        message: "Format d'email invalide"
    },
    phone: {
        pattern: /^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/,
        message: "Format de téléphone invalide (ex: 06 12 34 56 78)"
    },
    password: {
        pattern: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/,
        message: "Min 8 caractères avec majuscule, minuscule, chiffre et caractère spécial"
    },
    date: {
        validator: (value) => {
            const date = new Date(value);
            return !isNaN(date.getTime());
        },
        message: "Date invalide"
    },
    age18: {
        validator: (value) => {
            const birthDate = new Date(value);
            const today = new Date();
            const age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                return age - 1 >= 18;
            }
            return age >= 18;
        },
        message: "Vous devez être majeur (18 ans minimum)"
    },
    required: {
        validator: (value) => value.trim().length > 0,
        message: "Ce champ est requis"
    },
    minLength: (min) => ({
        validator: (value) => value.length >= min,
        message: `Minimum ${min} caractères requis`
    }),
    maxLength: (max) => ({
        validator: (value) => value.length <= max,
        message: `Maximum ${max} caractères autorisés`
    }),
    number: {
        pattern: /^\d+$/,
        message: "Doit être un nombre entier"
    },
    decimal: {
        pattern: /^\d+(\.\d+)?$/,
        message: "Doit être un nombre"
    },
    postalCode: {
        pattern: /^\d{5}$/,
        message: "Code postal invalide (5 chiffres)"
    }
};

/**
 * Valider un champ
 */
function validateField(input) {
    const validationType = input.dataset.validate;
    if (!validationType) return true;

    const value = input.value;
    const types = validationType.split(',').map(t => t.trim());
    let isValid = true;
    let errorMessage = '';

    for (const type of types) {
        const validator = validators[type];
        if (!validator) continue;

        if (validator.pattern) {
            if (!validator.pattern.test(value)) {
                isValid = false;
                errorMessage = validator.message;
                break;
            }
        } else if (validator.validator) {
            if (!validator.validator(value)) {
                isValid = false;
                errorMessage = validator.message;
                break;
            }
        }
    }

    updateFieldValidation(input, isValid, errorMessage);
    return isValid;
}

/**
 * Mettre à jour l'affichage de validation
 */
function updateFieldValidation(input, isValid, message) {
    const wrapper = input.closest('.form-group') || input.parentElement;
    let errorDiv = wrapper.querySelector('.validation-error');
    let successIcon = wrapper.querySelector('.validation-success');

    // Retirer les anciennes indications
    wrapper.classList.remove('field-valid', 'field-invalid');
    if (errorDiv) errorDiv.remove();
    if (successIcon) successIcon.remove();

    if (input.value.trim() === '') {
        return; // Ne rien afficher si le champ est vide
    }

    if (isValid) {
        wrapper.classList.add('field-valid');
        const icon = document.createElement('span');
        icon.className = 'validation-success';
        icon.innerHTML = '✓';
        input.parentElement.appendChild(icon);
    } else {
        wrapper.classList.add('field-invalid');
        errorDiv = document.createElement('div');
        errorDiv.className = 'validation-error';
        errorDiv.textContent = message;
        input.parentElement.appendChild(errorDiv);
    }
}

/**
 * Calculer la force du mot de passe
 */
function checkPasswordStrength(password) {
    let strength = 0;
    const feedback = [];

    if (password.length >= 8) strength++;
    else feedback.push('Au moins 8 caractères');

    if (/[a-z]/.test(password)) strength++;
    else feedback.push('Une minuscule');

    if (/[A-Z]/.test(password)) strength++;
    else feedback.push('Une majuscule');

    if (/\d/.test(password)) strength++;
    else feedback.push('Un chiffre');

    if (/[@$!%*?&]/.test(password)) strength++;
    else feedback.push('Un caractère spécial');

    return { strength, feedback };
}

/**
 * Afficher un indicateur de force de mot de passe
 */
function showPasswordStrength(input) {
    const wrapper = input.closest('.form-group') || input.parentElement;
    let indicator = wrapper.querySelector('.password-strength');

    if (!indicator) {
        indicator = document.createElement('div');
        indicator.className = 'password-strength';
        input.parentElement.appendChild(indicator);
    }

    const { strength, feedback } = checkPasswordStrength(input.value);
    const levels = ['Très faible', 'Faible', 'Moyen', 'Fort', 'Très fort'];
    const colors = ['#ef4444', '#f59e0b', '#fbbf24', '#10b981', '#059669'];

    indicator.innerHTML = `
        <div class="strength-bar">
            <div class="strength-fill" style="width: ${strength * 20}%; background: ${colors[strength - 1] || colors[0]}"></div>
        </div>
        <div class="strength-label" style="color: ${colors[strength - 1] || colors[0]}">
            ${levels[strength - 1] || levels[0]}
        </div>
        ${feedback.length > 0 ? `<div class="strength-feedback">Manque: ${feedback.join(', ')}</div>` : ''}
    `;
}

/**
 * Initialiser la validation en temps réel
 */
function initRealtimeValidation() {
    // Tous les champs avec data-validate
    document.querySelectorAll('[data-validate]').forEach(input => {
        // Validation à la perte de focus
        input.addEventListener('blur', () => validateField(input));

        // Validation pendant la saisie (avec délai)
        let timeout;
        input.addEventListener('input', () => {
            clearTimeout(timeout);
            timeout = setTimeout(() => validateField(input), 500);
        });
    });

    // Champs de mot de passe
    document.querySelectorAll('input[type="password"][data-validate*="password"]').forEach(input => {
        input.addEventListener('input', () => showPasswordStrength(input));
    });

    // Validation du formulaire avant soumission
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', (e) => {
            let isValid = true;
            form.querySelectorAll('[data-validate]').forEach(input => {
                if (!validateField(input)) {
                    isValid = false;
                }
            });

            if (!isValid) {
                e.preventDefault();
                showToast('Veuillez corriger les erreurs dans le formulaire', 'error');
                // Scroller vers la première erreur
                const firstError = form.querySelector('.field-invalid');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    });
}

// Initialiser au chargement
document.addEventListener('DOMContentLoaded', initRealtimeValidation);
