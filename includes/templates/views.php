<?php

/* -------------------------------------------------------------------------- */
/*                                LOGIN VIEW                                  */
/* -------------------------------------------------------------------------- */

function larkon_view_login_page($error_code)
{
?>
    <div class="auth-page-wrapper">
        <div class="lk-card login-card" style="max-width: 450px; width: 100%; margin: 0 auto;">
            <div class="lk-card-header" style="text-align:center;">
                <h2 class="lk-card-title">Login to Dashboard</h2>
                <p style="color:var(--text-color); font-size:13px; margin:0;">Welcome back! Please sign in.</p>
            </div>

            <?php if ($error_code == 'invalid_credentials'): ?>
                <div style="background-color: #ffe5e5; color: #ff3333; padding: 10px 24px; font-size: 13px; border-bottom: 1px solid #ffcccc;">
                    <i class="fas fa-exclamation-circle"></i> Invalid username or password.
                </div>
            <?php endif; ?>

            <div class="lk-card-body">
                <form method="POST" action="">
                    <?php wp_nonce_field('custom_login_nonce', 'login_nonce'); ?>
                    <div class="lk-form-group">
                        <label class="lk-label">Username or Email</label>
                        <input type="text" name="user_name" class="lk-input" placeholder="Enter username" required>
                    </div>
                    <div class="lk-form-group">
                        <label class="lk-label">Password</label>
                        <input type="password" name="user_password" class="lk-input" placeholder="Enter password" required>
                    </div>
                    <div class="lk-form-group btn-group" style="margin-bottom:0;">
                        <button type="submit" name="login_user" class="lk-btn-submit" style="width:100%;">Login</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php
}

function larkon_view_logout_action()
{
    $logout_url = wp_logout_url(home_url('/login-form'));
?>
    <div class="lk-card">
        <div class="lk-card-body" style="text-align:center; padding: 50px;">
            <h3>Logging you out...</h3>
            <p>Please wait.</p>
        </div>
    </div>
    <script type="text/javascript">
        window.location.href = "<?php echo $logout_url; ?>";
    </script>
<?php
}

function larkon_view_permission_denied($role)
{
    return '<div class="lk-card" style="padding:20px; color: var(--danger-color);">
                <h3>Permission Denied</h3>
                <p>You do not have the required permissions (' . esc_html($role) . ') to view this dashboard.</p>
            </div>';
}

/* -------------------------------------------------------------------------- */
/*                            DASHBOARD SHELL                                 */
/* -------------------------------------------------------------------------- */

function larkon_view_dashboard_shell($user, $role, $tabs, $current_tab, $content)
{
    $base_url = get_permalink();
?>
    <style>
        .lk-menu-item.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
            border-right: 3px solid #fff;
        }

        .lk-menu-link {
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            height: 100%;
        }

        .lk-badge-count {
            background-color: #e74c3c;
            color: white;
            font-size: 11px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: auto;
        }
    </style>

    <div class="larkon-wrapper">
        <!-- SIDEBAR -->
        <div class="lk-sidebar">
            <div class="lk-logo-section"><i class='bx bxs-basket lk-logo-icon'></i> <span>Dashboard</span></div>
            <div class="lk-menu-header">Menu</div>

            <?php if ($tabs): ?>
                <?php foreach ($tabs as $key => $tab) : ?>
                    <?php
                    if (!$tab['show_in_menu']) continue;
                    $is_active = ($current_tab === $key) ? 'active' : '';
                    $link = esc_url(add_query_arg('tab', $key, $base_url));
                    ?>
                    <div class="lk-menu-item <?php echo $is_active; ?>">
                        <a href="<?php echo $link; ?>" class="lk-menu-link">
                            <i class='<?php echo esc_attr($tab['icon']); ?> lk-menu-icon'></i>
                            <span><?php echo esc_html($tab['title']); ?></span>
                            <?php if (isset($tab['badge']) && $tab['badge'] > 0): ?>
                                <span class="lk-badge-count"><?php echo intval($tab['badge']); ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- MAIN CONTENT -->
        <div class="lk-main-content">
            <div class="lk-top-header">
                <div class="lk-page-title">Welcome <?= ucfirst($user->display_name) ?></div>
                <div><?php echo get_avatar($user->ID, 32, '', '', array('class' => 'user-avatar', 'style' => 'border-radius:50%;')); ?></div>
            </div>
            <div class="lk-tab-content">
                <?= $content; ?>
            </div>
        </div>
    </div>
