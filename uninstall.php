<?php
/**
 * Uninstall script for Checkout After Product Plugin
 * 
 * This file is executed when the plugin is deleted from WordPress admin.
 * It removes all plugin data including options and any custom database tables.
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('cap_options');
delete_option('cap_version');

// Delete any custom database tables if they exist
global $wpdb;

// Example: Delete custom table if it exists
// $table_name = $wpdb->prefix . 'cap_orders';
// $wpdb->query("DROP TABLE IF EXISTS $table_name");

// Clear any cached data that has been removed
wp_cache_flush();

// Remove any scheduled events
wp_clear_scheduled_hook('cap_cleanup_old_orders');

// Delete any uploaded files (if any)
$upload_dir = wp_upload_dir();
$plugin_upload_dir = $upload_dir['basedir'] . '/checkout-after-product/';

if (is_dir($plugin_upload_dir)) {
    // Remove directory and all contents
    function cap_remove_directory($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        cap_remove_directory($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }
    
    cap_remove_directory($plugin_upload_dir);
}

// Log uninstall for debugging (optional)
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Checkout After Product plugin uninstalled successfully');
} 