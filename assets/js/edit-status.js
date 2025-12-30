jQuery(document).ready(function($) {

    // 1. OPEN FORM
    $(document).on('click', '.larkon-quick-edit-trigger', function(e) {
        e.preventDefault();
        var $cell = $(this).closest('td');
        $cell.find('.larkon-view-mode').hide();
        $cell.find('.larkon-edit-mode').fadeIn(200);
    });

    // 2. CANCEL FORM
    $(document).on('click', '.larkon-cancel-btn', function(e) {
        e.preventDefault();
        var $cell = $(this).closest('td');
        $cell.find('.larkon-edit-mode').hide();
        $cell.find('.larkon-view-mode').fadeIn(200);
    });

    // 3. SAVE FORM (AJAX)
    $(document).on('click', '.larkon-save-btn', function(e) {
        e.preventDefault();

        var $btn  = $(this);
        var $form = $btn.closest('form');
        var $cell = $btn.closest('td');
        
        var postId = $form.data('post-id');
        var userRole = $form.data('role');
        var newVal = $form.find('select').val();

        // UI Updates: Lock button, show loader
        $btn.prop('disabled', true).text('...');
        $form.find('.larkon-loading').show();

        $.ajax({
            // FIX: Use larkon_vars.ajax_url, NOT ajaxurl
            url: larkon_vars.ajax_url, 
            type: 'POST',
            dataType: 'json', // Explicitly expect JSON
            data: {
                action: 'larkon_save_status',
                security: larkon_vars.nonce,
                post_id: postId,
                status: newVal,
                role : userRole
            },
            success: function(response) {
                if( response.success ) {
                    // Update the View Text
                    $cell.find('.status-text-label').text( response.data.new_label );
                    
                    // Close Form
                    $cell.find('.larkon-edit-mode').hide();
                    $cell.find('.larkon-view-mode').fadeIn(200);
                } else {
                    // Handle WP_Error or explicit failure
                    alert('Error: ' + (response.data || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.log(xhr.responseText); // Log actual error for debugging
                alert('Server error: ' + error);
            },
            complete: function() {
                // Reset UI
                $btn.prop('disabled', false).text('Save');
                $form.find('.larkon-loading').hide();
            }
        });
    });

});