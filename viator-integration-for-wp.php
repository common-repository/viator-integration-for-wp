<?php
/*
Plugin Name: Viator Integration For WP
Description: Viator Integration For WP - Sync API data of viator to Woocommerce Products
Version: 1.1
Text Domain: viator-integration-for-wp
Domain Path: /languages
Author: Integratordev
Author URI: https://integrator.dev
Donate link: https://www.paypal.me/Kaludi/25
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// Exit if accessed directly
if(!defined('ABSPATH')) exit;

define('VIATORWP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VIATORWP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VIATORWP_MAIN_PLUGIN_DIR', plugin_dir_url(__DIR__));
define('VIATORWP_VERSION', '1.1');
define('VIATORWP_API_ENDPOINT', 'https://api.viator.com/partner/');

require_once VIATORWP_PLUGIN_DIR.'frontend/viator_frontend_shop.php';

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'viatorwp_add_plugin_action_links', 10, 5);
function viatorwp_add_plugin_action_links($actions){
    $mylinks = array(
        '<a href="' . esc_url(admin_url( 'admin.php?page=viator-integration-for-wp' )) . '">'.esc_html__('Settings', 'viator-integration-for-wp').'</a>',
    );
    $actions = array_merge( $actions, $mylinks );
    return $actions;
}
require_once VIATORWP_PLUGIN_DIR.'admin/settings.php';
require_once VIATORWP_PLUGIN_DIR.'admin/newsletter.php';