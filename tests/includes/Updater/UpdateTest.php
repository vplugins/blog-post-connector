<?php

namespace VPlugins\BlogPostConnector\Tests\Updater;

use VPlugins\BlogPostConnector\Helper\Globals;
use VPlugins\BlogPostConnector\Updater\Update;
use WP_Mock\Tools\TestCase;

class UpdateTest extends TestCase {

    /**
     * Mocked WP_Filesystem the updater moves files with.
     */
    private $filesystem;

    /**
     * Set up the test environment.
     */
    public function setUp(): void {
        \WP_Mock::setUp();

        if (!defined('WP_PLUGIN_DIR')) {
            define('WP_PLUGIN_DIR', '/var/www/wp-content/plugins');
        }

        \WP_Mock::userFunction('is_wp_error', [
            'return' => function ($thing) {
                return $thing instanceof \WP_Error;
            },
        ]);
        \WP_Mock::userFunction('untrailingslashit', [
            'return' => function ($value) {
                return rtrim($value, '/\\');
            },
        ]);

        $this->filesystem = \Mockery::mock();
        $GLOBALS['wp_filesystem'] = $this->filesystem;
    }

    /**
     * Tear down the test environment.
     */
    public function tearDown(): void {
        unset($GLOBALS['wp_filesystem']);
        \WP_Mock::tearDown();
        parent::tearDown();
    }

    /**
     * Installing another plugin must leave its files and activation alone.
     */
    public function test_after_install_ignores_other_plugin_install() {
        $this->filesystem->shouldNotReceive('move');
        \WP_Mock::userFunction('activate_plugin', ['times' => 0]);

        $result = (new Update())->after_install(
            true,
            ['type' => 'plugin', 'action' => 'install'],
            ['destination' => WP_PLUGIN_DIR . '/woocommerce/']
        );

        $this->assertTrue($result);
    }

    /**
     * Updating another plugin must leave its files and activation alone.
     */
    public function test_after_install_ignores_other_plugin_update() {
        $this->filesystem->shouldNotReceive('move');
        \WP_Mock::userFunction('activate_plugin', ['times' => 0]);

        $result = (new Update())->after_install(
            true,
            ['plugin' => 'woocommerce/woocommerce.php', 'type' => 'plugin', 'action' => 'update'],
            ['destination' => WP_PLUGIN_DIR . '/woocommerce/']
        );

        $this->assertTrue($result);
    }

    /**
     * Updating this plugin moves the GitHub zipball folder back into the plugin's own folder.
     */
    public function test_after_install_moves_own_update_into_plugin_folder() {
        $plugin_folder = WP_PLUGIN_DIR . '/' . dirname(Globals::get_plugin_file());
        $extracted = WP_PLUGIN_DIR . '/vplugins-blog-post-connector-abc1234/';

        $this->filesystem->shouldReceive('move')->once()->with($extracted, $plugin_folder)->andReturn(true);
        \WP_Mock::userFunction('get_option', [
            'args' => ['sm_post_connector_db_version', '0'],
            'return' => Globals::get_version(),
        ]);
        \WP_Mock::userFunction('activate_plugin', [
            'times' => 1,
            'args' => [Globals::get_plugin_file()],
        ]);

        $result = (new Update())->after_install(
            true,
            ['plugin' => Globals::get_plugin_file(), 'type' => 'plugin', 'action' => 'update'],
            ['destination' => $extracted]
        );

        $this->assertSame($plugin_folder, $result['destination']);
    }

    /**
     * A failed move is reported as an error instead of a silent success.
     */
    public function test_after_install_returns_error_when_move_fails() {
        $this->filesystem->shouldReceive('move')->once()->andReturn(false);
        \WP_Mock::userFunction('activate_plugin', ['times' => 0]);

        $result = (new Update())->after_install(
            true,
            ['plugin' => Globals::get_plugin_file(), 'type' => 'plugin', 'action' => 'update'],
            ['destination' => WP_PLUGIN_DIR . '/vplugins-blog-post-connector-abc1234/']
        );

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('sm_post_connector_move_failed', $result->get_error_code());
    }
}
