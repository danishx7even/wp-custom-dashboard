<?php
add_action('init', 'larkon_handle_login_submission');
add_action('init', 'larkon_handle_logout_logic');
add_filter('logout_redirect', 'larkon_force_logout_redirect', 10, 3);
add_shortcode('login_form', 'larkon_controller_login_page');


function larkon_controller_login_page() {
    // Logic: Check if logged in and redirect
    if (is_user_logged_in()) {
        larkon_redirect_logged_in_user();
        return; 
    }

    // Logic: Prepare data
    $error_code = isset($_GET['login_error']) ? sanitize_text_field($_GET['login_error']) : '';
    show_admin_bar(false);

    // View: Render
    ob_start();
    larkon_view_login_page($error_code);
    return ob_get_clean();
}


function larkon_redirect_logged_in_user() {
    $user = wp_get_current_user();
    $url = in_array('administrator', $user->roles) ? admin_url() : home_url('/dashboard');
    echo '<script>window.location.href="' . esc_url($url) . '";</script>';
    exit;
}

function larkon_force_logout_redirect($redirect_to, $requested_redirect_to, $user) {
    return home_url('/login-form');
}

function larkon_handle_login_submission() {
    if (!isset($_POST['login_user'])) return;

    if (!isset($_POST['login_nonce']) || !wp_verify_nonce($_POST['login_nonce'], 'custom_login_nonce')) {
        wp_die('Security check failed.');
    }

    $creds = [
        'user_login'    => sanitize_text_field($_POST['user_name']),
        'user_password' => $_POST['user_password'],
        'remember'      => true
    ];

    $user = wp_signon($creds, is_ssl());

    if (is_wp_error($user)) {
        wp_redirect(add_query_arg('login_error', 'invalid_credentials', get_permalink()));
        exit;
    } else {
        $roles = $user->roles;
        if (in_array('administrator', $roles)) {
            wp_redirect(admin_url());
        } elseif (in_array('student', $roles) || in_array('teacher', $roles)) {
            wp_redirect(home_url('/' . $roles[0] . '-dashboard'));
        } else {
            wp_redirect(home_url('/dashboard'));
        }
        exit;
    }
}




function larkon_handle_logout_logic() {
    // Check if user is logged in AND is requesting the logout tab
    if ( is_user_logged_in() && isset($_GET['tab']) && $_GET['tab'] === 'logout' ) {
        
        // Perform server-side logout
        wp_logout();
        
        // Redirect to custom login form
        wp_redirect( home_url('/login-form') );
        exit;
    }
}

/**
 * 2. The View Callback (Fallback)
 * If the init hook works, this is never seen. 
 * If the init hook fails for some reason, this provides a JS fallback.
 */
function larkon_view_logout() {
    $custom_login_url = home_url( '/login-form' );
    
    // We do NOT call wp_logout() here to avoid header errors.
    // We assume the init hook handled it, or we force JS redirect.

    ob_start(); 
    ?>
    <div class="larkon-logout-container">
        <p>Logging out...</p>
        <script type="text/javascript">
            window.location.href = "<?php echo esc_url( $custom_login_url ); ?>";
        </script>
    </div>
    <?php
    return ob_get_clean(); 
}


function larkon_custom_logout_redirect( $redirect_to, $requested_redirect_to, $user ) {
    // Redirect to your custom login page
    return home_url( '/login-form' );
}
add_filter( 'logout_redirect', 'larkon_custom_logout_redirect', 10, 3 );