<?php
/**
 * Wikimedia preset helpers.
 *
 * @package ContentFindReplace
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wikimedia preset class.
 */
class WCFR_Wikimedia_Preset {

	/**
	 * Standard thumbnail sizes.
	 *
	 * @var int[]
	 */
	private const SIZES = array( 20, 40, 60, 120, 250, 330, 500, 960, 1280, 1920, 3840 );

	/**
	 * Create preset rule.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_rule(): array {
		return array(
			'id'             => wp_generate_uuid4(),
			'name'           => __( 'Wikimedia: Round thumbnail to next allowed size', 'wordpress-content-find-replace' ),
			'enabled'        => true,
			'strategy'       => 'wikimedia_thumbnail_roundup',
			'find'           => '',
			'replace'        => '',
			'use_regex'      => false,
			'ignore_case'    => false,
			'apply_in_admin' => false,
			'post_types'     => array(),
		);
	}

	/**
	 * Apply Wikimedia URL rewrites.
	 *
	 * @param string $content Content.
	 * @param array<int,array<string,string>> $changes Change report accumulator.
	 * @return string
	 */
	public static function rewrite_content( string $content, array &$changes ): string {
		$pattern = '~((?:https?:)?//upload\.wikimedia\.org/wikipedia/(?:commons|[a-z-]+)/)thumb/([^\s"\'<>]+/[^\s"\'<>]+/[^\s"\'<>]+)/([0-9]+)px-([^\s"\'<>]+)~i';

		$result = preg_replace_callback(
			$pattern,
			static function ( array $matches ) use ( &$changes ): string {
				$base     = $matches[1];
				$path     = $matches[2];
				$size     = (int) $matches[3];
				$file_png = $matches[4];

				$new_url = self::build_rewritten_url( $base, $path, $file_png, $size );
				$old_url = $matches[0];

				if ( $new_url !== $old_url ) {
					$changes[] = array(
						'type' => 'wikimedia',
						'from' => $old_url,
						'to'   => $new_url,
					);
				}

				return $new_url;
			},
			$content
		);

		return is_string( $result ) ? $result : $content;
	}

	/**
	 * Build rewritten URL.
	 *
	 * @param string $base Base URL.
	 * @param string $path Original thumb path segment.
	 * @param string $thumb_file Thumb filename segment.
	 * @param int    $requested_size Requested size.
	 * @return string
	 */
	private static function build_rewritten_url( string $base, string $path, string $thumb_file, int $requested_size ): string {
		if ( $requested_size > 3840 ) {
			return $base . $path;
		}

		$mapped_size = self::next_size( $requested_size );
		if ( null === $mapped_size ) {
			return $base . $path;
		}

		return sprintf( '%1$sthumb/%2$s/%3$dpx-%4$s', $base, $path, $mapped_size, $thumb_file );
	}

	/**
	 * Find next supported size.
	 *
	 * @param int $requested_size Requested size.
	 * @return int|null
	 */
	private static function next_size( int $requested_size ): ?int {
		foreach ( self::SIZES as $size ) {
			if ( $requested_size <= $size ) {
				return $size;
			}
		}

		return null;
	}
}
