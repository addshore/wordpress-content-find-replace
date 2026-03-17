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
 * @package WordPressContentFindReplace
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$message = isset( $_GET['wcfr_msg'] ) ? sanitize_text_field( (string) wp_unslash( $_GET['wcfr_msg'] ) ) : '';
if ( '' !== $message ) :
	?>
	<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
	<?php
endif;
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Content Find Replace', 'wordpress-content-find-replace' ); ?></h1>

	<p><?php esc_html_e( 'Create unlimited rules, install the Wikimedia preset, and preview/apply safe migrations with rollback.', 'wordpress-content-find-replace' ); ?></p>

	<h2><?php esc_html_e( 'Global Settings', 'wordpress-content-find-replace' ); ?></h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="wcfr_save_settings" />
		<?php wp_nonce_field( 'wcfr_save_settings' ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Plugin Enabled', 'wordpress-content-find-replace' ); ?></th>
				<td><label><input type="checkbox" name="wcfr[enabled]" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?> /> <?php esc_html_e( 'Enable runtime replacement.', 'wordpress-content-find-replace' ); ?></label></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Filter the_content', 'wordpress-content-find-replace' ); ?></th>
				<td><label><input type="checkbox" name="wcfr[filter_content]" value="1" <?php checked( ! empty( $settings['filter_content'] ) ); ?> /> <?php esc_html_e( 'Apply rules to post content.', 'wordpress-content-find-replace' ); ?></label></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Filter the_excerpt', 'wordpress-content-find-replace' ); ?></th>
				<td><label><input type="checkbox" name="wcfr[filter_excerpt]" value="1" <?php checked( ! empty( $settings['filter_excerpt'] ) ); ?> /> <?php esc_html_e( 'Apply rules to excerpts.', 'wordpress-content-find-replace' ); ?></label></td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Rules', 'wordpress-content-find-replace' ); ?></h2>
		<p><?php esc_html_e( 'Rules run in the listed order. The Wikimedia preset appears as a strategy rule and can be toggled like others.', 'wordpress-content-find-replace' ); ?></p>

		<div id="wcfr-rules">
			<?php foreach ( $rules as $index => $rule ) : ?>
				<?php $row_index = (int) $index; ?>
				<div class="wcfr-rule-card">
					<input type="hidden" name="wcfr_rules[<?php echo esc_attr( (string) $row_index ); ?>][id]" value="<?php echo esc_attr( (string) ( $rule['id'] ?? '' ) ); ?>" />
					<p>
						<label><?php esc_html_e( 'Name', 'wordpress-content-find-replace' ); ?><br />
							<input type="text" class="regular-text" name="wcfr_rules[<?php echo esc_attr( (string) $row_index ); ?>][name]" value="<?php echo esc_attr( (string) ( $rule['name'] ?? '' ) ); ?>" />
						</label>
					</p>
					<p>
						<label><?php esc_html_e( 'Strategy', 'wordpress-content-find-replace' ); ?><br />
							<select name="wcfr_rules[<?php echo esc_attr( (string) $row_index ); ?>][strategy]">
								<option value="find_replace" <?php selected( ( $rule['strategy'] ?? 'find_replace' ), 'find_replace' ); ?>><?php esc_html_e( 'Find/Replace', 'wordpress-content-find-replace' ); ?></option>
								<option value="wikimedia_thumbnail_roundup" <?php selected( ( $rule['strategy'] ?? '' ), 'wikimedia_thumbnail_roundup' ); ?>><?php esc_html_e( 'Wikimedia Thumbnail Roundup', 'wordpress-content-find-replace' ); ?></option>
							</select>
						</label>
					</p>
					<p>
						<label><?php esc_html_e( 'Find', 'wordpress-content-find-replace' ); ?><br />
							<textarea rows="3" class="large-text" name="wcfr_rules[<?php echo esc_attr( (string) $row_index ); ?>][find]"><?php echo esc_textarea( (string) ( $rule['find'] ?? '' ) ); ?></textarea>
						</label>
					</p>
					<p>
						<label><?php esc_html_e( 'Replace', 'wordpress-content-find-replace' ); ?><br />
							<textarea rows="3" class="large-text" name="wcfr_rules[<?php echo esc_attr( (string) $row_index ); ?>][replace]"><?php echo esc_textarea( (string) ( $rule['replace'] ?? '' ) ); ?></textarea>
						</label>
					</p>
					<p>
						<label><input type="checkbox" name="wcfr_rules[<?php echo esc_attr( (string) $row_index ); ?>][enabled]" value="1" <?php checked( ! empty( $rule['enabled'] ) ); ?> /> <?php esc_html_e( 'Enabled', 'wordpress-content-find-replace' ); ?></label>
						<label><input type="checkbox" name="wcfr_rules[<?php echo esc_attr( (string) $row_index ); ?>][use_regex]" value="1" <?php checked( ! empty( $rule['use_regex'] ) ); ?> /> <?php esc_html_e( 'Use regex', 'wordpress-content-find-replace' ); ?></label>
						<label><input type="checkbox" name="wcfr_rules[<?php echo esc_attr( (string) $row_index ); ?>][ignore_case]" value="1" <?php checked( ! empty( $rule['ignore_case'] ) ); ?> /> <?php esc_html_e( 'Ignore case', 'wordpress-content-find-replace' ); ?></label>
						<label><input type="checkbox" name="wcfr_rules[<?php echo esc_attr( (string) $row_index ); ?>][apply_in_admin]" value="1" <?php checked( ! empty( $rule['apply_in_admin'] ) ); ?> /> <?php esc_html_e( 'Apply in admin', 'wordpress-content-find-replace' ); ?></label>
					</p>
					<p>
						<?php esc_html_e( 'Post Types (leave empty for all):', 'wordpress-content-find-replace' ); ?><br />
						<?php foreach ( $post_types as $post_type_name => $post_type_obj ) : ?>
							<?php $selected_types = ( isset( $rule['post_types'] ) && is_array( $rule['post_types'] ) ) ? $rule['post_types'] : array(); ?>
							<label style="margin-right:10px;"><input type="checkbox" name="wcfr_rules[<?php echo esc_attr( (string) $row_index ); ?>][post_types][]" value="<?php echo esc_attr( $post_type_name ); ?>" <?php checked( in_array( $post_type_name, $selected_types, true ) ); ?> /> <?php echo esc_html( $post_type_obj->labels->singular_name ?? $post_type_name ); ?></label>
						<?php endforeach; ?>
					</p>
				</div>
			<?php endforeach; ?>
		</div>

		<p><button type="button" class="button" id="wcfr-add-rule"><?php esc_html_e( 'Add Rule', 'wordpress-content-find-replace' ); ?></button></p>

		<?php submit_button( __( 'Save Settings', 'wordpress-content-find-replace' ) ); ?>
	</form>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:24px;">
		<input type="hidden" name="action" value="wcfr_install_wikimedia_preset" />
		<?php wp_nonce_field( 'wcfr_install_wikimedia_preset' ); ?>
		<?php submit_button( __( 'Install Wikimedia Preset Rule', 'wordpress-content-find-replace' ), 'secondary', 'submit', false ); ?>
	</form>

	<hr />

	<h2><?php esc_html_e( 'Database Migration', 'wordpress-content-find-replace' ); ?></h2>
	<p><?php esc_html_e( 'Preview changes before applying. Rollback is available per run.', 'wordpress-content-find-replace' ); ?></p>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="wcfr_migration_preview" />
		<?php wp_nonce_field( 'wcfr_migration_preview' ); ?>
		<p>
			<label><?php esc_html_e( 'Limit posts scanned', 'wordpress-content-find-replace' ); ?>
				<input type="number" min="1" max="500" name="wcfr_scope[limit]" value="100" />
			</label>
		</p>
		<p>
			<?php esc_html_e( 'Post types:', 'wordpress-content-find-replace' ); ?><br />
			<?php foreach ( $post_types as $post_type_name => $post_type_obj ) : ?>
				<label style="margin-right:10px;"><input type="checkbox" name="wcfr_scope[post_types][]" value="<?php echo esc_attr( $post_type_name ); ?>" checked="checked" /> <?php echo esc_html( $post_type_obj->labels->singular_name ?? $post_type_name ); ?></label>
			<?php endforeach; ?>
		</p>
		<p>
			<?php esc_html_e( 'Post statuses:', 'wordpress-content-find-replace' ); ?><br />
			<?php foreach ( $post_statuses as $status_name => $status_obj ) : ?>
				<label style="margin-right:10px;"><input type="checkbox" name="wcfr_scope[post_statuses][]" value="<?php echo esc_attr( (string) $status_name ); ?>" <?php checked( in_array( (string) $status_name, array( 'publish' ), true ) ); ?> /> <?php echo esc_html( $status_obj->label ?? (string) $status_name ); ?></label>
			<?php endforeach; ?>
		</p>
		<?php submit_button( __( 'Generate Preview', 'wordpress-content-find-replace' ), 'secondary', 'submit', false ); ?>
	</form>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:8px;">
		<input type="hidden" name="action" value="wcfr_migration_apply" />
		<?php wp_nonce_field( 'wcfr_migration_apply' ); ?>
		<input type="hidden" name="wcfr_scope[limit]" value="100" />
		<?php foreach ( $post_types as $post_type_name => $post_type_obj ) : ?>
			<input type="hidden" name="wcfr_scope[post_types][]" value="<?php echo esc_attr( $post_type_name ); ?>" />
		<?php endforeach; ?>
		<input type="hidden" name="wcfr_scope[post_statuses][]" value="publish" />
		<?php submit_button( __( 'Apply Migration', 'wordpress-content-find-replace' ), 'primary', 'submit', false, array( 'onclick' => "return confirm('Apply migration now? This writes to post_content.');" ) ); ?>
	</form>

	<?php if ( is_array( $preview ) ) : ?>
		<h3><?php esc_html_e( 'Latest Preview Results', 'wordpress-content-find-replace' ); ?></h3>
		<p><?php printf( esc_html__( 'Generated at %s', 'wordpress-content-find-replace' ), esc_html( (string) ( $preview['generated_at'] ?? '' ) ) ); ?></p>
		<?php if ( ! empty( $preview['errors'] ) && is_array( $preview['errors'] ) ) : ?>
			<div class="notice notice-warning"><ul>
				<?php foreach ( $preview['errors'] as $error ) : ?>
					<li><?php echo esc_html( (string) $error ); ?></li>
				<?php endforeach; ?>
			</ul></div>
		<?php endif; ?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Post ID', 'wordpress-content-find-replace' ); ?></th>
					<th><?php esc_html_e( 'Title', 'wordpress-content-find-replace' ); ?></th>
					<th><?php esc_html_e( 'Before', 'wordpress-content-find-replace' ); ?></th>
					<th><?php esc_html_e( 'After', 'wordpress-content-find-replace' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $preview['items'] ) && is_array( $preview['items'] ) ) : ?>
					<?php foreach ( $preview['items'] as $item ) : ?>
						<tr>
							<td><?php echo esc_html( (string) ( $item['post_id'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $item['post_title'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $item['before_snippet'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $item['after_snippet'] ?? '' ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No rewrite candidates found.', 'wordpress-content-find-replace' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<h3><?php esc_html_e( 'Available Rollbacks', 'wordpress-content-find-replace' ); ?></h3>
	<?php if ( ! empty( $runs ) ) : ?>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Run ID', 'wordpress-content-find-replace' ); ?></th><th><?php esc_html_e( 'Created', 'wordpress-content-find-replace' ); ?></th><th><?php esc_html_e( 'Updated IDs', 'wordpress-content-find-replace' ); ?></th><th><?php esc_html_e( 'Action', 'wordpress-content-find-replace' ); ?></th></tr></thead>
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
								<?php submit_button( __( 'Rollback', 'wordpress-content-find-replace' ), 'delete', 'submit', false, array( 'onclick' => "return confirm('Rollback this migration run?');" ) ); ?>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p><?php esc_html_e( 'No rollback snapshots yet.', 'wordpress-content-find-replace' ); ?></p>
	<?php endif; ?>
</div>
