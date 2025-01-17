<?php

namespace VPlugins\BlogPostConnector\Auth;

/**
 * Class Token
 *
 * Handles token generation, validation, and plugin settings page for the Blog Post Connector plugin.
 */
class Token {

    /**
     * Token constructor.
     *
     * Initializes hooks for admin menu and settings.
     */
    public function __construct() {
        // Hook into admin menu
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_media_uploader']);
    }

    /**
     * Generates a new random token and saves it in the database.
     *
     * @return string The generated token.
     */
    public function generate_token() {
        $token = bin2hex(random_bytes(16));
        update_option('sm_post_connector_token', $token);
        return $token;
    }

    /**
     * Validates a given token against the stored token.
     *
     * @param string $token The token to validate.
     * @return bool True if the token matches the stored token, false otherwise.
     */
    public function validate_token($token) {
        $saved_token = get_option('sm_post_connector_token');
        return hash_equals($saved_token, $token);
    }

    /**
     * Adds a settings page to the WordPress admin.
     */
    public function add_settings_page() {
        add_options_page(
            __('Blog Post Connector Settings', 'blog-post-connector'),
            __('Blog Post Connector', 'blog-post-connector'),
            'manage_options',
            'blog-post-connector',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Enqueue media uploader script
     */
    public function enqueue_media_uploader($hook) {
        if ($hook !== 'settings_page_blog-post-connector') {
            return;
        }
        wp_enqueue_media(); // Enqueue media uploader script
    }

    /**
     * Registers settings for the plugin.
     */
    public function register_settings() {
        register_setting('sm_post_connector_settings_token', 'sm_post_connector_token');
        register_setting('sm_post_connector_settings', 'sm_post_connector_default_post_type');
        register_setting('sm_post_connector_settings', 'sm_post_connector_default_author');
        register_setting('sm_post_connector_settings', 'sm_post_connector_default_category');
        register_setting('sm_post_connector_settings', 'sm_post_connector_logo');

        add_settings_section(
            'sm_post_connector_settings_section_token',
            __('Token Settings', 'blog-post-connector'),
            null,
            'blog-post-connector-token'
        );

        add_settings_section(
            'sm_post_connector_settings_section_post',
            __('Post Settings', 'blog-post-connector'),
            null,
            'blog-post-connector-post'
        );

        add_settings_field(
            'sm_post_connector_token',
            __('Access Token', 'blog-post-connector'),
            [$this, 'render_token_field'],
            'blog-post-connector-token',
            'sm_post_connector_settings_section_token'
        );

        add_settings_field(
            'sm_post_connector_default_post_type',
            __('Default Post Type', 'blog-post-connector'),
            [$this, 'render_post_type_field'],
            'blog-post-connector-post',
            'sm_post_connector_settings_section_post'
        );

        add_settings_field(
            'sm_post_connector_default_author',
            __('Default Author', 'blog-post-connector'),
            [$this, 'render_author_field'],
            'blog-post-connector-post',
            'sm_post_connector_settings_section_post'
        );

        add_settings_field(
            'sm_post_connector_default_category',
            __('Default Category', 'blog-post-connector'),
            [$this, 'render_category_field'],
            'blog-post-connector-post',
            'sm_post_connector_settings_section_post'
        );

        add_settings_field(
            'sm_post_connector_logo',
            __('Site Logo', 'blog-post-connector'),
            [$this, 'render_logo_field'],
            'blog-post-connector-post',
            'sm_post_connector_settings_section_post'
        );
    }

    /**
     * Renders the settings page for the plugin.
     */
    public function render_settings_page() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'token';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(__('Blog Post Connector Settings', 'blog-post-connector')); ?></h1>
            <h2 class="nav-tab-wrapper">
                <a href="?page=blog-post-connector&tab=token" class="nav-tab <?php echo $active_tab == 'token' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html(__('Token Settings', 'blog-post-connector')); ?></a>
                <a href="?page=blog-post-connector&tab=post" class="nav-tab <?php echo $active_tab == 'post' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html(__('Post Settings', 'blog-post-connector')); ?></a>
            </h2>
            <form method="post" action="options.php">
                <?php
                if ($active_tab == 'token') {
                    settings_fields('sm_post_connector_settings_token');
                    do_settings_sections('blog-post-connector-token');
                } else {
                    settings_fields('sm_post_connector_settings');
                    do_settings_sections('blog-post-connector-post');
                }
                submit_button(__('Save Changes', 'blog-post-connector'), 'primary');
                ?>
            </form>
            <?php if ($active_tab == 'token'): ?>
                <form method="post">
                    <input type="hidden" name="generate_new_token" value="1">
                    <?php submit_button(__('Generate New Token', 'blog-post-connector'), 'secondary'); ?>
                </form>
                <?php $this->handle_generate_token_request(); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renders the field for the access token.
     */
    public function render_token_field() {
        $token = get_option('sm_post_connector_token', '');
        if (empty($token)) {
            $token = $this->generate_token();
        }
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
                copyText.setSelectionRange(0, 99999); /* For mobile devices */
                document.execCommand("copy");

                // Show snackbar
                var snackbar = document.getElementById("tokenSnackbar");
                snackbar.style.display = "block";
                setTimeout(function() {
                    snackbar.style.display = "none";
                }, 3000); // Hide snackbar after 3 seconds
            }
        </script>
        <?php
    }

    /**
     * Renders the field for selecting the default post type.
     */
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

    /**
     * Renders the field for selecting the default author.
     */
    public function render_author_field() {
        $authors = get_users(['capability' => 'edit_posts']);
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

    /**
     * Renders the field for selecting the default category.
     */
    public function render_category_field() {
        $categories = get_categories(['hide_empty' => false]);
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

    /**
     * Renders the logo upload field.
     */
    public function render_logo_field() {
        $logo = get_option('sm_post_connector_logo'); // Retrieve the logo option
        ?>
        <input type="hidden" id="sm_post_connector_logo" name="sm_post_connector_logo" value="<?php echo esc_attr($logo); ?>" />
        <button type="button" class="button" id="sm_post_connector_upload_logo" style="margin-bottom: 10px !important;"><?php _e('Upload Logo', 'blog-post-connector'); ?></button>
        <div id="sm_post_connector_logo_preview">
            <?php if ($logo): ?>
                <img src="<?php echo esc_url($logo); ?>" style="max-width: 150px; max-height: 150px;">
            <?php endif; ?>
        </div>
        <script>
            jQuery(document).ready(function($) {
                var mediaUploader;

                $('#sm_post_connector_upload_logo').click(function(e) {
                    e.preventDefault();

                    if (mediaUploader) {
                        mediaUploader.open();
                        return;
                    }

                    mediaUploader = wp.media.frames.file_frame = wp.media({
                        title: '<?php _e('Select Logo', 'blog-post-connector'); ?>',
                        button: {
                            text: '<?php _e('Select Logo', 'blog-post-connector'); ?>'
                        },
                        multiple: false
                    });

                    mediaUploader.on('select', function() {
                        var attachment = mediaUploader.state().get('selection').first().toJSON();
                        $('#sm_post_connector_logo').val(attachment.url);
                        $('#sm_post_connector_logo_preview').html('<img src="' + attachment.url + '" style="max-width: 150px; max-height: 150px;">');
                    });

                    mediaUploader.open();
                });
            });
        </script>
        <?php
    }

    /**
     * Handles generating a new token on request.
     */
    private function handle_generate_token_request() {
        if (isset($_POST['generate_new_token'])) {
            $new_token = $this->generate_token();
            update_option('sm_post_connector_token', $new_token);
            // Reload the page to reflect the new token in the text box
            echo '<script>window.location.reload();</script>';
        }
    }
}