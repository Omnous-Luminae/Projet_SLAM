// Helper function to convert string to title case
function titleCase(str) {
    return str.toLowerCase().replace(/\b\w/g, l => l.toUpperCase());
}

// Autocomplete functionality for communes
function initCommuneAutocomplete(selector, hiddenSelector) {
    console.log('Initializing commune autocomplete for:', selector, hiddenSelector);
    
    $(selector).autocomplete({
        source: function(request, response) {
            console.log('Commune autocomplete - searching for:', request.term);
            $.ajax({
                url: "../api/search_communes.php",
                dataType: "json",
                data: {
                    q: request.term
                },
                success: function(data) {
                    console.log('search_communes autocomplete results:', data);
                    if (!data || data.length === 0) {
                        console.warn('No communes found for:', request.term);
                    }
                    response(data.map(item => ({
                        label: titleCase(item.label),
                        value: titleCase(item.label),
                        id: item.id,
                        code_insee: item.code_insee
                    })));
                },
                error: function(xhr, status, error) {
                    console.error('Commune autocomplete error:', status, error, xhr.responseText);
                    response([]);
                }
            });
        },
        minLength: 2,
    select: function(event, ui) {
            console.log('Commune selected:', ui.item);
            $(selector).val(ui.item.label);
            $(hiddenSelector).val(ui.item.id);
            
            // Store code_insee in a data attribute for later use
            if (ui.item.code_insee) {
                $(selector).data('code_insee', ui.item.code_insee);
                console.log('Stored code_insee:', ui.item.code_insee);
                
                // Trigger custom event to notify that commune was selected with code_insee
                $(selector).trigger('commune-selected', [ui.item.code_insee]);
            }
            
            // Trigger change event so status indicator updates
            $(hiddenSelector).trigger('change');
            return false;
        }
    });

    $(selector).on('input', function() {
        $(hiddenSelector).val('');
        // Clear stored code_insee
        $(selector).removeData('code_insee');
        // Trigger change event
        $(hiddenSelector).trigger('change');
    });
}

// Initialize autocomplete for search form
function initSearchCommuneAutocomplete() {
    initCommuneAutocomplete("#search_commune_input", "#search_commune_id");
}

// Initialize autocomplete for add form
function initAddCommuneAutocomplete() {
    // Some forms use different IDs for the commune input/hidden fields (e.g. "#commune" vs "#commune_input", "#id_commune" vs "#commune_id").
    // Choose the first selector that exists in the DOM so autocomplete works across pages.
    const possibleInputs = ['#commune', '#commune_input'];
    const possibleHidden = ['#id_commune', '#commune_id'];
    let inputSel = null;
    let hiddenSel = null;
    for (const s of possibleInputs) { if (document.querySelector(s)) { inputSel = s; break; } }
    for (const s of possibleHidden) { if (document.querySelector(s)) { hiddenSel = s; break; } }
    // If nothing found, fall back to '#commune' and '#id_commune' so other pages that include this script won't break
    initCommuneAutocomplete(inputSel || '#commune', hiddenSel || '#id_commune');
}

// Initialize autocomplete for edit form
function initEditCommuneAutocomplete() {
    initCommuneAutocomplete("#commune_input", "#commune_id");
}

// Autocomplete functionality for biens
function initBiensAutocomplete(selector, hiddenSelector) {
    $(selector).autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "../api/search_biens.php",
                dataType: "json",
                data: {
                    q: request.term
                },
                success: function(data) {
                    response(data);
                }
            });
        },
        minLength: 2,
        select: function(event, ui) {
            $(selector).val(ui.item.label);
            $(hiddenSelector).val(ui.item.id);
            return false;
        }
    });

    $(selector).on('input', function() {
        $(hiddenSelector).val('');
    });
}

// Initialize autocomplete for biens in reservation form
function initReservationBiensAutocomplete() {
    initBiensAutocomplete("#biens_input", "#biens_id");
}

// Initialize autocomplete for biens in edit modal
function initEditBiensAutocomplete() {
    initBiensAutocomplete("#edit_biens_input", "#edit_biens_id");
}

// Autocomplete functionality for compositions (prestations)
function initCompositionAutocomplete(selector, hiddenSelector) {
    $(selector).autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "../api/search_composition.php",
                dataType: "json",
                data: {
                    q: request.term
                },
                success: function(data) {
                    response(data);
                }
            });
        },
        minLength: 2,
        select: function(event, ui) {
            $(selector).val(ui.item.label);
            $(hiddenSelector).val(ui.item.id);
            return false;
        }
    });

    $(selector).on('input', function() {
        $(hiddenSelector).val('');
    });
}

// Initialize autocomplete for composition in add form
function initAddCompositionAutocomplete() {
    // Apply to all composition inputs dynamically added
    $(document).on('focus', 'input[name*="[label]"]', function() {
        const $input = $(this);
        const index = $input.attr('name').match(/\[(\d+)\]/)[1];
        const hiddenName = `composition[${index}][id_prestation]`;
        if (!$input.data('autocomplete-initialized')) {
            $input.autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: "../api/search_composition.php",
                        dataType: "json",
                        data: {
                            q: request.term
                        },
                        success: function(data) {
                            response(data);
                        }
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    $input.val(ui.item.label);
                    // Add hidden input if not exists
                    let $hidden = $input.siblings(`input[name="${hiddenName}"]`);
                    if ($hidden.length === 0) {
                        $hidden = $('<input type="hidden" name="' + hiddenName + '">');
                        $input.after($hidden);
                    }
                    $hidden.val(ui.item.id);
                    return false;
                }
            });
            $input.data('autocomplete-initialized', true);
        }
    });
}

// Autocomplete for full addresses using adresse.data.gouv.fr
function initAddressAutocomplete(selector, onSelectCallback) {
    $(selector).autocomplete({
        source: function(request, response) {
            $.ajax({
                url: 'https://api-adresse.data.gouv.fr/search/',
                dataType: 'json',
                data: {
                    q: request.term,
                    limit: 6
                },
                success: function(data) {
                    const results = (data.features || []).map(function(f) {
                        return {
                            label: f.properties.label,
                            value: f.properties.name || f.properties.label,
                            postcode: f.properties.postcode,
                            city: f.properties.city,
                            context: f.properties.context,
                            properties: f.properties
                        };
                    });
                    response(results);
                },
                error: function() { response([]); }
            });
        },
        minLength: 3,
        select: function(event, ui) {
            $(selector).val(ui.item.label);
            // Set commune_input if city is available
            if (ui.item.properties && ui.item.properties.city) {
                var pc = ui.item.properties.postcode || '';
                var ct = ui.item.properties.city;
                $('#commune_input').val(ct + (pc ? ' (' + pc + ')' : ''));
            }
            if (typeof onSelectCallback === 'function') {
                onSelectCallback(ui.item);
            }
            return false;
        }
    });
}
