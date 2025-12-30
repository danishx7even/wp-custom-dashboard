<?php

/**
 * 1. CONFIGURATION (The "Brain")
 * Define all your tabs here.
 */
function larkon_get_dashboard_config()
{

    

    $tabs = [
        'info' => [
            'title' => 'Dashboard Overview',
            'icon'  => 'fas fa-user',
            'show_in_menu' => true,
            'callback' => 'larkon_controller_tab_info'
        ],
        // 'all_posts' => [
        //     'title'    => 'All Posts',
        //     'icon'     => 'bx bxs-grid-alt', // Boxicons class
        //     'callback' => 'larkon_render_posts_view',
        //     'show_in_menu' => true
        // ],
        // 'insert_post' => [
        //     'title'    => 'Insert Post',
        //     'icon'     => 'bx bxs-edit',
        //     'callback' => 'larkon_render_post_form_view',
        //     'show_in_menu' => true
        // ],
        'all_users' => [
            'title'    => 'All Users',
            'icon'     => 'bx bxs-user',
            'callback' => 'larkon_render_sub_users_view',
            'show_in_menu' => true
        ],
        'insert_user' => [
            'title'    => 'Insert User',
            'icon'     => 'bx bxs-user-plus',
            'callback' => 'larkon_render_user_form_view',
            'show_in_menu' => true
        ],
        // Hidden tab for Editing (Re-uses the Insert Post form)
        'edit_post' => [
            'title'    => 'Edit Post',
            'icon'     => '',
            'callback' => 'larkon_render_post_form_view',
            'show_in_menu' => false
        ],
        'all_contracts' => [
            'title'    => 'All Contracts',
            'icon'     => 'bx bxs-archive',
            'callback' => 'larkon_render_contracts_view',
            'show_in_menu' => true
        ],
        'insert_contract' => [
            'title'    => 'Insert Contract',
            'icon'     => 'bx bxs-user-plus',
            'callback' => 'larkon_render_contract_form_view',
            'show_in_menu' => true
        ],
        'discussion' => [
            'title' => 'Discussion',
            'icon'  => 'fas fa-comments',
            'show_in_menu' => false,
            'callback' => 'larkon_controller_tab_discussion'
        ],
        'logout' => [
            'title' => 'Logout',
            'icon'  => 'fas fa-sign-out-alt', // Logout Icon
            'show_in_menu' => true,
            'callback' => 'larkon_render_logout_action' // The function below
        ]
    ];

    return apply_filters('larkon_dashboard_tabs', $tabs);
}

/**
 * 2. THE MAIN ROUTER (The Container)
 */
function larkon_dashboard_shortcode()
{
    // 1. Security Check
    if (!is_user_logged_in()) {
        return '<div class="lk-card" style="padding:20px;">Please <a href="' . wp_login_url() . '">login</a> to view the dashboard.</div>';
    }

    $current_user_id = get_current_user_id();
    $user = wp_get_current_user();

    // 2. Get Config & Current Tab
    $tabs = larkon_get_dashboard_config();
    // Default to first key if 'tab' is missing in URL
    $default_tab = array_key_first($tabs);
    $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : $default_tab;

    // 3. Start Output
    ob_start();
?>

    <!-- Inline Style for Active State (Optional, helps visual debugging) -->
    <style>
        /* Add a class for active state in your CSS file, or use this: */
        .lk-menu-item.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
            border-right: 3px solid #fff;
        }

        /* Make links encompass the menu item */
        .lk-menu-link {
            text-decoration: none;
            color: inherit;
            display: block;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>

    <div class="larkon-wrapper">

        <!-- SIDEBAR -->
        <div class="lk-sidebar">
            <div class="lk-logo-section">
                <i class='bx bxs-basket lk-logo-icon'></i>
                <span>Dashboard</span>
            </div>

            <div class="lk-menu-header">Menu</div>

            <?php foreach ($tabs as $key => $tab) : ?>
                <?php if (!$tab['show_in_menu']) continue; ?>

                <?php
                $is_active = ($current_tab === $key) ? 'active' : '';
                $link = esc_url(add_query_arg('tab', $key));
                ?>

                <!-- Note: Wrapped content in <a> tag for real navigation -->
                <div class="lk-menu-item <?php echo $is_active; ?>">
                    <a href="<?php echo $link; ?>" class="lk-menu-link">
                        <i class='<?php echo esc_attr($tab['icon']); ?> lk-menu-icon'></i>
                        <span><?php echo esc_html($tab['title']); ?></span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- MAIN CONTENT -->
        <div class="lk-main-content">

            <!-- Header -->
            <div class="lk-top-header">
                <div class="lk-page-title">
                    <?php
                    // Dynamic Title based on current tab
                    echo isset($tabs[$current_tab]) ? esc_html($tabs[$current_tab]['title']) : 'Dashboard';
                    ?>
                </div>
                <div>
                    <?php echo get_avatar($current_user_id, 32, '', '', array('class' => 'user-avatar', 'style' => 'border-radius:50%;')); ?>
                </div>
            </div>

            <!-- Dynamic Content Area -->
            <div class="lk-tab-content">
                <?php
                if (isset($tabs[$current_tab]) && is_callable($tabs[$current_tab]['callback'])) {
                    call_user_func($tabs[$current_tab]['callback']);
                } else {
                    echo '<div class="lk-card"><div class="lk-card-header"><h2>404 - Tab Not Found</h2></div></div>';
                }
                ?>
            </div>

        </div>
    </div>

<?php
    return ob_get_clean();
}
add_shortcode('larkon_dashboard', 'larkon_dashboard_shortcode');


