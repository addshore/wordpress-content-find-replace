<?php
/**
 * PHPUnit bootstrap placeholder.
 *
 * @package WordPressContentFindReplace
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
	function __( string $text ): string {
		return $text;
	}
}

require_once dirname( __DIR__, 2 ) . '/includes/class-wcfr-wikimedia-preset.php';
