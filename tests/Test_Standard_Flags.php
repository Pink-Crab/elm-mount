<?php

declare(strict_types=1);

/**
 * Tests for PinkCrab\ElmMount\Standard_Flags.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\ElmMount\Tests
 */

namespace PinkCrab\ElmMount\Tests;

use WP_UnitTestCase;
use PinkCrab\ElmMount\Standard_Flags;

class Test_Standard_Flags extends WP_UnitTestCase {

	/**
	 * Convenience constructor — most tests use the same handle/mount node.
	 */
	private function make( string $handle = 'demo_app', string $mount = 'demo_app-root' ): Standard_Flags {
		return new Standard_Flags( $handle, $mount );
	}

	public function test_build_returns_array_with_all_required_keys(): void {
		$flags = $this->make()->build();

		$expected_keys = array(
			'restRoot',
			'restNonce',
			'restNamespace',
			'ajaxUrl',
			'ajaxNonce',
			'mountNode',
			'locale',
			'currentUser',
		);

		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, $flags, "missing key: {$key}" );
		}
	}

	public function test_rest_root_matches_rest_url(): void {
		$flags = $this->make()->build();
		$this->assertSame( esc_url_raw( rest_url() ), $flags['restRoot'] );
	}

	public function test_rest_nonce_validates_against_wp_rest_action(): void {
		$flags = $this->make()->build();
		$this->assertSame( 1, wp_verify_nonce( $flags['restNonce'], 'wp_rest' ) );
	}

	public function test_rest_namespace_defaults_to_wp_v2(): void {
		$flags = $this->make()->build();
		$this->assertSame( 'wp/v2', $flags['restNamespace'] );
	}

	public function test_ajax_url_matches_admin_ajax(): void {
		$flags = $this->make()->build();
		$this->assertSame( esc_url_raw( admin_url( 'admin-ajax.php' ) ), $flags['ajaxUrl'] );
	}

	public function test_ajax_nonce_uses_handle_specific_action(): void {
		$flags = $this->make( 'my_widget' )->build();
		$this->assertSame( 1, wp_verify_nonce( $flags['ajaxNonce'], 'elm_mount_my_widget' ) );
	}

	public function test_mount_node_is_passed_through(): void {
		$flags = $this->make( 'app', 'custom-mount-id' )->build();
		$this->assertSame( 'custom-mount-id', $flags['mountNode'] );
	}

	public function test_locale_matches_get_locale(): void {
		$flags = $this->make()->build();
		$this->assertSame( get_locale(), $flags['locale'] );
	}

	public function test_current_user_is_null_when_logged_out(): void {
		wp_set_current_user( 0 );
		$flags = $this->make()->build();
		$this->assertNull( $flags['currentUser'] );
	}

	public function test_current_user_has_correct_shape_when_logged_in(): void {
		$user_id = self::factory()->user->create(
			array(
				'role'         => 'editor',
				'display_name' => 'Test User',
			)
		);
		wp_set_current_user( $user_id );

		$flags = $this->make()->build();
		$user  = $flags['currentUser'];

		$this->assertIsArray( $user );
		$this->assertSame( $user_id, $user['id'] );
		$this->assertSame( 'Test User', $user['displayName'] );
		$this->assertContains( 'editor', $user['roles'] );
		$this->assertIsArray( $user['capabilities'] );
		$this->assertContains( 'edit_posts', $user['capabilities'] );
	}

	public function test_capabilities_only_includes_truthy_caps(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$flags = $this->make()->build();
		$caps  = $flags['currentUser']['capabilities'];

		// Subscribers should not have manage_options.
		$this->assertNotContains( 'manage_options', $caps );
	}

	public function test_ajax_action_returns_prefixed_handle(): void {
		$this->assertSame( 'elm_mount_demo_app', $this->make( 'demo_app' )->ajax_action() );
	}
}