/* ==========================================================================
   3. VIEW FUNCTIONS (The Content)
   Renamed slightly to indicate they are Views.
   Removed inner ob_start/return, just echo content.
   ========================================================================== */

function larkon_render_posts_view()
{
    // Logic for query
    $args = array(
        'post_type'      => 'post',
        'posts_per_page' => 10,
        'post_status'    => array('publish', 'pending', 'draft'),
        'author'         => get_current_user_id()
    );

    $query = new WP_Query($args);
?>

    <div class="lk-card">
        <div class="lk-card-header">
            <h2 class="lk-card-title">All Product List</h2>
        </div>
        <div class="lk-table-responsive">
            <table class="lk-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($query->have_posts()) : ?>
                        <?php while ($query->have_posts()) : $query->the_post();
                            $author_id = get_the_author_meta('ID');
                            $is_owner = ($author_id === get_current_user_id());
                        ?>
                            <tr>
                                <td data-label="ID">#<?php the_ID(); ?></td>
                                <td data-label="Title">
                                    <strong><?php the_title(); ?></strong><br>
                                    <small style="color:var(--text-color);"><?php echo get_the_category_list(', '); ?></small>
                                </td>
                                <td data-label="Author"><?php the_author(); ?></td>
                                <td data-label="Published"><?php echo get_the_date(); ?></td>
                                <td data-label="Status">
                                    <?php
                                    $status = get_post_status();
                                    $color = ($status == 'publish') ? '#22c55e' : '#f59e0b';
                                    ?>
                                    <span style="color:<?php echo $color; ?>; font-weight:600; text-transform:capitalize;"><?php echo $status; ?></span>
                                </td>
                                <td data-label="Actions">
                                    <a href="<?php the_permalink(); ?>" class="lk-action-btn lk-btn-view" target="_blank" title="View"><i class='bx bx-show'></i></a>

                                    <?php if ($is_owner) : ?>
                                        <!-- Edit Link: Points to 'edit_post' tab -->
                                        <?php
                                        $edit_url = add_query_arg(['tab' => 'insert_post', 'pid' => get_the_ID()]);
                                        ?>
                                        <a href="<?php echo esc_url($edit_url); ?>" class="lk-action-btn lk-btn-edit" title="Edit">
                                            <i class='bx bx-pencil'></i>
                                        </a>

                                        <!-- Delete Link: Keeps existing logic -->
                                        <a href="?larkon_action=delete&pid=<?php the_ID(); ?>" class="lk-action-btn lk-btn-delete" onclick="return confirm('Are you sure?');" title="Delete">
                                            <i class='bx bx-trash'></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile;
                        wp_reset_postdata(); ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="6" style="text-align:center;">No posts found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php
}

