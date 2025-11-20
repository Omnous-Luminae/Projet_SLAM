// Autocomplete functionality for communes
function initCommuneAutocomplete(selector, hiddenSelector) {
    $(selector).autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "../api/search_communes.php",
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
