<?php 

namespace VPlugins\BlogPostConnector\Admin;

class SettingsPageController {
    protected $tabs = [];

    public function __construct() {
        $this->tabs = [
            'token' => new TokenSettingsTab(),
            'post'  => new PostSettingsTab(),
            'logs-settings' => new LogSettingsTab(),
            'logs' => new LogsTab(),
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

        echo '<div class="wrap">';
        echo '<h1>' . esc_html(__('Blog Post Connector Settings', 'blog-post-connector')) . '</h1>';
        echo '<h2 class="nav-tab-wrapper">';
        foreach ($this->tabs as $slug => $tab) {
            $active_class = $slug === $active_tab ? 'nav-tab-active' : '';
            echo "<a href='?page=blog-post-connector&tab={$slug}' class='nav-tab {$active_class}'>" . esc_html($tab->get_title()) . "</a>";
        }
        echo '</h2>';

        echo '<form method="post" action="options.php">';
        $this->tabs[$active_tab]->render_settings_fields();
        submit_button();
        echo '</form>';

        $this->tabs[$active_tab]->maybe_render_extra_content();

        echo '</div>';
    }
}