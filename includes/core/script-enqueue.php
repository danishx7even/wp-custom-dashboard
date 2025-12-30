<?php




// /**
//  * Enqueue main css file
//  */

// function htc_enqueue_main_styles()
// {

//     wp_enqueue_style(
//         'hello-elementor-main-style-sheet',
//         PARENT_URL . '/style.css',
//         array(),
//         '1.0.0',
//         'all'
//     );
//     wp_enqueue_style(
//         'htc-main-style-sheet',
//         HTC_URL . '/style.css',
//         array('hello-elementor-main-style-sheet', 'elementor-frontend'),
//         '1.0.0',
//         'all'
//     );
// }

// add_action('wp_enqueue_scripts', 'htc_enqueue_main_styles', 20);



// /**
//  * Enqueue scripts and styles for the admin pages
//  */


// function htc_admin_assets()
// {
//     global $typenow;
//     if ('hotel' === $typenow || 'car' === $typenow) {
//         wp_enqueue_media(); // Load WordPress Media Uploader

//         // Inject CSS and JS directly to keep it in one file for you
//         add_action('admin_footer', 'htc_cpt_gallery_metabox_footer_scripts');
//     }
// }

// add_action('admin_enqueue_scripts', 'htc_admin_assets');

// function htc_cpt_gallery_metabox_footer_scripts()
// {

//     wp_enqueue_style('htc-gallery-metabox', HTC_STYLE_URL . 'cpt-gallery-mb.css');
//     wp_enqueue_script('htc-gallery-metabox', HTC_SCRIPTS_URL . 'cpt-gallery-mb.js', array('jquery'), null, true);

//     wp_enqueue_script('htc-admin-script', HTC_SCRIPTS_URL . 'main.js', array('jquery'), null, true);
// }




add_action('wp_head', 'include_font_awesome_library');

function include_font_awesome_library()
{
?>
    <script src="https://kit.fontawesome.com/bf7c3dbbe5.js" crossorigin="anonymous"></script>
<?php
}


/**
 * Enqueue css style sheet for the frontend pages
 */


function htc_enqueue_public_styles()
{

    wp_enqueue_style(
            'global-style',
            WCD_ASSETS .'css/style.css',
            array(),
            '1.0.0',
            'all'
        );

    if (is_page('dashboard') || is_page('login-form') || is_page('student-dashboard') || is_page('teacher-dashboard')) {
        wp_enqueue_style(
            'post-page-style',
            WCD_ASSETS .'css/frontend-dashboard.css',
            array(),
            '1.0.0',
            'all'
        );

        wp_enqueue_style('google-font-inter', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        wp_enqueue_style('boxicons', 'https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css');

       
    }



}


add_action('wp_enqueue_scripts', 'htc_enqueue_public_styles', 10);
