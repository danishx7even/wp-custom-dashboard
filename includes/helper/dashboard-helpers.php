<?php
function get_sub_dashboard_config($user) {
    // Helper to get unread count
    $unread_count = 0;
    if(function_exists('spn_count_notifications')) {
        $unread_count = spn_count_notifications($user->ID, $user->roles[0]);
    }

    return [
        'info' => [
        'title' => 'Dashboard Overview',
        'icon'  => 'fas fa-tachometer-alt', // dashboard icon, more intuitive than user
        'show_in_menu' => true,
        'callback' => 'larkon_controller_tab_info'
    ],

    'notifications' => [
        'title'    => 'Notifications',
        'icon'     => 'fas fa-bell', // bell is perfect for notifications
        'show_in_menu' => true,
        'callback' => 'larkon_controller_tab_notifications',
        'badge'    => $unread_count
    ],

    'contracts' => [
        'title' => 'Contracts',
        'icon'  => 'fas fa-file-alt', // simpler document icon, matches contracts
        'show_in_menu' => true,
        'callback' => 'larkon_controller_tab_contracts'
    ],

    'discussion' => [
        'title' => 'Discussion',
        'icon'  => 'fas fa-comments', // conversation/comments icon is perfect
        'show_in_menu' => false, 
        'callback' => 'larkon_controller_tab_discussion'
    ],

    'logout' => [
        'title' => 'Logout',
        'icon'  => 'fas fa-sign-out-alt', // classic logout icon
        'show_in_menu' => true,
        'callback' => 'larkon_view_logout'
    ]
    ];
}

/**
 * 1. CONFIGURATION
 * Defines tabs and maps them to Controller functions.
 */
function get_main_dashboard_config() {
    $tabs = [
    'info' => [
        'title' => 'Dashboard Overview',
        'icon'  => 'bx bxs-dashboard',
        'show_in_menu' => true,
        'controller' => 'larkon_controller_tab_info'
    ],

    'form_builder' => [
        'title'    => 'Form Builder',
        'icon'     => 'bx bxs-layout',
        'controller' => 'wcd_form_builder_controller',
        'show_in_menu' => true
    ],

    'all_users' => [
        'title'    => 'All Users',
        'icon'     => 'bx bxs-group',
        'controller' => 'larkon_ctrl_sub_users',
        'show_in_menu' => true
    ],

    'insert_user' => [
        'title'    => 'Insert User',
        'icon'     => 'bx bxs-user-plus',
        'controller' => 'larkon_ctrl_user_form',
        'show_in_menu' => true
    ],

    'all_contracts' => [
        'title'    => 'All Contracts',
        'icon'     => 'bx bxs-file',
        'controller' => 'larkon_ctrl_contracts',
        'show_in_menu' => true
    ],

    'insert_contract' => [
        'title'    => 'Insert Contract',
        'icon'     => 'bx bxs-file-plus',
        'controller' => 'larkon_ctrl_contract_form',
        'show_in_menu' => true
    ],

    'discussion' => [
        'title' => 'Discussion',
        'icon'  => 'fas fa-comments', // conversation/comments icon is perfect
        'show_in_menu' => false, 
        'callback' => 'larkon_controller_tab_discussion'
    ],
    // Hidden tab for Editing
    'edit_contract' => [
        'title'    => 'Edit Contract',
        'icon'     => 'bx bxs-edit',
        'controller' => 'larkon_ctrl_contract_form',
        'show_in_menu' => false
    ],

    'logout' => [
        'title' => 'Logout',
        'icon'  => 'bx bxs-log-out',
        'show_in_menu' => true,
        'controller' => 'larkon_ctrl_logout'
    ]
];

    return apply_filters('larkon_dashboard_tabs', $tabs);
}





function larkon_get_allowed_statuses_for_contracts($current_user_roles) {
    // Mocking the ACF field retrieval for logic separation
    // $all_choices = [
    //     'assign_student' => 'Assigned to the Student',
    //     'submit_teacher' => 'Submitted to the Teacher',
    //     'work_start' => 'Work Start',
    //     'need_details' => 'Need More Details',
    //     'reject' => 'Rejected',
    //     'approve' => 'Approved'
    // ];
    
    // if(function_exists('get_field_object')) {
    //     $field_obj = get_field_object('field_6946d132ac215');
    //     if($field_obj) $all_choices = $field_obj['choices'];
    // }

    $all_choices = get_contract_status_choices();
    $allowed_keys = [];
    if (in_array('administrator', $current_user_roles) || in_array('subscriber', $current_user_roles)) {
        return $all_choices;
    } elseif (in_array('student', $current_user_roles)) {
        $allowed_keys = ['submit_teacher', 'work_start', 'need_details']; 
    } elseif (in_array('teacher', $current_user_roles)) {
        $allowed_keys = ['reject', 'approve']; 
    }

    return array_filter($all_choices, function($key) use ($allowed_keys) {
        return in_array($key, $allowed_keys);
    }, ARRAY_FILTER_USE_KEY);
}