function larkon_render_post_form_view()
{
    // 1. Setup Variables
    $post_id = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
    $is_edit = ($post_id > 0);
    $post_to_edit = $is_edit ? get_post($post_id) : null;

    // 2. Security: Ensure user owns post if editing
    // We check if $post_to_edit exists AND if the author matches
    if ($is_edit && (!$post_to_edit || $post_to_edit->post_author != get_current_user_id())) {
        echo '<div class="lk-card"><div style="padding:20px; color:red;">Permission Denied or Post Not Found.</div></div>';
        return;
    }

    // 3. Prepare Default Values
    $title_val   = $is_edit ? $post_to_edit->post_title : '';
    $content_val = $is_edit ? $post_to_edit->post_content : '';

    // Get current category ID for pre-selection
    $cat_val = 0;
    if ($is_edit) {
        $cats = get_the_category($post_id);
        if (!empty($cats)) {
            $cat_val = $cats[0]->term_id;
        }
    }
?>

    <div class="lk-card">
        <div class="lk-card-header">
            <h2 class="lk-card-title"><?php echo $is_edit ? 'Edit Post' : 'Create New Post'; ?></h2>
        </div>

        <form class="creation-form" id="createPostForm" method="POST" action="<?= esc_url(admin_url('admin-post.php')) ?>" enctype="multipart/form-data">

            <input type="hidden" name="action" value="handle_post_form">

            <!-- NONCE & ACTION -->
            <?php wp_nonce_field('larkon_new_post', 'larkon_nonce'); ?>

            <!-- HIDDEN ID (Vital for the handler to know which post to update) -->
            <?php if ($is_edit): ?>
                <input type="hidden" name="editing_post_id" value="<?php echo $post_id; ?>">
            <?php endif; ?>

            <div class="lk-form-group">
                <label class="lk-label">Post Title</label>
                <input type="text" name="post_title" class="lk-input" value="<?php echo esc_attr($title_val); ?>" placeholder="Enter post title" required>
            </div>

            <div class="lk-form-group">
                <label class="lk-label">Category</label>
                <?php
                // Added 'selected' argument to pre-fill the category
                wp_dropdown_categories([
                    'name'       => 'post_category',
                    'class'      => 'lk-select',
                    'hide_empty' => 0,
                    'selected'   => $cat_val
                ]);
                ?>
            </div>

            <div class="lk-form-group">
                <label class="lk-label">Content</label>
                <textarea name="post_content" class="lk-textarea" placeholder="Write something amazing..." rows="8" required><?php echo esc_textarea($content_val); ?></textarea>
            </div>

            <div class="lk-form-group">
                <label class="lk-label">Featured Image</label>

                <!-- PREVIEW EXISTING IMAGE -->
                <?php if ($is_edit && has_post_thumbnail($post_id)): ?>
                    <div style="margin-bottom: 10px;">
                        <?php echo get_the_post_thumbnail($post_id, 'thumbnail', ['style' => 'height: 60px; width: auto; border-radius: 4px; border: 1px solid #ddd;']); ?>
                        <div style="font-size: 12px; color: #666; margin-top: 5px;">Current Image (Upload below to replace)</div>
                    </div>
                <?php endif; ?>


                <input type="file" name="post_thumbnail" class="lk-input">

            </div>

            <button type="submit" name="larkon_create_post" class="lk-btn-submit">
                <?php echo $is_edit ? 'Update Post' : 'Create Post'; ?>
            </button>
        </form>
    </div>
<?php
}

