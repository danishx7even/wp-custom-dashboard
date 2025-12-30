<?php

if( ! defined('ABSPATH')) exit;


// Defining paths 
define('WCD_INCLUDE', WCD_PATH . 'includes/');
define('WCD_ASSETS', WCD_URL . 'assets/');






// including files
require_once WCD_INCLUDE . 'helper/debug-helpers.php';
require_once WCD_INCLUDE . 'helper/dashboard-helpers.php';
require_once WCD_INCLUDE . 'auth/dashboard-auth.php';
require_once WCD_INCLUDE . 'templates/views.php';
require_once WCD_INCLUDE . 'core/script-enqueue.php';
require_once WCD_INCLUDE . 'custom/contract-registration.php';
require_once WCD_INCLUDE . 'custom/form-handlers.php';
require_once WCD_INCLUDE . 'custom/dashboard-notification.php';
require_once WCD_INCLUDE . 'custom/dashboard.php';
require_once WCD_INCLUDE . 'custom/controllers.php';
// require_once WCD_INCLUDE . 'custom/main-dashboard.php';
require_once WCD_INCLUDE . 'custom/sub-dashboard.php';
require_once WCD_INCLUDE . 'custom/custom-pages.php';
require_once WCD_INCLUDE . 'custom/form-builder.php';