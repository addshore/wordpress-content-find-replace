<?php
/**
 * Tools page template.
 *
 * @var array<string,mixed>                          $settings
 * @var array<int,array<string,mixed>>               $rules
 * @var array<string,mixed>|null                     $preview
 * @var array<string,array<string,mixed>>            $runs
 * @var array<string,WP_Post_Type>                   $post_types
 * @var array<string,stdClass>                       $post_statuses
 *
 * @package ContentFindReplace
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template file; variables are local to this include, not globals.

$message = isset( $_GET['wcfr_msg'] ) ? sanitize_text_field( (string) wp_unslash( $_GET['wcfr_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only status message set by plugin's own safe redirect.
if ( '' !== $message ) :
	?>
	<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
	<?php
endif;
?>

<div class="wrap">
	<h1><?php esc_html_e( 'All the Content Changes', 'all-the-content-changes' ); ?></h1>

	<p><?php esc_html_e( 'Create unlimited rules, install the Wikimedia preset, and preview/apply safe migrations with rollback.', 'all-the-content-changes' ); ?></p>

	<h2><?php esc_html_e( 'Global Settings', 'all-the-content-changes' ); ?></h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="wcfr_save_settings" />
		<?php wp_nonce_field( 'wcfr_save_settings' ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Plugin Enabled', 'all-the-content-changes' ); ?></th>
				<td><label><input type="checkbox" name="wcfr[enabled]" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?> /> <?php esc_html_e( 'Enable runtime replacement.', 'all-the-content-changes' ); ?></label></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Filter the_content', 'all-the-content-changes' ); ?></th>
				<td><label><input type="checkbox" name="wcfr[filter_content]" value="1" <?php checked( ! empty( $settings['filter_content'] ) ); ?> /> <?php esc_html_e( 'Apply rules to post content.', 'all-the-content-changes' ); ?></label></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Filter the_excerpt', 'all-the-content-changes' ); ?></th>
				<td><label><input type="checkbox" name="wcfr[filter_excerpt]" value="1" <?php checked( ! empty( $settings['filter_excerpt'] ) ); ?> /> <?php esc_html_e( 'Apply rules to excerpts.', 'all-the-content-changes' ); ?></label></td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Rules', 'all-the-content-changes' ); ?></h2>
		<p><?php esc_html_e( 'Rules run in the listed order. The Wikimedia preset appears as a strategy rule and can be toggled like others.', 'all-the-content-changes' ); ?></p>

		<div id="wcfr-rules">
			<?php foreach ( $rules as $index => $rule ) : ?>
				<?php
				$row_index = (int) $index;
				$strategy  = (string) ( $rule['strategy'] ?? 'find_replace' );
				$is_preset = 'wikimedia_thumbnail_roundup' === $strategy;
				?>
				<div class="wcfr-rule-card">
					<input type="hidden" name="wcfr_rules[<?php echo esc_attr( (string) $row_index ); ?>][id]" value="<?php echo esc_attr( (string) ( $rule['id'] ?? '' ) ); ?>" />
					<div class="wcfr-rule-header">
						<strong><?php echo esc_html( (string) ( $rule['name'] ?? __( 'Rule', 'all-the-content-changes' ) ) ); ?></strong>
						<button type="button" class="button button-small wcfr-remove-rule"><?php esc_html_e( 'Remove', 'all-the-content-changes' ); ?></button>
					</div>
					<p>
						<label><?php esc_html_e( 'Name', 'all-the-content-changes' ); ?><br />
							<input type="text" class="regular-text" name="wcfr_rules[<?php echo esc_attr( (string) $row_index ); ?>][name]" value="<?php echo esc_attr( (string) ( $rule['name'] ?? '' ) ); ?>" />
						</label>
					</p>
					<p>
						<label><?php esc_html_e( 'Strategy', 'all-the-content-changes' ); ?><br />
							<select class="wcfr-strategy-select" name="wcfr_rules[<?php echo esc_attr( (string) $row_index ); ?>][strategy]">
								<option value="find_replace" <?php selected( $strategy, 'find_replace' ); ?>><?php esc_html_e( 'Find/Replace', 'all-the-content-changes' ); ?></option>
								<option value="wikimedia_thumbnail_roundup" <?php selected( $strategy, 'wikimedia_thumbnail_roundup' ); ?>><?php esc_html_e( 'Wikimedia Thumbnail Roundup', 'all-the-content-changes' ); ?></option>
							</select>
						</label>
					</p>

					<div class="wcfr-preset-info<?php echo $is_preset ? '' : ' wcfr-hidden'; ?>">
						<strong><?php esc_html_e( 'How this rule works:', 'all-the-content-changes' ); ?></strong>
						<p><?php esc_html_e( 'Scans post content for Wikimedia thumbnail URLs and rewrites the pixel size to the next allowed size. No find/replace text is needed — the URL pattern and size ladder are built in.', 'all-the-content-changes' ); ?></p>
						<strong><?php esc_html_e( 'Matches URLs like:', 'all-the-content-changes' ); ?></strong>
						<code>//upload.wikimedia.org/wikipedia/commons/thumb/a/ab/File.png/240px-File.png</code>
						<strong><?php esc_html_e( 'Rewrites to next size from:', 'all-the-content-changes' ); ?></strong>
						<code>20 &rarr; 40 &rarr; 60 &rarr; 120 &rarr; 250 &rarr; 330 &rarr; 500 &rarr; 960 &rarr; 1280 &rarr; 1920 &rarr; 3840 &rarr; (full-size source file)</code>
					</div>

					<div class="wcfr-rule-fields-fr<?php echo $is_preset ? ' wcfr-hidden' : ''; ?>">
						<p>
							<label><?php esc_html_e( 'Find', 'all-the-content-changes' ); ?><br />
								<textarea rows="3" class="large-text" name="wcfr_rules[<?php echo esc_attr( (string) $row_index ); ?>][find]"><?php echo esc_textarea( (string) ( $rule['find'] ?? '' ) ); ?></textarea>
							</label>
						</p>
						<p>
							<label><?php esc_html_e( 'Replace', 'all-the-content-changes' ); ?><br />
								<textarea rows="3" class="large-text" name="wcfr_rules[<?php echo esc_attr( (string) $row_index ); ?>][replace]"><?php echo esc_textarea( (string) ( $rule['replace'] ?? '' ) ); ?></textarea>
							</label>
						</p>
					</div>

					<p class="wcfr-rule-flags<?php echo $is_preset ? ' wcfr-hidden' : ''; ?>">
						<label><input type="checkbox" name="wcfr_rules[<?php echo esc_attr( (string) $row_index ); ?>][use_regex]" value="1" <?php checked( ! empty( $rule['use_regex'] ) ); ?> /> <?php esc_html_e( 'Use regex', 'all-the-content-changes' ); ?></label>
						<label><input type="checkbox" name="wcfr_rules[<?php echo esc_attr( (string) $row_index ); ?>][ignore_case]" value="1" <?php checked( ! empty( $rule['ignore_case'] ) ); ?> /> <?php esc_html_e( 'Ignore case', 'all-the-content-changes' ); ?></label>
					</p>
					<p>
						<label><input type="checkbox" name="wcfr_rules[<?php echo esc_attr( (string) $row_index ); ?>][enabled]" value="1" <?php checked( ! empty( $rule['enabled'] ) ); ?> /> <?php esc_html_e( 'Enabled', 'all-the-content-changes' ); ?></label>
						<label><input type="checkbox" name="wcfr_rules[<?php echo esc_attr( (string) $row_index ); ?>][apply_in_admin]" value="1" <?php checked( ! empty( $rule['apply_in_admin'] ) ); ?> /> <?php esc_html_e( 'Apply in admin', 'all-the-content-changes' ); ?></label>
					</p>
					<p>
						<?php esc_html_e( 'Post Types (leave empty for all):', 'all-the-content-changes' ); ?><br />
						<?php foreach ( $post_types as $post_type_name => $post_type_obj ) : ?>
							<?php $selected_types = ( isset( $rule['post_types'] ) && is_array( $rule['post_types'] ) ) ? $rule['post_types'] : array(); ?>
							<label style="margin-right:10px;"><input type="checkbox" name="wcfr_rules[<?php echo esc_attr( (string) $row_index ); ?>][post_types][]" value="<?php echo esc_attr( $post_type_name ); ?>" <?php checked( in_array( $post_type_name, $selected_types, true ) ); ?> /> <?php echo esc_html( $post_type_obj->labels->singular_name ?? $post_type_name ); ?></label>
						<?php endforeach; ?>
					</p>
				</div>
			<?php endforeach; ?>
		</div>

		<p>
			<button type="button" class="button" id="wcfr-add-rule"><?php esc_html_e( 'Add Rule', 'all-the-content-changes' ); ?></button>
			<?php if ( ! $has_wikimedia_preset ) : // phpcs:ignore -- $has_wikimedia_preset set in render_page() ?>
				<button type="button" class="button wcfr-add-wikimedia-preset" style="margin-left:8px;"><?php esc_html_e( 'Add Wikimedia Preset Rule', 'all-the-content-changes' ); ?></button>
			<?php else : ?>
				<span class="description" style="margin-left:12px;"><?php esc_html_e( 'Wikimedia Thumbnail Roundup rule is already in the list above.', 'all-the-content-changes' ); ?></span>
			<?php endif; ?>
		</p>

		<?php submit_button( __( 'Save Settings', 'all-the-content-changes' ) ); ?>
	</form>

	<hr />

	<h2><?php esc_html_e( 'Database Migration', 'all-the-content-changes' ); ?></h2>
	<p><?php esc_html_e( 'Preview changes before applying to the database. Rollback is available per run.', 'all-the-content-changes' ); ?></p>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="wcfr-migration-form">
		<input type="hidden" name="action" value="wcfr_migration_preview" />
		<?php wp_nonce_field( 'wcfr_migration_preview' ); ?>

		<h3><?php esc_html_e( 'Rules to Apply', 'all-the-content-changes' ); ?></h3>
		<?php if ( ! empty( $rules ) ) : ?>
			<p class="description"><?php esc_html_e( 'Uncheck any rules you want to exclude from this migration run.', 'all-the-content-changes' ); ?></p>
			<?php foreach ( $rules as $rule ) : ?>
				<?php $rule_id = (string) ( $rule['id'] ?? '' ); ?>
				<label style="display:block;margin-bottom:6px;">
					<input type="checkbox" name="wcfr_scope[rule_ids][]" value="<?php echo esc_attr( $rule_id ); ?>" checked="checked" />
					<?php echo esc_html( ( $rule['name'] ?? __( 'Unnamed rule', 'all-the-content-changes' ) ) . ' (' . ( $rule['strategy'] ?? 'find_replace' ) . ( empty( $rule['enabled'] ) ? ' — disabled' : '' ) . ')' ); ?>
				</label>
			<?php endforeach; ?>
		<?php else : ?>
			<p class="description"><?php esc_html_e( 'No rules configured yet. Add rules above first.', 'all-the-content-changes' ); ?></p>
		<?php endif; ?>

		<h3><?php esc_html_e( 'Scope', 'all-the-content-changes' ); ?></h3>
		<p>
			<label><?php esc_html_e( 'Limit posts scanned', 'all-the-content-changes' ); ?>
				<input type="number" min="1" max="500" name="wcfr_scope[limit]" value="100" />
			</label>
		</p>
		<p>
			<?php esc_html_e( 'Post types:', 'all-the-content-changes' ); ?><br />
			<?php foreach ( $post_types as $post_type_name => $post_type_obj ) : ?>
				<label style="margin-right:10px;"><input type="checkbox" name="wcfr_scope[post_types][]" value="<?php echo esc_attr( $post_type_name ); ?>" checked="checked" /> <?php echo esc_html( $post_type_obj->labels->singular_name ?? $post_type_name ); ?></label>
			<?php endforeach; ?>
		</p>
		<p>
			<?php esc_html_e( 'Post statuses:', 'all-the-content-changes' ); ?><br />
			<?php foreach ( $post_statuses as $status_name => $status_obj ) : ?>
				<label style="margin-right:10px;"><input type="checkbox" name="wcfr_scope[post_statuses][]" value="<?php echo esc_attr( (string) $status_name ); ?>" <?php checked( in_array( (string) $status_name, array( 'publish' ), true ) ); ?> /> <?php echo esc_html( $status_obj->label ?? (string) $status_name ); ?></label>
			<?php endforeach; ?>
		</p>
		<?php submit_button( __( 'Generate Preview', 'all-the-content-changes' ), 'secondary', 'submit', false ); ?>
	</form>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:8px;" id="wcfr-migration-apply-form">
		<input type="hidden" name="action" value="wcfr_migration_apply" />
		<?php wp_nonce_field( 'wcfr_migration_apply' ); ?>
		<p class="description"><?php esc_html_e( 'The Apply button will use the same rule selection and scope entered in the preview form above.', 'all-the-content-changes' ); ?></p>
		<div id="wcfr-apply-scope-mirror"></div>
		<?php submit_button( __( 'Apply Migration', 'all-the-content-changes' ), 'primary', 'submit', false, array( 'onclick' => 'return wcfrMirrorScopeAndConfirm(this);' ) ); ?>
	</form>

	<?php if ( is_array( $preview ) ) : ?>
		<h3><?php esc_html_e( 'Latest Preview Results', 'all-the-content-changes' ); ?></h3>
		<p class="description">
			<?php
			printf(
				/* translators: %s: ISO8601 date string. */
				esc_html__( 'Generated at %s', 'all-the-content-changes' ),
				esc_html( (string) ( $preview['generated_at'] ?? '' ) )
			);
			?>
		</p>
		<?php if ( ! empty( $preview['errors'] ) && is_array( $preview['errors'] ) ) : ?>
			<div class="notice notice-warning"><ul>
				<?php foreach ( $preview['errors'] as $preview_error ) : ?>
					<li><?php echo esc_html( (string) $preview_error ); ?></li>
				<?php endforeach; ?>
			</ul></div>
		<?php endif; ?>

		<?php
		$preview_scope_rule_ids      = isset( $preview['scope']['rule_ids'] ) && is_array( $preview['scope']['rule_ids'] ) ? $preview['scope']['rule_ids'] : null;
		$preview_scope_post_types    = isset( $preview['scope']['post_types'] ) && is_array( $preview['scope']['post_types'] ) ? $preview['scope']['post_types'] : array();
		$preview_scope_post_statuses = isset( $preview['scope']['post_statuses'] ) && is_array( $preview['scope']['post_statuses'] ) ? $preview['scope']['post_statuses'] : array();
		?>

		<?php if ( ! empty( $preview['items'] ) && is_array( $preview['items'] ) ) : ?>
		<p>
			<?php
			/* translators: %d: number of posts that will be affected. */
			printf( esc_html__( '%d post(s) will be affected.', 'all-the-content-changes' ), count( $preview['items'] ) );
			?>
		</p>
			<?php foreach ( $preview['items'] as $preview_item ) : ?>
				<?php
				$p_id       = (int) ( $preview_item['post_id'] ?? 0 );
				$p_title    = (string) ( $preview_item['post_title'] ?? '' );
				$p_type     = (string) ( $preview_item['post_type'] ?? '' );
				$p_changes  = isset( $preview_item['changes'] ) && is_array( $preview_item['changes'] ) ? $preview_item['changes'] : array();
				$p_view_url = get_permalink( $p_id );
				$p_edit_url = get_edit_post_link( $p_id );
				?>
				<div class="wcfr-preview-item">
					<div class="wcfr-preview-item-header">
						<span class="wcfr-preview-post-meta">
							<span class="wcfr-preview-post-id">#<?php echo esc_html( (string) $p_id ); ?></span>
							<?php if ( $p_view_url ) : ?>
								<a href="<?php echo esc_url( $p_view_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( '' !== $p_title ? $p_title : __( '(no title)', 'all-the-content-changes' ) ); ?></a>
							<?php else : ?>
									<?php echo esc_html( '' !== $p_title ? $p_title : __( '(no title)', 'all-the-content-changes' ) ); ?>
							<?php endif; ?>
							<?php if ( $p_edit_url ) : ?>
									<a href="<?php echo esc_url( $p_edit_url ); ?>" class="wcfr-edit-link"><?php esc_html_e( '(edit)', 'all-the-content-changes' ); ?></a>
							<?php endif; ?>
							<span class="wcfr-post-type-badge"><?php echo esc_html( $p_type ); ?></span>
						</span>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wcfr-apply-single-form">
							<input type="hidden" name="action" value="wcfr_migration_apply_single" />
							<input type="hidden" name="wcfr_post_id" value="<?php echo esc_attr( (string) $p_id ); ?>" />
							<?php if ( null !== $preview_scope_rule_ids ) : ?>
								<?php foreach ( $preview_scope_rule_ids as $rid ) : ?>
									<input type="hidden" name="wcfr_scope[rule_ids][]" value="<?php echo esc_attr( (string) $rid ); ?>" />
								<?php endforeach; ?>
							<?php endif; ?>
							<?php foreach ( $preview_scope_post_types as $pt ) : ?>
								<input type="hidden" name="wcfr_scope[post_types][]" value="<?php echo esc_attr( (string) $pt ); ?>" />
							<?php endforeach; ?>
							<?php foreach ( $preview_scope_post_statuses as $ps ) : ?>
								<input type="hidden" name="wcfr_scope[post_statuses][]" value="<?php echo esc_attr( (string) $ps ); ?>" />
							<?php endforeach; ?>
							<?php wp_nonce_field( 'wcfr_migration_apply_single' ); ?>
							<?php submit_button( __( 'Apply to this post', 'all-the-content-changes' ), 'small', 'submit', false, array( 'onclick' => "return confirm('Apply changes to this post now?');" ) ); ?>
						</form>
					</div>

					<?php foreach ( $p_changes as $change ) : ?>
						<div class="wcfr-change-group">
							<?php if ( 'wikimedia' === ( $change['type'] ?? '' ) ) : ?>
								<div class="wcfr-diff-line wcfr-diff-remove"><span class="wcfr-diff-marker">-</span><code><?php echo esc_html( (string) ( $change['from'] ?? '' ) ); ?></code></div>
								<div class="wcfr-diff-line wcfr-diff-add"><span class="wcfr-diff-marker">+</span><code><?php echo esc_html( (string) ( $change['to'] ?? '' ) ); ?></code></div>
							<?php elseif ( ! empty( $change['snippets'] ) && is_array( $change['snippets'] ) ) : ?>
								<?php foreach ( $change['snippets'] as $snippet ) : ?>
									<div class="wcfr-diff-context-block">
										<div class="wcfr-diff-line wcfr-diff-remove">
											<span class="wcfr-diff-marker">-</span>
											<?php
											if ( ! empty( $snippet['truncated_before'] ) ) :
												?>
												<span class="wcfr-ctx-ellipsis">&hellip;</span><?php endif; ?>
											<span class="wcfr-ctx"><?php echo esc_html( (string) ( $snippet['ctx_before'] ?? '' ) ); ?></span><span class="wcfr-diff-highlight"><?php echo esc_html( (string) ( $snippet['matched'] ?? '' ) ); ?></span><span class="wcfr-ctx"><?php echo esc_html( (string) ( $snippet['ctx_after'] ?? '' ) ); ?></span>
											<?php
											if ( ! empty( $snippet['truncated_after'] ) ) :
												?>
												<span class="wcfr-ctx-ellipsis">&hellip;</span><?php endif; ?>
										</div>
										<div class="wcfr-diff-line wcfr-diff-add">
											<span class="wcfr-diff-marker">+</span>
											<?php
											if ( ! empty( $snippet['truncated_before'] ) ) :
												?>
												<span class="wcfr-ctx-ellipsis">&hellip;</span><?php endif; ?>
											<span class="wcfr-ctx"><?php echo esc_html( (string) ( $snippet['ctx_before'] ?? '' ) ); ?></span><span class="wcfr-diff-highlight"><?php echo esc_html( (string) ( $snippet['replacement'] ?? '' ) ); ?></span><span class="wcfr-ctx"><?php echo esc_html( (string) ( $snippet['ctx_after'] ?? '' ) ); ?></span>
											<?php
											if ( ! empty( $snippet['truncated_after'] ) ) :
												?>
												<span class="wcfr-ctx-ellipsis">&hellip;</span><?php endif; ?>
										</div>
									</div>
								<?php endforeach; ?>
								<?php
								$shown     = count( $change['snippets'] );
								$total     = (int) ( $change['occurrences'] ?? $shown );
								$remaining = $total - $shown;
								if ( $remaining > 0 ) :
									?>
									<p class="wcfr-more-occurrences">
										<?php
										printf(
											/* translators: %d number of additional occurrences. */
											esc_html__( '&hellip; and %d more occurrence(s) in this post.', 'all-the-content-changes' ),
											(int) $remaining
										);
										?>
									</p>
								<?php endif; ?>
							<?php else : ?>
								<p class="wcfr-change-fallback">
									<code><?php echo esc_html( (string) ( $change['from'] ?? $change['pattern'] ?? '' ) ); ?></code>
									<span class="wcfr-diff-arrow">&rarr;</span>
									<code><?php echo esc_html( (string) ( $change['to'] ?? $change['replacement'] ?? '' ) ); ?></code>
									<span class="wcfr-occurrence-count">(<?php echo esc_html( (string) ( $change['occurrences'] ?? 1 ) ); ?> occurrence(s))</span>
								</p>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		<?php else : ?>
			<p><?php esc_html_e( 'No rewrite candidates found.', 'all-the-content-changes' ); ?></p>
		<?php endif; ?>
	<?php endif; ?>

	<h3><?php esc_html_e( 'Available Rollbacks', 'all-the-content-changes' ); ?></h3>
	<?php if ( ! empty( $runs ) ) : ?>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Run ID', 'all-the-content-changes' ); ?></th><th><?php esc_html_e( 'Created', 'all-the-content-changes' ); ?></th><th><?php esc_html_e( 'Updated IDs', 'all-the-content-changes' ); ?></th><th><?php esc_html_e( 'Action', 'all-the-content-changes' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $runs as $run_id => $run ) : ?>
					<tr>
						<td><code><?php echo esc_html( (string) $run_id ); ?></code></td>
						<td><?php echo esc_html( (string) ( $run['created_at'] ?? '' ) ); ?></td>
						<td><?php echo esc_html( (string) count( $run['updated_ids'] ?? array() ) ); ?></td>
						<td>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<input type="hidden" name="action" value="wcfr_migration_rollback" />
								<input type="hidden" name="run_id" value="<?php echo esc_attr( (string) $run_id ); ?>" />
								<?php wp_nonce_field( 'wcfr_migration_rollback' ); ?>
								<?php submit_button( __( 'Rollback', 'all-the-content-changes' ), 'delete', 'submit', false, array( 'onclick' => "return confirm('Rollback this migration run?');" ) ); ?>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p><?php esc_html_e( 'No rollback snapshots yet.', 'all-the-content-changes' ); ?></p>
	<?php endif; ?>
</div>