function larkon_render_sub_users_view()
{
    $current_user_id = get_current_user_id();
    $args = array(
        'meta_key'   => 'parent_user_id',
        'meta_value' => $current_user_id,
        'orderby'    => 'ID',
        'order'      => 'DESC',
    );
    $sub_users = get_users($args);
?>

    <div class="lk-card">
        <div class="lk-card-header">
            <h2 class="lk-card-title">Users List</h2>
        </div>
        <div class="lk-table-responsive">
            <table class="lk-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($sub_users)) : ?>
                        <?php foreach ($sub_users as $user) :
                            $user_id = $user->ID;
                            $user_info = get_userdata($user_id);
                            $roles = $user_info->roles;
                            $role_name = !empty($roles) ? ucfirst($roles[0]) : 'None';

                            $img_id = get_user_meta($user_id, 'profile_image_id', true);
                            $img_url = $img_id ? wp_get_attachment_url($img_id) : get_avatar_url($user_id);

                            $delete_url = add_query_arg([
                                'hec_action' => 'delete_child_user',
                                'child_id'   => $user_id,
                                '_wpnonce'   => wp_create_nonce('delete_child_' . $user_id)
                            ]);
                        ?>
                            <tr>
                                <td data-label="ID">#<?php echo esc_html($user_id); ?></td>
                                <td data-label="Username">
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <?php if ($img_url): ?>
                                            <img src="<?= esc_url($img_url) ?>" style="width:30px; height:30px; border-radius:50%; object-fit:cover;">
                                        <?php endif; ?>
                                        <strong><?php echo esc_html($user->user_login); ?></strong>
                                    </div>
                                </td>
                                <td data-label="Email"><?php echo esc_html($user->user_email); ?></td>
                                <td data-label="Role">
                                    <span style="background:#eef2ff; color:#4f46e5; padding:2px 8px; border-radius:4px; font-size:12px; font-weight:600;"><?php echo esc_html($role_name); ?></span>
                                </td>
                                <td data-label="Actions">
                                    <?php

                                    $edit_url = add_query_arg(['tab' => 'insert_user', 'uid' => $user->ID]);
                                    ?>
                                    <a href="<?php echo esc_url($edit_url); ?>" class="lk-action-btn lk-btn-edit" title="Edit">
                                        <i class='bx bx-pencil'></i>
                                    </a>
                                    <a href="<?php echo esc_url($delete_url); ?>" class="lk-action-btn lk-btn-delete" onclick="return confirm('Delete this user?');" title="Delete"><i class='bx bx-trash'></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5" style="text-align:center;">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php
}

function larkon_render_user_form_view()
{
    // 1. Check for Edit Mode
    $is_edit = false;
    $user_data = null;
    $current_user_id = get_current_user_id();

    if (isset($_GET['uid'])) {
        $edit_id = intval($_GET['uid']);
        $parent_id = get_user_meta($edit_id, 'parent_user_id', true);

        // Security: Ensure current user is the parent
        if ($parent_id == $current_user_id) {
            $is_edit = true;
            $user_data = get_userdata($edit_id);
        }
    }

    // 2. Set Default Values
    $val_name  = $is_edit ? $user_data->user_login : '';
    $val_email = $is_edit ? $user_data->user_email : '';
    // Get first role
    $val_role  = ($is_edit && !empty($user_data->roles)) ? array_values($user_data->roles)[0] : '';

?>
    <div class="lk-card">
        <h3><?php echo $is_edit ? 'Edit User' : 'Create User'; ?></h3>

        <form method="POST" action="<?= esc_url(admin_url('admin-post.php')) ?>" enctype="multipart/form-data">

            <input type="hidden" name="action" value="handle_user_form">
            <?php wp_nonce_field('hec_new_user', 'user_nonce'); ?>

            <!-- CRITICAL: Hidden ID field for Handler -->
            <?php if ($is_edit): ?>
                <input type="hidden" name="editing_user_id" value="<?php echo $edit_id; ?>">
            <?php endif; ?>

            <div class="lk-form-group">
                <label class="lk-label">Username</label>
                <!-- Username cannot be changed easily in WP, make readonly if editing -->
                <input type="text" name="user_name" class="lk-input" value="<?php echo esc_attr($val_name); ?>"
                    <?php echo $is_edit ? 'readonly style="background:#eee"' : 'required'; ?>>
            </div>

            <div class="lk-form-group">
                <label class="lk-label">Email</label>
                <input type="email" name="user_email" class="lk-input" value="<?php echo esc_attr($val_email); ?>" required>
            </div>

            <div class="lk-form-group">
                <label class="lk-label">Password <?php echo $is_edit ? '(Leave empty to keep)' : ''; ?></label>
                <input type="password" name="user_password" class="lk-input" <?php echo $is_edit ? '' : 'required'; ?>>
            </div>

            <div class="lk-form-group">
                <label class="lk-label">Role</label>
                <select name="user_role" class="lk-select">
                    <?php global $wp_roles;
                    $roles = ['student', 'teacher'];
                    foreach ($roles as $role): ?>
                        <?php if ($role == 'administrator') continue; ?>
                        <option value="<?php echo $role; ?>" <?php selected($val_role, $role); ?>>
                            <?php echo ucfirst($role); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="lk-form-group">
                <label class="lk-label">Profile Image</label>
                <input type="file" name="profile_image" class="lk-input">
            </div>

            <button type="submit" name="hec_create_user" class="lk-btn-submit">
                <?php echo $is_edit ? 'Update User' : 'Create User'; ?>
            </button>
        </form>
    </div>
<?php
}

