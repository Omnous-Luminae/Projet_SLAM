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
    initCommuneAutocomplete("#edit_commune", "#edit_id_commune");
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