<?php
}

/* -------------------------------------------------------------------------- */
/*                             TAB CONTENT VIEWS                              */
/* -------------------------------------------------------------------------- */

function larkon_view_tab_info($stats)
{
?>
    <div class="dashboard-container">
        <h2 class="section-title">Contract Statistics</h2>
        <div class="cards-grid">
            <?php foreach ($stats as $stat):
                // Create a CSS class based on the status key (e.g., 'stat-approve', 'stat-reject')
                $status_class = 'stat-' . esc_attr($stat['type']);
            ?>
                <div class="card lk-card-stat <?php echo $status_class; ?>">
                    <h3 class="card-title"><?= esc_html($stat['label']) ?></h3>
                    <p class="card-count"><?= intval($stat['count']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <style>
        /* Basic Styles to make it look decent immediately */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .lk-card-stat {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #ccc;
        }

        .lk-card-stat .card-title {
            margin: 0 0 10px;
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .lk-card-stat .card-count {
            font-size: 28px;
            font-weight: bold;
            margin: 0;
            color: #333;
        }

        /* Optional Color Coding based on status */
        .stat-all {
            border-left-color: #333;
        }

        .stat-assign_student {
            border-left-color: #3498db;
        }

        /* Blue */
        .stat-work_start {
            border-left-color: #f39c12;
        }

        /* Orange */
        .stat-submit_teacher {
            border-left-color: #9b59b6;
        }

        /* Purple */
        .stat-approve {
            border-left-color: #2ecc71;
        }

        /* Green */
        .stat-reject {
            border-left-color: #e74c3c;
        }

        /* Red */
    </style>
<?php
}

function larkon_view_tab_contracts($query, $current_user, $allowed_choices)
{
    // Enqueue script for quick edit
    wp_enqueue_script('larkon-js', WCD_ASSETS . 'js/edit-status.js', ['jquery'], '1.0.0', true);
    wp_localize_script('larkon-js', 'larkon_vars', ['ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('larkon_quick_edit_nonce')]);

    $role = $current_user->roles[0];

    // Initialize role-specific labels
    $role_labels = [];

    // Define labels based on role
    if (in_array('student', $current_user->roles)) {
        $role_labels = [
            'assign_student' => 'Assigned to me',
            'submit_teacher' => 'Submitted to Teacher',
            'work_start'     => 'Work Started',
            'need_details'   => 'Need More Details',
            'reject'         => 'Rejected by Teacher',
            'approve'        => 'Approved by Teacher'
        ];
    } elseif (in_array('teacher', $current_user->roles)) {
        $role_labels = [
            'assign_student' => 'Pending Student Response',
            'submit_teacher' => 'Submitted to me',
            'work_start'     => 'Student Started Work',
            'need_details'   => 'Student Needs Details',
            'reject'         => 'Rejected',
            'approve'        => 'Approved'
        ];
    }
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
                        <th></th>
                        <?php if ($role === 'student') {
                            echo '<th>Assign_teacher</th>';
                        } elseif ($role === 'teacher') {
                            echo '<th>Assign_student</th>';
                        }?>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($query->have_posts()) : ?>
                        <?php while ($query->have_posts()) : $query->the_post();
                            $post_id = get_the_ID();
                            $is_owner = (get_the_author_meta('ID') === $current_user->ID);

                            $student_obj = get_userdata(get_post_meta($post_id, 'contract_student', true));
                            $teacher_obj = get_userdata(get_post_meta($post_id, 'contract_teacher', true));
                            $type_terms = wp_get_post_terms($post_id, 'contract-type');
                            $pay_terms = wp_get_post_terms($post_id, 'payment-model');

                            // Get raw status value from DB
                            $current_val = get_post_meta($post_id, 'contract_status', true);

                            $all_statuses = get_contract_status_choices();

                            $status_label = '';

                            if (array_key_exists($current_val, $all_statuses)) {
                                $status_label = $all_statuses[$current_val];
                            }


                            // 2. Determine Final Label: Check Role Array -> Fallback to ACF -> Fallback to raw
                            $current_label = $role_labels[$current_val] ?? $allowed_choices[$current_val] ?? $status_label;

                            // Clean up display
                            $current_label = ucfirst($current_label);

                            $can_edit = !empty($allowed_choices);
                        ?>
                            <tr>
                                <td colspan="2" data-label="Title"><strong><?php the_title(); ?></strong></td>
        
                                <?php
                                if ($role === 'student') : ?>
                                 <td data-label="Teacher"><?php echo $teacher_obj ? ucfirst($teacher_obj->user_nicename) : 'N/A'; ?></td>
                                 <?php elseif ($role === 'teacher') : ?>
                                    <td data-label="Student"><?php echo $student_obj ? ucfirst($student_obj->user_nicename) : 'N/A'; ?></td>
                                <?php endif ?>
                                
                            
                                

                                <td data-label="Status" class="larkon-status-cell">
                                    <div class="larkon-view-mode">
                                        <!-- DISPLAY THE ROLE SPECIFIC LABEL HERE -->
                                        <span class="status-text-label" style="font-weight:600;"><?php echo esc_html($current_label); ?></span>
                                        <?php if ($can_edit): ?>
                                            <a href="#" class="larkon-quick-edit-trigger" style="display:block; font-size:12px; color:chocolate;">Quick Edit</a>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($can_edit): ?>
                                        <div class="larkon-edit-mode" style="display:none;">
                                            <form class="larkon-inline-status-form" data-post-id="<?php echo esc_attr($post_id); ?>" data-role="<?= esc_attr($role) ?>">
                                                <select name="new_status" class="larkon-status-select" style="width:100%;">
                                                    <option value="<?php echo esc_attr($current_val); ?>">-- No Change --</option>
                                                    <?php
                                                    // Loop through allowed choices, but show Role Specific Text
                                                    foreach ($allowed_choices as $val => $lab):
                                                        if ($val === $current_val) continue;
                                                        // Determine the label for the dropdown option
                                                        $option_label = $role_labels[$val] ?? $lab;
                                                    ?>
                                                        <option value="<?= esc_attr($val) ?>"><?= esc_html(ucfirst($option_label)) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div style="margin-top:5px;">
                                                    <button type="button" class="larkon-save-btn button-primary">Save</button>
                                                    <button type="button" class="larkon-cancel-btn button-secondary">Cancel</button>
                                                </div>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo get_the_date(); ?></td>
                                <td>
                                    <?php $discuss_url = add_query_arg(['tab' => 'discussion', 'pid' => $post_id]); ?>
                                    <a href="<?php echo esc_url($discuss_url); ?>" class="lk-action-btn lk-btn-chat" style="background-color:bisque;"><i class='bx bx-message-rounded-dots'></i></a>
                                    <a href="<?php the_permalink(); ?>" class="lk-action-btn lk-btn-view" target="_blank"><i class='bx bx-show'></i></a>
                                    <?php if ($is_owner) : ?>
                                        <a href="?larkon_action=delete&pid=<?php the_ID(); ?>" class="lk-action-btn lk-btn-delete" onclick="return confirm('Are you sure?');"><i class='bx bx-trash'></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile;
                        wp_reset_postdata(); ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="8" style="text-align:center;">No contracts found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php
}

function larkon_view_tab_notifications($notifications, $current_user_id)
{

    // Enqueue script for quick edit
    wp_enqueue_script('larkon-js', WCD_ASSETS . 'js/notification.js', ['jquery'], '1.0.0', true);
    wp_localize_script('larkon-js', 'larkon_vars', ['ajax_url' => admin_url('admin-ajax.php')]);
?>
    <style>
        .lk-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .lk-table th,
        .lk-table td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .row-unread {
            background-color: #f0fbff;
        }
    </style>
    <div class="lk-card">
        <div class="lk-card-header">
            <h3>Your Notifications</h3>
        </div>
        <?php if (empty($notifications)): ?>
            <p style="padding:20px;">No notifications.</p>
        <?php else: ?>
            <table class="lk-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notifications as $row):
                        $is_student = ($row['student_id'] == $current_user_id);
                        $is_read = $is_student ? (int)$row["student_read"] : (int)$row["teacher_read"];
                        $row_class = $is_read ? '' : 'row-unread';
                    ?>
                        <tr class="<?= $row_class; ?>" id="notif-row-<?php echo $row['id']; ?>">
                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td><?php echo esc_html($row['message']); ?></td>
                            <td class="status-cell"><?php echo $is_read ? '<span style="color:green;">Read</span>' : '<span style="color:orange;">Unread</span>'; ?></td>
                            <td>
                                <?php if (!$is_read): ?>
                                    <button class="lk-btn-read mark-read-btn" data-id="<?php echo $row['id']; ?>" data-nonce="<?php echo wp_create_nonce('lk_read_nonce'); ?>">Mark Read</button>
                                <?php else: ?> — <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php
}

function larkon_view_tab_discussion($post_id, $contract_title, $comments, $current_user_id, $back_link)
{
    // Enqueue script for quick edit
    wp_enqueue_script('larkon-discussion-js', WCD_ASSETS . 'js/discussion.js', [], '1.0.0', true);
    wp_localize_script('larkon-discussion-js', 'larkon_vars', ['ajax_url' => admin_url('admin-ajax.php')]);
?>
    <div id="larkon-chat-container-unique" class="lk-card larkon-chat-wrapper">
        <div class="lk-card-header">
            <div class="lk-header-left">
                <a href="<?php echo esc_url($back_link); ?>" class="lk-back-btn"><i class='bx bx-arrow-back'></i> Back</a>
                <h2 class="lk-card-title">Chat: <?php echo esc_html($contract_title); ?></h2>
            </div>
        </div>
        <div class="larkon-chat-body">
            <div class="larkon-chat-history" id="chat-history-<?php echo $post_id; ?>">
                <?php if ($comments) : foreach ($comments as $comment) :
                        $is_me = ($comment->user_id == $current_user_id);
                        $container_class = $is_me ? 'lk-msg-container-right' : 'lk-msg-container-left';
                        $bubble_class = $is_me ? 'lk-bubble-me' : 'lk-bubble-other';
                ?>
                        <div class="lk-chat-row <?php echo $container_class; ?>">
                            <div class="lk-chat-bubble <?php echo $bubble_class; ?>">
                                <div class="lk-chat-meta"><strong><?php echo $is_me ? 'You' : esc_html($comment->comment_author); ?></strong> <span><?php echo get_comment_date('M d, H:i', $comment); ?></span></div>
                                <div class="lk-chat-text"><?php echo wpautop($comment->comment_content); ?></div>
                            </div>
                        </div>
                    <?php endforeach;
                else: ?>
                    <div class="lk-no-messages">
                        <p>No messages yet. Start the discussion!</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="larkon-chat-footer">
                <form class="larkon-chat-form">
                    <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                    <input type="hidden" name="action" value="larkon_submit_chat_msg">
                    <?php wp_nonce_field('larkon_chat_action', 'security'); ?>
                    <div class="lk-chat-input-group">
                        <textarea name="message" placeholder="Type message..." required></textarea>
                        <button type="submit" class="button button-primary">Send</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php
}




















function larkon_view_error($data)
{
    echo '<div class="lk-card"><div style="padding:20px; color:red;">' . esc_html($data['msg']) . '</div></div>';
}

function larkon_view_sub_users($data)
{
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
                    <?php if (empty($data['users'])) : ?>
                        <tr>
                            <td colspan="5" style="text-align:center;">No users found.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($data['users'] as $u) : ?>
                            <tr>
                                <td data-label="ID">#<?php echo esc_html($u['ID']); ?></td>
                                <td data-label="Username">
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <img src="<?= esc_url($u['avatar_url']) ?>" style="width:30px; height:30px; border-radius:50%; object-fit:cover;">
                                        <strong><?php echo esc_html($u['login']); ?></strong>
                                    </div>
                                </td>
                                <td data-label="Email"><?php echo esc_html($u['email']); ?></td>
                                <td data-label="Role"><span class="lk-badge"><?php echo esc_html($u['role_name']); ?></span></td>
                                <td data-label="Actions">
                                    <a href="<?php echo esc_url($u['edit_url']); ?>" class="lk-action-btn lk-btn-edit"><i class='bx bx-pencil'></i></a>
                                    <a href="<?php echo esc_url($u['delete_url']); ?>" class="lk-action-btn lk-btn-delete" onclick="return confirm('Delete user?');"><i class='bx bx-trash'></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php
}

function larkon_view_user_form($data)
{
?>
    <div class="lk-card">
        <h3><?php echo $data['is_edit'] ? 'Edit User' : 'Create User'; ?></h3>
        <form method="POST" action="<?= $data['form_action'] ?>" enctype="multipart/form-data">
            <input type="hidden" name="action" value="handle_user_form">
            <input type="hidden" name="user_nonce" value="<?= $data['nonce'] ?>">
            <?php if ($data['is_edit']): ?>
                <input type="hidden" name="editing_user_id" value="<?php echo $data['edit_id']; ?>">
            <?php endif; ?>

            <div class="lk-form-group">
                <label class="lk-label">Username</label>
                <input type="text" name="user_name" class="lk-input" value="<?php echo esc_attr($data['val_name']); ?>"
                    <?php echo $data['is_edit'] ? 'readonly style="background:#eee"' : 'required'; ?>>
            </div>
            <div class="lk-form-group">
                <label class="lk-label">Email</label>
                <input type="email" name="user_email" class="lk-input" value="<?php echo esc_attr($data['val_email']); ?>" required>
            </div>
            <div class="lk-form-group">
                <label class="lk-label">Password <?php echo $data['is_edit'] ? '(Leave empty to keep)' : ''; ?></label>
                <input type="password" name="user_password" class="lk-input" <?php echo $data['is_edit'] ? '' : 'required'; ?>>
            </div>
            <div class="lk-form-group">
                <label class="lk-label">Role</label>
                <select name="user_role" class="lk-select">
                    <?php foreach ($data['available_roles'] as $role): ?>
                        <option value="<?= $role; ?>" <?php selected($data['val_role'], $role); ?>><?= ucfirst($role); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="lk-form-group">
                <label class="lk-label">Profile Image</label>
                <input type="file" name="profile_image" class="lk-input">
            </div>
            <button type="submit" name="hec_create_user" class="lk-btn-submit">
                <?php echo $data['is_edit'] ? 'Update User' : 'Create User'; ?>
            </button>
        </form>
    </div>
<?php
}

function larkon_view_contracts($data)
{
?>
    <div class="lk-card">
        <div class="lk-card-header">
            <h2 class="lk-card-title">All Contracts</h2>
        </div>
        <div class="lk-table-responsive">
            <table class="lk-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th></th>
                        <!-- <th>Type</th>
                        <th>Pay Model</th> -->
                        <th>Assigned_student</th>
                        <th>Assigned_teacher</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['contracts'])) : ?>
                        <tr>
                            <td colspan="8" style="text-align:center;">No contracts found.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($data['contracts'] as $c) : ?>
                            <tr>
                                <td colspan="2"><strong><?php echo esc_html($c['title']); ?></strong></td>
                                <!-- <td><?php echo esc_html(ucfirst($c['type'])); ?></td>
                                <td><?php echo esc_html(ucfirst($c['payment_model'])); ?></td> -->
                                <td><?php echo esc_html(ucfirst($c['student_name'])); ?></td>
                                <td><?php echo esc_html(ucfirst($c['teacher_name'])); ?></td>
                                <td><?php echo esc_html(ucfirst($c['status_label'])); ?></td>
                                <td><?php echo esc_html($c['date']); ?></td>
                                <td>
                                    <a href="<?php echo $c['permalink']; ?>" target="_blank" class="lk-action-btn lk-btn-view"><i class='bx bx-show'></i></a>
                                    <a href="<?php echo $c['edit_url']; ?>" class="lk-action-btn lk-btn-edit"><i class='bx bx-pencil'></i></a>
                                    <a href="<?php echo $c['discuss_url']; ?>" class="lk-action-btn lk-btn-chat" style="background-color:bisque;"><i class='bx bx-message-rounded-dots'></i></a>
                                    <a href="<?php echo $c['delete_url']; ?>" class="lk-action-btn lk-btn-delete" onclick="return confirm('Sure?');"><i class='bx bx-trash'></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php
}

function larko_view_contract_form($data)
{
?>
    <div class="lk-card">
        <div class="lk-card-header">
            <h2 class="lk-card-title"><?php echo $data['is_edit'] ? 'Edit Contract' : 'Create New Contract'; ?></h2>
        </div>

        <form method="POST" action="<?= $data['form_action'] ?>" enctype="multipart/form-data">
            <input type="hidden" name="action" value="handle_contract_form">
            <input type="hidden" name="larkon_contract_nonce" value="<?= $data['nonce'] ?>">
            <?php if ($data['is_edit']): ?>
                <input type="hidden" name="editing_post_id" value="<?php echo $data['post_id']; ?>">
            <?php endif; ?>

            <div class="lk-form-group">
                <label class="lk-label">Contract Title</label>
                <input type="text" name="post_title" class="lk-input" value="<?php echo esc_attr($data['title']); ?>" required>
            </div>
            <div class="lk-form-group">
                <label class="lk-label">Content</label>
                <textarea name="post_content" class="lk-textarea" rows="8" required><?php echo esc_textarea($data['content']); ?></textarea>
            </div>

            <!-- Taxonomy Dropdowns -->
            <div class="lk-form-group">
                <label class="lk-label">Type</label>
                <select name="contract_type" required>
                    <?php foreach ($data['contract_types'] as $term): ?>
                        <option value="<?= $term->term_id ?>" <?= selected($term->term_id, $data['saved_type']) ?>><?= ucfirst($term->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="lk-form-group">
                <label class="lk-label">Payment Model</label>
                <select name="payment_model" required>
                    <?php foreach ($data['payment_models'] as $term): ?>
                        <option value="<?= $term->term_id ?>" <?= selected($term->term_id, $data['saved_model']) ?>><?= ucfirst($term->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="lk-form-group">
                <label class="lk-label">Status</label>
                <select name="contract_status" required>
                    <?php foreach ($data['status_choices'] as $val => $label): ?>
                        <option value="<?= esc_attr($val) ?>" <?= selected($data['saved_status'], $val) ?>><?= esc_html($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- User Dropdowns -->
            <div class="lk-form-group">
                <label class="lk-label">Student</label>
                <select name="contract_student" required>
                    <option value="">Select Student</option>
                    <?php foreach ($data['students'] as $u): ?>
                        <option value="<?= $u->ID ?>" <?= selected($u->ID, $data['saved_student']) ?>><?= ucfirst($u->display_name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="lk-form-group">
                <label class="lk-label">Teacher</label>
                <select name="contract_teacher" required>
                    <option value="">Select Teacher</option>
                    <?php foreach ($data['teachers'] as $u): ?>
                        <option value="<?= $u->ID ?>" <?= selected($u->ID, $data['saved_teacher']) ?>><?= ucfirst($u->display_name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="lk-form-group">
                <label class="lk-label">Featured Image</label>
                <?php if ($data['has_thumbnail']): ?>
                    <div style="margin-bottom:10px;">
                        <?= $data['thumbnail_html'] ?>
                        <small>Current Image</small>
                    </div>
                <?php endif; ?>
                <input type="file" name="post_thumbnail" class="lk-input" <?= $data['is_edit'] ? '' : 'required' ?>>
            </div>

            <button type="submit" name="larkon_create_contract" class="lk-btn-submit">
                <?php echo $data['is_edit'] ? 'Update Contract' : 'Assign Contract'; ?>
            </button>
        </form>
    </div>
<?php
}




function wcd_form_builder_view($data)
{

    // wp_enqueue_script('form_builder', WCD_ASSETS . 'js/form-builder.js', array('jquery'));

    ob_start();
    echo do_shortcode('[contract_template_builder]');
    return ob_get_clean();
}

function larkon_view_contract_form($data)
{
    ob_start();
    echo do_shortcode('[contract_submission]');
    return ob_get_clean();
}














