/* ==========================================================================
   4. ACTION HANDLERS (Controllers)
   These stay largely the same, just ensure redirects don't break.
   ========================================================================== */

function larkon_handle_dashboard_actions()
{
    // Post Deletion
    if (isset($_GET['larkon_action']) && $_GET['larkon_action'] == 'delete' && isset($_GET['pid'])) {
        $post_id = intval($_GET['pid']);
        $post = get_post($post_id);

        if ($post->post_type === 'post') {
            $redirect_tab = 'all_posts';
        } elseif ($post->post_type === 'contract') {
            $redirect_tab = 'all_contracts';
        }

        if ($post && $post->post_author == get_current_user_id()) {
            wp_trash_post($post_id);
            // Redirect back to the All Posts tab
            $redirect = add_query_arg('tab', $redirect_tab, remove_query_arg(['larkon_action', 'pid']));
            wp_safe_redirect($redirect);
            exit;
        }
    }
}
add_action('template_redirect', 'larkon_handle_dashboard_actions');

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

// Custom Roles (Unchanged)
function create_custom_roles()
{
    add_role('student', 'Student', ['read' => true, 'edit_own_jobs' => true]);
    add_role('teacher', 'Teacher', ['read' => true, 'edit_own_jobs' => true]);
}
add_action('init', 'create_custom_roles');


