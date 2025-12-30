<?php

/**
 * 2. MAIN ROUTER
 * Orchestrates: Request -> Controller -> View
 */
function larkon_dashboard_shortcode() {
    if (!is_user_logged_in()) {
        return '<div class="lk-card" style="padding:20px;">Please <a href="' . wp_login_url() . '">login</a>.</div>';
    }

    $current_user_id = get_current_user_id();
    $tabs = get_main_dashboard_config();
    $default_tab = array_key_first($tabs);
    $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : $default_tab;

    // Get current page URL without any query parameters
    $base_url = remove_query_arg(array_keys($_GET));

    // Output Layout Wrapper
    ob_start();
    ?>
    <div class="larkon-wrapper">
        <!-- Sidebar -->
        <div class="lk-sidebar">
            <div class="lk-logo-section"><i class='bx bxs-basket lk-logo-icon'></i><span>Dashboard</span></div>
            <div class="lk-menu-header">Menu</div>
            <?php foreach ($tabs as $key => $tab) : ?>
                <?php if (!$tab['show_in_menu']) continue; ?>
                <?php $is_active = ($current_tab === $key) ? 'active' : ''; ?>
                <div class="lk-menu-item <?php echo $is_active; ?>">
                    <a href="<?php echo esc_url(add_query_arg('tab', $key, $base_url)); ?>" class="lk-menu-link">
                        <i class='<?php echo esc_attr($tab['icon']); ?> lk-menu-icon'></i>
                        <span><?php echo esc_html($tab['title']); ?></span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Main Content -->
        <div class="lk-main-content">
            <div class="lk-top-header">
                <div class="lk-page-title"><?php echo isset($tabs[$current_tab]) ? esc_html($tabs[$current_tab]['title']) : 'Dashboard'; ?></div>
                <div><?php echo get_avatar($current_user_id, 32, '', '', array('class' => 'user-avatar')); ?></div>
            </div>

            <div class="lk-tab-content">
                <?php
                // Tab content logic
                if (isset($tabs[$current_tab])) {
                    $tab = $tabs[$current_tab];
                    
                    // Check for controller first
                    if (isset($tab['controller']) && function_exists($tab['controller'])) {
                        // Execute controller logic
                        $controller_func = $tab['controller'];
                        $data = call_user_func($controller_func); 
                        
                        // Render view if controller returns view function
                        if (isset($data['view_func']) && function_exists($data['view_func'])) {
                            echo call_user_func($data['view_func'], $data);
                        } 
                        // If controller returns content directly
                        elseif (isset($data['content'])) {
                            echo $data['content'];
                        }
                        // If controller already echoed output
                        else {
                            // Controller handled output directly
                        }
                    } 
                    // Check for callback
                    elseif (isset($tab['callback']) && function_exists($tab['callback'])) {
                        echo call_user_func($tab['callback']);
                    }
                    // No valid handler found
                    else {
                        echo '<div class="lk-card">Tab handler not found or not configured properly.</div>';
                    }
                } else {
                    echo '<div class="lk-card">Tab Not Found</div>';
                }
                ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('larkon_dashboard', 'larkon_dashboard_shortcode');