<?php
/**
 * Options and settings schema.
 *
 * @package ContentFindReplace
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Options service.
 */
class WCFR_Options {

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'wcfr_settings_group',
			WCFR_OPTION_SETTINGS,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->get_defaults(),
			)
		);
	}

	/**
	 * Get settings defaults.
	 *
	 * @return array<string,mixed>
	 */
	public function get_defaults(): array {
		return array(
			'enabled'        => true,
			'filter_content' => true,
			'filter_excerpt' => false,
			'rules'          => array(),
		);
	}

	/**
	 * Get all settings with defaults.
	 *
	 * @return array<string,mixed>
	 */
	public function get_settings(): array {
		$stored = get_option( WCFR_OPTION_SETTINGS, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return wp_parse_args( $stored, $this->get_defaults() );
	}

	/**
	 * Save settings.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @return void
	 */
	public function save_settings( array $settings ): void {
		update_option( WCFR_OPTION_SETTINGS, $this->sanitize_settings( $settings ) );
	}

	/**
	 * Get rules.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_rules(): array {
		$settings = $this->get_settings();
		$rules    = $settings['rules'] ?? array();

		return is_array( $rules ) ? $rules : array();
	}

	/**
	 * Sanitize settings.
	 *
	 * @param mixed $settings Settings.
	 * @return array<string,mixed>
	 */
	public function sanitize_settings( $settings ): array {
		$defaults = $this->get_defaults();
		if ( ! is_array( $settings ) ) {
			return $defaults;
		}

		$sanitized                   = array();
		$sanitized['enabled']        = ! empty( $settings['enabled'] );
		$sanitized['filter_content'] = ! empty( $settings['filter_content'] );
		$sanitized['filter_excerpt'] = ! empty( $settings['filter_excerpt'] );
		$sanitized['rules']          = $this->sanitize_rules( $settings['rules'] ?? array() );

		return wp_parse_args( $sanitized, $defaults );
	}

	/**
	 * Sanitize rules.
	 *
	 * @param mixed $rules Rules.
	 * @return array<int,array<string,mixed>>
	 */
	public function sanitize_rules( $rules ): array {
		if ( ! is_array( $rules ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $rules as $rule ) {
			if ( ! is_array( $rule ) ) {
				continue;
			}

			$rule_id = isset( $rule['id'] ) ? sanitize_text_field( (string) $rule['id'] ) : wp_generate_uuid4();
			$name    = isset( $rule['name'] ) ? sanitize_text_field( (string) $rule['name'] ) : '';
			$find    = isset( $rule['find'] ) ? sanitize_textarea_field( (string) $rule['find'] ) : '';
			$replace = isset( $rule['replace'] ) ? sanitize_textarea_field( (string) $rule['replace'] ) : '';

			if ( '' === $name ) {
				$name = sprintf( 'Rule %s', substr( $rule_id, 0, 8 ) );
			}

			if ( ! empty( $rule['strategy'] ) ) {
				$strategy = sanitize_key( (string) $rule['strategy'] );
			} else {
				$strategy = 'find_replace';
			}

			$post_types = array();
			if ( ! empty( $rule['post_types'] ) && is_array( $rule['post_types'] ) ) {
				$post_types = array_values( array_unique( array_map( 'sanitize_key', $rule['post_types'] ) ) );
			}

			$sanitized[] = array(
				'id'             => $rule_id,
				'name'           => $name,
				'enabled'        => ! empty( $rule['enabled'] ),
				'strategy'       => $strategy,
				'find'           => $find,
				'replace'        => $replace,
				'use_regex'      => ! empty( $rule['use_regex'] ),
				'ignore_case'    => ! empty( $rule['ignore_case'] ),
				'apply_in_admin' => ! empty( $rule['apply_in_admin'] ),
				'post_types'     => $post_types,
			);
		}

		return $sanitized;
	}
}
