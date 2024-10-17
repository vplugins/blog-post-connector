<?php
/**
 * Uninstall script for SM Plugin.
 *
 * This file is called when the plugin is uninstalled
 */

// Exit if accessed directly.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Function to remove plugin-specific options from the options table.
function sm_remove_options() {
    global $wpdb;

    // Specify the options to be removed.
    $options = [
        'sm_post_connector_token',
        'sm_post_connector_default_post_type',
        'sm_post_connector_default_author',
        'sm_post_connector_default_category',
        'sm_post_connector_secret_key',
        'sm_post_connector_logo',
        '_transient_timeout_sm_post_connector_latest_release',
        '_site_transient_update_plugins',
    ];

    // Loop through each option and delete it.
    foreach ($options as $option) {
        // Use delete_option() for regular options
        delete_option($option);

        // Use $wpdb->query() to delete transient options, as they might be stored in a different manner
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name = %s", $option));
    }
}

// Function to remove tables.
function sm_remove_tables() {
    global $wpdb;

    // Specify the tables to be removed.
    $tables = [
        'sm_post_connector_token',
        'sm_post_connector_default_post_type',
        'sm_post_connector_default_author',
        'sm_post_connector_default_category',
        'sm_post_connector_secret_key',
        'sm_post_connector_logo',
    ];

    // Loop through each table and drop it.
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
}

// Execute the removal functions.
sm_remove_options();
sm_remove_tables();
