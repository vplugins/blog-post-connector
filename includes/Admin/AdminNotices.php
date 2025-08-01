<?php

namespace VPlugins\BlogPostConnector\Admin;

class AdminNotices {
    public function __construct() {
        add_action('admin_notices', [$this, 'permalinkNotice']);
        add_action('network_admin_notices', [$this, 'multisiteWarning']);
        add_action('admin_post_fix_permalink_structure', [$this, 'fixPermalinkStructure']);
    }

    public function multisiteWarning() {
        if (is_multisite() && is_network_admin() && current_user_can('manage_network_plugins')) {
            $screen = get_current_screen();
            if ($screen && $screen->id === 'plugins-network') {
                echo '<div class="notice notice-warning is-dismissible">';
                echo '<p><strong>SM Blog Post Connector plugin should be activated individually on each subsite for full functionality.</strong></p>';
                echo '</div>';
            }
        }
    }

    public function permalinkNotice() {
        if (!current_user_can('manage_options')) return;

        $permalink_structure = get_option('permalink_structure');
        if ($permalink_structure !== '/%postname%/') {
            $fix_url = wp_nonce_url(admin_url('admin-post.php?action=fix_permalink_structure'), 'fix_permalink');
            echo '<div class="notice notice-warning">';
            echo '<p><strong>Notice:</strong> The current permalink structure is set to <em>"Plain"</em>, which is not compatible with this plugin’s API routing.</p>';
            echo '<p>To ensure proper functionality, it’s recommended to switch to the <strong>"Post name"</strong> structure.<br>';
            echo 'You can update it by clicking <a class="button button-primary" href="' . esc_url($fix_url) . '">here</a>.</p>';
            echo '<p style="color: #d63638;"><strong>Caution:</strong> Changing the permalink structure may impact existing URLs on your site. Please proceed carefully.</p>';
            echo '</div>';
        }
    }

    public function fixPermalinkStructure() {
        if (!current_user_can('manage_options') || !check_admin_referer('fix_permalink')) {
            wp_die('You are not allowed to perform this action.', 'Error', ['response' => 403]);
        }

        update_option('permalink_structure', '/%postname%/');
        flush_rewrite_rules();

        wp_redirect(admin_url('options-permalink.php?message=permalink-fixed'));
        exit;
    }
}
