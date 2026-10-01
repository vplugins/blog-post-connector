<?php

namespace VPlugins\BlogPostConnector\Tests\Admin;

use VPlugins\BlogPostConnector\Admin\PostSettingsTab;
use WP_Mock\Tools\TestCase;

class PostSettingsTabTest extends TestCase {

    /**
     * @var PostSettingsTab
     */
    private $tab;

    /**
     * Set up the test environment.
     */
    public function setUp(): void {
        \WP_Mock::setUp();
        $this->tab = new PostSettingsTab();
    }

    /**
     * Tear down the test environment.
     */
    public function tearDown(): void {
        \WP_Mock::tearDown();
        parent::tearDown();
    }

    /**
     * Test that with nothing saved the dropdown preselects the lowest-ID administrator and says
     * it is the site default, instead of silently showing the first name in the list.
     */
    public function test_author_field_preselects_and_labels_the_site_default_when_nothing_is_saved() {
        $this->mock_site([
            15 => $this->make_user(15, 'Amy Admin', 'administrator'),
            13 => $this->make_user(13, 'Zed Admin', 'administrator'),
            1  => $this->make_user(1, 'Site Editor', 'editor'),
        ], null);

        $html = $this->render();

        $this->assertStringContainsString("<option value=\"13\" selected='selected'>Zed Admin (site default)</option>", $html);
        $this->assertStringContainsString('<option value="15">Amy Admin</option>', $html);
        $this->assertStringContainsString('<option value="1">Site Editor</option>', $html);
        $this->assertStringContainsString('first administrator', $html);
    }

    /**
     * Test that a saved Default Author is preselected as a plain choice, with no site-default
     * label or note.
     */
    public function test_author_field_preselects_the_saved_default_author() {
        $this->mock_site([
            15 => $this->make_user(15, 'Amy Admin', 'administrator'),
            13 => $this->make_user(13, 'Zed Admin', 'administrator'),
        ], '15');

        $html = $this->render();

        $this->assertStringContainsString("<option value=\"15\" selected='selected'>Amy Admin</option>", $html);
        $this->assertStringContainsString('<option value="13">Zed Admin</option>', $html);
        $this->assertStringNotContainsString('site default', $html);
        $this->assertStringNotContainsString('first administrator', $html);
    }

    /**
     * Test that when the saved Default Author no longer exists the dropdown falls back to the
     * site default and says so.
     */
    public function test_author_field_falls_back_to_the_site_default_when_the_saved_user_is_gone() {
        $this->mock_site([
            15 => $this->make_user(15, 'Amy Admin', 'administrator'),
            13 => $this->make_user(13, 'Zed Admin', 'administrator'),
        ], '99');

        $html = $this->render();

        $this->assertStringContainsString("<option value=\"13\" selected='selected'>Zed Admin (site default)</option>", $html);
        $this->assertStringContainsString('first administrator', $html);
    }

    /**
     * Renders the Default Author field and returns its HTML.
     *
     * @return string
     */
    private function render() {
        ob_start();
        $this->tab->render_author_field();

        return ob_get_clean();
    }

    /**
     * Builds a user carrying every field a real WP_User has.
     *
     * @param int    $id           The user ID.
     * @param string $display_name The display name.
     * @param string $role         The user's single role, as WordPress stores it (lowercase key).
     * @return \WP_User
     */
    private function make_user($id, $display_name, $role = 'administrator') {
        $user = new \WP_User([
            'ID'                  => (string) $id,
            'user_login'          => 'user' . $id,
            'user_pass'           => '$P$Bnotarealhash' . $id,
            'user_nicename'       => 'user' . $id,
            'user_email'          => 'user' . $id . '@example.com',
            'user_url'            => '',
            'user_registered'     => '2025-01-16 13:06:36',
            'user_activation_key' => '',
            'user_status'         => '0',
            'display_name'        => $display_name,
        ]);
        $user->roles = [$role];

        return $user;
    }

    /**
     * Mocks the WordPress calls render_author_field() makes. get_users() answers the dropdown's
     * capability query with every user in the order given, and role queries the way WordPress
     * does (filtered by role, ordered by ID, limited by number).
     *
     * @param array       $users          Users keyed by ID.
     * @param string|null $default_author The stored Default Author option, or null when it was never saved.
     */
    private function mock_site($users, $default_author) {
        \WP_Mock::userFunction('get_option', [
            'return' => function ($name, $default = false) use ($default_author) {
                if ($name === 'sm_post_connector_default_author') {
                    return $default_author === null ? $default : $default_author;
                }

                return $default;
            },
        ]);
        \WP_Mock::userFunction('get_users', [
            'return' => function ($args = []) use ($users) {
                $list = array_values($users);
                if (isset($args['role'])) {
                    $list = array_values(array_filter($list, function ($user) use ($args) {
                        return in_array($args['role'], $user->roles, true);
                    }));
                }
                if (isset($args['orderby']) && $args['orderby'] === 'ID') {
                    usort($list, function ($a, $b) {
                        return $a->ID <=> $b->ID;
                    });
                }
                if (!empty($args['number'])) {
                    $list = array_slice($list, 0, (int) $args['number']);
                }

                return $list;
            },
        ]);
        \WP_Mock::userFunction('get_user_by', [
            'return' => function ($field, $value) use ($users) {
                return $field === 'ID' && isset($users[(int) $value]) ? $users[(int) $value] : false;
            },
        ]);
        // WordPress's selected(): returns the attribute when the two values match as strings.
        \WP_Mock::userFunction('selected', [
            'return' => function ($selected, $current = true, $echo = true) {
                return (string) $selected === (string) $current ? " selected='selected'" : '';
            },
        ]);
    }

}
