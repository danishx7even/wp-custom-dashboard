<?php



function spn_install_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'notifications';
    $charset_collate = $wpdb->get_charset_collate();

    // Added 'target_role' column
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        student_id mediumint(9) NOT NULL,
        teacher_id mediumint(9) NOT NULL,
        message text NOT NULL,
        target_role varchar(20) DEFAULT 'both' NOT NULL, 
        student_read tinyint(1) DEFAULT 0 NOT NULL,
        teacher_read tinyint(1) DEFAULT 0 NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

// Updated Create Function to accept $target_role
function spn_create_notification( $student_id, $teacher_id, $message, $target_role = 'both' ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'notifications';

    $result = $wpdb->insert(
        $table_name,
        array(
            'created_at'   => current_time( 'mysql' ),
            'student_id'   => intval( $student_id ),
            'teacher_id'   => intval( $teacher_id ),
            'message'      => sanitize_textarea_field( $message ),
            'target_role'  => $target_role, // 'student', 'teacher', or 'both'
            'student_read' => 0,
            'teacher_read' => 0
        ),
        array( '%s', '%d', '%d', '%s', '%s', '%d', '%d' )
    );

    return $result ? $wpdb->insert_id : false;
}

/**
 * 3. READ (Get Single or All)
 */

// Get a single notification by ID
function spn_get_notification( $id ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'notifications';
    
    // Prepare statement prevents SQL injection
    $query = $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id );
    return $wpdb->get_row( $query, ARRAY_A );
}

// Get all notifications for a specific user (either as student or teacher)
function spn_get_user_notifications( $user_id, $role = 'student' ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'notifications';
    
    // 1. Identify which ID column to check
    $id_column = ( $role === 'teacher' ) ? 'teacher_id' : 'student_id';

    // 2. Filter: Only show if target_role matches current user OR is for 'both'
    // We check if target_role is the current role OR 'both'
    $query = $wpdb->prepare( 
        "SELECT * FROM $table_name 
         WHERE $id_column = %d 
         AND (target_role = %s OR target_role = 'both') 
         ORDER BY created_at DESC", 
        $user_id, 
        $role 
    );

    return $wpdb->get_results( $query, ARRAY_A );
}

/**
 * 4. UPDATE (General Update & Mark as Read)
 */

// General update function
function spn_update_notification( $id, $data ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'notifications';

    // $data should be an associative array, e.g., ['message' => 'New text']
    $updated = $wpdb->update( 
        $table_name, 
        $data, 
        array( 'id' => $id ) 
    );

    return $updated !== false;
}

// Specific function to mark as read based on role
function spn_mark_as_read( $notification_id, $role ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'notifications';

    $column = ( $role === 'teacher' ) ? 'teacher_read' : 'student_read';

    $updated = $wpdb->update(
        $table_name,
        array( $column => 1 ), // Set to 1 (Read)
        array( 'id' => $notification_id ),
        array( '%d' ),
        array( '%d' )
    );

    return $updated !== false;
}

/**
 * 5. DELETE
 */
function spn_delete_notification( $id ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'notifications';

    $deleted = $wpdb->delete(
        $table_name,
        array( 'id' => $id ),
        array( '%d' )
    );

    return $deleted;
}

/**
 * 6. COUNT FUNCTIONS (Read vs Unread by Role)
 */
function spn_count_notifications( $user_id, $role, $status = 'unread' ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'notifications';

    $id_column   = ( $role === 'teacher' ) ? 'teacher_id' : 'student_id';
    $read_column = ( $role === 'teacher' ) ? 'teacher_read' : 'student_read';
    $status_val  = ( $status === 'read' ) ? 1 : 0;

    $query = $wpdb->prepare( 
        "SELECT COUNT(*) FROM $table_name 
         WHERE $id_column = %d 
         AND $read_column = %d 
         AND (target_role = %s OR target_role = 'both')", 
        $user_id, 
        $status_val,
        $role 
    );

    return $wpdb->get_var( $query );
}


/*
*Call this when a Teacher rejects a contract
*/
function spn_notify_student_rejection( $student_id, $teacher_id, $post_id ) {
$contract_title = get_post($post_id)->post_title;
$message = sprintf( 'Your contract "%s" was rejected by the teacher. Please review comments.', $contract_title );

// Target is 'student' only. Teacher doesn't need to see this notification.
spn_create_notification( $student_id, $teacher_id, $message, 'student' );

}

/*
*Call this when a Student submits a contract
*/
function spn_notify_teacher_submission( $student_id, $teacher_id, $post_id ) {
$contract_title = get_post($post_id)->post_title;
$student_info = get_userdata($student_id);
$name = $student_info ? $student_info->display_name : 'A student';

$message = sprintf( '%s has submitted the contract "%s" for review.', $name, $contract_title );

// Target is 'teacher' only.
spn_create_notification( $student_id, $teacher_id, $message, 'teacher' );

}