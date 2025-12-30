<?php
function create_contract_post_type() {
    $labels = array(
        'name'                  => _x( 'Contracts', 'Post Type General Name', 'text_domain' ),
        'singular_name'         => _x( 'Contract', 'Post Type Singular Name', 'text_domain' ),
        'menu_name'             => __( 'Contracts', 'text_domain' ),
        'name_admin_bar'        => __( 'Contract', 'text_domain' ),
        'archives'              => __( 'Contract Archives', 'text_domain' ),
        'attributes'            => __( 'Contract Attributes', 'text_domain' ),
        'parent_item_colon'     => __( 'Parent Contract:', 'text_domain' ),
        'all_items'             => __( 'All Contracts', 'text_domain' ),
        'add_new_item'          => __( 'Add New Contract', 'text_domain' ),
        'add_new'               => __( 'Add New', 'text_domain' ),
        'new_item'              => __( 'New Contract', 'text_domain' ),
        'edit_item'             => __( 'Edit Contract', 'text_domain' ),
        'update_item'           => __( 'Update Contract', 'text_domain' ),
        'view_item'             => __( 'View Contract', 'text_domain' ),
        'view_items'            => __( 'View Contracts', 'text_domain' ),
        'search_items'          => __( 'Search Contract', 'text_domain' ),
        'not_found'             => __( 'Not found', 'text_domain' ),
        'not_found_in_trash'    => __( 'Not found in Trash', 'text_domain' ),
        'featured_image'        => __( 'Featured Image', 'text_domain' ),
        'set_featured_image'    => __( 'Set featured image', 'text_domain' ),
        'remove_featured_image' => __( 'Remove featured image', 'text_domain' ),
        'use_featured_image'    => __( 'Use as featured image', 'text_domain' ),
        'insert_into_item'      => __( 'Insert into contract', 'text_domain' ),
        'uploaded_to_this_item' => __( 'Uploaded to this contract', 'text_domain' ),
        'items_list'            => __( 'Contracts list', 'text_domain' ),
        'items_list_navigation' => __( 'Contracts list navigation', 'text_domain' ),
        'filter_items_list'     => __( 'Filter contracts list', 'text_domain' ),
    );
    $args = array(
        'label'                 => __( 'Contract', 'text_domain' ),
        'description'           => __( 'Contract Information', 'text_domain' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor', 'revisions', 'comments', 'thumbnail' ), // Add 'custom-fields' if not using ACF
        'taxonomies'            => array( 'payment-model', 'contract-type' ),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 5,
        'menu_icon'             => 'dashicons-media-document', // Icon for the menu
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
        'show_in_rest'          => false, // Set to false if you want Classic Editor instead of Gutenberg
    );
    register_post_type( 'contract', $args );
}
add_action( 'init', 'create_contract_post_type', 0 );


function register_contract_taxonomies() {
    // Taxonomy: Payment Models
    $labels_payment = array(
        'name'                       => _x( 'Payment Models', 'Taxonomy General Name', 'text_domain' ),
        'singular_name'              => _x( 'Payment Model', 'Taxonomy Singular Name', 'text_domain' ),
        'menu_name'                  => __( 'Payment Models', 'text_domain' ),
        'all_items'                  => __( 'All Payment Models', 'text_domain' ),
        'new_item_name'              => __( 'New Payment Model Name', 'text_domain' ),
        'add_new_item'               => __( 'Add New Payment Model', 'text_domain' ),
        'edit_item'                  => __( 'Edit Payment Model', 'text_domain' ),
        'update_item'                => __( 'Update Payment Model', 'text_domain' ),
        'view_item'                  => __( 'View Payment Model', 'text_domain' ),
        'separate_items_with_commas' => __( 'Separate payment models with commas', 'text_domain' ),
        'add_or_remove_items'        => __( 'Add or remove payment models', 'text_domain' ),
        'choose_from_most_used'      => __( 'Choose from the most used', 'text_domain' ),
        'popular_items'              => __( 'Popular Payment Models', 'text_domain' ),
        'search_items'               => __( 'Search Payment Models', 'text_domain' ),
        'not_found'                  => __( 'Not Found', 'text_domain' ),
    );
    $args_payment = array(
        'labels'                     => $labels_payment,
        'hierarchical'               => false, // Non-hierarchical (like tags)
        'public'                     => true,
        'show_ui'                    => true,
        'show_admin_column'          => true,
        'show_in_nav_menus'          => true,
        'show_tagcloud'              => true,
        'show_in_rest'               => true,
    );
    register_taxonomy( 'payment-model', array( 'contract' ), $args_payment );

    // Taxonomy: Contract Types
    $labels_type = array(
        'name'                       => _x( 'Types', 'Taxonomy General Name', 'text_domain' ),
        'singular_name'              => _x( 'Type', 'Taxonomy Singular Name', 'text_domain' ),
        'menu_name'                  => __( 'Types', 'text_domain' ),
        'all_items'                  => __( 'All Types', 'text_domain' ),
        'new_item_name'              => __( 'New Type Name', 'text_domain' ),
        'add_new_item'               => __( 'Add New Type', 'text_domain' ),
        'edit_item'                  => __( 'Edit Type', 'text_domain' ),
        'update_item'                => __( 'Update Type', 'text_domain' ),
        'view_item'                  => __( 'View Type', 'text_domain' ),
        'separate_items_with_commas' => __( 'Separate types with commas', 'text_domain' ),
        'add_or_remove_items'        => __( 'Add or remove types', 'text_domain' ),
        'choose_from_most_used'      => __( 'Choose from the most used', 'text_domain' ),
        'popular_items'              => __( 'Popular Types', 'text_domain' ),
        'search_items'               => __( 'Search Types', 'text_domain' ),
        'not_found'                  => __( 'Not Found', 'text_domain' ),
    );
    $args_type = array(
        'labels'                     => $labels_type,
        'hierarchical'               => false, // Non-hierarchical (like tags)
        'public'                     => true,
        'show_ui'                    => true,
        'show_admin_column'          => true,
        'show_in_nav_menus'          => true,
        'show_tagcloud'              => true,
        'show_in_rest'               => true,
    );
    register_taxonomy( 'contract-type', array( 'contract' ), $args_type );
}
add_action( 'init', 'register_contract_taxonomies', 0 );



/**
 * 1. Register the Meta Box
 */
function contract_add_custom_meta_box() {
    add_meta_box(
        'contract_related_info_box',       // Unique ID
        'Contract Related Info',           // Box Title
        'contract_render_meta_box',        // Content Callback
        'contract',                        // Post Type
        'normal',                          // Context (normal, side, advanced)
        'high'                             // Priority
    );
}
add_action( 'add_meta_boxes', 'contract_add_custom_meta_box' );

/**
 * 2. Render the Meta Box Content
 */
function contract_render_meta_box( $post ) {
    // Add a nonce field so we can check for it later.
    wp_nonce_field( 'contract_save_meta_box_data', 'contract_meta_box_nonce' );

    // Retrieve existing values from the database
    $current_status  = get_post_meta( $post->ID, 'contract_status', true );
    $current_student = get_post_meta( $post->ID, 'contract_student', true );
    $current_teacher = get_post_meta( $post->ID, 'contract_teacher', true );

    // Define Status Options
    $status_options = get_contract_status_choices();
    
    // Default status if empty
    if( empty($current_status) ) {
        $current_status = 'assign_student';
    }

    ?>
    <style>
        /* Simple styling to make the fields look nice */
        .contract-meta-row { margin-bottom: 15px; }
        .contract-meta-row label { display: block; font-weight: bold; margin-bottom: 5px; }
        .contract-meta-row select { width: 100%; max-width: 400px; }
    </style>

    <div class="contract-meta-box-container">
        
        <!-- Field 1: Contract Status -->
        <div class="contract-meta-row">
            <label for="contract_status">Contract Status</label>
            <select name="contract_status" id="contract_status">
                <?php foreach ( $status_options as $value => $label ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_status, $value ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description">This is the name which will appear on the EDIT page</p>
        </div>

        <!-- Field 2: Student (User Dropdown) -->
        <div class="contract-meta-row">
            <label for="contract_student">Student</label>
            <?php
            // Native WP function to create a user dropdown
            wp_dropdown_users( array(
                'name'             => 'contract_student',
                'selected'         => $current_student,
                'show_option_none' => 'Select Student',
                'role'             => 'student', // IMPORTANT: Ensure your role slug is exactly 'student'
            ) );
            ?>
        </div>

        <!-- Field 3: Teacher (User Dropdown) -->
        <div class="contract-meta-row">
            <label for="contract_teacher">Teacher</label>
            <?php
            // Native WP function to create a user dropdown
            wp_dropdown_users( array(
                'name'             => 'contract_teacher',
                'selected'         => $current_teacher,
                'show_option_none' => 'Select Teacher',
                'role'             => 'teacher', // IMPORTANT: Ensure your role slug is exactly 'teacher'
            ) );
            ?>
        </div>

    </div>
    <?php
}

/**
 * 3. Save the Data
 */
function contract_save_meta_box_data( $post_id ) {
    
    // Check if our nonce is set.
    if ( ! isset( $_POST['contract_meta_box_nonce'] ) ) {
        return;
    }

    // Verify that the nonce is valid.
    if ( ! wp_verify_nonce( $_POST['contract_meta_box_nonce'], 'contract_save_meta_box_data' ) ) {
        return;
    }

    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Check the user's permissions.
    if ( isset( $_POST['post_type'] ) && 'contract' === $_POST['post_type'] ) {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    }

    /* OK, it's safe for us to save the data now. */

    // 1. Save Contract Status
    if ( isset( $_POST['contract_status'] ) ) {
        $status_data = sanitize_text_field( $_POST['contract_status'] );
        update_post_meta( $post_id, 'contract_status', $status_data );
    }

    // 2. Save Student (User ID)
    if ( isset( $_POST['contract_student'] ) ) {
        // Sanitize check: ensure it's an integer (User ID)
        $student_id = intval( $_POST['contract_student'] );
        // If "Select Student" (value -1 or 0) is picked, delete the meta, otherwise save it
        if ( $student_id > 0 ) {
            update_post_meta( $post_id, 'contract_student', $student_id );
        } else {
            delete_post_meta( $post_id, 'contract_student' );
        }
    }

    // 3. Save Teacher (User ID)
    if ( isset( $_POST['contract_teacher'] ) ) {
        $teacher_id = intval( $_POST['contract_teacher'] );
        if ( $teacher_id > 0 ) {
            update_post_meta( $post_id, 'contract_teacher', $teacher_id );
        } else {
            delete_post_meta( $post_id, 'contract_teacher' );
        }
    }
}
add_action( 'save_post', 'contract_save_meta_box_data' );


function get_contract_status_choices() {
    return array(
        'assign_student' => 'Assigned to the Student',
        'submit_teacher' => 'Submitted to the Teacher',
        'work_start'     => 'Work Start',
        'need_details'   => 'Need More Details',
        'reject'         => 'Rejected',
        'approve'        => 'Approved'
    );
}