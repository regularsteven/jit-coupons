jQuery(document).ready(function($) {
    // When user clicks "Add New Reference"
    $('#jit-add-row').on('click', function(e) {
        e.preventDefault();

        // Clone the hidden template
        var newRow = $('#jit-reference-template tr.jit-reference-row').clone();

        // Append it to the table body
        $('#jit-reference-body').append(newRow);
    });

    // Handle row removal - note we use event delegation
    $(document).on('click', '.jit-remove-row', function(e) {
        e.preventDefault();
        $(this).closest('tr').remove();
    });
});
