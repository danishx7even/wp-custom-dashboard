<?php


if ( ! defined( 'ABSPATH' ) ) {
    exit; // Prevent direct access
}

/**
 * Create pages on plugin activation
 */
function cpp_create_pages() {

    $pages = array(
        array(
            'title'   => 'Student Dashboard',
            'slug'    => 'student-dashboard',
            'content' => '[user_dashboard role="student"]'
        ),
        array(
            'title'   => 'Teacher Dashboard',
            'slug'    => 'teacher-dashboard',
            'content' => '[user_dashboard role="teacher"]'
        ),
        array(
            'title'   => 'Dashboard',
            'slug'    => 'dashboard',
            'content' => '[larkon_dashboard]'
        ),
        array(
            'title'   => 'Login Form',
            'slug'    => 'login-form',
            'content' => '[login_form]'
        )
    );

    foreach ( $pages as $page ) {

        // Check if page already exists
        $existing_page = get_page_by_path( $page['slug'] );

        if ( ! $existing_page ) {
            wp_insert_post( array(
                'post_title'   => $page['title'],
                'post_name'    => $page['slug'],
                'post_content' => $page['content'],
                'post_status'  => 'publish',
                'post_type'    => 'page'
            ) );
        }
    }
}

// Run on plugin activation
// register_activation_hook( __FILE__, 'cpp_create_pages' );
