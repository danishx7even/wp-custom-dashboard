<?php

/**
 * Controller: All Users
 */
function larkon_ctrl_sub_users() {
    $current_user_id = get_current_user_id();
    $users = get_users([
        'meta_key'   => 'parent_user_id',
        'meta_value' => $current_user_id,
        'orderby'    => 'ID',
        'order'      => 'DESC',
    ]);

    // Format data for the view
    $formatted_users = [];
    foreach ($users as $user) {
        $img_id = get_user_meta($user->ID, 'profile_image_id', true);
        $roles = $user->roles;
        
        $formatted_users[] = [
            'ID' => $user->ID,
            'login' => $user->user_login,
            'email' => $user->user_email,
            'role_name' => !empty($roles) ? ucfirst($roles[0]) : 'None',
            'avatar_url' => $img_id ? wp_get_attachment_url($img_id) : get_avatar_url($user->ID),
            'edit_url' => add_query_arg(['tab' => 'insert_user', 'uid' => $user->ID]),
            'delete_url' => add_query_arg([
                'hec_action' => 'delete_child_user',
                'child_id'   => $user->ID,
                '_wpnonce'   => wp_create_nonce('delete_child_' . $user->ID)
            ])
        ];
    }

    return [
        'view_func' => 'larkon_view_sub_users',
        'users'     => $formatted_users
    ];
}

/**
 * Controller: User Form (Insert/Edit)
 */
function larkon_ctrl_user_form() {
    $is_edit = false;
    $user_data = null;
    $current_user_id = get_current_user_id();
    $edit_id = 0;

    if (isset($_GET['uid'])) {
        $edit_id = intval($_GET['uid']);
        $parent_id = get_user_meta($edit_id, 'parent_user_id', true);
        if ($parent_id == $current_user_id) {
            $is_edit = true;
            $user_data = get_userdata($edit_id);
        }
    }

    $roles = ['student', 'teacher']; // Dynamic roles could be fetched here

    return [
        'view_func' => 'larkon_view_user_form',
        'is_edit'   => $is_edit,
        'edit_id'   => $edit_id,
        'form_action' => esc_url(admin_url('admin-post.php')),
        'nonce'     => wp_create_nonce('hec_new_user'),
        'val_name'  => $is_edit ? $user_data->user_login : '',
        'val_email' => $is_edit ? $user_data->user_email : '',
        'val_role'  => ($is_edit && !empty($user_data->roles)) ? array_values($user_data->roles)[0] : '',
        'available_roles' => $roles
    ];
}

/**
 * Controller: All Contracts
 */
function larkon_ctrl_contracts() {
    $args = [
        'post_type'  => 'contract',
        'author'     => get_current_user_id(),
        'posts_per_page' => -1 // Or paginate
    ];
    $query = new WP_Query($args);

    $config_status =get_contract_status_choices();

    $contracts = [];
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $pid = get_the_ID();
            
            // Fetch Meta
            $s_id = get_post_meta($pid, 'contract_student', true);
            $t_id = get_post_meta($pid, 'contract_teacher', true);
            $raw_status = get_post_meta($pid, 'contract_status', true);
            
            // Fetch Terms
            $type_objs = wp_get_post_terms($pid, 'contract-type');
            $type_name = !empty($type_objs) ? $type_objs[0]->name : '-';
            
            $model_objs = wp_get_post_terms($pid, 'payment-model');
            $model_name = !empty($model_objs) ? $model_objs[0]->name : '-';

            $contracts[] = [
                'ID' => $pid,
                'title' => get_the_title(),
                'date'  => get_the_date(),
                'permalink' => get_permalink(),
                'type' => $type_name,
                'payment_model' => $model_name,
                'student_name' => $s_id ? get_userdata($s_id)->display_name : 'N/A',
                'teacher_name' => $t_id ? get_userdata($t_id)->display_name : 'N/A',
                'status_label' => $config_status[$raw_status] ?? 'Unknown',
                'edit_url' => add_query_arg(['tab' => 'insert_contract', 'pid' => $pid]),
                'delete_url' => '?larkon_action=delete&pid=' . $pid,
                'discuss_url' => add_query_arg(['tab' => 'discussion', 'pid' => $pid]),
            ];
        }
        wp_reset_postdata();
    }

    return [
        'view_func' => 'larkon_view_contracts',
        'contracts' => $contracts
    ];
}

/**
 * Controller: Contract Form
 */
function larkon_ctrl_contract_form() {
    $post_id = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
    $is_edit = ($post_id > 0);
    $current_user = get_current_user_id();
    
    // Security check
    if ($is_edit) {
        $post = get_post($post_id);
        if (!$post || $post->post_author != $current_user) {
            return ['view_func' => 'larkon_view_error', 'msg' => 'Permission Denied'];
        }
        $title = $post->post_title;
        $content = $post->post_content;
    } else {
        $title = '';
        $content = '';
    }

    // Fetch Taxonomy Terms
    $contract_types = get_terms(['taxonomy' => 'contract-type', 'hide_empty' => false]);
    $payment_models = get_terms(['taxonomy' => 'payment-model', 'hide_empty' => false]);

    // Fetch Users belonging to this parent
    $students = get_users(['role' => 'student', 'meta_key' => 'parent_user_id', 'meta_value' => $current_user]);
    $teachers = get_users(['role' => 'teacher', 'meta_key' => 'parent_user_id', 'meta_value' => $current_user]);

    // Fetch Saved Values
    $saved_type   = $is_edit ? (wp_get_post_terms($post_id, 'contract-type')[0]->term_id ?? 0) : 0;
    $saved_model  = $is_edit ? (wp_get_post_terms($post_id, 'payment-model')[0]->term_id ?? 0) : 0;
    $saved_student= $is_edit ? get_post_meta($post_id, 'contract_student', true) : 0;
    $saved_teacher= $is_edit ? get_post_meta($post_id, 'contract_teacher', true) : 0;
    $saved_status = $is_edit ? get_post_meta($post_id, 'contract_status', true) : '';

    // Status Options (Logic extracted from view)
    $status_choices = get_contract_status_choices();

    return [
        'view_func' => 'larkon_view_contract_form',
        'is_edit'   => $is_edit,
        'post_id'   => $post_id,
        'title'     => $title,
        'content'   => $content,
        'has_thumbnail' => $is_edit && has_post_thumbnail($post_id),
        'thumbnail_html' => $is_edit ? get_the_post_thumbnail($post_id, 'thumbnail', ['style' => 'height:60px;']) : '',
        'form_action' => esc_url(admin_url('admin-post.php')),
        'nonce'     => wp_create_nonce('larkon_new_contract'),
        // Select Options
        'contract_types' => $contract_types,
        'payment_models' => $payment_models,
        'students'       => $students,
        'teachers'       => $teachers,
        'status_choices' => $status_choices,
        // Saved Values
        'saved_type'    => $saved_type,
        'saved_model'   => $saved_model,
        'saved_student' => $saved_student,
        'saved_teacher' => $saved_teacher,
        'saved_status'  => $saved_status,
    ];
}

/**
 * Controller: Logout
 */
function larkon_ctrl_logout() {
    wp_logout();
    wp_safe_redirect(home_url());
    exit;
}



/**
 * Controller: Form builder
 */
function wcd_form_builder_controller() {


    return [
       'view_func' => 'wcd_form_builder_view', 
    ];
}