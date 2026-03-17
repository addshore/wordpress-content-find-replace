<?php
/**
 * Content rule engine.
 *
 * @package WordPressContentFindReplace
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rule engine.
 */
class WCFR_Rule_Engine {

	/**
	 * Options service.
	 *
	 * @var WCFR_Options
	 */
	private WCFR_Options $options;

	/**
	 * Runtime errors.
	 *
	 * @var array<int,string>
	 */
	private array $errors = array();

	/**
	 * Constructor.
	 *
	 * @param WCFR_Options $options Options.
	 */
	public function __construct( WCFR_Options $options ) {
		$this->options = $options;
	}

	/**
	 * Filter content.
	 *
	 * @param string $content Content.
	 * @param string $filter_name Current filter.
	 * @return string
	 */
	public function filter( string $content, string $filter_name ): string {
		$result = $this->apply_rules( $content, $filter_name, false );
		$this->errors = $result['errors'];

		return $result['content'];
	}

	/**
	 * Apply rules with report.
	 *
	 * @param string            $content Content.
	 * @param string            $filter_name Current filter.
	 * @param bool              $force_admin_override Force execution in admin.
	 * @param array<int,string>|null $rule_ids Limit to these rule IDs (null = all).
	 * @return array{content:string,changes:array<int,array<string,mixed>>,errors:array<int,string>}
	 */
	public function apply_rules( string $content, string $filter_name = 'the_content', bool $force_admin_override = false, ?array $rule_ids = null ): array {
		$settings = $this->options->get_settings();
		if ( empty( $settings['enabled'] ) ) {
			return array(
				'content' => $content,
				'changes' => array(),
				'errors'  => array(),
			);
		}

		if ( 'the_content' === $filter_name && empty( $settings['filter_content'] ) ) {
			return array(
				'content' => $content,
				'changes' => array(),
				'errors'  => array(),
			);
		}

		if ( 'the_excerpt' === $filter_name && empty( $settings['filter_excerpt'] ) ) {
			return array(
				'content' => $content,
				'changes' => array(),
				'errors'  => array(),
			);
		}

		$post      = get_post();
		$post_type = $post instanceof WP_Post ? $post->post_type : '';

		$changes  = array();
		$errors   = array();
		$current  = $content;
		$rules    = $this->options->get_rules();
		$in_admin = is_admin();

		foreach ( $rules as $rule ) {
			if ( empty( $rule['enabled'] ) || ! is_array( $rule ) ) {
				continue;
			}

			if ( null !== $rule_ids && ! in_array( (string) ( $rule['id'] ?? '' ), $rule_ids, true ) ) {
				continue;
			}

			if ( $in_admin && ! $force_admin_override && empty( $rule['apply_in_admin'] ) ) {
				continue;
			}

			if ( ! empty( $rule['post_types'] ) && is_array( $rule['post_types'] ) && '' !== $post_type ) {
				if ( ! in_array( $post_type, $rule['post_types'], true ) ) {
					continue;
				}
			}

			$strategy = isset( $rule['strategy'] ) ? (string) $rule['strategy'] : 'find_replace';

			if ( 'wikimedia_thumbnail_roundup' === $strategy ) {
				$current = WCFR_Wikimedia_Preset::rewrite_content( $current, $changes );
				continue;
			}

			$execution = $this->execute_find_replace_rule( $current, $rule );
			$current   = $execution['content'];

			if ( ! empty( $execution['changes'] ) ) {
				$changes = array_merge( $changes, $execution['changes'] );
			}
			if ( ! empty( $execution['errors'] ) ) {
				$errors = array_merge( $errors, $execution['errors'] );
			}
		}

		return array(
			'content' => $current,
			'changes' => $changes,
			'errors'  => $errors,
		);
	}

	/**
	 * Get runtime errors.
	 *
	 * @return array<int,string>
	 */
	public function get_runtime_errors(): array {
		return $this->errors;
	}

	/**
	 * Execute normal find/replace rule.
	 *
	 * @param string              $content Content.
	 * @param array<string,mixed> $rule Rule.
	 * @return array{content:string,changes:array<int,array<string,mixed>>,errors:array<int,string>}
	 */
	private function execute_find_replace_rule( string $content, array $rule ): array {
		$find        = isset( $rule['find'] ) ? (string) $rule['find'] : '';
		$replace     = isset( $rule['replace'] ) ? (string) $rule['replace'] : '';
		$ignore_case = ! empty( $rule['ignore_case'] );
		$use_regex   = ! empty( $rule['use_regex'] );
		$rule_name   = isset( $rule['name'] ) ? (string) $rule['name'] : __( 'Unnamed rule', 'wordpress-content-find-replace' );

		if ( '' === $find ) {
			return array(
				'content' => $content,
				'changes' => array(),
				'errors'  => array(),
			);
		}

		if ( $use_regex ) {
			$pattern = $find;

			if ( ! $this->is_valid_regex( $pattern ) ) {
				return array(
					'content' => $content,
					'changes' => array(),
					'errors'  => array(
						sprintf(
							/* translators: %s: rule name. */
							__( 'Regex rule skipped due to invalid pattern: %s', 'wordpress-content-find-replace' ),
							$rule_name
						),
					),
				);
			}

			$match_count = preg_match_all( $pattern, $content, $unused_matches );
			if ( false === $match_count || 0 === $match_count ) {
				return array(
					'content' => $content,
					'changes' => array(),
					'errors'  => array(),
				);
			}

			set_error_handler( 'wcfr_silence_pcre_errors' );
			$result = preg_replace( $pattern, $replace, $content );
			restore_error_handler();

			if ( ! is_string( $result ) ) {
				return array(
					'content' => $content,
					'changes' => array(),
					'errors'  => array(
						sprintf(
							/* translators: %s: rule name. */
							__( 'Regex execution failed for rule: %s', 'wordpress-content-find-replace' ),
							$rule_name
						),
					),
				);
			}

			return array(
				'content' => $result,
				'changes' => array(
					array(
						'type'        => 'regex',
						'pattern'     => $pattern,
						'replacement' => $replace,
						'occurrences' => (int) $match_count,
					),
				),
				'errors'  => array(),
			);
		}

		$occurrence_count = $ignore_case ? substr_count( strtolower( $content ), strtolower( $find ) ) : substr_count( $content, $find );
		if ( 0 === $occurrence_count ) {
			return array(
				'content' => $content,
				'changes' => array(),
				'errors'  => array(),
			);
		}

		$updated = $ignore_case ? str_ireplace( $find, $replace, $content ) : str_replace( $find, $replace, $content );
		if ( ! is_string( $updated ) ) {
			$updated = $content;
		}

		$changes = array(
			array(
				'type'       => 'text',
				'from'       => $find,
				'to'         => $replace,
				'occurrences'=> $occurrence_count,
			),
		);

		return array(
			'content' => $updated,
			'changes' => $changes,
			'errors'  => array(),
		);
	}

	/**
	 * Validate regex.
	 *
	 * @param string $pattern Pattern.
	 * @return bool
	 */
	private function is_valid_regex( string $pattern ): bool {
		if ( '' === $pattern ) {
			return false;
		}

		set_error_handler( 'wcfr_silence_pcre_errors' );
		$result = preg_match( $pattern, '' );
		restore_error_handler();

		return false !== $result;
	}
}

if ( ! function_exists( 'wcfr_silence_pcre_errors' ) ) {
	/**
	 * Silence expected PCRE warnings.
	 *
	 * @return bool
	 */
	function wcfr_silence_pcre_errors(): bool {
		return true;
	}
}
