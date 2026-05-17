<?php
/**
 * Main plugin orchestrator.
 *
 * @package ContentFindReplace
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 */
class WCFR_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var WCFR_Plugin|null
	 */
	private static ?WCFR_Plugin $instance = null;

	/**
	 * Options handler.
	 *
	 * @var WCFR_Options
	 */
	private WCFR_Options $options;

	/**
	 * Rule engine.
	 *
	 * @var WCFR_Rule_Engine
	 */
	private WCFR_Rule_Engine $engine;

	/**
	 * Migrations service.
	 *
	 * @var WCFR_Migrations
	 */
	private WCFR_Migrations $migrations;

	/**
	 * Admin page service.
	 *
	 * @var WCFR_Admin_Tools_Page
	 */
	private WCFR_Admin_Tools_Page $admin_page;

	/**
	 * Get instance.
	 *
	 * @return WCFR_Plugin
	 */
	public static function get_instance(): WCFR_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->options    = new WCFR_Options();
		$this->engine     = new WCFR_Rule_Engine( $this->options );
		$this->migrations = new WCFR_Migrations( $this->options, $this->engine );
		$this->admin_page = new WCFR_Admin_Tools_Page( $this->options, $this->engine, $this->migrations );

		add_action( 'admin_init', array( $this->options, 'register_settings' ) );
		add_action( 'admin_init', array( $this->admin_page, 'register_actions' ) );
		add_action( 'admin_menu', array( $this->admin_page, 'register_menu' ) );

		add_filter( 'the_content', array( $this, 'filter_content' ), 20 );
		add_filter( 'the_excerpt', array( $this, 'filter_excerpt' ), 20 );
	}

	/**
	 * Filter content.
	 *
	 * @param string $content Content to filter.
	 * @return string
	 */
	public function filter_content( string $content ): string {
		return self::sanitize_filtered_output( $this->engine->filter( $content, 'the_content' ) );
	}

	/**
	 * Filter excerpt.
	 *
	 * @param string $excerpt Excerpt to filter.
	 * @return string
	 */
	public function filter_excerpt( string $excerpt ): string {
		return self::sanitize_filtered_output( $this->engine->filter( $excerpt, 'the_excerpt' ) );
	}

	/**
	 * Sanitize filtered output before WordPress renders it.
	 *
	 * @param string $content Filtered content.
	 * @return string
	 */
	public static function sanitize_filtered_output( string $content ): string {
		return wp_kses_post( $content );
	}
}
