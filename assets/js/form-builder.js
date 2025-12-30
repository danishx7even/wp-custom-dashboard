jQuery(document).ready(function($) {
    
    let new_field = '<div class="field">Field</div>';
    $(".btn-field").on('click', function(e) {

        $(".container").append(new_field);
    })


})