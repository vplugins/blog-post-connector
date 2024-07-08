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
        register_setting('sm_post_connector_settings', 'sm_post_connector_default_post_type');
        register_setting('sm_post_connector_settings', 'sm_post_connector_default_author');
        register_setting('sm_post_connector_settings', 'sm_post_connector_default_category');

        add_settings_section(
            'sm_post_connector_settings_section_token',
            'Token Settings',
            null,
            'sm-post-connector-token'
        );

        add_settings_section(
            'sm_post_connector_settings_section_post',
            'Post Settings',
            null,
            'sm-post-connector-post'
        );

        add_settings_field(
            'sm_post_connector_token',
            'Access Token',
            [$this, 'render_token_field'],
            'sm-post-connector-token',
            'sm_post_connector_settings_section_token'
        );

        add_settings_field(
            'sm_post_connector_default_post_type',
            'Default Post Type',
            [$this, 'render_post_type_field'],
            'sm-post-connector-post',
            'sm_post_connector_settings_section_post'
        );

        add_settings_field(
            'sm_post_connector_default_author',
            'Default Author',
            [$this, 'render_author_field'],
            'sm-post-connector-post',
            'sm_post_connector_settings_section_post'
        );

        add_settings_field(
            'sm_post_connector_default_category',
            'Default Category',
            [$this, 'render_category_field'],
            'sm-post-connector-post',
            'sm_post_connector_settings_section_post'
        );
    }

    public function render_settings_page() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'token';
        ?>
        <div class="wrap">
            <h1>SM Post Connector Settings</h1>
            <h2 class="nav-tab-wrapper">
                <a href="?page=sm-post-connector&tab=token" class="nav-tab <?php echo $active_tab == 'token' ? 'nav-tab-active' : ''; ?>">Token Settings</a>
                <a href="?page=sm-post-connector&tab=post" class="nav-tab <?php echo $active_tab == 'post' ? 'nav-tab-active' : ''; ?>">Post Settings</a>
            </h2>
            <form method="post" action="options.php">
                <?php
                if ($active_tab == 'token') {
                    settings_fields('sm_post_connector_settings');
                    do_settings_sections('sm-post-connector-token');
                } else {
                    settings_fields('sm_post_connector_settings');
                    do_settings_sections('sm-post-connector-post');
                }
                submit_button( __( 'Save Changes', 'textdomain' ), 'primary' );
                ?>
            </form>
            <?php if ($active_tab == 'token'): ?>
            <form method="post">
                <input type="hidden" name="generate_new_token" value="1">
                <?php submit_button( __('Generate New Token'), 'secondary' ); ?>
            </form>
            <?php $this->handle_generate_token_request(); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_token_field() {
        $token = get_option('sm_post_connector_token', '');
        if (empty($token)) {
            $token = $this->generate_token();
        }
        ?>
        <input type="text" class="regular-text" name="sm_post_connector_token" value="<?php echo esc_attr($token); ?>" readonly>
        <?php
    }

    public function render_post_type_field() {
        $post_types = get_post_types(['public' => true], 'objects');
        $default_post_type = get_option('sm_post_connector_default_post_type', '');

        ?>
        <select name="sm_post_connector_default_post_type">
            <?php foreach ($post_types as $post_type): ?>
                <option value="<?php echo esc_attr($post_type->name); ?>" <?php selected($default_post_type, $post_type->name); ?>>
                    <?php echo esc_html($post_type->label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    public function render_author_field() {
        $authors = get_users(['who' => 'authors']);
        $default_author = get_option('sm_post_connector_default_author', '');

        ?>
        <select name="sm_post_connector_default_author">
            <?php foreach ($authors as $author): ?>
                <option value="<?php echo esc_attr($author->ID); ?>" <?php selected($default_author, $author->ID); ?>>
                    <?php echo esc_html($author->display_name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    public function render_category_field() {
        $categories = get_categories();
        $default_category = get_option('sm_post_connector_default_category', '');

        ?>
        <select name="sm_post_connector_default_category">
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo esc_attr($category->term_id); ?>" <?php selected($default_category, $category->term_id); ?>>
                    <?php echo esc_html($category->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
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