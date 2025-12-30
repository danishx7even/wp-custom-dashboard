<?php
/* ==========================================================================
   HELPER: FILE UPLOADER (Keep this)
   ========================================================================== */
function larkon_handle_file_upload($file_input_name, $post_id = 0) {
    if (!isset($_FILES[$file_input_name]) || empty($_FILES[$file_input_name]['name'])) {
        return false;
    }
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    
    $attachment_id = media_handle_upload($file_input_name, $post_id);
    return is_wp_error($attachment_id) ? false : $attachment_id;
}

/* ==========================================================================
   HANDLER: POSTS (CREATE & EDIT)
   ========================================================================== */
function larkon_process_post_form() {
    // 1. Check if form submitted
    if (!isset($_POST['larkon_create_post'])) return;

    // 2. Security: Nonce
    if (!isset($_POST['larkon_nonce']) || !wp_verify_nonce($_POST['larkon_nonce'], 'larkon_new_post')) {
        wp_die('Security check failed.');
    }

    // 3. Security: Logged in
    if (!is_user_logged_in()) return;

    $current_user_id = get_current_user_id();
    $title   = sanitize_text_field($_POST['post_title']);
    $content = wp_kses_post($_POST['post_content']);
    $cat_id  = intval($_POST['post_category']);

    // 4. Determine if Edit or Create
    $post_id = isset($_POST['editing_post_id']) ? intval($_POST['editing_post_id']) : 0;
    $is_edit = ($post_id > 0);

    $post_data = array(
        'post_title'    => $title,
        'post_content'  => $content,
        'post_status'   => 'publish', 
        'post_type'     => 'post',
        'post_category' => array($cat_id)
    );

    if ($is_edit) {
        // --- EDIT MODE ---
        // CRITICAL: Check ownership
        $existing_post = get_post($post_id);
        if (!$existing_post || $existing_post->post_author != $current_user_id) {
            wp_die('You do not have permission to edit this post.');
        }
        $post_data['ID'] = $post_id;
        $result_id = wp_update_post($post_data);
    } else {
        // --- CREATE MODE ---
        $post_data['post_author'] = $current_user_id;
        $result_id = wp_insert_post($post_data);
    }

    // 5. Handle Image & Redirect
    if (!is_wp_error($result_id)) {
        $attachment_id = larkon_handle_file_upload('post_thumbnail', $result_id);
        if ($attachment_id) {
            set_post_thumbnail($result_id, $attachment_id);
        }
        
        // Redirect to All Posts
        $redirect = add_query_arg('tab', 'all_posts', home_url('/dashboard/')); // Change URL if needed
        wp_safe_redirect($redirect);
        exit;
    }
}
add_action('admin_post_handle_post_form', 'larkon_process_post_form');

/* ==========================================================================
   HANDLER: USERS (CREATE & EDIT)
   ========================================================================== */
function larkon_process_user_form() {
    // 1. Check if form submitted
    if (!isset($_POST['hec_create_user'])) return;

    // 2. Security: Nonce
    if (!isset($_POST['user_nonce']) || !wp_verify_nonce($_POST['user_nonce'], 'hec_new_user')) {
        wp_die('Security check failed.');
    }

    // 3. Security: Logged in
    if (!is_user_logged_in()) return;

    $current_user_id = get_current_user_id();

    // Inputs
    $username = isset($_POST['user_name']) ? sanitize_user($_POST['user_name']) : '';
    $email    = sanitize_email($_POST['user_email']);
    $role     = sanitize_text_field($_POST['user_role']);
    $password = $_POST['user_password']; // wp_insert_user handles hashing

    // 4. Determine if Edit or Create
    $target_user_id = isset($_POST['editing_user_id']) ? intval($_POST['editing_user_id']) : 0;
    $is_edit = ($target_user_id > 0);

    if ($is_edit) {
        // --- EDIT MODE ---
        // CRITICAL: Check if the logged-in user is the PARENT of the user being edited
        $parent_id = get_user_meta($target_user_id, 'parent_user_id', true);
        if ($parent_id != $current_user_id) {
            wp_die('Permission denied. You do not manage this user.');
        }

        $args = [
            'ID'         => $target_user_id,
            'user_email' => $email,
            'role'       => $role
        ];
        
        // Only update password if provided
        if (!empty($password)) {
            $args['user_pass'] = $password;
        }

        $user_id = wp_update_user($args);
    } else {
        // --- CREATE MODE ---
        $user_id = wp_insert_user([
            'user_login' => $username,
            'user_pass'  => $password,
            'user_email' => $email,
            'role'       => $role,
        ]);

        if (!is_wp_error($user_id)) {
            // Link new user to current user
            update_user_meta($user_id, 'parent_user_id', $current_user_id);
        }
    }

    // 5. Handle Image & Redirect
    if (!is_wp_error($user_id)) {
        // Handle Profile Image
        $attachment_id = larkon_handle_file_upload('profile_image', 0);
        if ($attachment_id) {
            update_user_meta($user_id, 'profile_image_id', $attachment_id);
        }

        // Redirect to All Users
        $redirect = add_query_arg('tab', 'all_users', home_url('/dashboard/'));
        wp_safe_redirect($redirect);
        exit;
    } else {
        wp_die($user_id->get_error_message());
    }
}




