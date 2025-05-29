<?php

namespace VPlugins\BlogPostConnector\Admin;

use VPlugins\BlogPostConnector\Auth\TokenManager;

class TokenSettingsTab {
    protected $tokenManager;

    public function __construct() {
        $this->tokenManager = new TokenManager();
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_media_uploader']);
    }

    public function get_title() {
        return __('Token Settings', 'blog-post-connector');
    }

    public function enqueue_media_uploader($hook) {
        if ($hook === 'settings_page_blog-post-connector') {
            wp_enqueue_media();
        }
    }

    public function register_settings() {
        register_setting('sm_post_connector_settings_token', 'sm_post_connector_token');

        add_settings_section('token_section', '', null, 'blog-post-connector-token');

        add_settings_field(
            'sm_post_connector_token',
            __('Access Token', 'blog-post-connector'),
            [$this, 'render_token_field'],
            'blog-post-connector-token',
            'token_section'
        );
    }

    public function render_settings_fields() {
        settings_fields('sm_post_connector_settings_token');
        do_settings_sections('blog-post-connector-token');
    }

    public function maybe_render_extra_content() {
        ?>
        <form method="post">
            <input type="hidden" name="generate_new_token" value="1">
            <?php submit_button(__('Generate New Token', 'blog-post-connector'), 'secondary'); ?>
        </form>
        <?php $this->handle_generate_token_request(); ?>
        <?php
    }

    public function render_token_field() {
        $token = get_option('sm_post_connector_token', '') ?: $this->tokenManager->generate_token();
        ?>
        <div style="position: relative;">
            <input type="text" id="sm_post_connector_token" class="regular-text" name="sm_post_connector_token" value="<?php echo esc_attr($token); ?>" readonly>
            <button type="button" class="button button-secondary" onclick="copyTokenToClipboard()">Copy Token</button>
        </div>
        <div id="tokenSnackbar" class="components-snackbar" style="display: none;">
            Token copied to clipboard!
        </div>
        <script>
            function copyTokenToClipboard() {
                var copyText = document.getElementById("sm_post_connector_token");
                copyText.select();
                document.execCommand("copy");
                var snackbar = document.getElementById("tokenSnackbar");
                snackbar.style.display = "block";
                setTimeout(() => snackbar.style.display = "none", 3000);
            }
        </script>
        <?php
    }

    private function handle_generate_token_request() {
        if (isset($_POST['generate_new_token'])) {
            $new_token = $this->tokenManager->generate_token();
            update_option('sm_post_connector_token', $new_token);
            echo '<script>window.location.reload();</script>';
        }
    }
}