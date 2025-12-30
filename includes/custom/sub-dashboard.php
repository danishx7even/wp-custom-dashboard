<?php




/**
 * Shortcode: [user_dashboard role="student"]
 */
add_shortcode('user_dashboard', 'larkon_controller_dashboard');

function larkon_controller_dashboard($atts) {
    $attributes = shortcode_atts(['role' => 'subscriber'], $atts);
    $target_role = sanitize_key($attributes['role']);
    $current_user = wp_get_current_user();

    // Logic: Security Checks
    if (!is_user_logged_in()) {
        return '<div class="lk-card" style="padding:20px;">Please <a href="' . home_url('/login-form') . '">login</a>.</div>';
    }
    if (!in_array($target_role, (array) $current_user->roles)) {
        return larkon_view_permission_denied($target_role);
    }

    // Logic: Get Configuration & Current Tab
    $tabs = get_sub_dashboard_config($current_user);
    $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : array_key_first($tabs);
    
    // Logic: Route to specific content controller
    ob_start();
    
    // Buffer the inner content first
    $inner_content = '';
    if (isset($tabs[$current_tab]) && is_callable($tabs[$current_tab]['callback'])) {
        ob_start();
        call_user_func($tabs[$current_tab]['callback']); 
        $inner_content = ob_get_clean();
    } else {
        $inner_content = '<div class="lk-card"><h3>Tab Not Found</h3></div>';
    }

    // View: Render the Main Dashboard Shell with inner content
    larkon_view_dashboard_shell($current_user, $target_role, $tabs, $current_tab, $inner_content);

    return ob_get_clean();
}






/* -------------------------------------------------------------------------- */
/*                            TAB CONTROLLERS                                 */
/* -------------------------------------------------------------------------- */

function larkon_controller_tab_info() {
    if ( ! is_user_logged_in() ) { return; }

    $user = wp_get_current_user();
    $role = ( ! empty( $user->roles ) ) ? reset( $user->roles ) : '';
    
    // 1. Get the keys allowed for this user
    $allowed_status_array = larkon_get_allowed_statuses( $user->roles );
    
    // 2. Define Context-Aware Labels (Better UX)
    $ux_labels = [];
    
    if ( $role === 'student' ) {
        $ux_labels = [
            'assign_student' => 'Assigned to me',       
            'work_start'     => 'In Progress',          
            'submit_teacher' => 'Submitted',            
            'need_details'   => 'Need Details',
            'reject'         => 'Returned by Teacher', 
            'approve'        => 'Completed'             
        ];
    } elseif ( $role === 'teacher' ) {
        $ux_labels = [
            'assign_student' => 'Pending Student Response', // Clarity: Waiting on student to accept/start
            'work_start'     => 'Student Working',          // Clarity: Work is happening
            'need_details'   => 'Student Needs Info',       // Clarity: Action required by Teacher to explain
            'submit_teacher' => 'Submitted to me',        // Clarity: Action required by Teacher to grade
            'reject'         => 'Returned for Revision',    // Softer than "Rejected"
            'approve'        => 'Graded & Approved'         // Confirmation
        ];
    }

    $stats = [];
    $total_count = 0;

    // 3. Calculate "All Contracts" Count
    $total_args = [
        'post_type'      => 'contract',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => false,
    ];

    if ($role === 'student') {
        $total_args['meta_query'][] = ['key' => 'contract_student', 'value' => $user->ID];
    } elseif ($role === 'teacher') {
        $total_args['meta_query'][] = ['key' => 'contract_teacher', 'value' => $user->ID];
    } else {
        $total_args['author'] = $user->ID;
    }
    
    $total_query = new WP_Query($total_args);
    $total_count = (int) $total_query->found_posts;

    // Add "All Contracts" Card
    $stats[] = [
        'type'  => 'all',
        'label' => 'All Contracts',
        'count' => $total_count
    ];

    // 4. Loop through allowed statuses and build cards
    foreach( $allowed_status_array as $status_key => $original_label ) {
        
        // Determine the label: Use UX label if exists, otherwise fallback to Original
        $display_label = $ux_labels[$status_key] ?? $original_label;

        // Get the count
        $count = count_contracts_by_role_status( $role, $status_key );

        $stats[] = [
            'type'  => $status_key,
            'label' => $display_label,
            'count' => $count
        ];
    }
    
    larkon_view_tab_info( $stats );
}

function larkon_controller_tab_contracts() {
    $current_user = wp_get_current_user();
    $role = $current_user->roles[0];
    
    // Data Query
    $args = [
        'post_type'  => 'contract',
        'posts_per_page' => -1, 
        'meta_query' => [
            [
                'key'     => 'contract_' . $role,
                'value'   => $current_user->ID,
                'compare' => '=',
            ]
        ],
        'orderby' => 'date',
        'order'   => 'DESC',
    ];
    $query = new WP_Query($args);
    
    // Status Config
    $status_choices = larkon_get_allowed_statuses_for_contracts($current_user->roles);

    larkon_view_tab_contracts($query, $current_user, $status_choices);
}

function larkon_controller_tab_discussion() {
    $current_user_id = get_current_user_id();
    $post_id = isset($_GET['pid']) ? intval($_GET['pid']) : 0;

    // Security Check
    if (!$post_id || get_post_type($post_id) !== 'contract') {
        echo '<div class="lk-card"><h3>Contract not found.</h3></div>';
        return;
    }

    $student_id = get_post_meta($post_id, 'contract_student', true);
    $teacher_id = get_post_meta($post_id, 'contract_teacher', true);
    $author_id  = get_post_field('post_author', $post_id);

    if (!in_array($current_user_id, [$student_id, $teacher_id, $author_id]) && !current_user_can('administrator')) {
        echo '<div class="lk-card"><h3 style="color:red;">Unauthorized Access</h3></div>';
        return;
    }

    // Get Data
    $contract_title = get_the_title($post_id);
    $comments = get_comments([
        'post_id' => $post_id,
        'orderby' => 'comment_date',
        'order'   => 'ASC'
    ]);
    
    $back_link = remove_query_arg(['pid', 'tab'], add_query_arg('tab', 'contracts'));

    larkon_view_tab_discussion($post_id, $contract_title, $comments, $current_user_id, $back_link);
}

function larkon_controller_tab_notifications() {
    $user = wp_get_current_user();
    
    // Assuming spn_get_user_notifications is defined elsewhere as per prompt
    $notifications = [];
    if(function_exists('spn_get_user_notifications')) {
        $notifications = spn_get_user_notifications($user->ID, $user->roles[0]);
    }

    larkon_view_tab_notifications($notifications, $user->ID);
}








