<?php
/**
 * Uninstall script for Blog Post Connector Plugin.
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
    }
}

// Execute the removal functions.
sm_remove_options();
