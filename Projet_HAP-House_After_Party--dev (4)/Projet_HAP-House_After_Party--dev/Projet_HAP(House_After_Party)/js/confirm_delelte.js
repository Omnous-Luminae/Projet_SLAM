document.addEventListener('DOMContentLoaded', function () {
    // Find all forms that contain a submit button or input with a name starting with "delete_"
    var forms = document.querySelectorAll('form');
    forms.forEach(function (form) {
        var hasDelete = false;
        var deleteElement = null;
        // look for inputs/buttons named like delete_*
        var candidates = form.querySelectorAll('button[name], input[name]');
        candidates.forEach(function (el) {
            var name = el.getAttribute('name') || '';
            if (name.indexOf('delete_') === 0) {
                hasDelete = true;
                deleteElement = el;
            }
        });

        if (hasDelete) {
            // If form already has an onsubmit attribute that returns false/confirm, skip
            var onsubmitAttr = form.getAttribute('onsubmit');
            if (onsubmitAttr && onsubmitAttr.trim() !== '') return;

            form.addEventListener('submit', function (e) {
                // Use data-confirm on the form or the delete button if provided
                var msg = form.getAttribute('data-confirm') || (deleteElement && deleteElement.getAttribute('data-confirm')) || 'Confirmer la suppression ?';
                if (!confirm(msg)) {
                    e.preventDefault();
                    return false;
                }
                return true;
            });
        }
    });
});