add_action('admin_post_handle_user_form', 'larkon_process_user_form');



function larkon_process_contract_form() {

    if ( ! isset( $_POST['larkon_create_contract'] ) ) return;

    if (
        ! isset( $_POST['larkon_contract_nonce'] ) ||
        ! wp_verify_nonce( $_POST['larkon_contract_nonce'], 'larkon_new_contract' )
    ) {
        wp_die('Security check failed.');
    }

    if ( ! is_user_logged_in() ) return;

    $current_user_id   = get_current_user_id();
    $title             = sanitize_text_field( $_POST['post_title'] );
    $content           = wp_kses_post( $_POST['post_content'] );
    $contract_type_id  = intval( $_POST['contract_type'] );
    $payment_method_id = intval( $_POST['payment_model'] );
    $student_id        = intval( $_POST['contract_student'] );
    $teacher_id        = intval( $_POST['contract_teacher'] );
    $status        = $_POST['contract_status'];

    $post_id = isset($_POST['editing_post_id']) ? intval($_POST['editing_post_id']) : 0;
    $is_edit = $post_id > 0;

    $post_data = [
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => 'publish',
        'post_type'    => 'contract',
    ];

    if ( $is_edit ) {
        $existing_post = get_post( $post_id );
        if ( ! $existing_post || $existing_post->post_author != $current_user_id ) {
            wp_die('You do not have permission.');
        }
        $post_data['ID'] = $post_id;
        $result_id = wp_update_post( $post_data );
    } else {
        $post_data['post_author'] = $current_user_id;
        $result_id = wp_insert_post( $post_data );
    }

    if ( is_wp_error( $result_id ) || ! $result_id ) {
        wp_die('Failed to save contract.');
    }

    // Taxonomies
    wp_set_object_terms( $result_id, $contract_type_id, 'contract-type' );
    wp_set_object_terms( $result_id, $payment_method_id, 'payment-model' );

    // Meta
    update_post_meta( $result_id, 'contract_student', $student_id );
    update_post_meta( $result_id, 'contract_teacher', $teacher_id );
    update_post_meta( $result_id, 'contract_status', $status );

    if(! $is_edit) {
        $message = "New Contract Assigned:
        $title";
        spn_create_notification( $student_id, $teacher_id, $message );
    }

    wp_safe_redirect( add_query_arg( 'tab', 'all_contracts', home_url('/dashboard/') ) );
    exit;
}
add_action( 'admin_post_handle_contract_form', 'larkon_process_contract_form' );


add_action('wp_ajax_larkon_submit_chat_msg', 'larkon_handle_chat_save');

