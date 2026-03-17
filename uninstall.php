<?php
/**
 * Plugin uninstall cleanup.
 *
 * @package WordPressContentFindReplace
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wcfr_settings' );
delete_option( 'wcfr_migration_runs' );
