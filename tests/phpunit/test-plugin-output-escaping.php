<?php
/**
 * Plugin output escaping tests.
 *
 * @package ContentFindReplace
 */

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 2 ) . '/includes/class-wcfr-plugin.php';

/**
 * Tests for filtered output sanitization.
 */
final class Test_Plugin_Output_Escaping extends TestCase {

	/**
	 * Filtered content returned to WordPress should be sanitized.
	 *
	 * @return void
	 */
	public function test_sanitize_filtered_output_removes_disallowed_markup(): void {
		$input = '<p>Allowed</p><script>alert(1)</script><em>Still allowed</em>';

		self::assertSame(
			'<p>Allowed</p><em>Still allowed</em>',
			WCFR_Plugin::sanitize_filtered_output( $input )
		);
	}
}