function larkon_render_contracts_view()
{
    $current_user_id = get_current_user_id();
    $args = array(
        'post_type'  => 'contract',
        'author' => $current_user_id,
    );
    $query = new WP_Query($args);

    $config_status = [
        'assign_student' => 'Assigned to the Student',
        'submit_teacher' => 'Submitted to the Teacher',
        'work_start' => 'Work Start',
        'need_details' => 'Need More Details',
        'reject' => 'Rejected',
        'approve' => 'Approved'
    ]
?>

    <div class="lk-card">
        <div class="lk-card-header">
            <h2 class="lk-card-title">All Product List</h2>
        </div>
        <div class="lk-table-responsive">
            <table class="lk-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Payment Model</th>
                        <th>Assign_student</th>
                        <th>Assign_teacher</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($query->have_posts()) : ?>
                        <?php while ($query->have_posts()) : $query->the_post();
                            $author_id = get_the_author_meta('ID');
                            $is_owner = ($author_id === get_current_user_id());
                        ?>
                            <tr>
                                <td data-label="Title">
                                    <strong><?php the_title(); ?></strong><br>

                                </td>
                                <?php
                                $student_id = get_post_meta(get_the_ID(), 'contract_student', true);
                                $teacher_id = get_post_meta(get_the_ID(), 'contract_teacher', true);
                                $student_name = get_userdata($student_id)->display_name;
                                $teacher_name = get_userdata($teacher_id)->display_name;
                                $type = (wp_get_post_terms(get_the_ID(), 'contract-type')[0])->name;
                                $payment_model = (wp_get_post_terms(get_the_ID(), 'payment-model')[0])->name;

                                $status = get_post_meta(get_the_ID(), 'contract_status', true);
                                $status_name = isset($config_status[$status]) ? $config_status[$status] : 'unknown'

                                ?>
                                <td data-label="Type"><?php echo ucfirst($type) ?></td>
                                <td data-label="Payment Model"><?php echo ucfirst($payment_model); ?></td>
                                <td data-label="Student"><?php echo ucfirst($student_name); ?></td>
                                <td data-label="Teacher"><?php echo ucfirst($teacher_name); ?></td>
                                <td data-label="Status"><?php echo ucfirst($status_name) ?></td>
                                <td data-label="Published"><?php echo get_the_date(); ?></td>
                                <td data-label="Actions">
                                    <a href="<?php the_permalink(); ?>" class="lk-action-btn lk-btn-view" target="_blank" title="View"><i class='bx bx-show'></i></a>

                                    <?php if ($is_owner) : ?>
                                        <!-- Edit Link: Points to 'edit_post' tab -->
                                        <?php
                                        $edit_url = add_query_arg(['tab' => 'insert_contract', 'pid' => get_the_ID()]);
                                        ?>
                                        <a href="<?php echo esc_url($edit_url); ?>" class="lk-action-btn lk-btn-edit" title="Edit">
                                            <i class='bx bx-pencil'></i>
                                        </a>

                                        <?php $discuss_url = add_query_arg(['tab' => 'discussion', 'pid' => get_the_ID()]); ?>
                                        <a href="<?php echo esc_url($discuss_url); ?>" class="lk-action-btn lk-btn-chat" style="background-color:bisque;"><i class='bx bx-message-rounded-dots'></i></a>

                                        <!-- Delete Link: Keeps existing logic -->
                                        <a href="?larkon_action=delete&pid=<?php the_ID(); ?>" class="lk-action-btn lk-btn-delete" onclick="return confirm('Are you sure?');" title="Delete">
                                            <i class='bx bx-trash'></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile;
                        wp_reset_postdata(); ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="6" style="text-align:center;">No posts found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php
}


