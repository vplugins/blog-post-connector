<?php

namespace VPlugins\SMPostConnector\Auth;

class Token {
    public function __construct() {
        // Hook into admin menu
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function generate_token() {
        $token = bin2hex(random_bytes(16));
        update_option('sm_post_connector_token', $token);
        return $token;
    }

    public function validate_token($token) {
        $saved_token = get_option('sm_post_connector_token');
        return hash_equals($saved_token, $token);
    }

    public function add_settings_page() {
        add_options_page(
            'SM Post Connector Settings',
            'SM Post Connector',
            'manage_options',
            'sm-post-connector',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        register_setting('sm_post_connector_settings', 'sm_post_connector_token');
        add_settings_section(
            'sm_post_connector_settings_section',
            'Token Settings',
            null,
            'sm-post-connector'
        );
        add_settings_field(
            'sm_post_connector_token',
            'Access Token',
            [$this, 'render_token_field'],
            'sm-post-connector',
            'sm_post_connector_settings_section'
        );
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>SM Post Connector Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('sm_post_connector_settings');
                do_settings_sections('sm-post-connector');
                submit_button();
                ?>
            </form>
            <form method="post">
                <input type="hidden" name="generate_new_token" value="1">
                <?php submit_button('Generate New Token'); ?>
            </form>
            <?php $this->handle_generate_token_request(); ?>
        </div>
        <?php
    }

    public function render_token_field() {
        $token = get_option('sm_post_connector_token', '');
        if (empty($token)) {
            $token = $this->generate_token();
        }
        ?>
        <input type="text" name="sm_post_connector_token" value="<?php echo esc_attr($token); ?>" readonly>
        <?php
    }

    private function handle_generate_token_request() {
        if (isset($_POST['generate_new_token'])) {
            $new_token = $this->generate_token();
            update_option('sm_post_connector_token', $new_token);
            // Reload the page to reflect the new token in the text box
            echo '<script>window.location.reload();</script>';
        }
    }
}