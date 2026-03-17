<?php
/**
 * Plugin uninstall cleanup.
 *
 * @package ContentFindReplace
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wcfr_settings' );
delete_option( 'wcfr_migration_runs' );
