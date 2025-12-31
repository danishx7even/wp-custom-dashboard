<?php
/**
 * Plugin Name:       WP Custom Dashboard
 * Plugin URI:        https://example.com/plugins/the-basics/
 * Description:       This plugin provides the functionality to create dashboards , custom roles and working with custom post types. 
 * Version:           1.1.1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Muhammad Danish
 * Author URI:        https://author.example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://example.com/my-plugin/
 * Text Domain:       wp-custom-dashboard
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


define('WCD_PATH', plugin_dir_path(__FILE__));
define('WCD_URL', plugin_dir_url(__FILE__));
define('WCD_BASENAME', plugin_basename(__FILE__));


// require WCD_PATH . 'plugin-update-checker-5.6/plugin-update-checker.php';
// use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// $myUpdateChecker = PucFactory::buildUpdateChecker(
// 	'https://github.com/danishx7even/wp-custom-dashboard.git',
// 	__FILE__,
// 	'wp-custom-dashboard'
// );

//Set the branch that contains the stable release.
// $myUpdateChecker->setBranch('main');

//Optional: If you're using a private repository, specify the access token like this:
// $myUpdateChecker->setAuthentication('your-token-here');

// including the initialization file
require_once WCD_PATH . 'includes/init.php';



register_activation_hook(__FILE__, function() {

    spn_install_table();


    // create pages
    cpp_create_pages();

    // changing permalinks style
    global $wp_rewrite;
    $wp_rewrite->set_permalink_structure('/%postname%/');

    // Save the setting
    $wp_rewrite->flush_rules();
});