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

if ( ! function_exists( 'wp_kses_post' ) ) {
	/**
	 * Minimal wp_kses_post() shim for isolated tests.
	 *
	 * @param string $text Text to sanitize.
	 * @return string
	 */
	function wp_kses_post( string $text ): string {
		$sanitized = preg_replace( '~<script\b[^>]*>.*?</script>~is', '', $text );

		return is_string( $sanitized ) ? $sanitized : $text;
	}
}

require_once dirname( __DIR__, 2 ) . '/includes/class-wcfr-wikimedia-preset.php';
