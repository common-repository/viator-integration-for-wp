<?php
/** 
 * Uninstall Viator API Sync Plugin 
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

global $wpdb;
$table_name = $wpdb->prefix.'viatorwp_uploaded_products';
$sql = "DROP TABLE IF EXISTS $table_name";
$wpdb->query($sql);

$table_name = $wpdb->prefix.'viatorwp_api_destinations';
$sql = "DROP TABLE IF EXISTS $table_name";
$wpdb->query($sql);

delete_option('viator_data');