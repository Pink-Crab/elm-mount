<?php

declare(strict_types=1);

/**
 * Tests for PinkCrab\ElmMount\Elm_App.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\ElmMount\Tests
 */

namespace PinkCrab\ElmMount\Tests;

use WP_UnitTestCase;
use PinkCrab\ElmMount\Elm_App;
use Gin0115\WPUnit_Helpers\Objects;

class Test_Elm_App extends WP_UnitTestCase {

	public function tearDown(): void {
		// Each test enqueues with a unique handle, but make sure nothing leaks.
		wp_dequeue_script( 'demo_app' );
		wp_dequeue_script( 'custom_app' );
		wp_dequeue_script( 'merge_app' );
		wp_dequeue_script( 'idempotent_app' );
		wp_dequeue_script( 'render_app' );
		wp_dequeue_script( 'parse_app' );
		parent::tearDown();
	}

	public function test_create_returns_instance(): void {
		$app = Elm_App::create( 'demo_app' );
		$this->assertInstanceOf( Elm_App::class, $app );
	}

	public function test_default_mount_node_is_handle_dash_root(): void {
		$app  = Elm_App::create( 'demo_app' )->script( 'https://example.test/main.js' );
		$html = $app->parse();
		$this->assertStringContainsString( 'id="demo_app-root"', $html );
	}

	public function test_custom_mount_node_overrides_default(): void {
		$app  = Elm_App::create( 'custom_app' )
			->script( 'https://example.test/main.js' )
			->mount_node( 'my-custom-id' );
		$html = $app->parse();
		$this->assertStringContainsString( 'id="my-custom-id"', $html );
	}

	public function test_parse_returns_only_a_div(): void {
		$app  = Elm_App::create( 'parse_app' )->script( 'https://example.test/main.js' );
		$html = $app->parse();
		$this->assertSame( '<div id="parse_app-root"></div>', $html );
	}

	public function test_parse_escapes_mount_node_id(): void {
		$app  = Elm_App::create( 'parse_app' )
			->script( 'https://example.test/main.js' )
			->mount_node( 'evil"<script>' );
		$html = $app->parse();
		$this->assertStringNotContainsString( '"<script>', $html );
		$this->assertStringContainsString( '&quot;', $html );
	}

	public function test_render_echoes_div(): void {
		$app = Elm_App::create( 'render_app' )->script( 'https://example.test/main.js' );

		ob_start();
		$app->render();
		$output = ob_get_clean();

		$this->assertSame( '<div id="render_app-root"></div>', $output );
	}

	public function test_parse_enqueues_script(): void {
		$app = Elm_App::create( 'demo_app' )->script( 'https://example.test/main.js' );
		$app->parse();

		$this->assertTrue( wp_script_is( 'demo_app', 'enqueued' ) );
	}

	public function test_parse_localizes_flags_blob(): void {
		$app = Elm_App::create( 'demo_app' )
			->script( 'https://example.test/main.js' )
			->flags( array( 'pageTitle' => 'Settings' ) );

		$app->parse();

		$data = wp_scripts()->get_data( 'demo_app', 'data' );
		$this->assertIsString( $data );
		$this->assertStringContainsString( 'restRoot', $data );
		$this->assertStringContainsString( 'pluginData', $data );
		$this->assertStringContainsString( 'Settings', $data );
	}

	public function test_plugin_flags_nest_under_pluginData(): void {
		$app = Elm_App::create( 'merge_app' )
			->script( 'https://example.test/main.js' )
			->flags( array( 'foo' => 'bar' ) );

		$app->parse();

		$data = wp_scripts()->get_data( 'merge_app', 'data' );
		$this->assertStringContainsString( '"pluginData":{"foo":"bar"}', $data );
	}

	public function test_render_and_parse_idempotent(): void {
		$app = Elm_App::create( 'idempotent_app' )->script( 'https://example.test/main.js' );

		$first  = $app->parse();
		$second = $app->parse();
		ob_start();
		$app->render();
		$third = ob_get_clean();

		$this->assertSame( $first, $second );
		$this->assertSame( $first, $third );

		// Internal flag flips on first call only.
		$this->assertTrue( Objects::get_property( $app, 'enqueued' ) );
	}

	public function test_script_args_propagate_to_wp_enqueue(): void {
		$app = Elm_App::create( 'demo_app' )
			->script( 'https://example.test/main.js', array( 'wp-api-fetch' ), '1.2.3', false );

		$this->assertSame( 'https://example.test/main.js', Objects::get_property( $app, 'script_src' ) );
		$this->assertSame( array( 'wp-api-fetch' ), Objects::get_property( $app, 'script_deps' ) );
		$this->assertSame( '1.2.3', Objects::get_property( $app, 'script_ver' ) );
		$this->assertFalse( Objects::get_property( $app, 'script_in_footer' ) );
	}
}
