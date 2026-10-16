<?php

namespace VPlugins\BlogPostConnector\Tests\Admin;

use VPlugins\BlogPostConnector\Admin\AdminNotices;
use WP_Mock\Tools\TestCase;

class AdminNoticesTest extends TestCase {

    /**
     * The stored permalink structure; '' is "Plain".
     */
    private $permalink_structure = '';

    /**
     * Whether the test runs on a multisite network.
     */
    private $multisite = false;

    /**
     * Set up the test environment.
     */
    public function setUp(): void {
        \WP_Mock::setUp();

        \WP_Mock::userFunction('current_user_can', ['return' => true]);
        \WP_Mock::userFunction('get_option', [
            'args' => ['permalink_structure'],
            'return' => function () {
                return $this->permalink_structure;
            },
        ]);
        \WP_Mock::userFunction('is_multisite', [
            'return' => function () {
                return $this->multisite;
            },
        ]);
        \WP_Mock::userFunction('admin_url', [
            'return' => function ($path = '') {
                return 'https://example.com/wp-admin/' . $path;
            },
        ]);
        \WP_Mock::userFunction('wp_nonce_url', [
            'return' => function ($url) {
                return $url . '&_wpnonce=abc';
            },
        ]);
    }

    /**
     * Tear down the test environment.
     */
    public function tearDown(): void {
        \WP_Mock::tearDown();
        parent::tearDown();
    }

    /**
     * Captures what the permalink notice prints.
     */
    private function render_notice(): string {
        ob_start();
        (new AdminNotices())->permalinkNotice();
        return ob_get_clean();
    }

    /**
     * Plain permalinks on a single site: the notice offers the one-click fix.
     */
    public function test_plain_permalinks_on_single_site_show_fix_button() {
        $output = $this->render_notice();

        $this->assertStringContainsString('"Plain"', $output);
        $this->assertStringContainsString('action=fix_permalink_structure', $output);
    }

    /**
     * Any structure other than Plain serves the plugin's routes, including a /blog/ prefix.
     */
    public function test_non_plain_permalinks_show_no_notice() {
        $this->permalink_structure = '/blog/%postname%/';

        $this->assertSame('', $this->render_notice());
    }

    /**
     * Plain permalinks on multisite: link to Permalink Settings, never the fix that overwrites the structure.
     */
    public function test_plain_permalinks_on_multisite_link_to_settings_without_fix_button() {
        $this->multisite = true;

        $output = $this->render_notice();

        $this->assertStringContainsString('options-permalink.php', $output);
        $this->assertStringNotContainsString('fix_permalink_structure', $output);
    }

    /**
     * The fix refuses to run on multisite and leaves the stored structure alone.
     */
    public function test_fix_permalink_structure_is_refused_on_multisite() {
        $this->multisite = true;

        \WP_Mock::userFunction('check_admin_referer', ['return' => true]);
        \WP_Mock::userFunction('update_option', ['times' => 0]);
        \WP_Mock::userFunction('wp_die', [
            'times' => 1,
            'return' => function () {
                throw new \RuntimeException('wp_die');
            },
        ]);

        $this->expectException(\RuntimeException::class);

        (new AdminNotices())->fixPermalinkStructure();
    }
}
