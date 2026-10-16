<?php

namespace VPlugins\BlogPostConnector\Admin;

use VPlugins\BlogPostConnector\Helper\Globals;

class AdminNotices {
    private static bool $initialized = false;

    public function __construct() {
        if (self::$initialized) return;
        self::$initialized = true;

        add_action('admin_notices', [$this, 'permalinkNotice']);
        add_action('admin_post_fix_permalink_structure', [$this, 'fixPermalinkStructure']);
    }

    public function permalinkNotice() {
        if (!current_user_can('manage_options')) return;

        // Any structure other than Plain serves the plugin's REST routes.
        if (!Globals::isPermalinkPlain()) return;

        echo '<div class="notice notice-warning">';
        echo '<p><strong>Notice:</strong> The current permalink structure is set to <em>"Plain"</em>, which is not compatible with this plugin’s API routing.</p>';
        if (is_multisite()) {
            // The structure is left for the admin to pick: a subdirectory network's main site normally uses
            // one that starts with /blog/, which WordPress does not add back after Plain.
            echo '<p>To ensure proper functionality, choose any structure other than <strong>"Plain"</strong> in ';
            echo '<a class="button button-primary" href="' . esc_url(admin_url('options-permalink.php')) . '">Permalink Settings</a>.</p>';
        } else {
            $fix_url = wp_nonce_url(admin_url('admin-post.php?action=fix_permalink_structure'), 'fix_permalink');
            echo '<p>To ensure proper functionality, it’s recommended to switch to the <strong>"Post name"</strong> structure.<br>';
            echo 'You can update it by clicking <a class="button button-primary" href="' . esc_url($fix_url) . '">here</a>.</p>';
        }
        echo '<p style="color: #d63638;"><strong>Caution:</strong> Changing the permalink structure may impact existing URLs on your site. Please proceed carefully.</p>';
        echo '</div>';
    }

    public function fixPermalinkStructure() {
        if (!current_user_can('manage_options') || !check_admin_referer('fix_permalink') || is_multisite()) {
            wp_die('You are not allowed to perform this action.', 'Error', ['response' => 403]);
        }

        update_option('permalink_structure', '/%postname%/');
        flush_rewrite_rules();

        wp_redirect(admin_url('options-permalink.php?message=permalink-fixed'));
        exit;
    }
}
