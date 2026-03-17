<?php
/**
 * Wikimedia preset tests.
 *
 * @package ContentFindReplace
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests for Wikimedia preset mapper.
 */
final class Test_Wikimedia_Preset extends TestCase {

	/**
	 * Test that 240 maps to 250.
	 *
	 * @return void
	 */
	public function test_rounds_up_240_to_250(): void {
		$changes = array();
		$input   = 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/90/Wikimedia_Hackathon_Amsterdam_2013.svg/240px-Wikimedia_Hackathon_Amsterdam_2013.svg.png';
		$output  = WCFR_Wikimedia_Preset::rewrite_content( $input, $changes );

		self::assertStringContainsString( '/250px-Wikimedia_Hackathon_Amsterdam_2013.svg.png', $output );
	}
}
