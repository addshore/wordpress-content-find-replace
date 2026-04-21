<?php
/**
 * Tools page.
 *
 * @package ContentFindReplace
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin tools page class.
 */
class WCFR_Admin_Tools_Page {

	/**
	 * Options service.
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
	 * Migration service.
	 *
	 * @var WCFR_Migrations
	 */
	private WCFR_Migrations $migrations;

	/**
	 * Constructor.
	 *
	 * @param WCFR_Options     $options Options.
	 * @param WCFR_Rule_Engine $engine Engine.
	 * @param WCFR_Migrations  $migrations Migrations.
	 */
	public function __construct( WCFR_Options $options, WCFR_Rule_Engine $engine, WCFR_Migrations $migrations ) {
		$this->options    = $options;
		$this->engine     = $engine;
		$this->migrations = $migrations;
	}

	/**
	 * Register tools submenu.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_submenu_page(
			'tools.php',
			__( 'Content Find Replace', 'content-find-replace' ),
			__( 'Content Find Replace', 'content-find-replace' ),
			'manage_options',
			'wcfr-tools',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register admin actions.
	 *
	 * @return void
	 */
	public function register_actions(): void {
		add_action( 'admin_post_wcfr_save_settings', array( $this, 'handle_save_settings' ) );
		add_action( 'admin_post_wcfr_install_wikimedia_preset', array( $this, 'handle_install_wikimedia_preset' ) );
		add_action( 'admin_post_wcfr_migration_preview', array( $this, 'handle_migration_preview' ) );
		add_action( 'admin_post_wcfr_migration_apply', array( $this, 'handle_migration_apply' ) );
		add_action( 'admin_post_wcfr_migration_apply_single', array( $this, 'handle_migration_apply_single' ) );
		add_action( 'admin_post_wcfr_migration_rollback', array( $this, 'handle_migration_rollback' ) );
		add_action( 'admin_notices', array( $this, 'render_runtime_notices' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook_suffix Screen hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( 'tools_page_wcfr-tools' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style( 'wcfr-admin', WCFR_PLUGIN_URL . 'assets/css/admin.css', array(), WCFR_VERSION );
		wp_enqueue_script( 'wcfr-admin', WCFR_PLUGIN_URL . 'assets/js/admin.js', array(), WCFR_VERSION, true );
	}

	/**
	 * Render page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to access this page.', 'content-find-replace' ) );
		}

		$settings          = $this->options->get_settings();
		$rules             = $this->options->get_rules();
		$preview_transient = get_transient( $this->preview_transient_key() );
		$preview           = is_array( $preview_transient ) ? $preview_transient : null;
		$runs              = $this->migrations->get_runs();
		$post_types        = get_post_types( array( 'public' => true ), 'objects' );
		$post_statuses     = get_post_stati( array(), 'objects' );

		$has_wikimedia_preset = false;
		foreach ( $rules as $rule ) {
			if ( isset( $rule['strategy'] ) && 'wikimedia_thumbnail_roundup' === $rule['strategy'] ) {
				$has_wikimedia_preset = true;
				break;
			}
		}

		require WCFR_PLUGIN_DIR . 'admin/views/tools-page.php';
	}

	/**
	 * Handle settings save.
	 *
	 * @return void
	 */
	public function handle_save_settings(): void {
		$this->assert_admin_action( 'wcfr_save_settings' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified by assert_admin_action() above.
		$incoming = isset( $_POST['wcfr'] ) && is_array( $_POST['wcfr'] ) ? wp_unslash( $_POST['wcfr'] ) : array();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$rules    = isset( $_POST['wcfr_rules'] ) && is_array( $_POST['wcfr_rules'] ) ? wp_unslash( $_POST['wcfr_rules'] ) : array();

		$incoming['enabled']        = ! empty( $incoming['enabled'] );
		$incoming['filter_content'] = ! empty( $incoming['filter_content'] );
		$incoming['filter_excerpt'] = ! empty( $incoming['filter_excerpt'] );
		$incoming['rules']          = $this->hydrate_rules_from_request( $rules );

		$this->options->save_settings( $incoming );

		$this->redirect_with_message( 'saved', __( 'Settings saved.', 'content-find-replace' ) );
	}

	/**
	 * Install Wikimedia preset.
	 *
	 * @return void
	 */
	public function handle_install_wikimedia_preset(): void {
		$this->assert_admin_action( 'wcfr_install_wikimedia_preset' );

		$settings = $this->options->get_settings();
		$rules    = isset( $settings['rules'] ) && is_array( $settings['rules'] ) ? $settings['rules'] : array();

		$rules[] = WCFR_Wikimedia_Preset::get_rule();

		$settings['rules'] = $rules;
		$this->options->save_settings( $settings );

		$this->redirect_with_message( 'saved', __( 'Wikimedia preset installed.', 'content-find-replace' ) );
	}

	/**
	 * Handle preview action.
	 *
	 * @return void
	 */
	public function handle_migration_preview(): void {
		$this->assert_admin_action( 'wcfr_migration_preview' );

		$scope   = $this->read_scope_from_request();
		$preview = $this->migrations->preview( $scope );
		set_transient( $this->preview_transient_key(), $preview, HOUR_IN_SECONDS );

		$this->redirect_with_message( 'preview', __( 'Preview generated.', 'content-find-replace' ) );
	}

	/**
	 * Handle apply action.
	 *
	 * @return void
	 */
	public function handle_migration_apply(): void {
		$this->assert_admin_action( 'wcfr_migration_apply' );

		$scope  = $this->read_scope_from_request();
		$result = $this->migrations->apply( $scope );

		$message = sprintf(
			/* translators: 1: updated count, 2: migration id. */
			__( 'Migration applied to %1$d posts. Run ID: %2$s', 'content-find-replace' ),
			(int) $result['updated_count'],
			esc_html( (string) $result['run_id'] )
		);

		$this->redirect_with_message( 'applied', $message );
	}

	/**
	 * Apply migration to a single post.
	 *
	 * @return void
	 */
	public function handle_migration_apply_single(): void {
		$this->assert_admin_action( 'wcfr_migration_apply_single' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified by assert_admin_action() above.
		$post_id = isset( $_POST['wcfr_post_id'] ) ? (int) wp_unslash( $_POST['wcfr_post_id'] ) : 0;
		if ( $post_id <= 0 ) {
			$this->redirect_with_message( 'applied', __( 'Invalid post ID.', 'content-find-replace' ) );
		}

		$scope  = $this->read_scope_from_request();
		$result = $this->migrations->apply_single( $post_id, $scope );

		if ( ! $result['updated'] ) {
			$this->redirect_with_message( 'applied', (string) $result['message'] );
		}

		$this->redirect_with_message(
			'applied',
			sprintf(
				/* translators: %d post ID. */
				__( 'Applied to post #%d.', 'content-find-replace' ),
				$post_id
			)
		);
	}

	/**
	 * Handle rollback action.
	 *
	 * @return void
	 */
	public function handle_migration_rollback(): void {
		$this->assert_admin_action( 'wcfr_migration_rollback' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by assert_admin_action() above.
		$run_id = isset( $_POST['run_id'] ) ? sanitize_text_field( (string) wp_unslash( $_POST['run_id'] ) ) : '';
		if ( '' === $run_id ) {
			$this->redirect_with_message( 'rollback', __( 'Run ID missing.', 'content-find-replace' ) );
		}

		$result = $this->migrations->rollback( $run_id );
		$this->redirect_with_message( 'rollback', sprintf(
			/* translators: %d number of posts restored. */
			__( 'Rollback restored %d posts.', 'content-find-replace' ),
			(int) $result['restored_count']
		) );
	}

	/**
	 * Render runtime notices for regex errors.
	 *
	 * @return void
	 */
	public function render_runtime_notices(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$errors = $this->engine->get_runtime_errors();
		if ( empty( $errors ) ) {
			return;
		}

		echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'Content Find Replace warnings:', 'content-find-replace' ) . '</strong></p><ul>';
		foreach ( array_unique( $errors ) as $error ) {
			echo '<li>' . esc_html( $error ) . '</li>';
		}
		echo '</ul></div>';
	}

	/**
	 * Convert request rows to rules.
	 *
	 * @param array<int,mixed> $rows Rows.
	 * @return array<int,array<string,mixed>>
	 */
	private function hydrate_rules_from_request( array $rows ): array {
		$rules = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$rules[] = array(
				'id'             => $row['id'] ?? '',
				'name'           => $row['name'] ?? '',
				'enabled'        => ! empty( $row['enabled'] ),
				'strategy'       => $row['strategy'] ?? 'find_replace',
				'find'           => $row['find'] ?? '',
				'replace'        => $row['replace'] ?? '',
				'use_regex'      => ! empty( $row['use_regex'] ),
				'ignore_case'    => ! empty( $row['ignore_case'] ),
				'apply_in_admin' => ! empty( $row['apply_in_admin'] ),
				'post_types'     => isset( $row['post_types'] ) && is_array( $row['post_types'] ) ? $row['post_types'] : array(),
			);
		}

		return $rules;
	}

	/**
	 * Read migration scope from request.
	 *
	 * @return array<string,mixed>
	 */
	private function read_scope_from_request(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified by assert_admin_action() in the calling handler.
		$scope = isset( $_POST['wcfr_scope'] ) && is_array( $_POST['wcfr_scope'] ) ? wp_unslash( $_POST['wcfr_scope'] ) : array();

		$post_types    = isset( $scope['post_types'] ) && is_array( $scope['post_types'] ) ? array_map( 'sanitize_key', $scope['post_types'] ) : array( 'post', 'page' );
		$post_statuses = isset( $scope['post_statuses'] ) && is_array( $scope['post_statuses'] ) ? array_map( 'sanitize_key', $scope['post_statuses'] ) : array( 'publish' );
		$limit         = isset( $scope['limit'] ) ? (int) $scope['limit'] : 100;
		$rule_ids      = isset( $scope['rule_ids'] ) && is_array( $scope['rule_ids'] ) ? array_map( 'sanitize_text_field', $scope['rule_ids'] ) : null;

		return array(
			'post_types'    => $post_types,
			'post_statuses' => $post_statuses,
			'limit'         => max( 1, min( 500, $limit ) ),
			'rule_ids'      => $rule_ids,
		);
	}

	/**
	 * Verify action security.
	 *
	 * @param string $nonce_action Nonce action.
	 * @return void
	 */
	private function assert_admin_action( string $nonce_action ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to perform this action.', 'content-find-replace' ) );
		}

		check_admin_referer( $nonce_action );
	}

	/**
	 * Redirect with a message.
	 *
	 * @param string $code Code.
	 * @param string $message Message.
	 * @return void
	 */
	private function redirect_with_message( string $code, string $message ): void {
		$target = add_query_arg(
			array(
				'page'     => 'wcfr-tools',
				'wcfr_msg' => $message,
				'wcfr_code'=> $code,
			),
			admin_url( 'tools.php' )
		);
		wp_safe_redirect( $target );
		exit;
	}

	/**
	 * Preview transient key.
	 *
	 * @return string
	 */
	private function preview_transient_key(): string {
		return 'wcfr_preview_' . get_current_user_id();
	}
}
