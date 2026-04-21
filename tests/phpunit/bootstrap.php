<?php
/**
 * PHPUnit bootstrap placeholder.
 *
 * @package ContentFindReplace
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Translation shim.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- PHPUnit bootstrap shim for isolated tests.
	function __( string $text ): string {
		return $text;
	}
}

require_once dirname( __DIR__, 2 ) . '/includes/class-wcfr-wikimedia-preset.php';
