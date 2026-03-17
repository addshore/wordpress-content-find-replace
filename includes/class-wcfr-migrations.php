<?php
/**
 * Migration preview/apply/rollback service.
 *
 * @package ContentFindReplace
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Migration service.
 */
class WCFR_Migrations {

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
	 * Constructor.
	 *
	 * @param WCFR_Options     $options Options.
	 * @param WCFR_Rule_Engine $engine Rule engine.
	 */
	public function __construct( WCFR_Options $options, WCFR_Rule_Engine $engine ) {
		$this->options = $options;
		$this->engine  = $engine;
	}

	/**
	 * Build preview data.
	 *
	 * @param array<string,mixed> $scope Scope arguments.
	 * @return array<string,mixed>
	 */
	public function preview( array $scope ): array {
		$posts    = $this->query_posts( $scope );
		$items    = array();
		$errors   = array();
		$rule_ids = isset( $scope['rule_ids'] ) && is_array( $scope['rule_ids'] ) ? $scope['rule_ids'] : null;

		foreach ( $posts as $post ) {
			$before = (string) $post->post_content;
			$report = $this->engine->apply_rules( $before, 'the_content', true, $rule_ids );
			$after  = $report['content'];

			if ( $after === $before ) {
				continue;
			}

			$items[] = array(
				'post_id'       => (int) $post->ID,
				'post_type'     => (string) $post->post_type,
				'post_title'    => (string) $post->post_title,
				'before'        => $before,
				'after'         => $after,
				'before_snippet'=> wp_html_excerpt( wp_strip_all_tags( $before ), 240, '…' ),
				'after_snippet' => wp_html_excerpt( wp_strip_all_tags( $after ), 240, '…' ),
				'changes'       => $report['changes'],
			);

			if ( ! empty( $report['errors'] ) ) {
				$errors = array_merge( $errors, $report['errors'] );
			}
		}

		return array(
			'generated_at' => gmdate( 'c' ),
			'scope'        => $scope,
			'items'        => $items,
			'errors'       => array_values( array_unique( $errors ) ),
		);
	}

	/**
	 * Apply migration and persist snapshot.
	 *
	 * @param array<string,mixed> $scope Scope.
	 * @return array<string,mixed>
	 */
	public function apply( array $scope ): array {
		$preview = $this->preview( $scope );
		$items   = $preview['items'];

		if ( empty( $items ) || ! is_array( $items ) ) {
			return array(
				'run_id'        => '',
				'updated_count' => 0,
				'errors'        => $preview['errors'],
			);
		}

		$run_id   = wp_generate_uuid4();
		$snapshot = array(
			'run_id'      => $run_id,
			'created_at'  => gmdate( 'c' ),
			'scope'       => $scope,
			'updated_ids' => array(),
			'entries'     => array(),
		);

		foreach ( $items as $item ) {
			$post_id = (int) $item['post_id'];
			$before  = (string) $item['before'];
			$after   = (string) $item['after'];

			$updated = wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => $after,
				),
				true
			);

			if ( is_wp_error( $updated ) ) {
				continue;
			}

