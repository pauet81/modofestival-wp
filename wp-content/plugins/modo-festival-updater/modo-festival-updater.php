<?php
/**
 * Plugin Name: Modo Festival Updater
 * Description: Editorial updates for festivals (CPT festi) with verified sources and draft news generation.
 * Version: 0.1.0
 * Author: Modofestival
 * Text Domain: modo-festival-updater
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MFU_VERSION', '0.1.0' );
define( 'MFU_PLUGIN_DIR', __DIR__ );
define( 'MFU_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

define( 'MFU_OPTION_KEY', 'mfu_settings' );

define( 'MFU_CRON_HOOK', 'mfu_process_queue' );

define( 'MFU_TABLE_PREFIX', 'mf_' );

/**
 * Local-only guard for editorial experiments in this workspace.
 */
function mfu_is_local_workspace() {
	if ( function_exists( 'wp_get_environment_type' ) && wp_get_environment_type() === 'local' ) {
		return true;
	}

	$home_host = wp_parse_url( home_url(), PHP_URL_HOST );
	if ( is_string( $home_host ) ) {
		$home_host = strtolower( $home_host );
		if (
			strpos( $home_host, 'localhost' ) !== false ||
			substr( $home_host, -6 ) === '.local' ||
			substr( $home_host, -5 ) === '.test'
		) {
			return true;
		}
	}

	$abs_path = defined( 'ABSPATH' ) ? (string) ABSPATH : '';
	return strpos( $abs_path, '/home/pauca/proyectos/' ) !== false;
}

require_once MFU_PLUGIN_DIR . '/includes/class-mf-db.php';
require_once MFU_PLUGIN_DIR . '/includes/class-mf-activator.php';
require_once MFU_PLUGIN_DIR . '/includes/class-mf-cron.php';
require_once MFU_PLUGIN_DIR . '/includes/class-mf-ai.php';
require_once MFU_PLUGIN_DIR . '/includes/class-mf-processor.php';
require_once MFU_PLUGIN_DIR . '/includes/class-mf-festival-taxonomy.php';
require_once MFU_PLUGIN_DIR . '/includes/class-mf-news.php';
require_once MFU_PLUGIN_DIR . '/includes/class-mf-admin.php';
require_once MFU_PLUGIN_DIR . '/includes/class-mf-list-table.php';

register_activation_hook( __FILE__, array( 'MFU_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'MFU_Activator', 'deactivate' ) );

add_action( 'init', array( 'MFU_Cron', 'register_schedules' ) );
add_action( 'init', array( 'MFU_Cron', 'maybe_schedule' ) );
add_action( MFU_CRON_HOOK, array( 'MFU_Cron', 'process_queue' ) );
MFU_Festival_Taxonomy::bootstrap();

// Local-only: disable post tags to avoid reintroducing low-quality tag archives.
add_action(
	'init',
	function() {
		if ( ! mfu_is_local_workspace() ) {
			return;
		}

		if ( taxonomy_exists( 'post_tag' ) ) {
			unregister_taxonomy_for_object_type( 'post_tag', 'post' );
		}
	},
	99
);

add_filter(
	'pre_insert_term',
	function( $term, $taxonomy ) {
		if ( ! mfu_is_local_workspace() ) {
			return $term;
		}
		if ( $taxonomy === 'post_tag' ) {
			return new WP_Error( 'mfu_tags_disabled_local', __( 'Tags are disabled in local workspace.', 'modo-festival-updater' ) );
		}
		return $term;
	},
	10,
	2
);

add_action(
	'init',
	function() {
		if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
			wp_clear_scheduled_hook( 'mfu_fill_sources_cron' );
		}

		$settings = get_option( MFU_OPTION_KEY, array() );
		if ( is_array( $settings ) ) {
			$changed = false;
			foreach ( array( 'sources_cron_enabled', 'sources_cron_batch' ) as $key ) {
				if ( array_key_exists( $key, $settings ) ) {
					unset( $settings[ $key ] );
					$changed = true;
				}
			}
			if ( $changed ) {
				update_option( MFU_OPTION_KEY, $settings, false );
			}
		}

		foreach ( array(
			'mfu_sources_cron_last_run',
			'mfu_sources_cron_total',
			'mfu_sources_cron_last_counts',
			'mfu_sources_cron_offset',
			'mfu_sources_cron_last_offset',
		) as $option ) {
			delete_option( $option );
		}
	}
);

if ( is_admin() ) {
	new MFU_Admin();
}
