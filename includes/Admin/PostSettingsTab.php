<?php

namespace VPlugins\BlogPostConnector\Admin;

class PostSettingsTab {
    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_media_uploader']);
    }

    public function get_title() {
        return __('Post Settings', 'blog-post-connector');
    }

    public function enqueue_media_uploader($hook) {
        if ($hook === 'settings_page_blog-post-connector') {
            wp_enqueue_media();
        }
    }

    public function register_settings() {
        register_setting('sm_post_connector_settings', 'sm_post_connector_default_post_type');
        register_setting('sm_post_connector_settings', 'sm_post_connector_default_author');
        register_setting('sm_post_connector_settings', 'sm_post_connector_default_category');
        register_setting('sm_post_connector_settings', 'sm_post_connector_logo');

        add_settings_section(
            'post_section',
            '',
            null,
            'blog-post-connector-post'
        );

        add_settings_field(
            'sm_post_connector_default_post_type',
            __('Default Post Type', 'blog-post-connector'),
            [$this, 'render_post_type_field'],
            'blog-post-connector-post',
            'post_section'
        );

        add_settings_field(
            'sm_post_connector_default_author',
            __('Default Author', 'blog-post-connector'),
            [$this, 'render_author_field'],
            'blog-post-connector-post',
            'post_section'
        );

        add_settings_field(
            'sm_post_connector_default_category',
            __('Default Category', 'blog-post-connector'),
            [$this, 'render_category_field'],
            'blog-post-connector-post',
            'post_section'
        );

        add_settings_field(
            'sm_post_connector_logo',
            __('Site Logo', 'blog-post-connector'),
            [$this, 'render_logo_field'],
            'blog-post-connector-post',
            'post_section'
        );
    }

    public function render_settings_fields() {
        settings_fields('sm_post_connector_settings');
        do_settings_sections('blog-post-connector-post');
    }

    public function maybe_render_extra_content() {
        // No extra buttons or actions needed for this tab
    }

    public function render_post_type_field() {
        $post_types = get_post_types(['public' => true], 'objects');
        $default = get_option('sm_post_connector_default_post_type', '');
        echo '<select name="sm_post_connector_default_post_type">';
        foreach ($post_types as $post_type) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($post_type->name),
                selected($default, $post_type->name, false),
                esc_html($post_type->label)
            );
        }
        echo '</select>';
    }

    public function render_author_field() {
        $authors = get_users(['capability' => 'edit_posts']);
        $default = get_option('sm_post_connector_default_author', '');
        echo '<select name="sm_post_connector_default_author">';
        foreach ($authors as $author) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($author->ID),
                selected($default, $author->ID, false),
                esc_html($author->display_name)
            );
        }
        echo '</select>';
    }

    public function render_category_field() {
        $categories = get_categories(['hide_empty' => false]);
        $default = get_option('sm_post_connector_default_category', '');
        echo '<select name="sm_post_connector_default_category">';
        foreach ($categories as $category) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($category->term_id),
                selected($default, $category->term_id, false),
                esc_html($category->name)
            );
        }
        echo '</select>';
    }

    public function render_logo_field() {
        $logo = get_option('sm_post_connector_logo');
        ?>
        <input type="hidden" id="sm_post_connector_logo" name="sm_post_connector_logo" value="<?php echo esc_attr($logo); ?>" />
        <button type="button" class="button" id="sm_post_connector_upload_logo" style="margin-bottom: 10px;"><?php _e('Upload Logo', 'blog-post-connector'); ?></button>
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
}