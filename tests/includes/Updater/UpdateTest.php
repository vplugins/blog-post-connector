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
     * Plugin file of the copy that is running; a test can switch it to another install folder.
     */
    private $running_plugin_file = 'blog-post-connector/blog-post-connector.php';

    /**
     * Set up the test environment.
     */
    public function setUp(): void {
        \WP_Mock::setUp();

        if (!defined('WP_PLUGIN_DIR')) {
            define('WP_PLUGIN_DIR', '/var/www/wp-content/plugins');
        }

        \WP_Mock::userFunction('plugin_basename', [
            'return' => function () {
                return $this->running_plugin_file;
            },
        ]);
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
     * Updating this plugin moves the unpacked release asset (sm-post-connector-package/) into the plugin's own folder.
     */
    public function test_after_install_moves_own_update_into_plugin_folder() {
        $plugin_folder = WP_PLUGIN_DIR . '/' . dirname(Globals::get_plugin_file());
        $extracted = WP_PLUGIN_DIR . '/sm-post-connector-package/';

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
     * The Plugins screen's "Update now" link, Dashboard > Updates and WP-CLI use bulk_upgrade(),
     * whose hook_extra has no type or action.
     */
    public function test_after_install_moves_own_bulk_update_into_plugin_folder() {
        $plugin_folder = WP_PLUGIN_DIR . '/' . dirname(Globals::get_plugin_file());
        $extracted = WP_PLUGIN_DIR . '/sm-post-connector-package/';

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
            [
                'plugin' => Globals::get_plugin_file(),
                'temp_backup' => ['slug' => 'blog-post-connector', 'src' => WP_PLUGIN_DIR, 'dir' => 'plugins'],
            ],
            ['destination' => $extracted]
        );

        $this->assertSame($plugin_folder, $result['destination']);
    }

    /**
     * Files that already unpacked into the plugin's own folder (WordPress passes it with a
     * trailing slash) are not moved onto themselves.
     */
    public function test_after_install_skips_move_when_files_are_already_in_plugin_folder() {
        $plugin_folder = WP_PLUGIN_DIR . '/' . dirname(Globals::get_plugin_file());

        $this->filesystem->shouldNotReceive('move');
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
            ['destination' => $plugin_folder . '/']
        );

        $this->assertSame($plugin_folder, $result['destination']);
    }

    /**
     * With a second copy of the plugin installed, WordPress can update the copy that is not
     * running. Activating that copy here would load it twice, so its update is left alone.
     */
    public function test_after_install_ignores_update_of_a_copy_that_is_not_running() {
        $this->running_plugin_file = 'sm-post-connector-package/blog-post-connector.php';
        $this->filesystem->shouldNotReceive('move');
        \WP_Mock::userFunction('activate_plugin', ['times' => 0]);

        $result = (new Update())->after_install(
            true,
            ['plugin' => 'blog-post-connector/blog-post-connector.php', 'type' => 'plugin', 'action' => 'update'],
            ['destination' => WP_PLUGIN_DIR . '/sm-post-connector-package/']
        );

        $this->assertTrue($result);
    }

    /**
     * An error from an earlier upgrader_post_install callback is passed through: nothing is
     * moved and the plugin is not activated.
     */
    public function test_after_install_passes_through_an_earlier_error_for_own_update() {
        $error = new \WP_Error('earlier_failure', 'An earlier post-install step failed.');

        $this->filesystem->shouldNotReceive('move');
        \WP_Mock::userFunction('activate_plugin', ['times' => 0]);

        $result = (new Update())->after_install(
            $error,
            ['plugin' => Globals::get_plugin_file(), 'type' => 'plugin', 'action' => 'update'],
            ['destination' => WP_PLUGIN_DIR . '/sm-post-connector-package/']
        );

        $this->assertSame($error, $result);
    }

    /**
     * A failed move is reported as an error instead of a silent success, and the extracted
     * copy is removed so no second, inactive copy of the plugin is left behind.
     */
    public function test_after_install_returns_error_when_move_fails() {
        $extracted = WP_PLUGIN_DIR . '/sm-post-connector-package/';

        $this->filesystem->shouldReceive('move')->once()->andReturn(false);
        $this->filesystem->shouldReceive('delete')->once()->with($extracted, true)->andReturn(true);
        \WP_Mock::userFunction('activate_plugin', ['times' => 0]);

        $result = (new Update())->after_install(
            true,
            ['plugin' => Globals::get_plugin_file(), 'type' => 'plugin', 'action' => 'update'],
            ['destination' => $extracted]
        );

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('sm_post_connector_move_failed', $result->get_error_code());
    }

    /**
     * The handler takes three arguments. Hooked with fewer, every plugin and theme install or
     * update on the site would fail with an ArgumentCountError.
     */
    public function test_after_install_is_hooked_with_all_three_arguments() {
        $update = (new \ReflectionClass(Update::class))->newInstanceWithoutConstructor();
        \WP_Mock::expectFilterAdded('upgrader_post_install', [$update, 'after_install'], 10, 3);

        $update->__construct();

        $this->assertHooksAdded();
    }
}