			$snapshot['updated_ids'][] = $post_id;
			$snapshot['entries'][]     = array(
				'post_id' => $post_id,
				'before'  => $before,
				'after'   => $after,
			);
		}

		$this->store_snapshot( $snapshot );

		return array(
			'run_id'        => $run_id,
			'updated_count' => count( $snapshot['updated_ids'] ),
			'errors'        => $preview['errors'],
		);
	}

	/**
	 * Apply migration to a single post.
	 *
	 * @param int                 $post_id Post ID.
	 * @param array<string,mixed> $scope   Scope (may include rule_ids).
	 * @return array<string,mixed>
	 */
	public function apply_single( int $post_id, array $scope ): array {
		$rule_ids = isset( $scope['rule_ids'] ) && is_array( $scope['rule_ids'] ) ? $scope['rule_ids'] : null;

		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return array(
				'run_id'  => '',
				'updated' => false,
				'message' => __( 'Post not found.', 'wordpress-content-find-replace' ),
			);
		}

		$before = (string) $post->post_content;
		$report = $this->engine->apply_rules( $before, 'the_content', true, $rule_ids );
		$after  = $report['content'];

		if ( $after === $before ) {
			return array(
				'run_id'  => '',
				'updated' => false,
				'message' => __( 'No changes to apply.', 'wordpress-content-find-replace' ),
			);
		}

		$updated = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $after,
			),
			true
		);

		if ( is_wp_error( $updated ) ) {
			return array(
				'run_id'  => '',
				'updated' => false,
				'message' => $updated->get_error_message(),
			);
		}

		$run_id   = wp_generate_uuid4();
		$snapshot = array(
			'run_id'      => $run_id,
			'created_at'  => gmdate( 'c' ),
			'scope'       => $scope,
			'updated_ids' => array( $post_id ),
			'entries'     => array(
				array(
					'post_id' => $post_id,
					'before'  => $before,
					'after'   => $after,
				),
			),
		);

		$this->store_snapshot( $snapshot );

		return array(
			'run_id'  => $run_id,
			'updated' => true,
			'message' => '',
		);
	}

	/**
	 * Roll back migration by run id.
	 *
	 * @param string $run_id Run ID.
	 * @return array<string,mixed>
	 */
	public function rollback( string $run_id ): array {
		$runs = $this->get_runs();
		if ( ! isset( $runs[ $run_id ] ) || ! is_array( $runs[ $run_id ] ) ) {
			return array(
				'restored_count' => 0,
				'message'        => __( 'Migration run not found.', 'wordpress-content-find-replace' ),
			);
		}

		$run      = $runs[ $run_id ];
		$restored = 0;

		if ( ! empty( $run['entries'] ) && is_array( $run['entries'] ) ) {
			foreach ( $run['entries'] as $entry ) {
				$post_id = (int) ( $entry['post_id'] ?? 0 );
				$before  = (string) ( $entry['before'] ?? '' );
				if ( $post_id <= 0 ) {
					continue;
				}

				$updated = wp_update_post(
					array(
						'ID'           => $post_id,
						'post_content' => $before,
					),
					true
				);
				if ( ! is_wp_error( $updated ) ) {
					++$restored;
				}
			}
		}

		unset( $runs[ $run_id ] );
		update_option( WCFR_OPTION_MIGRATIONS, $runs );

		return array(
			'restored_count' => $restored,
			'message'        => __( 'Rollback complete.', 'wordpress-content-find-replace' ),
		);
	}

	/**
	 * Get stored migration runs.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_runs(): array {
		$runs = get_option( WCFR_OPTION_MIGRATIONS, array() );
		if ( ! is_array( $runs ) ) {
			return array();
		}

		return $runs;
	}

	/**
	 * Store migration snapshot.
	 *
	 * @param array<string,mixed> $snapshot Snapshot.
	 * @return void
	 */
	private function store_snapshot( array $snapshot ): void {
		$runs                       = $this->get_runs();
		$runs[ $snapshot['run_id'] ] = $snapshot;

		if ( count( $runs ) > 20 ) {
			$run_ids = array_keys( $runs );
			sort( $run_ids );
			$trim = array_slice( $run_ids, 0, count( $run_ids ) - 20 );
			foreach ( $trim as $run_id ) {
				unset( $runs[ $run_id ] );
			}
		}

		update_option( WCFR_OPTION_MIGRATIONS, $runs );
	}

	/**
	 * Query posts by scope.
	 *
	 * @param array<string,mixed> $scope Scope.
	 * @return array<int,WP_Post>
	 */
	private function query_posts( array $scope ): array {
		$post_types = array( 'post', 'page' );
		if ( ! empty( $scope['post_types'] ) && is_array( $scope['post_types'] ) ) {
			$post_types = array_values( array_unique( array_map( 'sanitize_key', $scope['post_types'] ) ) );
		}

		$statuses = array( 'publish' );
		if ( ! empty( $scope['post_statuses'] ) && is_array( $scope['post_statuses'] ) ) {
			$statuses = array_values( array_unique( array_map( 'sanitize_key', $scope['post_statuses'] ) ) );
		}

		$limit = isset( $scope['limit'] ) ? max( 1, min( 500, (int) $scope['limit'] ) ) : 100;

		$posts = get_posts(
			array(
				'post_type'      => $post_types,
				'post_status'    => $statuses,
				'posts_per_page' => $limit,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'fields'         => 'all',
			)
		);

		return is_array( $posts ) ? $posts : array();
	}
}
