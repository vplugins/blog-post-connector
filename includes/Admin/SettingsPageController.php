<?php

namespace VPlugins\BlogPostConnector\Admin;

class SettingsPageController {
    protected $tabs = [];

    public function __construct() {
        $this->tabs = [
            'token'         => new TokenSettingsTab(),
            'post'          => new PostSettingsTab(),
            'logs-settings' => new LogSettingsTab(),
            'logs'          => new LogsTab(),
        ];

        add_action('admin_menu', [$this, 'add_settings_page']);
    }

    public function add_settings_page() {
        add_options_page(
            __('Blog Post Connector Settings', 'blog-post-connector'),
            __('Blog Post Connector', 'blog-post-connector'),
            'manage_options',
            'blog-post-connector',
            [$this, 'render_settings_page']
        );
    }

    public function render_settings_page() {
        $active_tab = $_GET['tab'] ?? 'token';
        $tab = $this->tabs[$active_tab];

        echo '<div class="wrap">';
        echo '<h1>' . esc_html(__('Blog Post Connector Settings', 'blog-post-connector')) . '</h1>';
        echo '<h2 class="nav-tab-wrapper">';

        foreach ($this->tabs as $slug => $tab_instance) {
            $active_class = $slug === $active_tab ? 'nav-tab-active' : '';
            echo "<a href='?page=blog-post-connector&tab={$slug}' class='nav-tab {$active_class}'>" . esc_html($tab_instance->get_title()) . "</a>";
        }

        echo '</h2>';

        // Conditionally render the form only if the tab has settings fields
        if (!method_exists($tab, 'has_settings_fields') || $tab->has_settings_fields()) {
            echo '<form method="post" action="options.php">';
            $tab->render_settings_fields();
            submit_button();
            echo '</form>';
        } else {
            // Still render any visual content if present
            $tab->render_settings_fields();
        }

        // Optional additional content for tab
        $tab->maybe_render_extra_content();

        echo '</div>';
    }
}