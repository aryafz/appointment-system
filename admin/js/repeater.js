// appointment-system/admin/js/repeater.js
jQuery(document).ready(function($) {
    // Helper function to update field name indexes
    function updateRepeaterIndexes($container) {
        $container.find('.as-repeater-row').each(function(index) {
            $(this).find(':input').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    name = name.replace(/\[\d+\]/, '[' + index + ']');
                    $(this).attr('name', name);
                }
            });
        });
    }

    // Event for adding a new row
    $(document).on('click', '.as-add-repeater-row', function() {
        var $button = $(this);
        var targetContainerId = $button.data('repeater-target');
        var rowTemplateId = $button.data('row-template-id');

        var $container = $(targetContainerId);
        var templateHtml = $('#' + rowTemplateId).html();

        if (templateHtml) {
            var newIndex = $container.find('.as-repeater-row').length;
            var newRow = $(templateHtml.replace(/{repeater_index}/g, newIndex)); // Replace {repeater_index}

            newRow.find(':input').val(''); // Clear field values in the new row
            $container.append(newRow);

            // If it's a textarea, it might need TinyMCE settings
            if (typeof tinymce !== 'undefined') {
                newRow.find('textarea').each(function() {
                    var id = $(this).attr('id');
                    if (id && tinymce.get(id)) {
                        tinymce.execCommand('mceRemoveEditor', false, id);
                        tinymce.execCommand('mceAddEditor', false, id);
                    }
                });
            }
        }
    });

    // Event for removing a row
    $(document).on('click', '.as-remove-repeater-row', function() {
        var $row = $(this).closest('.as-repeater-row');
        var $container = $row.parent();
        if ($container.find('.as-repeater-row').length > 1) { // Ensure at least one row remains
            $row.remove();
            updateRepeaterIndexes($container); // Update indexes after removal
        } else {
            // If only one row remains, clear it but don't remove
            $row.find(':input').val('');
            // If it has TinyMCE, reset its content
            if (typeof tinymce !== 'undefined') {
                $row.find('textarea').each(function() {
                    var id = $(this).attr('id');
                    if (id && tinymce.get(id)) {
                        tinymce.get(id).setContent('');
                    }
                });
            }
        }
    });
});