function larkon_get_allowed_statuses($current_user_roles) {
    // // Default / Fallback labels
    // $all_choices = [
    //     'assign_student' => 'Assigned to the Student',
    //     'submit_teacher' => 'Submitted to the Teacher',
    //     'work_start'     => 'Work Start',
    //     'need_details'   => 'Need More Details',
    //     'reject'         => 'Rejected',
    //     'approve'        => 'Approved'
    // ];
    
    // // Attempt to fetch fresh labels from ACF if available
    // if(function_exists('get_field_object')) {
    //     $field_obj = get_field_object('field_6946d132ac215');
    //     if($field_obj && !empty($field_obj['choices'])) {
    //         $all_choices = $field_obj['choices'];
    //     }
    // }
    $all_choices = get_contract_status_choices();

    $allowed_keys = [];

    // Define which STATUS KEYs each role is allowed to see stats for
    if (in_array('administrator', $current_user_roles) || in_array('subscriber', $current_user_roles)) {
        return $all_choices; // Admins see everything
    } 
    elseif (in_array('student', $current_user_roles)) {
        // Student sees specific keys
        $allowed_keys = ['assign_student', 'work_start', 'submit_teacher', 'need_details', 'reject', 'approve']; 
    } 
    elseif (in_array('teacher', $current_user_roles)) {
        // UPDATE: Teacher now sees ALL keys too, just like the student
        $allowed_keys = ['assign_student', 'work_start', 'submit_teacher', 'need_details', 'reject', 'approve']; 
    }

    // Return only the allowed entries
    return array_filter($all_choices, function($key) use ($allowed_keys) {
        return in_array($key, $allowed_keys);
    }, ARRAY_FILTER_USE_KEY);
}

function count_contracts_by_role_status( $role, $status ) {
    $user_id = get_current_user_id();

    if ( empty( $role ) || empty( $status ) || empty( $user_id ) ) {
        return 0;
    }

    $args = [
        'post_type'      => 'contract',
        'post_status'    => 'publish',
        'posts_per_page' => 1,      
        'fields'         => 'ids',  
        'no_found_rows'  => false, 
        'meta_query'     => [
            'relation' => 'AND',
        ],
    ];

    // 1. Status Filter
    $args['meta_query'][] = [
        'key'   => 'contract_status', 
        'value' => $status,
    ];

    // 2. Role Ownership Filter
    if ( $role === 'student' ) {
        $args['meta_query'][] = [
            'key'   => 'contract_student',
            'value' => $user_id,
        ];
    } elseif ( $role === 'teacher' ) {
        $args['meta_query'][] = [
            'key'   => 'contract_teacher',
            'value' => $user_id,
        ];
    } else {
        // Admins/Others: count posts they authored
        $args['author'] = $user_id;
    }

    $query = new WP_Query( $args );
    return (int) $query->found_posts;
}


/**
 * Handle delete action via query vars
 * Example: ?larkon_action=delete&pid=374
 */
function larkon_handle_delete_action()
{
    if (!isset($_GET['larkon_action'], $_GET['pid'])) {
        return;
    }

    if ($_GET['larkon_action'] !== 'delete') {
        return;
    }

    $post_id = absint($_GET['pid']);
    if (!$post_id) {
        return;
    }

    $post = get_post($post_id);
    if (!$post) {
        return;
    }
    
    // 🔐 Author ownership check
    if ((int) $post->post_author !== get_current_user_id()) {
        return;
    }

    // Delete the post
    wp_delete_post($post_id, true);

    // Build clean redirect URL (remove query vars)
    $redirect_url = remove_query_arg(
        array('larkon_action', 'pid'),
        wp_get_referer() ?: home_url()
    );

    wp_safe_redirect($redirect_url);
    exit;
}
add_action('init', 'larkon_handle_delete_action');