function larkon_handle_chat_save() {
    // 1. Verify Nonce
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'larkon_chat_action')) {
        wp_send_json_error(['message' => 'Security check failed']);
    }

    // 2. Verify Login
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in']);
    }

    $current_user = wp_get_current_user();
    $post_id = intval($_POST['post_id']);
    $message = sanitize_textarea_field($_POST['message']);

    if (empty($message)) {
        wp_send_json_error(['message' => 'Message cannot be empty']);
    }

    // 3. Insert Comment
    $comment_id = wp_insert_comment([
        'comment_post_ID'      => $post_id,
        'comment_author'       => $current_user->display_name,
        'comment_author_email' => $current_user->user_email,
        'comment_content'      => $message,
        'user_id'              => $current_user->ID,
        'comment_approved'     => 1,
    ]);

   if ($comment_id) {
    // UPDATED HTML STRUCTURE TO MATCH THE NEW UI
    $html = '
    <div class="lk-chat-row lk-msg-container-right">
        <div class="lk-chat-bubble lk-bubble-me">
            <div class="lk-chat-meta">
                <strong>You</strong> 
                <span>' . date('M d, H:i') . '</span>
            </div>
            <div class="lk-chat-text">
                ' . wpautop(esc_html($message)) . '
            </div>
        </div>
    </div>';
    
    wp_send_json_success(['html' => $html]);
}
}




// Handle AJAX: Quick Edit Status
add_action('wp_ajax_larkon_save_status', 'larkon_ajax_save_status');

// Handle AJAX: Mark Notification Read
add_action('wp_ajax_larkon_mark_notification_read', 'larkon_ajax_mark_notification_read');


function larkon_ajax_save_status() {
    check_ajax_referer('larkon_quick_edit_nonce', 'security');

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $new_status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';

    if (!$post_id || !$new_status) wp_send_json_error('Invalid data.');

    update_post_meta($post_id, 'contract_status', $new_status);
    update_post_meta($post_id, $role . '_contract_status', $new_status);

    // Notifications
    $student_id = get_post_meta($post_id, 'contract_student', true);
    $teacher_id = get_post_meta($post_id, 'contract_teacher', true);

    if (function_exists('spn_notify_student_rejection') && $new_status === 'reject') {
        spn_notify_student_rejection($student_id, $teacher_id, $post_id);
    } elseif (function_exists('spn_notify_teacher_submission') && $new_status === 'submit_teacher') {
        spn_notify_teacher_submission($student_id, $teacher_id, $post_id);
    }
    
    // Get label for UI
    $choices = get_contract_status_choices();
    $new_label = $choices[$new_status] ?? ucfirst($new_status);

    wp_send_json_success(['message' => 'Status Updated', 'new_label' => $new_label]);
}

function larkon_ajax_mark_notification_read() {
    check_ajax_referer('lk_read_nonce', 'security');
    
    if (!function_exists('spn_get_notification')) wp_send_json_error('Missing notification functions');

    $notif_id = intval($_POST['notification_id']);
    $user_id = get_current_user_id();
    $row = spn_get_notification($notif_id);

    if (!$row) wp_send_json_error('Notification not found');

    $data_to_update = [];
    if ($row['student_id'] == $user_id) {
        $data_to_update['student_read'] = 1;
    } elseif ($row["teacher_id"] == $user_id) {
        $data_to_update['teacher_read'] = 1;
    } else {
        wp_send_json_error('Permission denied');
    }

    spn_update_notification($notif_id, $data_to_update);
    wp_send_json_success('Updated');
}


function hec_handle_child_user_deletion()
{
    if (isset($_GET['hec_action']) && $_GET['hec_action'] === 'delete_child_user') {
        if (!is_user_logged_in()) wp_die('Log in required.');

        $child_id = isset($_GET['child_id']) ? intval($_GET['child_id']) : 0;
        $current_user_id = get_current_user_id();

        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_child_' . $child_id)) {
            wp_die('Security failed.');
        }

        $parent_id = get_user_meta($child_id, 'parent_user_id', true);
        if ((int)$parent_id !== $current_user_id) wp_die('Permission denied.');

        require_once(ABSPATH . 'wp-admin/includes/user.php');
        if (wp_delete_user($child_id, $current_user_id)) {
            // Redirect back to the All Users tab
            $redirect = add_query_arg('tab', 'all_users', remove_query_arg(['hec_action', 'child_id', '_wpnonce']));
            wp_safe_redirect($redirect);
            exit;
        }
    }
}
add_action('init', 'hec_handle_child_user_deletion');