function larkon_render_contract_form_view()
{

    // 1. Setup Variables
    $post_id = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
    $is_edit = ($post_id > 0);
    $post_to_edit = $is_edit ? get_post($post_id) : null;

    // 2. Security: Ensure user owns post if editing
    // We check if $post_to_edit exists AND if the author matches
    if ($is_edit && (!$post_to_edit || $post_to_edit->post_author != get_current_user_id())) {
        echo '<div class="lk-card"><div style="padding:20px; color:red;">Permission Denied or Post Not Found.</div></div>';
        return;
    }

    // 3. Prepare Default Values
    $title_val   = $is_edit ? $post_to_edit->post_title : '';
    $content_val = $is_edit ? $post_to_edit->post_content : '';

    // Get current category ID for pre-selection
    $cat_val = 0;
    if ($is_edit) {
        $cats = get_the_category($post_id);
        if (!empty($cats)) {
            $cat_val = $cats[0]->term_id;
        }
    }

    $contract_type_id = (wp_get_post_terms($post_id, 'contract-type')[0])->term_id ?? 0;
    $payment_model_id = (wp_get_post_terms($post_id, 'payment-model')[0])->term_id ?? 0;
    $student_id = get_post_meta($post_id, 'contract_student', true) ?? 0;
    $teacher_id = get_post_meta($post_id, 'contract_teacher', true) ?? 0;

?>

    <div class="lk-card">
        <div class="lk-card-header">
            <h2 class="lk-card-title"><?php echo $is_edit ? 'Edit Contract' : 'Create New Contract'; ?></h2>
        </div>

        <form class="creation-form" id="createPostForm" method="POST" action="<?= esc_url(admin_url('admin-post.php')) ?>" enctype="multipart/form-data">

            <input type="hidden" name="action" value="handle_contract_form">

            <!-- NONCE & ACTION -->
            <?php wp_nonce_field('larkon_new_contract', 'larkon_contract_nonce'); ?>

            <!-- HIDDEN ID (Vital for the handler to know which post to update) -->
            <?php if ($is_edit): ?>
                <input type="hidden" name="editing_post_id" value="<?php echo $post_id; ?>">
            <?php endif; ?>

            <div class="lk-form-group">
                <label class="lk-label">Contract Title</label>
                <input type="text" name="post_title" class="lk-input" value="<?php echo esc_attr($title_val); ?>" placeholder="Enter contract title" required>
            </div>

            <div class="lk-form-group">
                <label class="lk-label">Content</label>
                <textarea name="post_content" class="lk-textarea" placeholder="Write something amazing..." rows="8" required><?php echo esc_textarea($content_val); ?></textarea>
            </div>

            <div class="lk-form-group">
                <label class="lk-label">Type</label>
                <select name="contract_type" id="" required>
                    <?php
                    $terms = get_terms(array('taxonomy'   => 'contract-type', 'hide_empty' => false));
                    foreach ($terms as $term):
                        $type_id = $term->term_id;
                        $term_name = $term->term_name;
                    ?>
                        <option value="<?= $type_id ?>" <?= $type_id === $contract_type_id ? 'selected' : '' ?>><?= ucfirst($term->name) ?></option>
                    <?php endforeach; ?>
                </select>

            </div>

            <div class="lk-form-group">
                <label class="lk-label">Payment Model</label>
                <select name="payment_model" id="" required>
                    <?php
                    $terms = get_terms(array('taxonomy'   => 'payment-model', 'hide_empty' => false));
                    foreach ($terms as $term):
                        $type_id = $term->term_id;
                        $term_name = $term->term_name;
                    ?>
                        <option value="<?= $type_id ?>" <?= $type_id === $payment_model_id ? 'selected' : '' ?>><?= ucfirst($term->name) ?></option>
                    <?php endforeach; ?>
                </select>

            </div>

            <div class="lk-form-group">
                <label class="lk-label">Status</label>
                <select name="contract_status" id="contract_status" required>
                    <?php
                    $choices = get_contract_status_choices() ?? [];


                    $saved_status = get_post_meta($post_id, 'contract_status', true);
                    $current_choice = $saved_status ?: array_key_first($choices);

                    foreach ($choices as $value => $label):
                    ?>
                        <option value="<?= esc_attr($value) ?>" <?php selected($current_choice, $value); ?>>
                            <?= esc_html(ucfirst($label)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="lk-form-group">
                <label class="lk-label">Student</label>
                <select name="contract_student" required>
                    <option value="">Select Student</option>

                    <?php

                    $users = get_users(array(
                        'role' => 'student',
                        'meta_key' => 'parent_user_id',
                        'meta_value' => get_current_user_id()
                    ));

                    foreach ($users as $user):
                    ?>
                        <option value="<?= $user->ID ?>" <?= selected($user->ID, $student_id) ?>> <?= ucfirst($user->display_name) ?> </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="lk-form-group">
                <label class="lk-label">Teacher</label>
                <select name="contract_teacher" required>
                    <option value="">Select Teacher</option>

                    <?php

                    $users = get_users(array(
                        'role' => 'teacher',
                        'meta_key' => 'parent_user_id',
                        'meta_value' => get_current_user_id()
                    ));

                    foreach ($users as $user):
                    ?>
                        <option value="<?= $user->ID ?>" <?= selected($user->ID, $teacher_id) ?>> <?= ucfirst($user->display_name) ?> </option>

                    <?php endforeach; ?>

                </select>


            </div>




            <div class="lk-form-group">
                <label class="lk-label">Featured Image</label>

                <!-- PREVIEW EXISTING IMAGE -->
                <?php if ($is_edit && has_post_thumbnail($post_id)): ?>
                    <div style="margin-bottom: 10px;">
                        <?php echo get_the_post_thumbnail($post_id, 'thumbnail', ['style' => 'height: 60px; width: auto; border-radius: 4px; border: 1px solid #ddd;']); ?>
                        <div style="font-size: 12px; color: #666; margin-top: 5px;">Current Image (Upload below to replace)</div>
                    </div>
                <?php endif; ?>


                <input type="file" name="post_thumbnail" class="lk-input" required>

            </div>

            <button type="submit" name="larkon_create_contract" class="lk-btn-submit">
                <?php echo $is_edit ? 'Update Contract' : 'Assign Contract'; ?>
            </button>
        </form>
    </div>
<?php
}
