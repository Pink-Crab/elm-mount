<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap for pinkcrab/elm-mount.
 *
 * @package PinkCrab\ElmMount
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

$wp_phpunit_dir = getenv( 'WP_PHPUNIT__DIR' );

if ( ! $wp_phpunit_dir || ! is_dir( $wp_phpunit_dir ) ) {
	throw new RuntimeException(
		'WP_PHPUNIT__DIR is not set or does not exist. Run composer install in the package directory and run tests via the project container.'
	);
}

require_once $wp_phpunit_dir . '/includes/functions.php';

try {
	$dotenv = Dotenv\Dotenv::createUnsafeImmutable( __DIR__ );
	$dotenv->load();
} catch ( \Throwable $th ) {
	// .env is optional — CI uses environment variables directly.
	unset( $th );
}

tests_add_filter(
	'muplugins_loaded',
	static function (): void {}
);

require $wp_phpunit_dir . '/includes/bootstrap.php';
