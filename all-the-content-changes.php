<?php
/**
 * Plugin Name:       All the Content Changes
 * Plugin URI:        https://wordpress.org/plugins/all-the-content-changes/
 * Description:       Unlimited find/replace rules with a Wikimedia thumbnail rewrite preset, previewable migrations, and rollback support.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Addshore
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       all-the-content-changes
 * Domain Path:       /languages
 *
 * @package ContentFindReplace
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCFR_VERSION', '0.1.0' );
define( 'WCFR_PLUGIN_FILE', __FILE__ );
define( 'WCFR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCFR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WCFR_OPTION_SETTINGS', 'wcfr_settings' );
define( 'WCFR_OPTION_MIGRATIONS', 'wcfr_migration_runs' );

require_once WCFR_PLUGIN_DIR . 'includes/class-wcfr-options.php';
require_once WCFR_PLUGIN_DIR . 'includes/class-wcfr-wikimedia-preset.php';
require_once WCFR_PLUGIN_DIR . 'includes/class-wcfr-rule-engine.php';
require_once WCFR_PLUGIN_DIR . 'includes/class-wcfr-migrations.php';
require_once WCFR_PLUGIN_DIR . 'includes/class-wcfr-admin-tools-page.php';
require_once WCFR_PLUGIN_DIR . 'includes/class-wcfr-plugin.php';

/**
 * Activation callback.
 *
 * @return void
 */
function wcfr_activate(): void {
	$options = new WCFR_Options();
	if ( false === get_option( WCFR_OPTION_SETTINGS, false ) ) {
		update_option( WCFR_OPTION_SETTINGS, $options->get_defaults() );
	}

	if ( false === get_option( WCFR_OPTION_MIGRATIONS, false ) ) {
		update_option( WCFR_OPTION_MIGRATIONS, array() );
	}
}

register_activation_hook( WCFR_PLUGIN_FILE, 'wcfr_activate' );

WCFR_Plugin::get_instance();
