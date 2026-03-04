<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MFU_Activator {
	public static function activate() {
		MFU_DB::create_tables();

		$defaults = array(
			'ai_provider' => 'openai',
			'api_key' => '',
			'pplx_api_key' => '',
			'model_extract' => 'gpt-5-mini',
			'model_write' => 'gpt-5.1',
			'use_web_search' => 1,
			'cron_interval' => 86400,
			'batch_size' => 5,
			'timeout' => 15,
			'max_concurrency' => 1,
			'cost_currency' => 'EUR',
			'cost_input' => 0.0,
			'cost_output' => 0.0,
			'cost_extract_input' => 0.0,
			'cost_extract_output' => 0.0,
			'cost_write_input' => 0.0,
			'cost_write_output' => 0.0,
		);

		$current = get_option( MFU_OPTION_KEY );
		if ( ! is_array( $current ) ) {
			add_option( MFU_OPTION_KEY, $defaults );
		} else {
			update_option( MFU_OPTION_KEY, array_merge( $defaults, $current ) );
		}

		MFU_Cron::maybe_schedule();

		if ( class_exists( 'MFU_Festival_Taxonomy' ) ) {
			MFU_Festival_Taxonomy::register_taxonomy();
			MFU_Festival_Taxonomy::sync_all_terms();
			update_option( 'mfu_festival_terms_initial_sync_done', '1', false );
		}

		flush_rewrite_rules( false );
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( MFU_CRON_HOOK );
		flush_rewrite_rules( false );
	}
}
