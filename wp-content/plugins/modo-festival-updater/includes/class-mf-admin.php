<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MFU_Admin {
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'acf/init', array( $this, 'register_acf_fields' ) );
		add_action( 'admin_head', array( $this, 'output_admin_styles' ) );

		add_action( 'admin_post_mfu_enqueue_single', array( $this, 'handle_enqueue_single' ) );
		add_action( 'admin_post_mfu_dequeue_single', array( $this, 'handle_dequeue_single' ) );
		add_action( 'admin_post_mfu_clear_queue_single', array( $this, 'handle_clear_queue_single' ) );
		add_action( 'admin_post_mfu_clear_all_single', array( $this, 'handle_clear_all_single' ) );
		add_action( 'admin_post_mfu_bulk_enqueue', array( $this, 'handle_bulk_enqueue' ) );
		add_action( 'admin_post_mfu_enqueue_all', array( $this, 'handle_enqueue_all' ) );
		add_action( 'admin_post_mfu_process_now', array( $this, 'handle_process_now' ) );
		add_action( 'admin_post_mfu_clear_queue', array( $this, 'handle_clear_queue' ) );
		add_action( 'admin_post_mfu_clear_by_status', array( $this, 'handle_clear_by_status' ) );
		add_action( 'admin_post_mfu_bulk_apply', array( $this, 'handle_bulk_apply' ) );
		add_action( 'admin_post_mfu_bulk_reject', array( $this, 'handle_bulk_reject' ) );
		add_action( 'admin_post_mfu_clear_error_log', array( $this, 'handle_clear_error_log' ) );
		add_action( 'admin_post_mfu_apply_content_preview', array( $this, 'handle_apply_content_preview' ) );
		add_action( 'admin_post_mfu_save_source', array( $this, 'handle_save_source' ) );
		add_action( 'admin_post_mfu_download_test_result', array( $this, 'handle_download_test_result' ) );
		add_action( 'admin_post_mfu_fill_sources', array( $this, 'handle_fill_sources' ) );
		add_action( 'admin_post_mfu_download_missing_instagram', array( $this, 'handle_download_missing_instagram' ) );
		add_action( 'admin_post_mfu_save_sources_fields', array( $this, 'handle_save_sources_fields' ) );
		add_action( 'admin_post_mfu_debug_download', array( $this, 'handle_debug_download' ) );
		add_action( 'admin_post_mfu_debug_clear', array( $this, 'handle_debug_clear' ) );
		add_action( 'admin_post_mfu_apply_content_suggestion', array( $this, 'handle_apply_content_suggestion' ) );
		add_action( 'admin_post_mfu_reject_content_suggestion', array( $this, 'handle_reject_content_suggestion' ) );
			add_action( 'admin_post_mfu_save_updated_content', array( $this, 'handle_save_updated_content' ) );
			add_action( 'wp_ajax_mfu_queue_status', array( $this, 'handle_queue_status_ajax' ) );
			add_action( 'wp_ajax_mfu_verify_update', array( $this, 'handle_verify_update_ajax' ) );
			add_action( 'wp_ajax_mfu_verify_content_perplexity', array( $this, 'handle_verify_content_perplexity_ajax' ) );
			add_action( 'wp_ajax_mfu_rewrite_updated_content_seo', array( $this, 'handle_rewrite_updated_content_seo_ajax' ) );
			add_action( 'wp_ajax_mfu_ai_prefill_festival', array( $this, 'handle_ai_prefill_festival_ajax' ) );
			add_action( 'admin_post_mfu_clear_error_log', array( $this, 'handle_clear_error_log' ) );
		add_action( 'admin_post_mfu_update_edicion', array( $this, 'handle_update_edicion' ) );
		add_action( 'admin_post_mfu_apply_update', array( $this, 'handle_apply_update' ) );
		add_action( 'admin_post_mfu_reject_update', array( $this, 'handle_reject_update' ) );
		add_action( 'admin_post_mfu_news_update', array( $this, 'handle_news_update' ) );
		add_action( 'admin_post_mfu_news_check', array( $this, 'handle_news_check' ) );
		add_action( 'admin_post_mfu_news_extract', array( $this, 'handle_news_extract' ) );
		add_action( 'admin_post_mfu_news_dismiss', array( $this, 'handle_news_dismiss' ) );
				add_action( 'admin_post_mfu_news_refresh', array( $this, 'handle_news_refresh' ) );
				add_action( 'wp_ajax_mfu_festival_search', array( $this, 'ajax_festival_search' ) );
				add_action( 'admin_post_mfu_add_festival', array( $this, 'handle_add_festival' ) );
				add_action( 'admin_post_mfu_press_release_process', array( $this, 'handle_press_release_process' ) );
				add_action( 'admin_post_mfu_external_news_check', array( $this, 'handle_external_news_check' ) );
				add_action( 'admin_post_mfu_external_news_process', array( $this, 'handle_external_news_process' ) );
				add_action( 'admin_post_mfu_rollover_prepare', array( $this, 'handle_rollover_prepare' ) );
		}

	public function output_admin_styles() {
		if ( empty( $_GET['page'] ) || 'mfu-updates' !== $_GET['page'] ) {
			return;
		}

		echo '<style>
.mfu-content-diff table.diff { width:100%; border-collapse:collapse; }
.mfu-content-diff .diff td { padding:6px 8px; vertical-align:top; }
.mfu-content-diff .diff-deletedline { background:#fdecea; }
.mfu-content-diff .diff-addedline { background:#e7f7ed; }
.mfu-content-diff .diff-context { background:#f6f7f7; }
.mfu-content-diff ins { background:#b7ebc6; text-decoration:none; }
.mfu-content-diff del { background:#f7b7b7; text-decoration:line-through; }
</style>';
	}

	public function register_menu() {
		add_menu_page(
			'Festival Updates',
			'Festival Updates',
			'manage_options',
			'mfu-updates',
			array( $this, 'render_updates_page' ),
			'dashicons-update',
			26
		);

		add_submenu_page(
			'mfu-updates',
			'Anadir festival',
			'Anadir festival',
			'manage_options',
			'mfu-add-festival',
			array( $this, 'render_add_festival_page' )
		);
			add_submenu_page(
				'mfu-updates',
				'Actualizacion via Noticias',
				'Actualizacion via Noticias',
				'manage_options',
				'mfu-news-update',
				array( $this, 'render_news_update_page' )
			);
			add_submenu_page(
				'mfu-updates',
				'Notas de prensa',
				'Notas de prensa',
				'manage_options',
				'mfu-press-releases',
				array( $this, 'render_press_releases_page' )
			);
			add_submenu_page(
				'mfu-updates',
				'Noticias (URL)',
				'Noticias (URL)',
				'manage_options',
				'mfu-external-news',
				array( $this, 'render_external_news_page' )
			);
			add_submenu_page(
				'mfu-updates',
				'Rollover edicion',
				'Rollover edicion',
				'manage_options',
				'mfu-rollover',
				array( $this, 'render_rollover_page' )
			);
			add_submenu_page(
				'mfu-updates',
				'Fuentes',
				'Fuentes',
			'manage_options',
			'mfu-sources',
			array( $this, 'render_sources_page' )
		);
		add_submenu_page(
			'mfu-updates',
			'Ajustes',
			'Ajustes',
			'manage_options',
			'mfu-settings',
			array( $this, 'render_settings_page' )
		);
		add_submenu_page(
			'mfu-updates',
			'Consumo APIs',
			'Consumo APIs',
			'manage_options',
			'mfu-usage',
			array( $this, 'render_usage_page' )
		);
		add_submenu_page(
			'mfu-updates',
			'Errores',
			'Errores',
			'manage_options',
			'mfu-errors',
			array( $this, 'render_errors_page' )
		);
		add_submenu_page(
			'mfu-updates',
			'Debug',
			'Debug',
			'manage_options',
			'mfu-debug',
			array( $this, 'render_debug_page' )
		);
		add_submenu_page(
			'mfu-updates',
			'Testeo de fuentes',
			'Testeo de fuentes',
			'manage_options',
			'mfu-test',
			array( $this, 'render_test_page' )
		);
	}

	public function register_settings() {
		register_setting( 'mfu_settings_group', MFU_OPTION_KEY, array( $this, 'sanitize_settings' ) );
		$this->cleanup_legacy_options();

		add_settings_section( 'mfu_ai', 'IA', '__return_false', 'mfu-settings' );
		add_settings_field( 'api_key', 'OpenAI API Key', array( $this, 'field_api_key' ), 'mfu-settings', 'mfu_ai' );
		add_settings_field( 'pplx_api_key', 'Perplexity API Key', array( $this, 'field_pplx_api_key' ), 'mfu-settings', 'mfu_ai' );
		add_settings_field( 'base_query_provider', 'Proveedor (query base)', array( $this, 'field_base_query_provider' ), 'mfu-settings', 'mfu_ai' );
		add_settings_field( 'verification_provider', 'Proveedor (verificacion)', array( $this, 'field_verification_provider' ), 'mfu-settings', 'mfu_ai' );
		add_settings_field( 'model_extract', 'Modelo IA (extraer)', array( $this, 'field_model_extract' ), 'mfu-settings', 'mfu_ai' );
		add_settings_field( 'model_write', 'Modelo IA (redactar)', array( $this, 'field_model_write' ), 'mfu-settings', 'mfu_ai' );
		add_settings_field( 'debug_enabled', 'Modo debug detallado', array( $this, 'field_debug_enabled' ), 'mfu-settings', 'mfu_ai' );
		add_settings_field( 'ai_content_update', 'Actualizar contenido con IA', array( $this, 'field_ai_content_update' ), 'mfu-settings', 'mfu_ai' );
		add_settings_field( 'auto_apply_when_verified', 'Aplicar automaticamente si verificacion OK', array( $this, 'field_auto_apply_when_verified' ), 'mfu-settings', 'mfu_ai' );
		add_settings_field( 'news_enabled', 'Noticias automaticas', array( $this, 'field_news_enabled' ), 'mfu-settings', 'mfu_ai' );
		add_settings_field( 'news_rss_sources', 'Fuentes RSS de noticias', array( $this, 'field_news_rss_sources' ), 'mfu-settings', 'mfu_ai' );
		add_settings_field( 'update_source_urls', 'Actualizar URLs oficiales', array( $this, 'field_update_source_urls' ), 'mfu-settings', 'mfu_ai' );

		add_settings_section( 'mfu_costs', 'Costes APIs', '__return_false', 'mfu-settings' );
		add_settings_field( 'cost_currency', 'Moneda (ej. EUR, USD)', array( $this, 'field_cost_currency' ), 'mfu-settings', 'mfu_costs' );
		add_settings_field( 'cost_input', 'Coste input (1M tokens)', array( $this, 'field_cost_input' ), 'mfu-settings', 'mfu_costs' );
		add_settings_field( 'cost_output', 'Coste output (1M tokens)', array( $this, 'field_cost_output' ), 'mfu-settings', 'mfu_costs' );
		add_settings_field( 'cost_extract_input', 'Coste extraccion input (1M tokens)', array( $this, 'field_cost_extract_input' ), 'mfu-settings', 'mfu_costs' );
		add_settings_field( 'cost_extract_output', 'Coste extraccion output (1M tokens)', array( $this, 'field_cost_extract_output' ), 'mfu-settings', 'mfu_costs' );
		add_settings_field( 'cost_write_input', 'Coste redaccion input (1M tokens)', array( $this, 'field_cost_write_input' ), 'mfu-settings', 'mfu_costs' );
		add_settings_field( 'cost_write_output', 'Coste redaccion output (1M tokens)', array( $this, 'field_cost_write_output' ), 'mfu-settings', 'mfu_costs' );
		add_settings_field( 'pplx_cost_input', 'Perplexity coste input (1M tokens)', array( $this, 'field_pplx_cost_input' ), 'mfu-settings', 'mfu_costs' );
		add_settings_field( 'pplx_cost_output', 'Perplexity coste output (1M tokens)', array( $this, 'field_pplx_cost_output' ), 'mfu-settings', 'mfu_costs' );
		add_settings_field( 'pplx_cost_extract_input', 'Perplexity coste extraccion input (1M tokens)', array( $this, 'field_pplx_cost_extract_input' ), 'mfu-settings', 'mfu_costs' );
		add_settings_field( 'pplx_cost_extract_output', 'Perplexity coste extraccion output (1M tokens)', array( $this, 'field_pplx_cost_extract_output' ), 'mfu-settings', 'mfu_costs' );
		add_settings_field( 'pplx_cost_write_input', 'Perplexity coste redaccion input (1M tokens)', array( $this, 'field_pplx_cost_write_input' ), 'mfu-settings', 'mfu_costs' );
		add_settings_field( 'pplx_cost_write_output', 'Perplexity coste redaccion output (1M tokens)', array( $this, 'field_pplx_cost_write_output' ), 'mfu-settings', 'mfu_costs' );

		add_settings_section( 'mfu_cron', 'Cron y limites', '__return_false', 'mfu-settings' );
		add_settings_field( 'cron_interval', 'Intervalo cron (segundos)', array( $this, 'field_cron_interval' ), 'mfu-settings', 'mfu_cron' );
		add_settings_field( 'batch_size', 'Festivales por lote', array( $this, 'field_batch_size' ), 'mfu-settings', 'mfu_cron' );
		add_settings_field( 'timeout', 'Timeout HTTP (segundos)', array( $this, 'field_timeout' ), 'mfu-settings', 'mfu_cron' );
	}

	public function sanitize_settings( $settings ) {
		$clean = array();
		$clean['api_key'] = isset( $settings['api_key'] ) ? sanitize_text_field( $settings['api_key'] ) : '';
		$clean['pplx_api_key'] = isset( $settings['pplx_api_key'] ) ? sanitize_text_field( $settings['pplx_api_key'] ) : '';
		$clean['base_query_provider'] = isset( $settings['base_query_provider'] ) && in_array( $settings['base_query_provider'], array( 'openai', 'perplexity' ), true ) ? $settings['base_query_provider'] : 'perplexity';
		$clean['verification_provider'] = isset( $settings['verification_provider'] ) && in_array( $settings['verification_provider'], array( 'openai', 'perplexity' ), true ) ? $settings['verification_provider'] : 'perplexity';
		$clean['model_extract'] = isset( $settings['model_extract'] ) ? sanitize_text_field( $settings['model_extract'] ) : 'gpt-5-mini';
		$clean['model_write'] = isset( $settings['model_write'] ) ? sanitize_text_field( $settings['model_write'] ) : 'gpt-5-mini';
		$clean['debug_enabled'] = ! empty( $settings['debug_enabled'] ) ? 1 : 0;
		$clean['ai_content_update'] = ! empty( $settings['ai_content_update'] ) ? 1 : 0;
		$clean['auto_apply_when_verified'] = ! empty( $settings['auto_apply_when_verified'] ) ? 1 : 0;
		$clean['news_enabled'] = ! empty( $settings['news_enabled'] ) ? 1 : 0;
		$clean['news_rss_sources'] = $this->sanitize_news_rss_sources( $settings['news_rss_sources'] ?? array() );
		$clean['update_source_urls'] = ! empty( $settings['update_source_urls'] ) ? 1 : 0;
		$clean['cost_currency'] = isset( $settings['cost_currency'] ) ? sanitize_text_field( $settings['cost_currency'] ) : 'EUR';
		$clean['cost_input'] = isset( $settings['cost_input'] ) ? (float) $settings['cost_input'] : 0.0;
		$clean['cost_output'] = isset( $settings['cost_output'] ) ? (float) $settings['cost_output'] : 0.0;
		$clean['cost_extract_input'] = isset( $settings['cost_extract_input'] ) ? (float) $settings['cost_extract_input'] : 0.0;
		$clean['cost_extract_output'] = isset( $settings['cost_extract_output'] ) ? (float) $settings['cost_extract_output'] : 0.0;
		$clean['cost_write_input'] = isset( $settings['cost_write_input'] ) ? (float) $settings['cost_write_input'] : 0.0;
		$clean['cost_write_output'] = isset( $settings['cost_write_output'] ) ? (float) $settings['cost_write_output'] : 0.0;
		$clean['pplx_cost_input'] = isset( $settings['pplx_cost_input'] ) ? (float) $settings['pplx_cost_input'] : 0.0;
		$clean['pplx_cost_output'] = isset( $settings['pplx_cost_output'] ) ? (float) $settings['pplx_cost_output'] : 0.0;
		$clean['pplx_cost_extract_input'] = isset( $settings['pplx_cost_extract_input'] ) ? (float) $settings['pplx_cost_extract_input'] : 0.0;
		$clean['pplx_cost_extract_output'] = isset( $settings['pplx_cost_extract_output'] ) ? (float) $settings['pplx_cost_extract_output'] : 0.0;
		$clean['pplx_cost_write_input'] = isset( $settings['pplx_cost_write_input'] ) ? (float) $settings['pplx_cost_write_input'] : 0.0;
		$clean['pplx_cost_write_output'] = isset( $settings['pplx_cost_write_output'] ) ? (float) $settings['pplx_cost_write_output'] : 0.0;
		$clean['cron_interval'] = isset( $settings['cron_interval'] ) ? max( 900, (int) $settings['cron_interval'] ) : 86400;
		$clean['batch_size'] = isset( $settings['batch_size'] ) ? max( 1, (int) $settings['batch_size'] ) : 5;
		$clean['timeout'] = isset( $settings['timeout'] ) ? max( 5, (int) $settings['timeout'] ) : 15;
		return $clean;
	}

	private function get_settings_value( $key, $default = '' ) {
		$options = get_option( MFU_OPTION_KEY, array() );
		return isset( $options[ $key ] ) ? $options[ $key ] : $default;
	}

	private function cleanup_legacy_options() {
		$options = get_option( MFU_OPTION_KEY, array() );
		if ( ! is_array( $options ) ) {
			return;
		}
		if ( array_key_exists( 'gemini_api_key', $options ) ) {
			unset( $options['gemini_api_key'] );
		}
		if ( empty( $options['base_query_provider'] ) ) {
			$options['base_query_provider'] = 'perplexity';
		}
		if ( empty( $options['verification_provider'] ) ) {
			$options['verification_provider'] = 'perplexity';
		}
		update_option( MFU_OPTION_KEY, $options );
	}

	private function format_date_value( $value ) {
		if ( $value === null ) {
			return '';
		}
		if ( is_int( $value ) ) {
			$value = (string) $value;
		}
		if ( ! is_string( $value ) ) {
			return $value;
		}
		$trimmed = trim( $value );
		if ( $trimmed === '' ) {
			return $trimmed;
		}
		if ( preg_match( '/^\d{2}\/\d{2}\/\d{4}$/', $trimmed ) ) {
			return $trimmed;
		}
		if ( preg_match( '/^\d{8}$/', $trimmed ) ) {
			$year = substr( $trimmed, 0, 4 );
			$month = substr( $trimmed, 4, 2 );
			$day = substr( $trimmed, 6, 2 );
			return $day . '/' . $month . '/' . $year;
		}
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $trimmed ) ) {
			list( $year, $month, $day ) = explode( '-', $trimmed );
			return $day . '/' . $month . '/' . $year;
		}
		return $value;
	}

	private function format_diff_value( $key, $value ) {
		if ( ! is_scalar( $value ) ) {
			return $value;
		}
		if ( in_array( $key, array( 'fecha_inicio', 'fecha_fin' ), true ) ) {
			return $this->format_date_value( $value );
		}
		return $value;
	}

		private function normalize_date_for_storage( $value ) {
			if ( $value === null ) {
				return '';
			}
			if ( is_int( $value ) ) {
				$value = (string) $value;
			}
			if ( ! is_string( $value ) ) {
				return $value;
			}
			$trimmed = trim( $value );
			if ( $trimmed === '' ) {
				return '';
			}
			$spanish = $this->parse_spanish_date( $trimmed );
			if ( $spanish !== '' ) {
				return $spanish;
			}
			if ( preg_match( '/^\d{8}$/', $trimmed ) ) {
				return $trimmed;
			}
			if ( preg_match( '/^\d{2}\/\d{2}\/\d{4}$/', $trimmed ) ) {
				$dt = DateTime::createFromFormat( 'd/m/Y', $trimmed );
				return $dt ? $dt->format( 'Ymd' ) : $trimmed;
			}
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $trimmed ) ) {
				$dt = DateTime::createFromFormat( 'Y-m-d', $trimmed );
				return $dt ? $dt->format( 'Ymd' ) : $trimmed;
			}
			return $trimmed;
		}

		private function normalize_date_to_input( $value ) {
			$value = trim( (string) $value );
			if ( $value === '' ) {
				return '';
			}
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
				return $value;
			}
			$storage = $this->normalize_date_for_storage( $value );
			if ( is_string( $storage ) && preg_match( '/^\d{8}$/', $storage ) ) {
				$year = substr( $storage, 0, 4 );
				$month = substr( $storage, 4, 2 );
				$day = substr( $storage, 6, 2 );
				return $year . '-' . $month . '-' . $day;
			}
			return '';
		}

		private function date_string_to_timestamp( $value ) {
			$normalized = $this->normalize_date_for_storage( $value );
			if ( ! is_string( $normalized ) || ! preg_match( '/^\d{8}$/', $normalized ) ) {
				return 0;
			}
			$year = (int) substr( $normalized, 0, 4 );
			$month = (int) substr( $normalized, 4, 2 );
			$day = (int) substr( $normalized, 6, 2 );
			if ( $year <= 0 || $month <= 0 || $day <= 0 ) {
				return 0;
			}
			return (int) mktime( 12, 0, 0, $month, $day, $year );
		}

		private function get_rollover_candidates( $from_year ) {
			$from_year = (int) $from_year;
			if ( $from_year <= 0 ) {
				$from_year = (int) current_time( 'Y' );
			}
			$today = strtotime( gmdate( 'Y-m-d', current_time( 'timestamp' ) ) );
			$candidates = array();

			$festivals = get_posts(
				array(
					'post_type' => 'festi',
					'post_status' => array( 'publish', 'draft' ),
					'posts_per_page' => -1,
					'orderby' => 'title',
					'order' => 'ASC',
				)
			);
			foreach ( $festivals as $festival ) {
				$festival_id = (int) $festival->ID;
				$edition = trim( (string) get_post_meta( $festival_id, 'edicion', true ) );
				if ( (int) $edition !== $from_year ) {
					continue;
				}

				$fecha_inicio = trim( (string) get_post_meta( $festival_id, 'fecha_inicio', true ) );
				$fecha_fin = trim( (string) get_post_meta( $festival_id, 'fecha_fin', true ) );
				$ts_start = $this->date_string_to_timestamp( $fecha_inicio );
				$ts_end = $this->date_string_to_timestamp( $fecha_fin );
				$event_ts = $ts_end > 0 ? $ts_end : $ts_start;
				if ( $event_ts <= 0 ) {
					continue;
				}
				if ( $event_ts >= $today ) {
					continue;
				}

				$candidates[] = array(
					'id' => $festival_id,
					'title' => (string) $festival->post_title,
					'status' => (string) $festival->post_status,
					'edition' => $edition,
					'fecha_inicio' => $fecha_inicio,
					'fecha_fin' => $fecha_fin,
					'link' => get_edit_post_link( $festival_id, '' ),
				);
			}

			return $candidates;
		}

		private function get_rollover_preview_transient_key() {
			return 'mfu_rollover_preview_' . (int) get_current_user_id();
		}

		private function get_rollover_applied_transient_key() {
			return 'mfu_rollover_applied_' . (int) get_current_user_id();
		}

		private function build_rollover_preview_row( $festival_id, $from_year, $to_year ) {
			$festival_id = (int) $festival_id;
			$festival = get_post( $festival_id );
			if ( ! $festival || $festival->post_type !== 'festi' ) {
				return null;
			}

			$before = array(
				'edicion' => (string) get_post_meta( $festival_id, 'edicion', true ),
				'fecha_inicio' => (string) get_post_meta( $festival_id, 'fecha_inicio', true ),
				'fecha_fin' => (string) get_post_meta( $festival_id, 'fecha_fin', true ),
				'mf_artistas' => (string) get_post_meta( $festival_id, 'mf_artistas', true ),
				'mf_cartel_completo' => (string) get_post_meta( $festival_id, 'mf_cartel_completo', true ),
				'sin_fechas_confirmadas' => (string) get_post_meta( $festival_id, 'sin_fechas_confirmadas', true ),
			);
			$after = array(
				'edicion' => (string) $to_year,
				'fecha_inicio' => '',
				'fecha_fin' => '',
				'mf_artistas' => '',
				'mf_cartel_completo' => '',
				'sin_fechas_confirmadas' => '1',
			);

			return array(
				'id' => $festival_id,
				'title' => (string) $festival->post_title,
				'from_year' => (int) $from_year,
				'to_year' => (int) $to_year,
				'before' => $before,
				'after' => $after,
				'edit_link' => get_edit_post_link( $festival_id, '' ),
			);
		}

	private function parse_spanish_date( $value ) {
		if ( ! is_string( $value ) || $value === '' ) {
			return '';
		}
		$months = array(
			'enero' => '01',
			'febrero' => '02',
			'marzo' => '03',
			'abril' => '04',
			'mayo' => '05',
			'junio' => '06',
			'julio' => '07',
			'agosto' => '08',
			'septiembre' => '09',
			'setiembre' => '09',
			'octubre' => '10',
			'noviembre' => '11',
			'diciembre' => '12',
		);
		$val = strtolower( remove_accents( $value ) );
		if ( preg_match( '/(\d{1,2})\s*(?:de)?\s*([a-z]+)\s*(?:de)?\s*(\d{4})/i', $val, $m ) ) {
			$day = str_pad( $m[1], 2, '0', STR_PAD_LEFT );
			$month_name = $m[2];
			$year = $m[3];
			if ( isset( $months[ $month_name ] ) ) {
				return $year . $months[ $month_name ] . $day;
			}
		}
		return '';
	}

	public function field_ai_provider() {
		$value = $this->get_settings_value( 'ai_provider', 'openai' );
		echo '<select name="' . esc_attr( MFU_OPTION_KEY ) . '[ai_provider]">';
		foreach ( array( 'openai' => 'OpenAI', 'perplexity' => 'Perplexity' ) as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '"' . selected( $value, $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
	}

	public function field_base_query_provider() {
		$value = $this->get_settings_value( 'base_query_provider', 'perplexity' );
		echo '<select name="' . esc_attr( MFU_OPTION_KEY ) . '[base_query_provider]">';
		foreach ( array( 'perplexity' => 'Perplexity', 'openai' => 'OpenAI (web search)' ) as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '"' . selected( $value, $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
	}

	public function field_verification_provider() {
		$value = $this->get_settings_value( 'verification_provider', 'perplexity' );
		echo '<select name="' . esc_attr( MFU_OPTION_KEY ) . '[verification_provider]">';
		foreach ( array( 'perplexity' => 'Perplexity', 'openai' => 'OpenAI (web search)' ) as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '"' . selected( $value, $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
	}

	public function field_api_key() {
		$value = $this->get_settings_value( 'api_key', '' );
		echo '<input type="password" name="' . esc_attr( MFU_OPTION_KEY ) . '[api_key]" value="' . esc_attr( $value ) . '" class="regular-text" />';
	}

	public function field_pplx_api_key() {
		$value = $this->get_settings_value( 'pplx_api_key', '' );
		echo '<input type="password" name="' . esc_attr( MFU_OPTION_KEY ) . '[pplx_api_key]" value="' . esc_attr( $value ) . '" class="regular-text" />';
	}

	public function field_apify_token() {
		$value = $this->get_settings_value( 'apify_token', '' );
		echo '<input type="password" name="' . esc_attr( MFU_OPTION_KEY ) . '[apify_token]" value="' . esc_attr( $value ) . '" class="regular-text" />';
	}

	public function field_apify_actor() {
		$value = $this->get_settings_value( 'apify_actor', 'apify/instagram-post-scraper' );
		echo '<input type="text" name="' . esc_attr( MFU_OPTION_KEY ) . '[apify_actor]" value="' . esc_attr( $value ) . '" class="regular-text" />';
	}

	public function field_model_extract() {
		$value = $this->get_settings_value( 'model_extract', 'gpt-5-mini' );
		$this->render_model_select( 'model_extract', $value );
	}

	public function field_model_write() {
		$value = $this->get_settings_value( 'model_write', 'gpt-5-mini' );
		$this->render_model_select( 'model_write', $value );
	}

	private function render_model_select( $key, $value ) {
		$models = array(
			'gpt-5.1',
			'gpt-5',
			'gpt-5-mini',
			'gpt-5-nano',
		);
		echo '<select name="' . esc_attr( MFU_OPTION_KEY ) . '[' . esc_attr( $key ) . ']">';
		foreach ( $models as $model ) {
			echo '<option value="' . esc_attr( $model ) . '"' . selected( $value, $model, false ) . '>' . esc_html( $model ) . '</option>';
		}
		echo '</select>';
		echo '<p class="description">Si necesitas otro modelo, dímelo y lo añadimos.</p>';
	}

	public function field_use_web_search() {
		$value = ! empty( $this->get_settings_value( 'use_web_search', 0 ) );
		echo '<label><input type="checkbox" name="' . esc_attr( MFU_OPTION_KEY ) . '[use_web_search]" value="1"' . checked( $value, true, false ) . '> Activar</label>';
	}

	public function field_ai_content_update() {
		$value = ! empty( $this->get_settings_value( 'ai_content_update', 0 ) );
		echo '<label><input type="checkbox" name="' . esc_attr( MFU_OPTION_KEY ) . '[ai_content_update]" value="1"' . checked( $value, true, false ) . '> Activar</label>';
	}

	public function field_auto_apply_when_verified() {
		$value = ! empty( $this->get_settings_value( 'auto_apply_when_verified', 0 ) );
		echo '<label><input type="checkbox" name="' . esc_attr( MFU_OPTION_KEY ) . '[auto_apply_when_verified]" value="1"' . checked( $value, true, false ) . '> Activar</label>';
	}

	public function field_news_enabled() {
		$value = ! empty( $this->get_settings_value( 'news_enabled', 0 ) );
		echo '<label><input type="checkbox" name="' . esc_attr( MFU_OPTION_KEY ) . '[news_enabled]" value="1"' . checked( $value, true, false ) . '> Activar</label>';
	}

	public function field_news_rss_sources() {
		$value = $this->get_settings_value( 'news_rss_sources', array() );
		$rows = is_array( $value ) ? $value : array();
		if ( empty( $rows ) ) {
			$rows = array(
				array( 'url' => '', 'keywords' => '' ),
			);
		}
		$base = esc_attr( MFU_OPTION_KEY ) . '[news_rss_sources]';
		echo '<div id="mfu-rss-sources">';
		foreach ( $rows as $index => $row ) {
			$url = is_array( $row ) ? (string) ( $row['url'] ?? '' ) : '';
			$keywords = is_array( $row ) ? (string) ( $row['keywords'] ?? '' ) : '';
			echo '<div class="mfu-rss-row" style="display:flex; gap:8px; margin-bottom:8px;">';
			echo '<input type="url" class="regular-text" name="' . $base . '[' . (int) $index . '][url]" placeholder="https://..." value="' . esc_attr( $url ) . '" />';
			echo '<input type="text" class="regular-text" name="' . $base . '[' . (int) $index . '][keywords]" placeholder="filtro (palabras separadas por coma)" value="' . esc_attr( $keywords ) . '" />';
			echo '<button type="button" class="button mfu-rss-remove">Eliminar</button>';
			echo '</div>';
		}
		echo '</div>';
		echo '<button type="button" class="button" id="mfu-rss-add">Anadir RSS</button>';
		echo '<p class="description">Un RSS por campo. El filtro busca palabras en titulo o contenido.</p>';
		$base_js = wp_json_encode( $base );
		echo "<script>
		(function(){
			var container = document.getElementById('mfu-rss-sources');
			var addBtn = document.getElementById('mfu-rss-add');
			if (!container || !addBtn) return;
			var base = {$base_js};
			function bindRemove(btn){
				btn.addEventListener('click', function(){
					var row = btn.closest('.mfu-rss-row');
					if (row) row.remove();
				});
			}
			container.querySelectorAll('.mfu-rss-remove').forEach(bindRemove);
			addBtn.addEventListener('click', function(){
				var index = container.querySelectorAll('.mfu-rss-row').length;
				var row = document.createElement('div');
				row.className = 'mfu-rss-row';
				row.style.cssText = 'display:flex; gap:8px; margin-bottom:8px;';
				row.innerHTML =
					'<input type=\"url\" class=\"regular-text\" name=\"' + base + '[' + index + '][url]\" placeholder=\"https://...\" value=\"\" />' +
					'<input type=\"text\" class=\"regular-text\" name=\"' + base + '[' + index + '][keywords]\" placeholder=\"filtro (palabras separadas por coma)\" value=\"\" />' +
					'<button type=\"button\" class=\"button mfu-rss-remove\">Eliminar</button>';
				container.appendChild(row);
				bindRemove(row.querySelector('.mfu-rss-remove'));
			});
		})();
		</script>";
	}

	public function field_debug_enabled() {
		$value = ! empty( $this->get_settings_value( 'debug_enabled', 0 ) );
		echo '<label><input type="checkbox" name="' . esc_attr( MFU_OPTION_KEY ) . '[debug_enabled]" value="1"' . checked( $value, true, false ) . '> Activar</label>';
	}

	public function field_update_source_urls() {
		$value = ! empty( $this->get_settings_value( 'update_source_urls', 0 ) );
		echo '<label><input type="checkbox" name="' . esc_attr( MFU_OPTION_KEY ) . '[update_source_urls]" value="1"' . checked( $value, true, false ) . '> Activar</label>';
	}

	public function field_cost_currency() {
		$value = $this->get_settings_value( 'cost_currency', 'EUR' );
		echo '<input type="text" name="' . esc_attr( MFU_OPTION_KEY ) . '[cost_currency]" value="' . esc_attr( $value ) . '" class="small-text" />';
	}

	public function field_cost_input() {
		$value = $this->get_settings_value( 'cost_input', 0 );
		echo '<input type="number" step="0.0001" name="' . esc_attr( MFU_OPTION_KEY ) . '[cost_input]" value="' . esc_attr( $value ) . '" class="small-text" />';
	}

	public function field_cost_output() {
		$value = $this->get_settings_value( 'cost_output', 0 );
		echo '<input type="number" step="0.0001" name="' . esc_attr( MFU_OPTION_KEY ) . '[cost_output]" value="' . esc_attr( $value ) . '" class="small-text" />';
	}

	public function field_cost_extract_input() {
		$value = $this->get_settings_value( 'cost_extract_input', 0 );
		echo '<input type="number" step="0.0001" name="' . esc_attr( MFU_OPTION_KEY ) . '[cost_extract_input]" value="' . esc_attr( $value ) . '" class="small-text" />';
	}

	public function field_cost_extract_output() {
		$value = $this->get_settings_value( 'cost_extract_output', 0 );
		echo '<input type="number" step="0.0001" name="' . esc_attr( MFU_OPTION_KEY ) . '[cost_extract_output]" value="' . esc_attr( $value ) . '" class="small-text" />';
	}

	public function field_cost_write_input() {
		$value = $this->get_settings_value( 'cost_write_input', 0 );
		echo '<input type="number" step="0.0001" name="' . esc_attr( MFU_OPTION_KEY ) . '[cost_write_input]" value="' . esc_attr( $value ) . '" class="small-text" />';
	}

	public function field_cost_write_output() {
		$value = $this->get_settings_value( 'cost_write_output', 0 );
		echo '<input type="number" step="0.0001" name="' . esc_attr( MFU_OPTION_KEY ) . '[cost_write_output]" value="' . esc_attr( $value ) . '" class="small-text" />';
	}

	public function field_pplx_cost_input() {
		$value = $this->get_settings_value( 'pplx_cost_input', 0 );
		echo '<input type="number" step="0.0001" name="' . esc_attr( MFU_OPTION_KEY ) . '[pplx_cost_input]" value="' . esc_attr( $value ) . '" class="small-text" />';
	}

	public function field_pplx_cost_output() {
		$value = $this->get_settings_value( 'pplx_cost_output', 0 );
		echo '<input type="number" step="0.0001" name="' . esc_attr( MFU_OPTION_KEY ) . '[pplx_cost_output]" value="' . esc_attr( $value ) . '" class="small-text" />';
	}

	public function field_pplx_cost_extract_input() {
		$value = $this->get_settings_value( 'pplx_cost_extract_input', 0 );
		echo '<input type="number" step="0.0001" name="' . esc_attr( MFU_OPTION_KEY ) . '[pplx_cost_extract_input]" value="' . esc_attr( $value ) . '" class="small-text" />';
	}

	public function field_pplx_cost_extract_output() {
		$value = $this->get_settings_value( 'pplx_cost_extract_output', 0 );
		echo '<input type="number" step="0.0001" name="' . esc_attr( MFU_OPTION_KEY ) . '[pplx_cost_extract_output]" value="' . esc_attr( $value ) . '" class="small-text" />';
	}

	public function field_pplx_cost_write_input() {
		$value = $this->get_settings_value( 'pplx_cost_write_input', 0 );
		echo '<input type="number" step="0.0001" name="' . esc_attr( MFU_OPTION_KEY ) . '[pplx_cost_write_input]" value="' . esc_attr( $value ) . '" class="small-text" />';
	}

	public function field_pplx_cost_write_output() {
		$value = $this->get_settings_value( 'pplx_cost_write_output', 0 );
		echo '<input type="number" step="0.0001" name="' . esc_attr( MFU_OPTION_KEY ) . '[pplx_cost_write_output]" value="' . esc_attr( $value ) . '" class="small-text" />';
	}

	public function field_cron_interval() {
		$value = $this->get_settings_value( 'cron_interval', 86400 );
		echo '<input type="number" name="' . esc_attr( MFU_OPTION_KEY ) . '[cron_interval]" value="' . esc_attr( $value ) . '" class="small-text" />';
	}

	public function field_batch_size() {
		$value = $this->get_settings_value( 'batch_size', 5 );
		echo '<input type="number" name="' . esc_attr( MFU_OPTION_KEY ) . '[batch_size]" value="' . esc_attr( $value ) . '" class="small-text" />';
	}

	public function field_timeout() {
		$value = $this->get_settings_value( 'timeout', 15 );
		echo '<input type="number" name="' . esc_attr( MFU_OPTION_KEY ) . '[timeout]" value="' . esc_attr( $value ) . '" class="small-text" />';
	}

	public function render_updates_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->maybe_handle_bulk_post();

		if ( isset( $_GET['update_id'] ) ) {
			$this->render_update_detail( (int) $_GET['update_id'] );
			return;
		}

		$list_table = new MFU_List_Table();
		$list_table->prepare_items();

		echo '<div class="wrap">';
		echo '<h1>Festival Updates</h1>';
		$this->render_updates_summary();
		echo '<div id="mfu-queue-status">';
		$this->render_queue_status();
		echo '</div>';
		echo '<div style="display:grid; grid-template-columns:repeat(2, minmax(280px, 1fr)); gap:12px; align-items:start; margin-top:12px;">';
		$this->render_recent_updates_panel();
		$this->render_queue_festivals_panel();
		echo '</div>';
		$this->render_bulk_notice();
		echo '<p>';
		echo '<a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mfu_enqueue_all' ), 'mfu_enqueue_all' ) ) . '">Buscar actualizaciones (todos)</a>';
		echo '</p>';
		echo '<p style="margin:6px 0 12px;">';
		echo '<strong>Filtros rapidos:</strong> ';
		echo '<a href="' . esc_url( admin_url( 'admin.php?page=mfu-updates&mfu_status=pending_review' ) ) . '">Pendientes</a> | ';
		echo '<a href="' . esc_url( admin_url( 'admin.php?page=mfu-updates&mfu_status=no_data' ) ) . '">Sin datos</a> | ';
		echo '<a href="' . esc_url( admin_url( 'admin.php?page=mfu-updates&mfu_status=no_change' ) ) . '">Sin cambios</a> | ';
		echo '<a href="' . esc_url( admin_url( 'admin.php?page=mfu-updates' ) ) . '">Quitar filtros</a>';
		echo '</p>';
		echo '<details style="margin:0 0 12px;"><summary style="cursor:pointer;">Limpieza avanzada</summary>';
		$this->render_clear_status_form();
		echo '</details>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin.php?page=mfu-updates' ) ) . '">';
		echo '<input type="hidden" name="page" value="mfu-updates" />';
		wp_nonce_field( 'mfu_bulk_enqueue', '_mfu_nonce' );
		wp_nonce_field( 'mfu_bulk_apply', '_mfu_nonce_apply' );
		$list_table->search_box( 'Buscar festival', 'mfu-search' );
		$this->render_active_filters_notice();
		$list_table->display();
		echo '</form>';
		echo '</div>';
	}

	private function render_recent_updates_panel() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;
		$table = MFU_DB::table( 'updates' );
		$rows = $wpdb->get_results( "SELECT id, festival_id, detected_at, status, applied_by, applied_at FROM {$table} ORDER BY detected_at DESC LIMIT 10" );

		echo '<div style="padding:12px; border:1px solid #dcdcde; background:#fff; max-height:420px; overflow:auto;">';
		echo '<h2 style="margin:0 0 10px;">Ultimos festivales procesados</h2>';
		if ( empty( $rows ) ) {
			echo '<p>Sin registros recientes.</p>';
			echo '</div>';
			return;
		}

		echo '<table class="widefat striped">';
		echo '<thead><tr>';
		echo '<th>Festival</th><th>Detectado</th><th>Estado</th><th>Acciones</th>';
		echo '</tr></thead><tbody>';

		foreach ( $rows as $row ) {
			$festival = get_post( (int) $row->festival_id );
			$title = $festival ? $festival->post_title : 'Festival #' . (int) $row->festival_id;
			$festival_url = $festival ? get_permalink( $festival->ID ) : '';
			$changes_url = admin_url( 'admin.php?page=mfu-updates&update_id=' . (int) $row->id );
			$status = (string) $row->status;
			$status_label = $this->format_update_status_label( $status );
			$row_style = '';
			switch ( $status ) {
				case 'auto_applied':
					$row_style = 'background:#d4f1ff; border-left:4px solid #0b6bcb;';
					break;
				case 'applied':
					$row_style = 'background:#d9fbe1; border-left:4px solid #15803d;';
					break;
				case 'pending_review':
					$row_style = 'background:#fff7e6;';
					break;
				case 'rejected':
					$row_style = 'background:#fdecea;';
					break;
				case 'no_data':
				case 'no_change':
					$row_style = 'background:#f6f7f7;';
					break;
				default:
					$row_style = '';
					break;
			}

			echo '<tr' . ( $row_style !== '' ? ' style="' . esc_attr( $row_style ) . '"' : '' ) . '>';
			echo '<td>' . ( $festival_url ? '<a href="' . esc_url( $festival_url ) . '" target="_blank" rel="noopener">' . esc_html( $title ) . '</a>' : esc_html( $title ) ) . '</td>';
			echo '<td>' . esc_html( (string) $row->detected_at ) . '</td>';
			echo '<td>' . esc_html( $status_label ) . '</td>';
			echo '<td>';
			if ( $festival_url ) {
				echo '<a href="' . esc_url( $festival_url ) . '" target="_blank" rel="noopener">Ver festival</a> | ';
			}
			echo '<a href="' . esc_url( $changes_url ) . '" target="_blank" rel="noopener">Ver cambios</a>';
			echo '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
		echo '</div>';
	}

	private function render_queue_festivals_panel() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;
		$table = MFU_DB::table( 'jobs' );
		$rows = $wpdb->get_results(
			"SELECT id, festival_id, created_at, priority
			 FROM {$table}
			 WHERE status='queued'
			 ORDER BY priority ASC, id ASC
			 LIMIT 10"
		);

		echo '<div style="padding:12px; border:1px solid #dcdcde; background:#fff; max-height:420px; overflow:auto;">';
		echo '<h2 style="margin:0 0 10px;">Festivales en cola</h2>';
		if ( empty( $rows ) ) {
			echo '<p>Sin festivales en cola.</p>';
			echo '</div>';
			return;
		}

		echo '<table class="widefat striped">';
		echo '<thead><tr>';
		echo '<th>Festival</th><th>Encolado</th><th>Prioridad</th>';
		echo '</tr></thead><tbody>';

		foreach ( $rows as $row ) {
			$festival = get_post( (int) $row->festival_id );
			$title = $festival ? $festival->post_title : 'Festival #' . (int) $row->festival_id;
			$festival_url = $festival ? get_edit_post_link( $festival->ID, '' ) : '';
			$created_at = $row->created_at ? (string) $row->created_at : '';
			$priority = isset( $row->priority ) ? (int) $row->priority : 0;

			echo '<tr>';
			echo '<td>' . ( $festival_url ? '<a href="' . esc_url( $festival_url ) . '">' . esc_html( $title ) . '</a>' : esc_html( $title ) ) . '</td>';
			echo '<td>' . esc_html( $created_at ) . '</td>';
			echo '<td>' . esc_html( (string) $priority ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
		echo '</div>';
	}

	private function format_update_status_label( $status ) {
		$status = (string) $status;
		if ( $status === 'auto_applied' ) {
			return 'Autoaplicado';
		}
		if ( $status === 'applied' ) {
			return 'Aplicado';
		}
		if ( $status === 'pending_review' ) {
			return 'Pendiente de revision';
		}
		if ( $status === 'no_change' ) {
			return 'Sin cambios';
		}
		if ( $status === 'no_data' ) {
			return 'Sin datos';
		}
		if ( $status === 'rejected' ) {
			return 'Rechazado';
		}
		return $status !== '' ? $status : '-';
	}

	private function render_update_detail( $update_id ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$update_id = (int) $update_id;
		if ( $update_id <= 0 ) {
			wp_die( 'Update invalido' );
		}

		global $wpdb;
		$table = MFU_DB::table( 'updates' );
		$update = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d", $update_id ) );
		if ( ! $update ) {
			wp_die( 'Update no encontrado' );
		}

		$festival = get_post( (int) $update->festival_id );
		$festival_title = $festival ? $festival->post_title : 'Festival desconocido';
		$festival_url = $festival ? get_edit_post_link( $festival->ID, '' ) : '';
		$diffs = $update->diffs_json ? json_decode( $update->diffs_json, true ) : array();
		$evidence = $update->evidence_json ? json_decode( $update->evidence_json, true ) : array();

		$back_url = admin_url( 'admin.php?page=mfu-updates' );
		echo '<div class="wrap">';
		echo '<p><a href="' . esc_url( $back_url ) . '">&larr; Volver</a></p>';
		echo '<h1>Detalle de cambios</h1>';
		echo '<p><strong>Festival:</strong> ';
		if ( $festival_url ) {
			echo '<a href="' . esc_url( $festival_url ) . '">' . esc_html( $festival_title ) . '</a>';
		} else {
			echo esc_html( $festival_title );
		}
		echo '</p>';
		echo '<p><strong>Estado:</strong> <span id="mfu-update-status">' . esc_html( (string) $update->status ) . '</span> | ';
		echo '<strong>Detectado:</strong> ' . esc_html( (string) $update->detected_at ) . '</p>';

		if ( is_array( $evidence ) && ! empty( $evidence['errors'] ) && is_array( $evidence['errors'] ) ) {
			echo '<div class="notice notice-warning"><p><strong>Errores:</strong></p><ul style="margin:0 0 0 18px;">';
			foreach ( $evidence['errors'] as $err ) {
				echo '<li>' . esc_html( (string) $err ) . '</li>';
			}
			echo '</ul></div>';
		}

		if ( is_array( $evidence ) && ! empty( $evidence['verification'] ) && is_array( $evidence['verification'] ) ) {
			$verif = $evidence['verification'];
			$verdict = (string) ( $verif['verdict'] ?? '' );
			$message = (string) ( $verif['message'] ?? '' );
			$issues = isset( $verif['issues'] ) && is_array( $verif['issues'] ) ? $verif['issues'] : array();
			$badge = $verdict !== '' ? strtoupper( $verdict ) : 'SIN VEREDICTO';
			echo '<div style="margin:12px 0; padding:12px; border:1px solid #dcdcde; background:#f6f7f7;">';
			echo '<strong>Decision final de veracidad</strong>';
			echo '<div style="margin-top:6px; font-size:14px;"><strong>' . esc_html( $badge ) . '</strong>';
			if ( $message !== '' ) {
				echo ' - ' . esc_html( $message );
			}
			echo '</div>';
			if ( ! empty( $issues ) ) {
				echo '<ul style="margin:8px 0 0 18px;">';
				foreach ( $issues as $issue ) {
					echo '<li>' . esc_html( (string) $issue ) . '</li>';
				}
				echo '</ul>';
			}
			echo '</div>';
		}

		echo '<h2>Campos detectados</h2>';
		if ( empty( $diffs ) ) {
			echo '<p>Sin cambios registrados.</p>';
		} else {
			echo '<table class="widefat striped" style="max-width:900px;">';
			echo '<thead><tr><th>Campo</th><th>Antes</th><th>Despues</th></tr></thead><tbody>';
			foreach ( $diffs as $key => $diff ) {
				$before = is_array( $diff ) && array_key_exists( 'before', $diff ) ? $diff['before'] : '';
				$after = is_array( $diff ) && array_key_exists( 'after', $diff ) ? $diff['after'] : '';
				$before_display = $this->format_diff_value( $key, $before );
				$after_display = $this->format_diff_value( $key, $after );
				echo '<tr>';
				echo '<td><strong>' . esc_html( (string) $key ) . '</strong></td>';
				echo '<td>' . esc_html( is_scalar( $before_display ) ? (string) $before_display : wp_json_encode( $before_display ) ) . '</td>';
				echo '<td>' . esc_html( is_scalar( $after_display ) ? (string) $after_display : wp_json_encode( $after_display ) ) . '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
		}

		if ( is_array( $evidence ) && ! empty( $evidence['updated_content'] ) && $festival ) {
			$current_content = (string) $festival->post_content;
			$updated_content = (string) $evidence['updated_content'];
				$verify_nonce = wp_create_nonce( 'mfu_verify_content_perplexity' );
				$rewrite_nonce = wp_create_nonce( 'mfu_rewrite_updated_content_seo' );
			$diff_html = wp_text_diff( $current_content, $updated_content, array( 'show_split_view' => true ) );
			if ( $diff_html ) {
				echo '<h2>Diff visual</h2>';
				echo '<style>
					.mfu-content-diff table.diff { width:100%; border-collapse:collapse; }
					.mfu-content-diff .diff td { padding:6px 8px; vertical-align:top; }
					.mfu-content-diff .diff-deletedline { background:#fdecea; }
					.mfu-content-diff .diff-addedline { background:#e7f7ed; }
					.mfu-content-diff .diff-context { background:#f6f7f7; }
					.mfu-content-diff ins { background:#b7ebc6; text-decoration:none; }
					.mfu-content-diff del { background:#f7b7b7; text-decoration:line-through; }
				</style>';
				echo '<div class="mfu-content-diff">' . wp_kses_post( $diff_html ) . '</div>';
			}
			echo '<details style="margin-top:14px;"><summary style="cursor:pointer;">Contenido actualizado (HTML)</summary>';
			echo '<p>Vista previa (HTML). Revisa antes de aplicar.</p>';
			echo '<div style="display:flex; gap:16px; flex-wrap:wrap;">';
			echo '<div style="flex:1 1 420px; min-width:320px;">';
			echo '<p><strong>Actual</strong></p>';
			echo '<textarea readonly style="width:100%; height:260px;">' . esc_textarea( $current_content ) . '</textarea>';
			echo '</div>';
			echo '<div style="flex:1 1 420px; min-width:320px;">';
			echo '<p><strong>Propuesto (IA)</strong></p>';
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'mfu_save_updated_content_' . (int) $update_id );
			echo '<input type="hidden" name="action" value="mfu_save_updated_content" />';
			echo '<input type="hidden" name="update_id" value="' . (int) $update_id . '" />';
				echo '<textarea id="mfu-updated-content" name="updated_content" style="width:100%; height:260px;">' . esc_textarea( $updated_content ) . '</textarea>';
				echo '<p style="margin:8px 0 0; display:flex; gap:8px; flex-wrap:wrap;">';
				echo '<button type="submit" class="button">Guardar cambios manuales</button>';
				echo '<button type="submit" class="button button-primary" name="apply_now" value="1">Guardar y aplicar cambios al festival</button>';
				echo '<button type="button" class="button" id="mfu-rewrite-seo-btn" data-update="' . (int) $update_id . '">Reescribir con OpenAI (SEO)</button>';
				echo '<span id="mfu-rewrite-seo-status" style="align-self:center; color:#50575e;"></span>';
				echo '</p>';
				echo '</form>';
			echo '</div>';
			echo '</div>';
			echo '<details style="margin-top:10px;"><summary style="cursor:pointer;">Verificacion manual de contenido</summary>';
			echo '<div style="margin-top:10px;">';
			echo '<button type="button" class="button" id="mfu-verify-content-btn" data-update="' . (int) $update_id . '">Verificar contenido (Perplexity)</button>';
			echo '<span id="mfu-verify-content-status" style="margin-left:10px;"></span>';
			echo '</div>';
			echo '<div id="mfu-verify-content-result" style="margin-top:8px;"></div>';
			echo '</details>';
			echo '</details>';
			echo '<script>
					(function(){
						var btn = document.getElementById("mfu-verify-content-btn");
						var rewriteBtn = document.getElementById("mfu-rewrite-seo-btn");
						var rewriteStatusEl = document.getElementById("mfu-rewrite-seo-status");
						var contentTextarea = document.getElementById("mfu-updated-content");
						if (!btn && !rewriteBtn) { return; }
						var statusEl = document.getElementById("mfu-verify-content-status");
						var resultEl = document.getElementById("mfu-verify-content-result");
						if (rewriteBtn) {
							rewriteBtn.addEventListener("click", function(){
								if (!contentTextarea) { return; }
								var currentContent = contentTextarea.value || "";
								if (currentContent.trim() === "") {
									if (rewriteStatusEl) { rewriteStatusEl.textContent = "No hay contenido para reescribir."; }
									return;
								}
								rewriteBtn.disabled = true;
								if (rewriteStatusEl) { rewriteStatusEl.textContent = "Reescribiendo..."; }
								var data = new FormData();
								data.append("action", "mfu_rewrite_updated_content_seo");
								data.append("_ajax_nonce", "' . esc_js( $rewrite_nonce ) . '");
								data.append("update_id", rewriteBtn.getAttribute("data-update"));
								data.append("updated_content", currentContent);
								fetch(ajaxurl, { method: "POST", credentials: "same-origin", body: data })
									.then(function(res){ return res.json(); })
									.then(function(json){
										rewriteBtn.disabled = false;
										if (!json || !json.success) {
											var msg = (json && json.data && json.data.message) ? json.data.message : "Error al reescribir.";
											if (rewriteStatusEl) { rewriteStatusEl.textContent = msg; }
											return;
										}
										var rewritten = (json.data && json.data.rewritten_content) ? String(json.data.rewritten_content) : "";
										if (rewritten !== "" && contentTextarea) {
											contentTextarea.value = rewritten;
										}
										if (rewriteStatusEl) {
											rewriteStatusEl.textContent = (json.data && json.data.message) ? String(json.data.message) : "Contenido reescrito.";
										}
									})
									.catch(function(){
										rewriteBtn.disabled = false;
										if (rewriteStatusEl) { rewriteStatusEl.textContent = "Error de red al reescribir."; }
									});
							});
						}
						btn.addEventListener("click", function(){
						btn.disabled = true;
						if (statusEl) { statusEl.textContent = "Verificando..."; }
						var data = new FormData();
						data.append("action", "mfu_verify_content_perplexity");
						data.append("_ajax_nonce", "' . esc_js( $verify_nonce ) . '");
						data.append("update_id", btn.getAttribute("data-update"));
						fetch(ajaxurl, { method: "POST", credentials: "same-origin", body: data })
							.then(function(res){ return res.json(); })
							.then(function(json){
								btn.disabled = false;
								if (statusEl) { statusEl.textContent = ""; }
								if (!json || !json.success) {
									var msg = (json && json.data && json.data.message) ? json.data.message : "Error al verificar.";
									if (resultEl) { resultEl.innerHTML = "<div class=\\"notice notice-error\\"><p>" + msg + "</p></div>"; }
									return;
								}
								var verdict = json.data.verdict || "";
								var message = json.data.message || "";
								var status = json.data.status || "";
								var suggestions = Array.isArray(json.data.suggestions) ? json.data.suggestions : [];
								if (resultEl) {
									var extra = "";
									if (suggestions.length > 0) {
										extra = " (" + suggestions.length + " sugerencia(s) guardada(s). Recargando...)";
									} else {
										extra = " (sin cambios propuestos)";
									}
									resultEl.innerHTML = "<div class=\\"notice notice-info\\"><p><strong>Verificacion Perplexity:</strong> " + verdict + " " + message + extra + "</p></div>";
								}
								if (status && document.getElementById("mfu-update-status")) {
									document.getElementById("mfu-update-status").textContent = status;
								}
								if (suggestions.length > 0) {
									setTimeout(function(){ window.location.reload(); }, 800);
								}
							})
							.catch(function(){
								btn.disabled = false;
								if (statusEl) { statusEl.textContent = ""; }
								if (resultEl) { resultEl.innerHTML = "<div class=\\"notice notice-error\\"><p>Error al verificar.</p></div>"; }
							});
					});
				})();
			</script>';

			if ( ! empty( $evidence['content_suggestions'] ) && is_array( $evidence['content_suggestions'] ) ) {
				echo '<h3>Sugerencias de mejora (verificación)</h3>';
				echo '<p>Aplica o descarta cada cambio propuesto.</p>';
				echo '<div style="display:flex; flex-direction:column; gap:12px;">';
				foreach ( $evidence['content_suggestions'] as $idx => $suggestion ) {
					$find = (string) ( $suggestion['find'] ?? '' );
					$replace = (string) ( $suggestion['replace'] ?? '' );
					$note = (string) ( $suggestion['note'] ?? '' );
					$apply_url = wp_nonce_url(
						admin_url( 'admin-post.php?action=mfu_apply_content_suggestion&update_id=' . $update_id . '&index=' . (int) $idx ),
						'mfu_apply_content_suggestion_' . $update_id . '_' . (int) $idx
					);
					$reject_url = wp_nonce_url(
						admin_url( 'admin-post.php?action=mfu_reject_content_suggestion&update_id=' . $update_id . '&index=' . (int) $idx ),
						'mfu_reject_content_suggestion_' . $update_id . '_' . (int) $idx
					);
					echo '<div style="border:1px solid #dcdcde; padding:10px; border-radius:8px; background:#fff;">';
					if ( $note !== '' ) {
						echo '<p><strong>Nota:</strong> ' . esc_html( $note ) . '</p>';
					}
					if ( $find !== '' ) {
						echo '<p><strong>Texto actual:</strong></p><textarea readonly style="width:100%; height:80px;">' . esc_textarea( $find ) . '</textarea>';
					}
					if ( $replace !== '' ) {
						echo '<p><strong>Texto propuesto:</strong></p><textarea readonly style="width:100%; height:80px;">' . esc_textarea( $replace ) . '</textarea>';
					}
					echo '<p style="margin-top:8px;">';
					echo '<a class="button button-primary" href="' . esc_url( $apply_url ) . '">Aplicar</a> ';
					echo '<a class="button" href="' . esc_url( $reject_url ) . '">Rechazar</a>';
					echo '</p>';
					echo '</div>';
				}
				echo '</div>';
			}
		}

		echo '<p style="margin-top:16px;">';
		if ( $update->status === 'pending_review' ) {
			$apply_url = wp_nonce_url( admin_url( 'admin-post.php?action=mfu_apply_update&update_id=' . $update_id ), 'mfu_apply_update_' . $update_id );
			$reject_url = wp_nonce_url( admin_url( 'admin-post.php?action=mfu_reject_update&update_id=' . $update_id ), 'mfu_reject_update_' . $update_id );
			echo '<a class="button button-primary" href="' . esc_url( $apply_url ) . '">Aplicar cambios</a> ';
			echo '<a class="button" href="' . esc_url( $reject_url ) . '">Rechazar</a>';
		}
		echo '</p>';
		echo '</div>';
	}

	private function render_updates_summary() {
		$log = get_option( 'mfu_error_log', array() );
		if ( ! is_array( $log ) ) {
			$log = array();
		}
		$options = get_option( MFU_OPTION_KEY, array() );
		$cost_currency = isset( $options['cost_currency'] ) ? $options['cost_currency'] : 'EUR';
		$cost_input = isset( $options['cost_input'] ) ? (float) $options['cost_input'] : 0.0;
		$cost_output = isset( $options['cost_output'] ) ? (float) $options['cost_output'] : 0.0;
		$cost_extract_input = isset( $options['cost_extract_input'] ) ? (float) $options['cost_extract_input'] : 0.0;
		$cost_extract_output = isset( $options['cost_extract_output'] ) ? (float) $options['cost_extract_output'] : 0.0;
		$cost_write_input = isset( $options['cost_write_input'] ) ? (float) $options['cost_write_input'] : 0.0;
		$cost_write_output = isset( $options['cost_write_output'] ) ? (float) $options['cost_write_output'] : 0.0;
		$pplx_cost_input = isset( $options['pplx_cost_input'] ) ? (float) $options['pplx_cost_input'] : 0.0;
		$pplx_cost_output = isset( $options['pplx_cost_output'] ) ? (float) $options['pplx_cost_output'] : 0.0;
		$pplx_cost_extract_input = isset( $options['pplx_cost_extract_input'] ) ? (float) $options['pplx_cost_extract_input'] : 0.0;
		$pplx_cost_extract_output = isset( $options['pplx_cost_extract_output'] ) ? (float) $options['pplx_cost_extract_output'] : 0.0;
		$pplx_cost_write_input = isset( $options['pplx_cost_write_input'] ) ? (float) $options['pplx_cost_write_input'] : 0.0;
		$pplx_cost_write_output = isset( $options['pplx_cost_write_output'] ) ? (float) $options['pplx_cost_write_output'] : 0.0;
		$usage = get_option( 'mfu_usage_log', array() );
		$days = isset( $usage['days'] ) && is_array( $usage['days'] ) ? $usage['days'] : array();
		$last = isset( $usage['last'] ) && is_array( $usage['last'] ) ? $usage['last'] : array();
		$today = current_time( 'Y-m-d' );
		$last_30 = array_slice( array_reverse( $days, true ), 0, 30, true );
		$totals = array(
			'requests' => 0,
			'input_tokens' => 0,
			'output_tokens' => 0,
			'cost' => 0.0,
		);
		foreach ( $last_30 as $row ) {
			$totals['requests'] += (int) ( $row['requests'] ?? 0 );
			$totals['input_tokens'] += (int) ( $row['input_tokens'] ?? 0 );
			$totals['output_tokens'] += (int) ( $row['output_tokens'] ?? 0 );
			$totals['cost'] += (float) ( $row['cost'] ?? 0 );
		}
		$today_row = isset( $days[ $today ] ) && is_array( $days[ $today ] ) ? $days[ $today ] : array();
		echo '<div style="margin:12px 0; padding:12px; border:1px solid #dcdcde; background:#f6f7f7;">';
		echo '<strong>Resumen rapido</strong>';
		echo '<p style="margin:6px 0 0;"><a href="' . esc_url( admin_url( 'admin.php?page=mfu-usage' ) ) . '">Ver consumo de APIs</a></p>';
		echo '<div style="margin-top:8px; display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:8px;">';
		echo '<div style="padding:8px 10px; background:#fff; border:1px solid #dcdcde; border-radius:6px;">';
		echo '<div style="font-size:12px; color:#50575e;">Ultimos 30 dias</div>';
		echo '<div style="font-weight:700;">' . esc_html( (string) $totals['requests'] ) . ' req · '
			. esc_html( number_format( (float) $totals['cost'], 4, '.', '' ) ) . ' ' . esc_html( $cost_currency ) . '</div>';
		echo '<div style="font-size:12px; color:#50575e;">in ' . esc_html( (string) $totals['input_tokens'] ) . ' / out '
			. esc_html( (string) $totals['output_tokens'] ) . '</div>';
		echo '</div>';
		echo '<div style="padding:8px 10px; background:#fff; border:1px solid #dcdcde; border-radius:6px;">';
		echo '<div style="font-size:12px; color:#50575e;">Hoy</div>';
		echo '<div style="font-weight:700;">' . esc_html( (string) ( $today_row['requests'] ?? 0 ) ) . ' req · '
			. esc_html( number_format( (float) ( $today_row['cost'] ?? 0 ), 4, '.', '' ) ) . ' ' . esc_html( $cost_currency ) . '</div>';
		echo '<div style="font-size:12px; color:#50575e;">in ' . esc_html( (string) ( $today_row['input_tokens'] ?? 0 ) )
			. ' / out ' . esc_html( (string) ( $today_row['output_tokens'] ?? 0 ) ) . '</div>';
		echo '</div>';
		if ( ! empty( $last ) ) {
			echo '<div style="padding:8px 10px; background:#fff; border:1px solid #dcdcde; border-radius:6px;">';
			echo '<div style="font-size:12px; color:#50575e;">Ultima llamada</div>';
			$action = $last['action'] ?? '';
			$model = $last['model'] ?? '';
			$cost = number_format( (float) ( $last['cost'] ?? 0 ), 4, '.', '' );
			$when = $last['when'] ?? '';
			echo '<div style="font-weight:700;">' . esc_html( trim( $action . ' ' . $model ) ) . '</div>';
			echo '<div style="font-size:12px; color:#50575e;">' . esc_html( $cost ) . ' ' . esc_html( $cost_currency ) . ' · '
				. esc_html( (string) ( $last['input_tokens'] ?? 0 ) ) . '/'
				. esc_html( (string) ( $last['output_tokens'] ?? 0 ) ) . ' · ' . esc_html( $when ) . '</div>';
			echo '</div>';
		}
		echo '</div>';
		if ( $cost_input > 0 || $cost_output > 0 || $cost_extract_input > 0 || $cost_extract_output > 0 || $cost_write_input > 0 || $cost_write_output > 0 || $pplx_cost_input > 0 || $pplx_cost_output > 0 || $pplx_cost_extract_input > 0 || $pplx_cost_extract_output > 0 || $pplx_cost_write_input > 0 || $pplx_cost_write_output > 0 ) {
			$base_costs = array();
			if ( $cost_input > 0 || $cost_output > 0 || $cost_extract_input > 0 || $cost_extract_output > 0 || $cost_write_input > 0 || $cost_write_output > 0 ) {
				$openai_bits = array();
				if ( $cost_input > 0 || $cost_output > 0 ) {
					$openai_bits[] = 'base ' . number_format( $cost_input, 4, '.', '' ) . '/' . number_format( $cost_output, 4, '.', '' );
				}
				if ( $cost_extract_input > 0 || $cost_extract_output > 0 ) {
					$openai_bits[] = 'extract ' . number_format( $cost_extract_input, 4, '.', '' ) . '/' . number_format( $cost_extract_output, 4, '.', '' );
				}
				if ( $cost_write_input > 0 || $cost_write_output > 0 ) {
					$openai_bits[] = 'write ' . number_format( $cost_write_input, 4, '.', '' ) . '/' . number_format( $cost_write_output, 4, '.', '' );
				}
				if ( ! empty( $openai_bits ) ) {
					$base_costs[] = 'OpenAI ' . implode( ', ', $openai_bits );
				}
			}
			if ( $pplx_cost_input > 0 || $pplx_cost_output > 0 || $pplx_cost_extract_input > 0 || $pplx_cost_extract_output > 0 || $pplx_cost_write_input > 0 || $pplx_cost_write_output > 0 ) {
				$pplx_bits = array();
				if ( $pplx_cost_input > 0 || $pplx_cost_output > 0 ) {
					$pplx_bits[] = 'base ' . number_format( $pplx_cost_input, 4, '.', '' ) . '/' . number_format( $pplx_cost_output, 4, '.', '' );
				}
				if ( $pplx_cost_extract_input > 0 || $pplx_cost_extract_output > 0 ) {
					$pplx_bits[] = 'extract ' . number_format( $pplx_cost_extract_input, 4, '.', '' ) . '/' . number_format( $pplx_cost_extract_output, 4, '.', '' );
				}
				if ( $pplx_cost_write_input > 0 || $pplx_cost_write_output > 0 ) {
					$pplx_bits[] = 'write ' . number_format( $pplx_cost_write_input, 4, '.', '' ) . '/' . number_format( $pplx_cost_write_output, 4, '.', '' );
				}
				if ( ! empty( $pplx_bits ) ) {
					$base_costs[] = 'Perplexity ' . implode( ', ', $pplx_bits );
				}
			}
			echo '<p style="margin:8px 0 0; font-size:12px; color:#50575e;">Costes configurados (' . esc_html( $cost_currency ) . ' / 1M tokens): ';
			echo esc_html( implode( ' | ', $base_costs ) ) . '.</p>';
		} else {
			echo '<p style="margin:8px 0 0; font-size:12px; color:#50575e;">Costes configurados: sin definir. Configura los precios por 1M tokens en Ajustes para ver estimaciones.</p>';
		}
		$error_count = count( $log );
		if ( $error_count === 0 ) {
			echo '<p style="margin:6px 0 0;">Sin errores recientes.</p>';
		} else {
			$errors_url = admin_url( 'admin.php?page=mfu-errors' );
			echo '<p style="margin:6px 0 0;">Errores recientes: <strong>' . esc_html( (string) $error_count ) . '</strong> ';
			echo '<a href="' . esc_url( $errors_url ) . '">Ver detalles</a></p>';
		}
		echo '</div>';
	}

	private function render_bulk_notice() {
		$messages = array();
		if ( isset( $_GET['bulk_applied'] ) ) {
			$messages[] = array( 'success', 'Cambios aplicados.' );
		}
		if ( isset( $_GET['bulk_rejected'] ) ) {
			$messages[] = array( 'warning', 'Cambios rechazados.' );
		}
		if ( isset( $_GET['bulk_error'] ) ) {
			$messages[] = array( 'error', 'No se pudieron aplicar los cambios.' );
		}

		foreach ( $messages as $row ) {
			$type = $row[0];
			$text = $row[1];
			echo '<div class="notice notice-' . esc_attr( $type ) . '"><p>' . esc_html( $text ) . '</p></div>';
		}
	}

	public function render_sources_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$list_table = new MFU_Sources_Table();
		$list_table->prepare_items();

		echo '<div class="wrap">';
		echo '<h1>Fuentes (web e Instagram)</h1>';
		echo '<p>';
		echo '<a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mfu_fill_sources&batch=1&batch_size=50' ), 'mfu_fill_sources' ) ) . '">Completar fuentes faltantes (Perplexity, lotes)</a>';
		echo ' <a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mfu_download_missing_instagram' ), 'mfu_download_missing_instagram' ) ) . '">Descargar faltan Instagram</a>';
		echo '</p>';

		$missing_web = $this->count_missing_field( 'mf_web_oficial' );
		$missing_ig = $this->count_missing_field( 'mf_instagram' );
		$missing_both = $this->count_missing_web_and_ig();
		echo '<p style="margin:0 0 12px; color:#50575e;">Fuentes pendientes: ';
		echo 'web ' . (int) $missing_web . ' | Instagram ' . (int) $missing_ig . ' | web + Instagram ' . (int) $missing_both;
		echo '</p>';

		echo '<form method="get" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin:8px 0 16px;">';
		echo '<input type="hidden" name="action" value="mfu_fill_sources" />';
		wp_nonce_field( 'mfu_fill_sources', '_wpnonce', false );
		echo '<label for="mfu_batch_size">Lote:</label> ';
		echo '<select id="mfu_batch_size" name="batch_size">';
		foreach ( array( 5, 10, 25, 50, 100 ) as $size ) {
			echo '<option value="' . esc_attr( $size ) . '"' . selected( $size, 50, false ) . '>' . esc_html( (string) $size ) . '</option>';
		}
		echo '</select> ';
		echo '<input type="hidden" name="batch" value="1" />';
		echo '<button class="button">Iniciar lote</button>';
		echo '</form>';

		if ( isset( $_GET['error'] ) && $_GET['error'] === 'pplx' ) {
			echo '<div class="notice notice-error"><p>Falta la Perplexity API key en Ajustes.</p></div>';
		}
		$options = get_option( MFU_OPTION_KEY, array() );
		$apify_token = isset( $options['apify_token'] ) ? trim( (string) $options['apify_token'] ) : '';
		if ( $apify_token === '' ) {
			echo '<div class="notice notice-warning"><p>Apify token no configurado: el Instagram no se rellenara.</p></div>';
		}
		if ( isset( $_GET['filled'] ) ) {
			$filled = (int) $_GET['filled'];
			$total = isset( $_GET['total'] ) ? (int) $_GET['total'] : 0;
			$batch = isset( $_GET['batch'] ) ? (int) $_GET['batch'] : 0;
			$next = isset( $_GET['next'] ) ? (int) $_GET['next'] : 0;
			$msg = 'Fuentes completadas: ' . $filled . ( $total ? ' / ' . $total : '' );
			echo '<div class="notice notice-success"><p>' . esc_html( $msg ) . '</p></div>';
			$last_key = 'mfu_fill_sources_last_' . get_current_user_id();
			$last = get_transient( $last_key );
			if ( is_array( $last ) ) {
				if ( ! empty( $last['counts'] ) ) {
					$counts = $last['counts'];
					$summary = 'Actualizados: ' . (int) ( $counts['updated'] ?? 0 ) . ' | Sin cambios: ' . (int) ( $counts['skipped'] ?? 0 ) . ' | Errores: ' . (int) ( $counts['errors'] ?? 0 );
					echo '<div class="notice notice-info"><p><strong>Resumen del lote:</strong> ' . esc_html( $summary ) . '</p></div>';
				}
				if ( ! empty( $last['updated'] ) ) {
					echo '<div class="notice notice-success"><p><strong>Actualizados:</strong> ' . esc_html( implode( ', ', $last['updated'] ) ) . '</p></div>';
				}
				if ( ! empty( $last['skipped'] ) ) {
					echo '<div class="notice notice-warning"><p><strong>Sin cambios:</strong> ' . esc_html( implode( ', ', $last['skipped'] ) ) . '</p></div>';
				}
				if ( ! empty( $last['errors'] ) ) {
					echo '<div class="notice notice-error"><p><strong>Errores:</strong> ' . esc_html( implode( ', ', $last['errors'] ) ) . '</p></div>';
				}
				if ( ! empty( $last['log'] ) && is_array( $last['log'] ) ) {
					echo '<div class="notice notice-info"><p><strong>Log de fuentes por festival (ultimo lote):</strong></p><ul>';
					foreach ( $last['log'] as $entry ) {
						$title = $entry['title'] ?? '';
						$web = $entry['web'] ?? '';
						$ig = $entry['ig'] ?? '';
						$error = $entry['error'] ?? '';
						$line = $title . ' | web: ' . ( $web ? $web : '-' ) . ' | ig: ' . ( $ig ? $ig : '-' );
						if ( $error ) {
							$line .= ' | error: ' . $error;
						}
						echo '<li>' . esc_html( $line ) . '</li>';
						if ( ! empty( $entry['items'] ) && is_array( $entry['items'] ) ) {
							$items_text = array();
							foreach ( $entry['items'] as $row ) {
								$items_text[] = ( $row['title'] ? $row['title'] . ' -> ' : '' ) . ( $row['url'] ?? '' );
							}
							if ( $items_text ) {
								echo '<li><em>Top resultados:</em> ' . esc_html( implode( ' | ', $items_text ) ) . '</li>';
							}
						}
					}
					echo '</ul></div>';
				}
			}
			if ( $next > 0 ) {
				$batch_size = isset( $_GET['batch_size'] ) ? (int) $_GET['batch_size'] : 50;
				$next_url = wp_nonce_url( admin_url( 'admin-post.php?action=mfu_fill_sources&batch=' . $next . '&batch_size=' . $batch_size ), 'mfu_fill_sources' );
				echo '<p><a class="button button-primary" href="' . esc_url( $next_url ) . '">Continuar lote ' . esc_html( (string) $next ) . '</a></p>';
			}
		}

		echo '<form method="get" action="' . esc_url( admin_url( 'admin.php' ) ) . '">';
		echo '<input type="hidden" name="page" value="mfu-sources" />';
		$list_table->search_box( 'Buscar festival', 'mfu-sources-search' );
		$list_table->display();
		echo '</form>';

		echo '</div>';
	}

	public function handle_save_sources_fields() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No autorizado.' );
		}
		$festival_id = isset( $_POST['festival_id'] ) ? (int) $_POST['festival_id'] : 0;
		$field = isset( $_POST['field'] ) ? sanitize_text_field( wp_unslash( $_POST['field'] ) ) : '';
		$value = isset( $_POST['value'] ) ? trim( (string) wp_unslash( $_POST['value'] ) ) : '';
		if ( ! $festival_id || ! in_array( $field, array( 'mf_web_oficial', 'mf_instagram' ), true ) ) {
			wp_die( 'Solicitud invalida.' );
		}
		check_admin_referer( 'mfu_save_sources_' . $festival_id );
		if ( function_exists( 'update_field' ) ) {
			update_field( $field, $value, $festival_id );
		}
		update_post_meta( $festival_id, $field, $value );
		if ( $field === 'mf_web_oficial' ) {
			update_post_meta( $festival_id, 'mfu_web_status', $value ? 'official' : 'missing' );
		}
		if ( $field === 'mf_instagram' && $value ) {
			update_post_meta( $festival_id, 'mfu_ig_status', '' );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=mfu-sources' ) );
		exit;
	}

	private function maybe_handle_bulk_post() {
		if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
			return;
		}

		$action = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : '';
		$action2 = isset( $_POST['action2'] ) ? sanitize_text_field( wp_unslash( $_POST['action2'] ) ) : '';
		$bulk_action = $action !== '-1' ? $action : $action2;
		if ( ! in_array( $bulk_action, array( 'bulk_update', 'bulk_apply' ), true ) ) {
			return;
		}

		$ids = isset( $_POST['festival_ids'] ) ? array_map( 'intval', (array) $_POST['festival_ids'] ) : array();
		if ( empty( $ids ) ) {
			return;
		}

		if ( $bulk_action === 'bulk_update' ) {
			$nonce = isset( $_POST['_mfu_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_mfu_nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'mfu_bulk_enqueue' ) ) {
				return;
			}
			foreach ( $ids as $festival_id ) {
				MFU_Cron::enqueue_job( (int) $festival_id, 10, 'manual' );
			}
			$this->safe_redirect( admin_url( 'admin.php?page=mfu-updates' ) );
		}

		if ( $bulk_action === 'bulk_apply' ) {
			$nonce = isset( $_POST['_mfu_nonce_apply'] ) ? sanitize_text_field( wp_unslash( $_POST['_mfu_nonce_apply'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'mfu_bulk_apply' ) ) {
				return;
			}
			$_POST['action'] = 'mfu_bulk_apply';
			$_POST['_mfu_nonce'] = wp_create_nonce( 'mfu_bulk_apply' );
			do_action( 'admin_post_mfu_bulk_apply' );
			exit;
		}
	}

	private function safe_redirect( $url ) {
		$url = esc_url_raw( $url );
		if ( ! headers_sent() ) {
			wp_safe_redirect( $url );
			exit;
		}
		echo '<!doctype html><html><head>';
		echo '<meta http-equiv="refresh" content="0;url=' . esc_attr( $url ) . '">';
		echo '<script>window.location.href=' . wp_json_encode( $url ) . ';</script>';
		echo '</head><body>';
		echo '<p>Redirigiendo… <a href="' . esc_url( $url ) . '">Continuar</a></p>';
		echo '</body></html>';
		exit;
	}

	public function render_errors_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$log = get_option( 'mfu_error_log', array() );
		if ( ! is_array( $log ) ) {
			$log = array();
		}

		echo '<div class="wrap">';
		echo '<h1>Log de errores</h1>';
		echo '<p>Se muestran los ultimos 200 errores.</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="mfu_clear_error_log" />';
		wp_nonce_field( 'mfu_clear_error_log', '_mfu_nonce' );
		echo '<button class="button">Limpiar log</button>';
		echo '</form>';

		if ( empty( $log ) ) {
			echo '<p>Sin errores.</p>';
			echo '</div>';
			return;
		}

		echo '<table class="widefat fixed striped" style="margin-top:10px;">';
		echo '<thead><tr><th>Fecha</th><th>Festival</th><th>Job</th><th>Mensaje</th></tr></thead><tbody>';
		foreach ( $log as $row ) {
			$festival_id = isset( $row['festival_id'] ) ? (int) $row['festival_id'] : 0;
			$job_id = isset( $row['job_id'] ) ? (int) $row['job_id'] : 0;
			$title = $festival_id ? get_the_title( $festival_id ) : '';
			$edit = $festival_id ? get_edit_post_link( $festival_id ) : '';
			$festival_label = $title ? $title : ( $festival_id ? (string) $festival_id : '-' );
			if ( $edit ) {
				$festival_label = '<a href="' . esc_url( $edit ) . '">' . esc_html( $festival_label ) . '</a>';
			} else {
				$festival_label = esc_html( $festival_label );
			}
			echo '<tr>';
			echo '<td>' . esc_html( $row['time'] ?? '' ) . '</td>';
			echo '<td>' . $festival_label . '</td>';
			echo '<td>' . esc_html( $job_id ? $job_id : '-' ) . '</td>';
			echo '<td>' . esc_html( $row['message'] ?? '' ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
		echo '</div>';
	}

		public function render_news_update_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$message = isset( $_GET['mfu_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['mfu_msg'] ) ) : '';
		$err = isset( $_GET['mfu_error'] ) ? sanitize_text_field( wp_unslash( $_GET['mfu_error'] ) ) : '';
		$news_url = isset( $_GET['news_url'] ) ? esc_url_raw( wp_unslash( $_GET['news_url'] ) ) : '';
		$festival_title = isset( $_GET['festival_title'] ) ? sanitize_text_field( wp_unslash( $_GET['festival_title'] ) ) : '';
		$confidence = isset( $_GET['confidence'] ) ? sanitize_text_field( wp_unslash( $_GET['confidence'] ) ) : '';
		$update_id = isset( $_GET['update_id'] ) ? (int) $_GET['update_id'] : 0;

		echo '<div class="wrap">';
		echo '<h1>Actualizacion via Noticias</h1>';
		echo '<p>Pega una URL de noticia para extraer cambios y aplicarlos al festival correspondiente.</p>';
		if ( $err !== '' ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $err ) . '</p></div>';
		}
		if ( $message !== '' ) {
			echo '<div class="notice notice-success"><p>' . esc_html( $message ) . '</p></div>';
		}

		if ( $update_id > 0 ) {
			$view_url = admin_url( 'admin.php?page=mfu-updates&update_id=' . $update_id );
			echo '<p><strong>Extraccion completada.</strong> Revisa los cambios y aplica o rechaza.</p>';
			echo '<p><a class="button button-primary" href="' . esc_url( $view_url ) . '" target="_blank" rel="noopener">Ver cambios</a></p>';
			echo '</div>';
			return;
		}

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'mfu_news_check', '_mfu_nonce' );
		echo '<input type="hidden" name="action" value="mfu_news_check" />';
		echo '<table class="form-table"><tbody>';
		echo '<tr>';
		echo '<th scope="row"><label for="mfu_news_url">URL de la noticia</label></th>';
		echo '<td><input type="url" class="regular-text" id="mfu_news_url" name="news_url" required placeholder="https://..." value="' . esc_attr( $news_url ) . '" /></td>';
		echo '</tr>';
		echo '</tbody></table>';
		echo '<p><button type="submit" class="button button-primary">Comprobar festival</button></p>';
		echo '</form>';

		if ( $news_url !== '' ) {
			echo '<hr />';
			echo '<h2>Seleccion manual</h2>';
			echo '<p>Si el festival no se detecta bien, puedes seleccionarlo manualmente antes de extraer los cambios.</p>';
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'mfu_news_extract', '_mfu_nonce' );
			echo '<input type="hidden" name="action" value="mfu_news_extract" />';
			echo '<input type="hidden" name="news_url" value="' . esc_attr( $news_url ) . '" />';
			echo '<input type="hidden" id="mfu_manual_festival_id" name="manual_festival_id" value="" />';
			echo '<p><input type="text" class="regular-text" id="mfu_festival_search" placeholder="Buscar festival..." autocomplete="off" />';
			echo '<div id="mfu_festival_results" style="margin-top:8px; max-height:220px; overflow:auto; border:1px solid #ccd0d4; background:#fff; display:none;"></div></p>';
			echo '<p><button type="submit" class="button button-primary">Extraer datos y preparar cambios</button></p>';
			echo '</form>';
			echo "<script>
			(function(){
				var input = document.getElementById('mfu_festival_search');
				var results = document.getElementById('mfu_festival_results');
				var hidden = document.getElementById('mfu_manual_festival_id');
				if (!input || !results || !hidden) return;
				var timer;
				function render(items){
					if (!items.length) {
						results.style.display = 'none';
						results.innerHTML = '';
						return;
					}
					results.innerHTML = '';
					items.forEach(function(item){
						var btn = document.createElement('button');
						btn.type = 'button';
						btn.className = 'button-link';
						btn.style.display = 'block';
						btn.style.padding = '6px 10px';
						btn.style.textAlign = 'left';
						btn.style.width = '100%';
						btn.textContent = item.title;
						btn.addEventListener('click', function(){
							hidden.value = item.id;
							input.value = item.title;
							results.style.display = 'none';
						});
						results.appendChild(btn);
					});
					results.style.display = 'block';
				}
				input.addEventListener('input', function(){
					var q = input.value.trim();
					hidden.value = '';
					if (q.length < 2) {
						render([]);
						return;
					}
					clearTimeout(timer);
					timer = setTimeout(function(){
						var url = ajaxurl + '?action=mfu_festival_search&q=' + encodeURIComponent(q);
						fetch(url, { credentials: 'same-origin' })
							.then(function(r){ return r.json(); })
							.then(function(data){ render(Array.isArray(data) ? data : []); })
							.catch(function(){ render([]); });
					}, 200);
				});
			})();
				</script>";
			}

			if ( $news_url !== '' && $festival_title !== '' ) {
				echo '<hr />';
				echo '<p><strong>Festival encontrado:</strong> ' . esc_html( $festival_title ) . '</p>';
			if ( $confidence !== '' ) {
				echo '<p><strong>Confianza IA:</strong> ' . esc_html( $confidence ) . '</p>';
			}
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'mfu_news_extract', '_mfu_nonce' );
			echo '<input type="hidden" name="action" value="mfu_news_extract" />';
			echo '<input type="hidden" name="news_url" value="' . esc_attr( $news_url ) . '" />';
			echo '<p><button type="submit" class="button button-primary">Extraer datos y preparar cambios</button></p>';
			echo '</form>';
		}

		$this->render_news_rss_feed();
			$this->render_recent_news_updates();
			echo '</div>';
		}

		public function render_press_releases_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$message = isset( $_GET['mfu_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['mfu_msg'] ) ) : '';
			$error = isset( $_GET['mfu_err'] ) ? sanitize_text_field( wp_unslash( $_GET['mfu_err'] ) ) : '';
			$post_id = isset( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;
			$update_id = isset( $_GET['update_id'] ) ? (int) $_GET['update_id'] : 0;

			echo '<div class="wrap">';
			echo '<h1>Notas de prensa</h1>';
			echo '<p>Pega una nota de prensa tal cual la recibes y elige si quieres republicarla (reescrita) o usarla para preparar una actualizacion de un festival.</p>';

			if ( $error !== '' ) {
				echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
			}
			if ( $message !== '' ) {
				echo '<div class="notice notice-success"><p>' . esc_html( $message ) . '</p></div>';
			}

			if ( $post_id > 0 ) {
				$edit = get_edit_post_link( $post_id, '' );
				if ( $edit ) {
					echo '<p><a class="button button-primary" href="' . esc_url( $edit ) . '" target="_blank" rel="noopener">Editar borrador generado</a></p>';
				}
			}
			if ( $update_id > 0 ) {
				$view_url = admin_url( 'admin.php?page=mfu-updates&update_id=' . $update_id );
				echo '<p><a class="button button-primary" href="' . esc_url( $view_url ) . '" target="_blank" rel="noopener">Ver cambios del festival</a></p>';
			}

			echo '<hr />';

			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" enctype="multipart/form-data">';
			wp_nonce_field( 'mfu_press_release_process', '_mfu_nonce' );
			echo '<input type="hidden" name="action" value="mfu_press_release_process" />';
			echo '<table class="form-table"><tbody>';

			echo '<tr>';
			echo '<th scope="row"><label for="mfu_press_text">Contenido de la nota de prensa</label></th>';
			echo '<td><textarea id="mfu_press_text" name="press_text" rows="12" style="width:100%;" placeholder="Pega aqui la nota de prensa completa..." required></textarea>';
			echo '<p class="description">Tip: pega el texto completo, incluyendo fecha y ciudad si aparecen. No hace falta que limpies formatos.</p></td>';
			echo '</tr>';

			echo '<tr>';
			echo '<th scope="row"><label for="mfu_press_file">O subir archivo</label></th>';
			echo '<td><input type="file" id="mfu_press_file" name="press_file" accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document" />';
			echo '<p class="description">Puedes subir un .docx o .pdf. Si el textarea esta vacio, intentaremos extraer el texto automaticamente.</p></td>';
			echo '</tr>';

			echo '<tr>';
			echo '<th scope="row">Accion</th>';
			echo '<td>';
			echo '<label style="display:block; margin:4px 0;"><input type="radio" name="press_mode" value="republish" checked /> Republicar nota de prensa (reescritura SEO + borrador)</label>';
			echo '<label style="display:block; margin:4px 0;"><input type="radio" name="press_mode" value="festival_update" /> Actualizacion de festival (preparar cambios)</label>';
			echo '</td>';
			echo '</tr>';

			echo '<tr id="mfu_press_festival_row" style="display:none;">';
			echo '<th scope="row"><label for="mfu_press_festival_search">Festival</label></th>';
			echo '<td>';
			echo '<input type="hidden" id="mfu_press_festival_id" name="festival_id" value="" />';
			echo '<input type="text" class="regular-text" id="mfu_press_festival_search" placeholder="Buscar festival..." autocomplete="off" />';
			echo '<div id="mfu_press_festival_results" style="margin-top:8px; max-height:220px; overflow:auto; border:1px solid #ccd0d4; background:#fff; display:none;"></div>';
			echo '<p class="description">Selecciona el festival al que hace referencia la nota de prensa.</p>';
			echo '</td>';
			echo '</tr>';

			echo '</tbody></table>';

			echo '<p><button type="submit" class="button button-primary">Procesar nota de prensa</button></p>';
			echo '</form>';

			echo "<script>
			(function(){
				var radios = document.querySelectorAll('input[name=\"press_mode\"]');
				var row = document.getElementById('mfu_press_festival_row');
				function refresh(){
					var mode = document.querySelector('input[name=\"press_mode\"]:checked');
					var show = mode && mode.value === 'festival_update';
					if (row) row.style.display = show ? '' : 'none';
				}
				radios.forEach(function(r){ r.addEventListener('change', refresh); });
				refresh();

				// Festival search (AJAX)
				var input = document.getElementById('mfu_press_festival_search');
				var results = document.getElementById('mfu_press_festival_results');
				var hidden = document.getElementById('mfu_press_festival_id');
				if (!input || !results || !hidden) return;
				var timer;
				function render(items){
					if (!items.length) {
						results.style.display = 'none';
						results.innerHTML = '';
						return;
					}
					results.innerHTML = '';
					items.forEach(function(item){
						var btn = document.createElement('button');
						btn.type = 'button';
						btn.className = 'button-link';
						btn.style.display = 'block';
						btn.style.padding = '6px 10px';
						btn.style.textAlign = 'left';
						btn.style.width = '100%';
						btn.textContent = item.title;
						btn.addEventListener('click', function(){
							hidden.value = item.id;
							input.value = item.title;
							results.style.display = 'none';
						});
						results.appendChild(btn);
					});
					results.style.display = 'block';
				}
				input.addEventListener('input', function(){
					var q = input.value.trim();
					hidden.value = '';
					if (q.length < 2) {
						render([]);
						return;
					}
					clearTimeout(timer);
					timer = setTimeout(function(){
						var url = ajaxurl + '?action=mfu_festival_search&q=' + encodeURIComponent(q);
						fetch(url, { credentials: 'same-origin' })
							.then(function(r){ return r.json(); })
							.then(function(data){ render(Array.isArray(data) ? data : []); })
							.catch(function(){ render([]); });
					}, 200);
				});
			})();
			</script>";

			echo '</div>';
		}

		public function handle_press_release_process() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'No permitido' );
			}

			check_admin_referer( 'mfu_press_release_process', '_mfu_nonce' );

			$press_text = isset( $_POST['press_text'] ) ? wp_unslash( $_POST['press_text'] ) : '';
			$press_text = is_string( $press_text ) ? trim( $press_text ) : '';
			$mode = isset( $_POST['press_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['press_mode'] ) ) : 'republish';
			$festival_id = isset( $_POST['festival_id'] ) ? (int) $_POST['festival_id'] : 0;

			$back = admin_url( 'admin.php?page=mfu-press-releases' );
			if ( $press_text === '' && ! empty( $_FILES['press_file'] ) && is_array( $_FILES['press_file'] ) && ! empty( $_FILES['press_file']['tmp_name'] ) ) {
				$extracted = $this->extract_press_release_text_from_upload( $_FILES['press_file'] );
				if ( is_wp_error( $extracted ) ) {
					wp_redirect( add_query_arg( 'mfu_err', rawurlencode( $extracted->get_error_message() ), $back ) );
					exit;
				}
				$press_text = trim( (string) $extracted );
			}
			if ( $press_text === '' ) {
				wp_redirect( add_query_arg( 'mfu_err', rawurlencode( 'La nota de prensa esta vacia.' ), $back ) );
				exit;
			}

			$ai = new MFU_AI();
			if ( ! $ai->has_openai_key() && ! $ai->has_perplexity_key() ) {
				wp_redirect( add_query_arg( 'mfu_err', rawurlencode( 'Falta API key para usar IA.' ), $back ) );
				exit;
			}

			if ( $mode === 'festival_update' ) {
				if ( $festival_id <= 0 || get_post_type( $festival_id ) !== 'festi' ) {
					wp_redirect( add_query_arg( 'mfu_err', rawurlencode( 'Selecciona un festival valido.' ), $back ) );
					exit;
				}
				$festival = get_post( $festival_id );
				if ( ! $festival ) {
					wp_redirect( add_query_arg( 'mfu_err', rawurlencode( 'Festival no encontrado.' ), $back ) );
					exit;
				}

				$current_fields = array(
					'fecha_inicio' => (string) get_post_meta( $festival_id, 'fecha_inicio', true ),
					'fecha_fin' => (string) get_post_meta( $festival_id, 'fecha_fin', true ),
					'mf_artistas' => (string) get_post_meta( $festival_id, 'mf_artistas', true ),
					'mf_web_oficial' => (string) get_post_meta( $festival_id, 'mf_web_oficial', true ),
					'mf_instagram' => (string) get_post_meta( $festival_id, 'mf_instagram', true ),
					'mf_cartel_completo' => (string) get_post_meta( $festival_id, 'mf_cartel_completo', true ),
					'cancelado' => (string) get_post_meta( $festival_id, 'cancelado', true ),
					'sin_fechas_confirmadas' => (string) get_post_meta( $festival_id, 'sin_fechas_confirmadas', true ),
				);
				$edition = (string) get_post_meta( $festival_id, 'edicion', true );

				$payload = $ai->press_release_festival_update_payload(
					(string) $festival->post_title,
					$edition,
					(string) $festival->post_content,
					$current_fields,
					$press_text
				);
				if ( is_wp_error( $payload ) ) {
					wp_redirect( add_query_arg( 'mfu_err', rawurlencode( $payload->get_error_message() ), $back ) );
					exit;
				}

				$fields = isset( $payload['fields'] ) && is_array( $payload['fields'] ) ? $payload['fields'] : array();
				$updated_content = isset( $payload['updated_content_html'] ) ? (string) $payload['updated_content_html'] : '';
				$summary = isset( $payload['summary'] ) ? (string) $payload['summary'] : '';

				$diffs = array();
				foreach ( array( 'fecha_inicio', 'fecha_fin', 'mf_artistas', 'mf_web_oficial', 'mf_instagram', 'mf_cartel_completo', 'cancelado', 'sin_fechas_confirmadas' ) as $key ) {
					if ( ! array_key_exists( $key, $fields ) ) {
						continue;
					}
					$after = $fields[ $key ];
					if ( $after === null ) {
						continue;
					}
					$after = is_string( $after ) ? trim( $after ) : (string) $after;
					$before = isset( $current_fields[ $key ] ) ? (string) $current_fields[ $key ] : '';
					if ( $after === $before ) {
						continue;
					}
					$diffs[ $key ] = array(
						'before' => $before,
						'after' => $after,
					);
				}

				if ( empty( $diffs ) && $updated_content !== '' ) {
					$current_content = (string) $festival->post_content;
					if ( trim( $updated_content ) !== trim( $current_content ) ) {
						$diffs['content_update'] = array(
							'before' => 'content',
							'after' => 'content',
						);
					}
				}

				if ( empty( $diffs ) ) {
					wp_redirect( add_query_arg( 'mfu_msg', rawurlencode( 'No se detectaron cambios aplicables.' ), $back ) );
					exit;
				}

				global $wpdb;
				$table = MFU_DB::table( 'updates' );
				$evidence = array(
					'facts' => array( 'summary' => $summary ),
					'sources' => array(
						array( 'url' => 'press_release', 'title' => 'Nota de prensa (pegada manualmente)' ),
					),
					'press_release' => mb_substr( $press_text, 0, 20000 ),
					'updated_content' => $updated_content,
				);
				$wpdb->insert(
					$table,
					array(
						'festival_id' => (int) $festival_id,
						'detected_at' => current_time( 'mysql' ),
						'status' => 'pending_review',
						'diffs_json' => wp_json_encode( $diffs ),
						'evidence_json' => wp_json_encode( $evidence ),
						'summary' => $summary,
					),
					array( '%d', '%s', '%s', '%s', '%s', '%s' )
				);
				$update_id = (int) $wpdb->insert_id;
				wp_redirect( add_query_arg( array( 'mfu_msg' => rawurlencode( 'Actualizacion preparada.' ), 'update_id' => $update_id ), $back ) );
				exit;
			}

				$original_title = '';
				$lines = preg_split( "/\\r\\n|\\r|\\n/", $press_text );
				if ( is_array( $lines ) ) {
					foreach ( $lines as $line ) {
						$line = trim( (string) $line );
						if ( $line === '' ) {
							continue;
						}
						$original_title = $line;
						break;
					}
				}
				if ( strlen( $original_title ) > 140 ) {
					$original_title = substr( $original_title, 0, 140 );
				}

				$author_id = (int) get_current_user_id();
				$carla = get_user_by( 'login', 'Carla' );
				if ( $carla && ! is_wp_error( $carla ) ) {
					$author_id = (int) $carla->ID;
				} else {
					$clara = get_user_by( 'login', 'Clara' );
					if ( $clara && ! is_wp_error( $clara ) ) {
						$author_id = (int) $clara->ID;
					}
				}

				$internal_links = array(
					'agenda' => home_url( '/agenda-festivales/' ),
				);
				$style_catalog = $this->get_style_catalog_links();
				$draft = $ai->rewrite_press_release_to_post( $press_text, $internal_links, $original_title, $style_catalog );
			if ( is_wp_error( $draft ) ) {
				wp_redirect( add_query_arg( 'mfu_err', rawurlencode( $draft->get_error_message() ), $back ) );
				exit;
			}

			$title = isset( $draft['title'] ) ? (string) $draft['title'] : '';
			$excerpt = isset( $draft['excerpt'] ) ? (string) $draft['excerpt'] : '';
			$content = isset( $draft['content_html'] ) ? (string) $draft['content_html'] : '';
			$yoast_title = isset( $draft['yoast_title'] ) ? (string) $draft['yoast_title'] : '';
			$yoast_desc = isset( $draft['yoast_metadesc'] ) ? (string) $draft['yoast_metadesc'] : '';
				$focus_kw = isset( $draft['focus_keyphrase'] ) ? (string) $draft['focus_keyphrase'] : '';
				$style_slugs = isset( $draft['style_slugs'] ) && is_array( $draft['style_slugs'] ) ? array_map( 'sanitize_title', $draft['style_slugs'] ) : array();

			if ( trim( $title ) === '' || trim( $content ) === '' ) {
				wp_redirect( add_query_arg( 'mfu_err', rawurlencode( 'La IA no devolvio contenido valido.' ), $back ) );
				exit;
			}

			$post_id = wp_insert_post(
				array(
					'post_type' => 'post',
					'post_status' => 'draft',
					'post_title' => $title,
					'post_content' => $content,
					'post_excerpt' => $excerpt,
						'post_author' => $author_id,
					),
					true
				);

			if ( is_wp_error( $post_id ) ) {
				wp_redirect( add_query_arg( 'mfu_err', rawurlencode( $post_id->get_error_message() ), $back ) );
				exit;
			}

			$post_id = (int) $post_id;
				$cat_pr = get_term_by( 'slug', 'notas-de-prensa', 'category' );
				$cat_act = get_term_by( 'slug', 'actualidad', 'category' );
				$cats = array();
				if ( $cat_pr && ! is_wp_error( $cat_pr ) ) {
					$cats[] = (int) $cat_pr->term_id;
				}
				if ( $cat_act && ! is_wp_error( $cat_act ) ) {
					$cats[] = (int) $cat_act->term_id;
				}
				if ( ! empty( $cats ) ) {
					wp_set_post_terms( $post_id, $cats, 'category', false );
				}
				$cat_uncat = get_term_by( 'slug', 'sin-categorizar', 'category' );
				if ( $cat_uncat && ! is_wp_error( $cat_uncat ) ) {
					wp_remove_object_terms( $post_id, array( (int) $cat_uncat->term_id ), 'category' );
				}

				if ( ! empty( $style_slugs ) ) {
					$valid = array();
					foreach ( $style_slugs as $slug ) {
						if ( $slug === '' ) {
							continue;
						}
						$term = get_term_by( 'slug', $slug, 'estilo_musical' );
						if ( $term && ! is_wp_error( $term ) ) {
							$valid[] = (int) $term->term_id;
						}
					}
					if ( ! empty( $valid ) ) {
						wp_set_post_terms( $post_id, $valid, 'estilo_musical', false );
					}
				}

			if ( $yoast_title !== '' ) {
				update_post_meta( $post_id, '_yoast_wpseo_title', $yoast_title );
			}
			if ( $yoast_desc !== '' ) {
				update_post_meta( $post_id, '_yoast_wpseo_metadesc', $yoast_desc );
			}
			if ( $focus_kw !== '' ) {
				update_post_meta( $post_id, '_yoast_wpseo_focuskw', $focus_kw );
			}

			wp_redirect( add_query_arg( array( 'mfu_msg' => rawurlencode( 'Borrador creado.' ), 'post_id' => $post_id ), $back ) );
			exit;
		}

		private function extract_press_release_text_from_upload( $file ) {
			if ( ! is_array( $file ) ) {
				return new WP_Error( 'mfu_press_file', 'Archivo invalido.' );
			}
			if ( empty( $file['tmp_name'] ) || ! is_string( $file['tmp_name'] ) ) {
				return new WP_Error( 'mfu_press_file', 'Archivo invalido (tmp).' );
			}
			if ( ! empty( $file['error'] ) ) {
				return new WP_Error( 'mfu_press_file', 'Error al subir el archivo.' );
			}
			$tmp = $file['tmp_name'];
			$name = isset( $file['name'] ) ? (string) $file['name'] : '';
			$ext = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );

			if ( ! in_array( $ext, array( 'pdf', 'docx' ), true ) ) {
				return new WP_Error( 'mfu_press_file', 'Formato no soportado. Sube un .docx o .pdf.' );
			}

			if ( $ext === 'docx' ) {
				if ( ! class_exists( 'ZipArchive' ) ) {
					return new WP_Error( 'mfu_press_file', 'No se puede leer .docx (ZipArchive no disponible).' );
				}
				$zip = new ZipArchive();
				$opened = $zip->open( $tmp );
				if ( $opened !== true ) {
					return new WP_Error( 'mfu_press_file', 'No se pudo abrir el .docx.' );
				}
				$xml = $zip->getFromName( 'word/document.xml' );
				$zip->close();
				if ( ! is_string( $xml ) || $xml === '' ) {
					return new WP_Error( 'mfu_press_file', 'No se pudo extraer texto del .docx.' );
				}
				$text = wp_strip_all_tags( $xml );
				$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5 );
				$text = preg_replace( '/\\s+/', ' ', $text );
				$text = trim( (string) $text );
				if ( strlen( $text ) < 200 ) {
					return new WP_Error( 'mfu_press_file', 'El .docx no contiene texto suficiente.' );
				}
				if ( strlen( $text ) > 50000 ) {
					$text = substr( $text, 0, 50000 );
				}
				return $text;
			}

			// PDF: best-effort via pdftotext if available on server.
			if ( ! function_exists( 'shell_exec' ) ) {
				return new WP_Error( 'mfu_press_file', 'No se puede extraer texto del PDF en este servidor. Pega el texto manualmente.' );
			}
			$bin = @shell_exec( 'command -v pdftotext 2>/dev/null' );
			$bin = is_string( $bin ) ? trim( $bin ) : '';
			if ( $bin === '' ) {
				return new WP_Error( 'mfu_press_file', 'No se puede extraer texto del PDF (pdftotext no disponible). Pega el texto manualmente.' );
			}
			$cmd = escapeshellcmd( $bin ) . ' -q ' . escapeshellarg( $tmp ) . ' -';
			$out = @shell_exec( $cmd );
			$out = is_string( $out ) ? trim( $out ) : '';
			$out = preg_replace( '/\\s+/', ' ', $out );
			$out = trim( (string) $out );
			if ( strlen( $out ) < 200 ) {
				return new WP_Error( 'mfu_press_file', 'No se pudo extraer texto suficiente del PDF. Pega el texto manualmente.' );
			}
			if ( strlen( $out ) > 50000 ) {
				$out = substr( $out, 0, 50000 );
			}
			return $out;
		}

		private function get_external_news_transient_key() {
			return 'mfu_external_news_extract_' . (int) get_current_user_id();
		}

		private function fetch_external_news_text( $url ) {
			$url = esc_url_raw( (string) $url );
			if ( $url === '' ) {
				return new WP_Error( 'mfu_external_news_url', 'URL invalida' );
			}
			$parts = wp_parse_url( $url );
			$scheme = isset( $parts['scheme'] ) ? strtolower( (string) $parts['scheme'] ) : '';
			if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
				return new WP_Error( 'mfu_external_news_url', 'URL invalida (scheme)' );
			}

			$options = get_option( MFU_OPTION_KEY, array() );
			$timeout = isset( $options['timeout'] ) ? max( 5, (int) $options['timeout'] ) : 15;
			$timeout = min( 25, max( 10, $timeout ) );

			$args = array(
				'timeout' => $timeout,
				'redirection' => 3,
				'headers' => array(
					'User-Agent' => 'MFU/1.0 (+external-news)',
					'Accept' => 'text/html,application/xhtml+xml',
				),
			);
			$response = wp_remote_get( $url, $args );
			if ( is_wp_error( $response ) ) {
				return $response;
			}
			$code = (int) wp_remote_retrieve_response_code( $response );
			$body = (string) wp_remote_retrieve_body( $response );
			if ( $code < 200 || $code >= 300 || $body === '' ) {
				return new WP_Error( 'mfu_external_news_fetch', 'No se pudo descargar la noticia (' . $code . ')' );
			}

			$title = '';
			if ( preg_match( '/<meta[^>]+property=[\"\\\']og:title[\"\\\'][^>]+content=[\"\\\']([^\"\\\']+)[\"\\\']/i', $body, $m ) ) {
				$title = trim( wp_strip_all_tags( $m[1] ) );
			}
			if ( $title === '' && preg_match( '/<title[^>]*>(.*?)<\\/title>/is', $body, $m ) ) {
				$title = trim( wp_strip_all_tags( $m[1] ) );
			}

			$body = preg_replace( '/<script\\b[^>]*>.*?<\\/script>/is', ' ', $body );
			$body = preg_replace( '/<style\\b[^>]*>.*?<\\/style>/is', ' ', $body );
			$text = wp_strip_all_tags( $body );
			$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5 );
			$text = preg_replace( '/\\s+/', ' ', $text );
			$text = trim( $text );

			if ( strlen( $text ) < 400 ) {
				return new WP_Error( 'mfu_external_news_extract', 'No se pudo extraer texto suficiente de la URL.' );
			}
			if ( strlen( $text ) > 20000 ) {
				$text = substr( $text, 0, 20000 );
			}

			return array(
				'title' => $title,
				'text' => $text,
			);
		}

		public function render_external_news_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$message = isset( $_GET['mfu_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['mfu_msg'] ) ) : '';
			$error = isset( $_GET['mfu_err'] ) ? sanitize_text_field( wp_unslash( $_GET['mfu_err'] ) ) : '';
			$post_id = isset( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;
			$update_id = isset( $_GET['update_id'] ) ? (int) $_GET['update_id'] : 0;

			$state = get_transient( $this->get_external_news_transient_key() );
			$state = is_array( $state ) ? $state : array();
			$source_url = isset( $state['url'] ) ? (string) $state['url'] : '';
			$extracted_title = isset( $state['title'] ) ? (string) $state['title'] : '';
			$extracted_text = isset( $state['text'] ) ? (string) $state['text'] : '';
			$extracted_ok = ! empty( $state['ok'] );

			echo '<div class="wrap">';
			echo '<h1>Noticias (URL)</h1>';
			echo '<p>Introduce una URL de una noticia externa. Primero se intentara extraer el contenido automaticamente; si no es posible, podras pegar el texto manualmente. Luego puedes crear un borrador reescrito o preparar una actualizacion de un festival.</p>';

			if ( $error !== '' ) {
				echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
			}
			if ( $message !== '' ) {
				echo '<div class="notice notice-success"><p>' . esc_html( $message ) . '</p></div>';
			}

			if ( $post_id > 0 ) {
				$edit = get_edit_post_link( $post_id, '' );
				if ( $edit ) {
					echo '<p><a class="button button-primary" href="' . esc_url( $edit ) . '" target="_blank" rel="noopener">Editar borrador generado</a></p>';
				}
			}
			if ( $update_id > 0 ) {
				$view_url = admin_url( 'admin.php?page=mfu-updates&update_id=' . $update_id );
				echo '<p><a class="button button-primary" href="' . esc_url( $view_url ) . '" target="_blank" rel="noopener">Ver cambios del festival</a></p>';
			}

			echo '<hr />';

			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="max-width:980px;">';
			wp_nonce_field( 'mfu_external_news_check', '_mfu_nonce' );
			echo '<input type="hidden" name="action" value="mfu_external_news_check" />';
			echo '<table class="form-table"><tbody>';
			echo '<tr>';
			echo '<th scope="row"><label for="mfu_external_news_url">URL de la noticia</label></th>';
			echo '<td><input type="url" class="regular-text" style="width:100%; max-width:760px;" id="mfu_external_news_url" name="news_url" placeholder="https://..." value="' . esc_attr( $source_url ) . '" required />';
			echo '<p class="description">Pulsa "Comprobar" para intentar extraer el contenido automaticamente.</p>';
			echo '<p><button type="submit" class="button">Comprobar URL</button></p>';
			echo '</td>';
			echo '</tr>';
			echo '</tbody></table>';
			echo '</form>';

			echo '<hr />';

			if ( $source_url === '' ) {
				echo '</div>';
				return;
			}

			if ( $extracted_ok ) {
				echo '<div class="notice notice-success"><p>Contenido extraido correctamente.</p></div>';
				if ( $extracted_title !== '' ) {
					echo '<p><strong>Titulo detectado:</strong> ' . esc_html( $extracted_title ) . '</p>';
				}
			} else {
				echo '<div class="notice notice-warning"><p>No se pudo extraer el contenido. Pega el texto manualmente en el campo de abajo.</p></div>';
			}

			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="max-width:980px;">';
			wp_nonce_field( 'mfu_external_news_process', '_mfu_nonce' );
			echo '<input type="hidden" name="action" value="mfu_external_news_process" />';
			echo '<input type="hidden" name="news_url" value="' . esc_attr( $source_url ) . '" />';
			echo '<input type="hidden" name="original_title" value="' . esc_attr( $extracted_title ) . '" />';

			echo '<table class="form-table"><tbody>';
			echo '<tr>';
			echo '<th scope="row"><label for="mfu_external_news_text">Contenido</label></th>';
			echo '<td><textarea id="mfu_external_news_text" name="article_text" rows="12" style="width:100%;" placeholder="Si no se pudo extraer automaticamente, pega aqui el texto completo..." required>' . esc_textarea( $extracted_text ) . '</textarea>';
			echo '<p class="description">Puedes editar el texto antes de procesar.</p></td>';
			echo '</tr>';

			echo '<tr>';
			echo '<th scope="row">Accion</th>';
			echo '<td>';
			echo '<label style="display:block; margin:4px 0;"><input type="radio" name="news_mode" value="republish" checked /> Crear entrada en borrador (reescritura SEO)</label>';
			echo '<label style="display:block; margin:4px 0;"><input type="radio" name="news_mode" value="festival_update" /> Actualizacion de festival (preparar cambios)</label>';
			echo '</td>';
			echo '</tr>';

			echo '<tr id="mfu_external_news_festival_row" style="display:none;">';
			echo '<th scope="row"><label for="mfu_external_news_festival_search">Festival</label></th>';
			echo '<td>';
			echo '<input type="hidden" id="mfu_external_news_festival_id" name="festival_id" value="" />';
			echo '<input type="text" class="regular-text" id="mfu_external_news_festival_search" placeholder="Buscar festival..." autocomplete="off" />';
			echo '<div id="mfu_external_news_festival_results" style="margin-top:8px; max-height:220px; overflow:auto; border:1px solid #ccd0d4; background:#fff; display:none;"></div>';
			echo '<p class="description">Selecciona el festival al que hace referencia la noticia.</p>';
			echo '</td>';
			echo '</tr>';

			echo '</tbody></table>';

			echo '<p><button type="submit" class="button button-primary">Procesar noticia</button></p>';
			echo '</form>';

			echo "<script>
			(function(){
				var radios = document.querySelectorAll('input[name=\"news_mode\"]');
				var row = document.getElementById('mfu_external_news_festival_row');
				function refresh(){
					var mode = document.querySelector('input[name=\"news_mode\"]:checked');
					var show = mode && mode.value === 'festival_update';
					if (row) row.style.display = show ? '' : 'none';
				}
				radios.forEach(function(r){ r.addEventListener('change', refresh); });
				refresh();

				// Festival search (AJAX)
				var input = document.getElementById('mfu_external_news_festival_search');
				var results = document.getElementById('mfu_external_news_festival_results');
				var hidden = document.getElementById('mfu_external_news_festival_id');
				if (!input || !results || !hidden) return;
				var timer;
				function render(items){
					if (!items.length) {
						results.style.display = 'none';
						results.innerHTML = '';
						return;
					}
					results.innerHTML = '';
					items.forEach(function(item){
						var btn = document.createElement('button');
						btn.type = 'button';
						btn.className = 'button-link';
						btn.style.display = 'block';
						btn.style.padding = '6px 10px';
						btn.style.textAlign = 'left';
						btn.style.width = '100%';
						btn.textContent = item.title;
						btn.addEventListener('click', function(){
							hidden.value = item.id;
							input.value = item.title;
							results.style.display = 'none';
						});
						results.appendChild(btn);
					});
					results.style.display = 'block';
				}
				input.addEventListener('input', function(){
					var q = input.value.trim();
					hidden.value = '';
					if (q.length < 2) {
						render([]);
						return;
					}
					clearTimeout(timer);
					timer = setTimeout(function(){
						var url = ajaxurl + '?action=mfu_festival_search&q=' + encodeURIComponent(q);
						fetch(url, { credentials: 'same-origin' })
							.then(function(r){ return r.json(); })
							.then(function(data){ render(Array.isArray(data) ? data : []); })
							.catch(function(){ render([]); });
					}, 200);
				});
			})();
			</script>";

			echo '</div>';
		}

		public function handle_external_news_check() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'No permitido' );
			}

			check_admin_referer( 'mfu_external_news_check', '_mfu_nonce' );

			$url = isset( $_POST['news_url'] ) ? esc_url_raw( wp_unslash( $_POST['news_url'] ) ) : '';
			$back = admin_url( 'admin.php?page=mfu-external-news' );
			if ( $url === '' ) {
				wp_safe_redirect( add_query_arg( 'mfu_err', rawurlencode( 'URL invalida.' ), $back ) );
				exit;
			}

			$payload = $this->fetch_external_news_text( $url );
			if ( is_wp_error( $payload ) ) {
				set_transient(
					$this->get_external_news_transient_key(),
					array(
						'ok' => false,
						'url' => $url,
						'title' => '',
						'text' => '',
						'error' => $payload->get_error_message(),
						'at' => time(),
					),
					10 * MINUTE_IN_SECONDS
				);
				wp_safe_redirect( add_query_arg( 'mfu_err', rawurlencode( $payload->get_error_message() ), $back ) );
				exit;
			}

			set_transient(
				$this->get_external_news_transient_key(),
				array(
					'ok' => true,
					'url' => $url,
					'title' => isset( $payload['title'] ) ? (string) $payload['title'] : '',
					'text' => isset( $payload['text'] ) ? (string) $payload['text'] : '',
					'error' => '',
					'at' => time(),
				),
				10 * MINUTE_IN_SECONDS
			);

			wp_safe_redirect( add_query_arg( 'mfu_msg', rawurlencode( 'URL comprobada.' ), $back ) );
			exit;
		}

		private function get_preferred_author_id() {
			$author_id = (int) get_current_user_id();
			$carla = get_user_by( 'login', 'carla' );
			if ( ! $carla ) {
				$carla = get_user_by( 'login', 'Carla' );
			}
			if ( $carla && ! is_wp_error( $carla ) ) {
				return (int) $carla->ID;
			}
			$clara = get_user_by( 'login', 'clara' );
			if ( ! $clara ) {
				$clara = get_user_by( 'login', 'Clara' );
			}
			if ( $clara && ! is_wp_error( $clara ) ) {
				return (int) $clara->ID;
			}
			return $author_id;
		}

		private function get_style_catalog_links() {
			$map = array();
			$terms = get_terms(
				array(
					'taxonomy' => 'estilo_musical',
					'hide_empty' => false,
				)
			);
			if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
				return $map;
			}
			foreach ( $terms as $t ) {
				if ( ! isset( $t->slug ) ) {
					continue;
				}
				$slug = sanitize_title( (string) $t->slug );
				if ( $slug === '' ) {
					continue;
				}
				$link = get_term_link( $t );
				if ( is_wp_error( $link ) ) {
					continue;
				}
				$map[ $slug ] = (string) $link;
			}
			return $map;
		}

		public function handle_external_news_process() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'No permitido' );
			}

			check_admin_referer( 'mfu_external_news_process', '_mfu_nonce' );

			$source_url = isset( $_POST['news_url'] ) ? esc_url_raw( wp_unslash( $_POST['news_url'] ) ) : '';
			$article_text = isset( $_POST['article_text'] ) ? wp_unslash( $_POST['article_text'] ) : '';
			$article_text = is_string( $article_text ) ? trim( $article_text ) : '';
			$original_title = isset( $_POST['original_title'] ) ? sanitize_text_field( wp_unslash( $_POST['original_title'] ) ) : '';
			$mode = isset( $_POST['news_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['news_mode'] ) ) : 'republish';
			$festival_id = isset( $_POST['festival_id'] ) ? (int) $_POST['festival_id'] : 0;

			$back = admin_url( 'admin.php?page=mfu-external-news' );

			if ( $source_url === '' ) {
				wp_safe_redirect( add_query_arg( 'mfu_err', rawurlencode( 'URL invalida.' ), $back ) );
				exit;
			}
			if ( $article_text === '' ) {
				wp_safe_redirect( add_query_arg( 'mfu_err', rawurlencode( 'Falta el texto de la noticia.' ), $back ) );
				exit;
			}

			$ai = new MFU_AI();
			if ( ! $ai->has_openai_key() && ! $ai->has_perplexity_key() ) {
				wp_safe_redirect( add_query_arg( 'mfu_err', rawurlencode( 'Falta API key para usar IA.' ), $back ) );
				exit;
			}

			if ( $mode === 'festival_update' ) {
				if ( $festival_id <= 0 || get_post_type( $festival_id ) !== 'festi' ) {
					wp_safe_redirect( add_query_arg( 'mfu_err', rawurlencode( 'Selecciona un festival valido.' ), $back ) );
					exit;
				}
				$festival = get_post( $festival_id );
				if ( ! $festival ) {
					wp_safe_redirect( add_query_arg( 'mfu_err', rawurlencode( 'Festival no encontrado.' ), $back ) );
					exit;
				}

				$current_fields = array(
					'fecha_inicio' => (string) get_post_meta( $festival_id, 'fecha_inicio', true ),
					'fecha_fin' => (string) get_post_meta( $festival_id, 'fecha_fin', true ),
					'mf_artistas' => (string) get_post_meta( $festival_id, 'mf_artistas', true ),
					'mf_web_oficial' => (string) get_post_meta( $festival_id, 'mf_web_oficial', true ),
					'mf_instagram' => (string) get_post_meta( $festival_id, 'mf_instagram', true ),
					'mf_cartel_completo' => (string) get_post_meta( $festival_id, 'mf_cartel_completo', true ),
					'cancelado' => (string) get_post_meta( $festival_id, 'cancelado', true ),
					'sin_fechas_confirmadas' => (string) get_post_meta( $festival_id, 'sin_fechas_confirmadas', true ),
				);
				$edition = (string) get_post_meta( $festival_id, 'edicion', true );

				$payload = $ai->external_news_festival_update_payload(
					(string) $festival->post_title,
					$edition,
					(string) $festival->post_content,
					$current_fields,
					$source_url,
					$article_text
				);
				if ( is_wp_error( $payload ) ) {
					wp_safe_redirect( add_query_arg( 'mfu_err', rawurlencode( $payload->get_error_message() ), $back ) );
					exit;
				}

				$fields = isset( $payload['fields'] ) && is_array( $payload['fields'] ) ? $payload['fields'] : array();
				$updated_content = isset( $payload['updated_content_html'] ) ? (string) $payload['updated_content_html'] : '';
				$summary = isset( $payload['summary'] ) ? (string) $payload['summary'] : '';

				$diffs = array();
				foreach ( array( 'fecha_inicio', 'fecha_fin', 'mf_artistas', 'mf_web_oficial', 'mf_instagram', 'mf_cartel_completo', 'cancelado', 'sin_fechas_confirmadas' ) as $key ) {
					if ( ! array_key_exists( $key, $fields ) ) {
						continue;
					}
					$after = $fields[ $key ];
					if ( $after === null ) {
						continue;
					}
					$after = is_string( $after ) ? trim( $after ) : (string) $after;
					$before = isset( $current_fields[ $key ] ) ? (string) $current_fields[ $key ] : '';
					if ( $after === $before ) {
						continue;
					}
					$diffs[ $key ] = array(
						'before' => $before,
						'after' => $after,
					);
				}
				if ( empty( $diffs ) && $updated_content !== '' ) {
					$current_content = (string) $festival->post_content;
					if ( trim( $updated_content ) !== trim( $current_content ) ) {
						$diffs['content_update'] = array(
							'before' => 'content',
							'after' => 'content',
						);
					}
				}
				if ( empty( $diffs ) ) {
					wp_safe_redirect( add_query_arg( 'mfu_msg', rawurlencode( 'No se detectaron cambios aplicables.' ), $back ) );
					exit;
				}

				global $wpdb;
				$table = MFU_DB::table( 'updates' );
				$evidence = array(
					'facts' => array( 'summary' => $summary ),
					'sources' => array(
						array( 'url' => $source_url, 'title' => $original_title !== '' ? $original_title : 'Noticia externa' ),
					),
					'external_news_url' => $source_url,
					'external_news_text' => mb_substr( $article_text, 0, 20000 ),
					'updated_content' => $updated_content,
				);
				$wpdb->insert(
					$table,
					array(
						'festival_id' => (int) $festival_id,
						'detected_at' => current_time( 'mysql' ),
						'status' => 'pending_review',
						'diffs_json' => wp_json_encode( $diffs ),
						'evidence_json' => wp_json_encode( $evidence ),
						'summary' => $summary,
					),
					array( '%d', '%s', '%s', '%s', '%s', '%s' )
				);
				$update_id = (int) $wpdb->insert_id;
				delete_transient( $this->get_external_news_transient_key() );
				wp_safe_redirect( add_query_arg( array( 'mfu_msg' => rawurlencode( 'Actualizacion preparada.' ), 'update_id' => $update_id ), $back ) );
				exit;
			}

			$author_id = $this->get_preferred_author_id();
			$internal_links = array(
				'agenda' => home_url( '/agenda-festivales/' ),
			);
			$style_catalog = $this->get_style_catalog_links();
			$draft = $ai->rewrite_external_news_to_post( $source_url, $article_text, $style_catalog, $internal_links, $original_title );
			if ( is_wp_error( $draft ) ) {
				wp_safe_redirect( add_query_arg( 'mfu_err', rawurlencode( $draft->get_error_message() ), $back ) );
				exit;
			}

			$title = isset( $draft['title'] ) ? (string) $draft['title'] : '';
			$excerpt = isset( $draft['excerpt'] ) ? (string) $draft['excerpt'] : '';
			$content = isset( $draft['content_html'] ) ? (string) $draft['content_html'] : '';
			$slug = isset( $draft['slug'] ) ? sanitize_title( (string) $draft['slug'] ) : '';
			$yoast_title = isset( $draft['yoast_title'] ) ? (string) $draft['yoast_title'] : '';
			$yoast_desc = isset( $draft['yoast_metadesc'] ) ? (string) $draft['yoast_metadesc'] : '';
			$focus_kw = isset( $draft['focus_keyphrase'] ) ? (string) $draft['focus_keyphrase'] : '';
			$style_slugs = isset( $draft['style_slugs'] ) && is_array( $draft['style_slugs'] ) ? array_map( 'sanitize_title', $draft['style_slugs'] ) : array();

			if ( trim( $title ) === '' || trim( $content ) === '' ) {
				wp_safe_redirect( add_query_arg( 'mfu_err', rawurlencode( 'La IA no devolvio contenido valido.' ), $back ) );
				exit;
			}
			if ( $slug === '' ) {
				$slug = sanitize_title( $title );
			}

			$post_id = wp_insert_post(
				array(
					'post_type' => 'post',
					'post_status' => 'draft',
					'post_title' => $title,
					'post_content' => $content,
					'post_excerpt' => $excerpt,
					'post_author' => $author_id,
					'post_name' => $slug,
				),
				true
			);
			if ( is_wp_error( $post_id ) ) {
				wp_safe_redirect( add_query_arg( 'mfu_err', rawurlencode( $post_id->get_error_message() ), $back ) );
				exit;
			}
			$post_id = (int) $post_id;

			$cat_act = get_term_by( 'slug', 'actualidad', 'category' );
			if ( $cat_act && ! is_wp_error( $cat_act ) ) {
				wp_set_post_terms( $post_id, array( (int) $cat_act->term_id ), 'category', false );
			}
			$cat_uncat = get_term_by( 'slug', 'sin-categorizar', 'category' );
			if ( $cat_uncat && ! is_wp_error( $cat_uncat ) ) {
				wp_remove_object_terms( $post_id, array( (int) $cat_uncat->term_id ), 'category' );
			}

			if ( ! empty( $style_slugs ) ) {
				$valid = array();
				foreach ( $style_slugs as $s ) {
					if ( $s === '' ) {
						continue;
					}
					$term = get_term_by( 'slug', $s, 'estilo_musical' );
					if ( $term && ! is_wp_error( $term ) ) {
						$valid[] = (int) $term->term_id;
					}
				}
				if ( ! empty( $valid ) ) {
					wp_set_post_terms( $post_id, $valid, 'estilo_musical', false );
				}
			}

			if ( $yoast_title !== '' ) {
				update_post_meta( $post_id, '_yoast_wpseo_title', $yoast_title );
			}
			if ( $yoast_desc !== '' ) {
				update_post_meta( $post_id, '_yoast_wpseo_metadesc', $yoast_desc );
			}
			if ( $focus_kw !== '' ) {
				update_post_meta( $post_id, '_yoast_wpseo_focuskw', $focus_kw );
			}
			update_post_meta( $post_id, '_mfu_external_news_url', $source_url );

			delete_transient( $this->get_external_news_transient_key() );
			wp_safe_redirect( add_query_arg( array( 'mfu_msg' => rawurlencode( 'Borrador creado.' ), 'post_id' => $post_id ), $back ) );
			exit;
		}

		public function render_rollover_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$from_year = isset( $_GET['from_year'] ) ? (int) $_GET['from_year'] : (int) current_time( 'Y' );
			if ( $from_year <= 0 ) {
				$from_year = (int) current_time( 'Y' );
			}
			$to_year = $from_year + 1;
			$message = isset( $_GET['mfu_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['mfu_msg'] ) ) : '';
			$error = isset( $_GET['mfu_err'] ) ? sanitize_text_field( wp_unslash( $_GET['mfu_err'] ) ) : '';
			$candidates = $this->get_rollover_candidates( $from_year );
			$preview_rows = get_transient( $this->get_rollover_preview_transient_key() );
			$preview_rows = is_array( $preview_rows ) ? $preview_rows : array();
			$applied_rows = get_transient( $this->get_rollover_applied_transient_key() );
			$applied_rows = is_array( $applied_rows ) ? $applied_rows : array();

			echo '<div class="wrap">';
			echo '<h1>Rollover de edicion</h1>';
			echo '<p>Esta herramienta detecta festivales de la edicion ' . esc_html( (string) $from_year ) . ' ya celebrados y los prepara para la edicion ' . esc_html( (string) $to_year ) . '.</p>';
			echo '<p><strong>Acciones que aplica:</strong> cambia edicion a ' . esc_html( (string) $to_year ) . ', limpia fechas y cartel de la nueva edicion, activa "sin fechas confirmadas", y conserva el contexto anterior en contenido ("Asi fue ' . esc_html( (string) $from_year ) . '").</p>';

			if ( $error !== '' ) {
				echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
			}
			if ( $message !== '' ) {
				echo '<div class="notice notice-success"><p>' . esc_html( $message ) . '</p></div>';
			}

			echo '<form method="get" action="' . esc_url( admin_url( 'admin.php' ) ) . '" style="margin:14px 0;">';
			echo '<input type="hidden" name="page" value="mfu-rollover" />';
			echo '<label for="mfu_rollover_from_year"><strong>Edicion origen:</strong></label> ';
			echo '<input type="number" min="2020" max="2100" id="mfu_rollover_from_year" name="from_year" value="' . esc_attr( (string) $from_year ) . '" style="width:100px;" /> ';
			echo '<button type="submit" class="button">Refrescar</button>';
			echo '</form>';

			if ( empty( $candidates ) ) {
				echo '<p><em>No hay festivales candidatos para rollover en la edicion ' . esc_html( (string) $from_year ) . '.</em></p>';
				if ( ! empty( $preview_rows ) ) {
					delete_transient( $this->get_rollover_preview_transient_key() );
				}
				echo '</div>';
				return;
			}

			if ( ! empty( $applied_rows ) ) {
				echo '<h2>Rollover aplicado</h2>';
				echo '<p>Festivales actualizados en la ultima ejecucion.</p>';
				echo '<ul style="margin-bottom:14px;">';
				foreach ( $applied_rows as $row ) {
					if ( ! is_array( $row ) ) {
						continue;
					}
					$title = isset( $row['title'] ) ? (string) $row['title'] : '';
					$edit_link = isset( $row['edit_link'] ) ? (string) $row['edit_link'] : '';
					$view_link = isset( $row['view_link'] ) ? (string) $row['view_link'] : '';
					echo '<li>';
					echo '<strong>' . esc_html( $title ) . '</strong>';
					if ( $edit_link !== '' ) {
						echo ' - <a href="' . esc_url( $edit_link ) . '" target="_blank" rel="noopener">Ver festival (admin)</a>';
					}
					if ( $view_link !== '' ) {
						echo ' | <a href="' . esc_url( $view_link ) . '" target="_blank" rel="noopener">Ver festival (web)</a>';
					}
					echo '</li>';
				}
				echo '</ul>';
			}

			if ( ! empty( $preview_rows ) ) {
				echo '<h2>Simulacion de cambios (preview)</h2>';
				echo '<p>Esto es una vista previa. Aun no se han aplicado cambios.</p>';
				echo '<table class="widefat striped" style="margin-bottom:14px;"><thead><tr>';
				echo '<th>Festival</th>';
				echo '<th>Edicion</th>';
				echo '<th>Fechas</th>';
				echo '<th>Artistas/Cartel</th>';
				echo '<th>Sin fechas</th>';
				echo '</tr></thead><tbody>';
				foreach ( $preview_rows as $row ) {
					if ( ! is_array( $row ) ) {
						continue;
					}
					$title = isset( $row['title'] ) ? (string) $row['title'] : '';
					$edit_link = isset( $row['edit_link'] ) ? (string) $row['edit_link'] : '';
					$before = isset( $row['before'] ) && is_array( $row['before'] ) ? $row['before'] : array();
					$after = isset( $row['after'] ) && is_array( $row['after'] ) ? $row['after'] : array();
					echo '<tr>';
					echo '<td><strong>' . esc_html( $title ) . '</strong>';
					if ( $edit_link !== '' ) {
						echo '<br /><a href="' . esc_url( $edit_link ) . '" target="_blank" rel="noopener">Editar ficha</a>';
					}
					echo '</td>';
					echo '<td>' . esc_html( (string) ( $before['edicion'] ?? '' ) ) . ' -> <strong>' . esc_html( (string) ( $after['edicion'] ?? '' ) ) . '</strong></td>';
					$before_start = $this->format_date_value( (string) ( $before['fecha_inicio'] ?? '' ) );
					$before_end = $this->format_date_value( (string) ( $before['fecha_fin'] ?? '' ) );
					$after_start = $this->format_date_value( (string) ( $after['fecha_inicio'] ?? '' ) );
					$after_end = $this->format_date_value( (string) ( $after['fecha_fin'] ?? '' ) );
					echo '<td>';
					echo 'Inicio: ' . esc_html( (string) $before_start ) . ' -> <strong>' . esc_html( (string) $after_start ) . '</strong><br />';
					echo 'Fin: ' . esc_html( (string) $before_end ) . ' -> <strong>' . esc_html( (string) $after_end ) . '</strong>';
					echo '</td>';
					echo '<td>';
					echo 'Artistas: ' . ( trim( (string) ( $before['mf_artistas'] ?? '' ) ) !== '' ? 'SI' : 'NO' ) . ' -> <strong>NO</strong><br />';
					echo 'Cartel: ' . ( trim( (string) ( $before['mf_cartel_completo'] ?? '' ) ) !== '' ? 'SI' : 'NO' ) . ' -> <strong>NO</strong>';
					echo '</td>';
					echo '<td>' . esc_html( (string) ( $before['sin_fechas_confirmadas'] ?? '' ) ) . ' -> <strong>' . esc_html( (string) ( $after['sin_fechas_confirmadas'] ?? '' ) ) . '</strong></td>';
					echo '</tr>';
				}
				echo '</tbody></table>';
			}

			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'mfu_rollover_prepare', '_mfu_nonce' );
			echo '<input type="hidden" name="action" value="mfu_rollover_prepare" />';
			echo '<input type="hidden" name="from_year" value="' . esc_attr( (string) $from_year ) . '" />';
			echo '<input type="hidden" name="to_year" value="' . esc_attr( (string) $to_year ) . '" />';

			echo '<p><label><input type="checkbox" id="mfu_rollover_select_all" checked /> Seleccionar todos (' . (int) count( $candidates ) . ')</label></p>';
			echo '<table class="widefat striped"><thead><tr>';
			echo '<th style="width:40px;"></th>';
			echo '<th>Festival</th>';
			echo '<th>Edicion</th>';
			echo '<th>Fecha inicio</th>';
			echo '<th>Fecha fin</th>';
			echo '<th>Estado</th>';
			echo '</tr></thead><tbody>';
			foreach ( $candidates as $item ) {
				echo '<tr>';
				echo '<td><input type="checkbox" class="mfu_rollover_item" name="festival_ids[]" value="' . (int) $item['id'] . '" checked /></td>';
				echo '<td>';
				echo '<strong>' . esc_html( $item['title'] ) . '</strong>';
				if ( ! empty( $item['link'] ) ) {
					echo '<br /><a href="' . esc_url( $item['link'] ) . '" target="_blank" rel="noopener">Editar ficha</a>';
				}
				echo '</td>';
				echo '<td>' . esc_html( (string) $item['edition'] ) . '</td>';
				echo '<td>' . esc_html( (string) $this->format_date_value( (string) $item['fecha_inicio'] ) ) . '</td>';
				echo '<td>' . esc_html( (string) $this->format_date_value( (string) $item['fecha_fin'] ) ) . '</td>';
				echo '<td>' . esc_html( (string) $item['status'] ) . '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';

			echo '<p style="margin-top:12px;">';
			echo '<button type="submit" name="rollover_mode" value="preview" class="button">Simular cambios</button> ';
			echo '<button type="submit" name="rollover_mode" value="apply" class="button button-primary">Aplicar rollover a seleccionados</button>';
			echo '</p>';
			echo '</form>';

			echo "<script>
			(function(){
				var all = document.getElementById('mfu_rollover_select_all');
				var items = document.querySelectorAll('.mfu_rollover_item');
				if (!all || !items.length) return;
				all.addEventListener('change', function(){
					items.forEach(function(i){ i.checked = all.checked; });
				});
			})();
			</script>";

			echo '</div>';
		}

		private function apply_rollover_to_festival( $festival_id, $from_year, $to_year ) {
			$festival_id = (int) $festival_id;
			if ( $festival_id <= 0 ) {
				return false;
			}
			$festival = get_post( $festival_id );
			if ( ! $festival || $festival->post_type !== 'festi' ) {
				return false;
			}

			$old_edition = trim( (string) get_post_meta( $festival_id, 'edicion', true ) );
			$old_fecha_inicio = trim( (string) get_post_meta( $festival_id, 'fecha_inicio', true ) );
			$old_fecha_fin = trim( (string) get_post_meta( $festival_id, 'fecha_fin', true ) );
			$old_artistas = trim( (string) get_post_meta( $festival_id, 'mf_artistas', true ) );
			$old_cartel = trim( (string) get_post_meta( $festival_id, 'mf_cartel_completo', true ) );
			$old_content = (string) $festival->post_content;

			update_post_meta( $festival_id, 'mfu_prev_edition_label', $from_year > 0 ? (string) $from_year : $old_edition );
			update_post_meta( $festival_id, 'mfu_prev_fecha_inicio', $old_fecha_inicio );
			update_post_meta( $festival_id, 'mfu_prev_fecha_fin', $old_fecha_fin );
			update_post_meta( $festival_id, 'mfu_prev_artistas', $old_artistas );
			update_post_meta( $festival_id, 'mfu_prev_cartel_completo', $old_cartel );
			update_post_meta( $festival_id, 'mfu_rollover_at', current_time( 'mysql' ) );

			update_post_meta( $festival_id, 'edicion', (string) $to_year );
			update_post_meta( $festival_id, 'fecha_inicio', '' );
			update_post_meta( $festival_id, 'fecha_fin', '' );
			update_post_meta( $festival_id, 'mf_artistas', '' );
			update_post_meta( $festival_id, 'mf_cartel_completo', '' );
			update_post_meta( $festival_id, 'sin_fechas_confirmadas', '1' );

			$content = $this->rewrite_rollover_content(
				$festival_id,
				$festival,
				$from_year,
				$to_year,
				$old_content,
				array(
					'edicion' => $old_edition,
					'fecha_inicio' => $old_fecha_inicio,
					'fecha_fin' => $old_fecha_fin,
					'mf_artistas' => $old_artistas,
					'mf_cartel_completo' => $old_cartel,
				)
			);

			$yoast_title = $this->build_rollover_yoast_title( $festival_id, (string) $festival->post_title, (int) $to_year );
			$yoast_desc = $this->build_rollover_yoast_description( $festival_id, (string) $festival->post_title, (int) $from_year, (int) $to_year );
			if ( $yoast_title !== '' ) {
				update_post_meta( $festival_id, '_yoast_wpseo_title', $yoast_title );
			}
			if ( $yoast_desc !== '' ) {
				update_post_meta( $festival_id, '_yoast_wpseo_metadesc', $yoast_desc );
			}

			wp_update_post(
				array(
					'ID' => $festival_id,
					'post_content' => $content,
				)
			);

			return true;
		}

		private function rewrite_rollover_content( $festival_id, $festival, $from_year, $to_year, $old_content, $old_fields ) {
			$festival_id = (int) $festival_id;
			$title = is_object( $festival ) ? (string) $festival->post_title : '';
			$from_year = (int) $from_year;
			$to_year = (int) $to_year;
			$old_content = preg_replace( '/<!-- mfu_no_dates_start -->(.*?)<!-- mfu_no_dates_end -->/s', '', (string) $old_content );
			$old_content = trim( (string) $old_content );
			$old_fields = is_array( $old_fields ) ? $old_fields : array();

			$ai = new MFU_AI();
			if ( $ai->has_key() ) {
				$diffs = array(
					'edicion' => array(
						'before' => (string) ( $old_fields['edicion'] ?? '' ),
						'after' => (string) $to_year,
					),
					'fecha_inicio' => array(
						'before' => (string) ( $old_fields['fecha_inicio'] ?? '' ),
						'after' => '',
					),
					'fecha_fin' => array(
						'before' => (string) ( $old_fields['fecha_fin'] ?? '' ),
						'after' => '',
					),
					'mf_artistas' => array(
						'before' => (string) ( $old_fields['mf_artistas'] ?? '' ),
						'after' => '',
					),
					'mf_cartel_completo' => array(
						'before' => (string) ( $old_fields['mf_cartel_completo'] ?? '' ),
						'after' => '',
					),
					'sin_fechas_confirmadas' => array(
						'before' => '0',
						'after' => '1',
					),
				);
				$evidence_list = array(
					'Rollover interno de edicion: el festival ya se celebro en ' . $from_year . ' y la ficha pasa a ' . $to_year . '.',
					'Debe mantener contexto util de la edicion ' . $from_year . ' (fechas y artistas si existen), sin mezclarlo como datos confirmados de ' . $to_year . '.',
					'No hay fechas/cartel confirmados para ' . $to_year . ' salvo que se indique expresamente en fuentes oficiales.',
				);
				if ( ! empty( $old_fields['fecha_inicio'] ) || ! empty( $old_fields['fecha_fin'] ) ) {
					$evidence_list[] = 'Fechas historicas ' . $from_year . ': ' . (string) ( $old_fields['fecha_inicio'] ?? '' ) . ' - ' . (string) ( $old_fields['fecha_fin'] ?? '' ) . '.';
				}
				if ( ! empty( $old_fields['mf_artistas'] ) ) {
					$evidence_list[] = 'Artistas historicos ' . $from_year . ': ' . (string) $old_fields['mf_artistas'] . '.';
				}

				$ai_content = $ai->rewrite_festival_content(
					$title,
					(string) $to_year,
					$old_content,
					$diffs,
					$evidence_list
				);
				if ( ! is_wp_error( $ai_content ) ) {
					$ai_content = $this->sanitize_ai_content( (string) $ai_content );
					$ai_content = $this->strip_ticket_links( $ai_content );
					$ai_content = $this->strip_ticket_mentions( $ai_content );
					$ai_content = trim( (string) $ai_content );
					if ( $ai_content !== '' && ! $this->has_rollover_content_red_flags( $ai_content ) ) {
						return $this->ensure_min_word_count( $festival_id, $ai_content );
					}
				}
			}

			$fallback = $this->build_rollover_fallback_content( $festival_id, $title, $from_year, $to_year, $old_fields );
			return $this->ensure_min_word_count( $festival_id, $fallback );
		}

		private function has_rollover_content_red_flags( $content ) {
			$content = strtolower( wp_strip_all_tags( (string) $content ) );
			$red_flags = array(
				'es la ficha de la proxima edicion',
				'es la ficha de la próxima edición',
				'esta ficha es la ficha',
				'la ficha de la proxima edicion del',
				'la ficha de la próxima edición del',
			);
			foreach ( $red_flags as $flag ) {
				if ( strpos( $content, $flag ) !== false ) {
					return true;
				}
			}
			return false;
		}

		private function build_rollover_fallback_content( $festival_id, $title, $from_year, $to_year, $old_fields ) {
			$title = trim( (string) $title );
			$from_year = (int) $from_year;
			$to_year = (int) $to_year;
			$old_fields = is_array( $old_fields ) ? $old_fields : array();
			$fecha_inicio = $this->format_date_value( (string) ( $old_fields['fecha_inicio'] ?? '' ) );
			$fecha_fin = $this->format_date_value( (string) ( $old_fields['fecha_fin'] ?? '' ) );
			$artistas = trim( (string) ( $old_fields['mf_artistas'] ?? '' ) );
			$localidad = $this->get_taxonomy_list( $festival_id, 'localidad' );
			$estilos = $this->get_taxonomy_list( $festival_id, 'estilo_musical' );

			$html  = '<p><strong>' . esc_html( $title ) . ' ' . esc_html( (string) $to_year ) . '</strong> entra en fase de seguimiento para la nueva edicion. ';
			$html .= 'Por ahora no hay una programacion oficial cerrada para ' . esc_html( (string) $to_year ) . ', asi que esta ficha queda preparada para integrar fechas, cartel y novedades en cuanto se publiquen.</p>';
			$html .= '<h2>Edicion ' . esc_html( (string) $to_year ) . ': estado actual</h2>';
			$html .= '<p>La organizacion todavia no ha confirmado de forma oficial ni el calendario completo ni el cartel final de la edicion ' . esc_html( (string) $to_year ) . '. ';
			$html .= 'Durante esta fase de transicion, el objetivo de la ficha es separar con claridad la informacion historica de la informacion nueva para evitar confusiones.</p>';
			$html .= '<p>En cuanto haya comunicacion oficial sobre preventa, anuncio de fechas o primeras confirmaciones artisticas, actualizaremos este contenido y los campos estructurados de la ficha para reflejar la edicion ' . esc_html( (string) $to_year ) . ' de forma precisa.</p>';
			$html .= '<h2>Asi fue ' . esc_html( (string) $from_year ) . '</h2>';
			if ( $fecha_inicio !== '' || $fecha_fin !== '' ) {
				$html .= '<p>La edicion ' . esc_html( (string) $from_year ) . ' se desarrollo ';
				if ( $fecha_inicio !== '' && $fecha_fin !== '' ) {
					$html .= 'entre el <strong>' . esc_html( $fecha_inicio ) . '</strong> y el <strong>' . esc_html( $fecha_fin ) . '</strong>.';
				} elseif ( $fecha_inicio !== '' ) {
					$html .= 'a partir del <strong>' . esc_html( $fecha_inicio ) . '</strong>.';
				} else {
					$html .= 'con fecha de cierre el <strong>' . esc_html( $fecha_fin ) . '</strong>.';
				}
				$html .= '</p>';
			} else {
				$html .= '<p>No disponemos de fechas historicas cerradas para documentar con detalle la edicion ' . esc_html( (string) $from_year ) . ' en este momento.</p>';
			}

			if ( $artistas !== '' ) {
				$list = array_filter( array_map( 'trim', preg_split( '/[,;]+/', $artistas ) ) );
				if ( ! empty( $list ) ) {
					$html .= '<h3>Artistas destacados en ' . esc_html( (string) $from_year ) . '</h3><ul>';
					foreach ( array_slice( $list, 0, 24 ) as $artist ) {
						$html .= '<li><strong>' . esc_html( $artist ) . '</strong></li>';
					}
					$html .= '</ul>';
				}
			}

			$html .= '<h2>Contexto del festival y previsiones para ' . esc_html( (string) $to_year ) . '</h2>';
			$html .= '<p>';
			if ( $localidad !== '' ) {
				$html .= '<strong>' . esc_html( $title ) . '</strong> mantiene su vinculacion con <strong>' . esc_html( $localidad ) . '</strong>. ';
			}
			if ( $estilos !== '' ) {
				$html .= 'En terminos artistico-editoriales, el festival se mueve en torno a <strong>' . esc_html( $estilos ) . '</strong>, una linea que suele marcar el tipo de cartel y de publico. ';
			}
			$html .= 'Hasta que se confirmen datos concretos de ' . esc_html( (string) $to_year ) . ', esta ficha se ira actualizando por bloques para conservar una lectura clara y util.</p>';
			$html .= '<p>Si estas planificando asistencia para la siguiente edicion, lo mas razonable es seguir los canales oficiales del evento y revisar periodicamente esta pagina. ';
			$html .= 'Nuestro criterio editorial en esta fase es no adelantar informacion no confirmada: cuando haya anuncio oficial de fechas o cartel, lo integraremos en la ficha con prioridad.</p>';
			return $html;
		}

		private function build_rollover_yoast_title( $festival_id, $title, $to_year ) {
			$title = trim( (string) $title );
			$to_year = (int) $to_year;
			$localidad = $this->get_taxonomy_list( $festival_id, 'localidad' );
			$base = $title . ' ' . $to_year;
			if ( $localidad !== '' ) {
				$base .= ' en ' . $localidad;
			}
			$meta_title = $base . ' | Fechas, cartel y entradas';
			if ( function_exists( 'mb_substr' ) ) {
				$meta_title = mb_substr( $meta_title, 0, 60 );
			} else {
				$meta_title = substr( $meta_title, 0, 60 );
			}
			return trim( $meta_title );
		}

		private function build_rollover_yoast_description( $festival_id, $title, $from_year, $to_year ) {
			$title = trim( (string) $title );
			$from_year = (int) $from_year;
			$to_year = (int) $to_year;
			$localidad = $this->get_taxonomy_list( $festival_id, 'localidad' );
			$desc = 'Consulta la ficha de ' . $title . ' ' . $to_year . ': estado de fechas, cartel y novedades. ';
			if ( $localidad !== '' ) {
				$desc .= 'Festival en ' . $localidad . '. ';
			}
			$desc .= 'Incluye contexto de la edicion ' . $from_year . ' y actualizaciones oficiales en cuanto se publiquen.';
			if ( function_exists( 'mb_substr' ) ) {
				$desc = mb_substr( $desc, 0, 155 );
			} else {
				$desc = substr( $desc, 0, 155 );
			}
			return trim( $desc );
		}

		public function handle_rollover_prepare() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'No permitido' );
			}
			check_admin_referer( 'mfu_rollover_prepare', '_mfu_nonce' );

			$from_year = isset( $_POST['from_year'] ) ? (int) $_POST['from_year'] : (int) current_time( 'Y' );
			$to_year = isset( $_POST['to_year'] ) ? (int) $_POST['to_year'] : ( $from_year + 1 );
			$mode = isset( $_POST['rollover_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['rollover_mode'] ) ) : 'apply';
			$ids = isset( $_POST['festival_ids'] ) ? (array) $_POST['festival_ids'] : array();
			$ids = array_values( array_unique( array_map( 'intval', $ids ) ) );
			$ids = array_filter( $ids );

			$back = admin_url( 'admin.php?page=mfu-rollover&from_year=' . $from_year );
			if ( empty( $ids ) ) {
				wp_safe_redirect( add_query_arg( 'mfu_err', rawurlencode( 'No seleccionaste festivales para el rollover.' ), $back ) );
				exit;
			}

			if ( $mode === 'preview' ) {
				$preview_rows = array();
				foreach ( $ids as $festival_id ) {
					$row = $this->build_rollover_preview_row( (int) $festival_id, $from_year, $to_year );
					if ( is_array( $row ) ) {
						$preview_rows[] = $row;
					}
				}
				set_transient( $this->get_rollover_preview_transient_key(), $preview_rows, 10 * MINUTE_IN_SECONDS );
				wp_safe_redirect( add_query_arg( 'mfu_msg', rawurlencode( 'Simulacion generada. Revisa la tabla antes de aplicar.' ), $back ) );
				exit;
			}

			$ok = 0;
			$applied_rows = array();
			foreach ( $ids as $festival_id ) {
				$festival_id = (int) $festival_id;
				if ( $this->apply_rollover_to_festival( $festival_id, $from_year, $to_year ) ) {
					$ok++;
					$applied_rows[] = array(
						'id' => $festival_id,
						'title' => (string) get_the_title( $festival_id ),
						'edit_link' => (string) get_edit_post_link( $festival_id, '' ),
						'view_link' => (string) get_permalink( $festival_id ),
					);
				}
			}
			delete_transient( $this->get_rollover_preview_transient_key() );
			set_transient( $this->get_rollover_applied_transient_key(), $applied_rows, 20 * MINUTE_IN_SECONDS );

			wp_safe_redirect( add_query_arg( 'mfu_msg', rawurlencode( 'Rollover aplicado: ' . $ok . ' festivales preparados para ' . $to_year . '.' ), $back ) );
			exit;
		}

		public function render_add_festival_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
		}

		$notice = isset( $_GET['mfu_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['mfu_msg'] ) ) : '';
		$error = isset( $_GET['mfu_error'] ) ? sanitize_text_field( wp_unslash( $_GET['mfu_error'] ) ) : '';
		$edit_id = isset( $_GET['festival_id'] ) ? (int) $_GET['festival_id'] : 0;

		echo '<div class="wrap">';
		echo '<h1>Anadir festival</h1>';
		if ( $error !== '' ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
		}
		if ( $notice !== '' ) {
			$link_html = '';
			if ( $edit_id > 0 ) {
				$edit_url = get_edit_post_link( $edit_id, 'raw' );
				if ( $edit_url ) {
					$link_html = ' <a href="' . esc_url( $edit_url ) . '">Abrir festival</a>';
				}
			}
			echo '<div class="notice notice-success"><p>' . esc_html( $notice ) . $link_html . '</p></div>';
		}
		if ( $edit_id > 0 ) {
			$edit_url = get_edit_post_link( $edit_id, 'raw' );
			if ( $edit_url ) {
				echo '<p><a class="button button-primary" href="' . esc_url( $edit_url ) . '">Editar festival</a></p>';
			}
		}

		wp_enqueue_media();

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'mfu_add_festival', '_mfu_nonce' );
		echo '<input type="hidden" name="action" value="mfu_add_festival" />';
		echo '<table class="form-table"><tbody>';
		echo '<tr>';
		echo '<th scope="row"><label for="mfu_festival_name">Nombre del festival</label></th>';
		echo '<td><input type="text" class="regular-text" id="mfu_festival_name" name="festival_name" required /></td>';
		echo '</tr>';
		echo '<tr>';
		echo '<th scope="row"><label for="mfu_festival_edition">Edicion</label></th>';
		echo '<td><input type="text" class="regular-text" id="mfu_festival_edition" name="festival_edition" placeholder="Ej: 2026" />';
		echo '<p class="description">Se usa para mejorar la busqueda IA: nombre + edicion.</p></td>';
		echo '</tr>';
			echo '<tr>';
			echo '<th scope="row"><label for="mfu_ticket_url">URL de entradas</label></th>';
			echo '<td><input type="url" class="regular-text" id="mfu_ticket_url" name="ticket_url" placeholder="https://..." /></td>';
			echo '</tr>';
			$localidad_options = $this->get_localidad_terms_for_select();
			echo '<tr>';
			echo '<th scope="row"><label for="mfu_localidad_term_id">Localidad</label></th>';
			echo '<td>';
			echo '<input type="text" id="mfu_localidad_search" class="regular-text" placeholder="Buscar localidad..." style="margin-bottom:8px;" />';
			echo '<select id="mfu_localidad_term_id" name="localidad_term_id" class="regular-text">';
			echo '<option value="">Seleccionar localidad existente...</option>';
			foreach ( $localidad_options as $opt ) {
				echo '<option value="' . (int) $opt['id'] . '">' . esc_html( $opt['label'] ) . '</option>';
			}
			echo '</select>';
			echo '<p class="description">Selecciona una localidad existente o crea una nueva abajo.</p>';
			echo '</td>';
			echo '</tr>';
			echo '<tr>';
			echo '<th scope="row"><label for="mfu_localidad_new">Nueva localidad</label></th>';
			echo '<td><input type="text" class="regular-text" id="mfu_localidad_new" name="localidad_new" placeholder="Ej: Arriondas" />';
			echo '<p class="description">Si completas este campo, se creará en la taxonomía <code>localidad</code>.</p></td>';
			echo '</tr>';
			echo '<tr>';
			echo '<th scope="row"><label for="mfu_localidad_parent_term_id">Localidad padre</label></th>';
			echo '<td>';
			echo '<input type="text" id="mfu_localidad_parent_search" class="regular-text" placeholder="Buscar localidad padre..." style="margin-bottom:8px;" />';
			echo '<select id="mfu_localidad_parent_term_id" name="localidad_parent_term_id" class="regular-text">';
			echo '<option value="">Sin padre</option>';
			foreach ( $localidad_options as $opt ) {
				echo '<option value="' . (int) $opt['id'] . '">' . esc_html( $opt['label'] ) . '</option>';
			}
			echo '</select>';
			echo '<p class="description">Se usa al crear una nueva localidad.</p>';
			echo '</td>';
			echo '</tr>';
			echo '<tr>';
			echo '<th scope="row"><label for="mfu_fecha_inicio">Fecha de inicio</label></th>';
			echo '<td><input type="date" id="mfu_fecha_inicio" name="fecha_inicio" /></td>';
			echo '</tr>';
			echo '<tr>';
			echo '<th scope="row"><label for="mfu_fecha_fin">Fecha fin</label></th>';
			echo '<td><input type="date" id="mfu_fecha_fin" name="fecha_fin" /></td>';
			echo '</tr>';
			echo '<tr>';
			echo '<th scope="row">Imagen destacada</th>';
			echo '<td>';
		echo '<input type="hidden" id="mfu_featured_image_id" name="featured_image_id" value="" />';
		echo '<button type="button" class="button" id="mfu_featured_image_btn">Seleccionar imagen</button>';
		echo '<div id="mfu_featured_image_preview" style="margin-top:10px;"></div>';
		echo '</td>';
		echo '</tr>';
			echo '</tbody></table>';
			echo '<p><label><input type="checkbox" name="use_ai" value="1" checked /> Completar datos con IA</label></p>';
			echo '<p style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">';
			echo '<button type="button" class="button" id="mfu_ai_prefill_btn">Autocompletar localidad y fechas con IA</button>';
			echo '<span id="mfu_ai_prefill_status" style="color:#50575e;"></span>';
			echo '</p>';
			echo '<p><button type="submit" class="button button-primary">Crear festival</button></p>';
			echo '</form>';

				echo "<script>
				(function(){
					var btn = document.getElementById('mfu_featured_image_btn');
					var idInput = document.getElementById('mfu_featured_image_id');
					var preview = document.getElementById('mfu_featured_image_preview');
					var localidadSearch = document.getElementById('mfu_localidad_search');
					var localidadSelect = document.getElementById('mfu_localidad_term_id');
					var parentSearch = document.getElementById('mfu_localidad_parent_search');
					var parentSelect = document.getElementById('mfu_localidad_parent_term_id');
					var aiPrefillBtn = document.getElementById('mfu_ai_prefill_btn');
					var aiPrefillStatus = document.getElementById('mfu_ai_prefill_status');
					var nameInput = document.getElementById('mfu_festival_name');
					var editionInput = document.getElementById('mfu_festival_edition');
					var dateStartInput = document.getElementById('mfu_fecha_inicio');
					var dateEndInput = document.getElementById('mfu_fecha_fin');
					var localidadNewInput = document.getElementById('mfu_localidad_new');
					var prefillNonce = '" . esc_js( wp_create_nonce( 'mfu_ai_prefill_festival' ) ) . "';

				function attachSelectSearch(input, select) {
					if (!input || !select) return;
					input.addEventListener('input', function(){
						var q = (input.value || '').toLowerCase().trim();
						var options = select.options;
						for (var i = 0; i < options.length; i++) {
							var opt = options[i];
							if (opt.value === '') {
								opt.hidden = false;
								continue;
							}
							var text = (opt.textContent || '').toLowerCase();
							opt.hidden = q !== '' && text.indexOf(q) === -1;
						}
					});
				}

					attachSelectSearch(localidadSearch, localidadSelect);
					attachSelectSearch(parentSearch, parentSelect);
					if (aiPrefillBtn) {
						aiPrefillBtn.addEventListener('click', function(e){
							e.preventDefault();
							var festivalName = nameInput ? (nameInput.value || '').trim() : '';
							if (!festivalName) {
								if (aiPrefillStatus) aiPrefillStatus.textContent = 'Indica primero el nombre del festival.';
								return;
							}
							aiPrefillBtn.disabled = true;
							if (aiPrefillStatus) aiPrefillStatus.textContent = 'Buscando y completando datos...';
							var data = new URLSearchParams();
							data.append('action', 'mfu_ai_prefill_festival');
							data.append('_ajax_nonce', prefillNonce);
							data.append('festival_name', festivalName);
							data.append('festival_edition', editionInput ? (editionInput.value || '').trim() : '');
							fetch(ajaxurl, {
								method: 'POST',
								headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
								credentials: 'same-origin',
								body: data.toString()
							}).then(function(res){ return res.json(); }).then(function(json){
								aiPrefillBtn.disabled = false;
								if (!json || !json.success) {
									var msg = (json && json.data && json.data.message) ? json.data.message : 'No se pudieron completar datos.';
									if (aiPrefillStatus) aiPrefillStatus.textContent = msg;
									return;
								}
								var p = json.data || {};
								if (dateStartInput && p.fecha_inicio) dateStartInput.value = p.fecha_inicio;
								if (dateEndInput && p.fecha_fin) dateEndInput.value = p.fecha_fin;
								if (localidadSelect) {
									if (p.localidad_term_id) {
										localidadSelect.value = String(p.localidad_term_id);
										if (localidadNewInput) localidadNewInput.value = '';
									} else {
										localidadSelect.value = '';
									}
								}
								if (localidadNewInput && p.localidad_new) {
									localidadNewInput.value = p.localidad_new;
								}
								if (parentSelect && p.localidad_parent_term_id) {
									parentSelect.value = String(p.localidad_parent_term_id);
								}
								if (aiPrefillStatus) aiPrefillStatus.textContent = p.message || 'Datos completados. Revisa y ajusta antes de crear.';
							}).catch(function(){
								aiPrefillBtn.disabled = false;
								if (aiPrefillStatus) aiPrefillStatus.textContent = 'Error de red al autocompletar.';
							});
						});
					}
					if (!btn || !idInput || !preview) return;
				var frame;
				btn.addEventListener('click', function(e){
				e.preventDefault();
				if (frame) { frame.open(); return; }
				frame = wp.media({
					title: 'Seleccionar imagen',
					button: { text: 'Usar imagen' },
					multiple: false
				});
				frame.on('select', function(){
					var attachment = frame.state().get('selection').first().toJSON();
					idInput.value = attachment.id;
					preview.innerHTML = '<img src=\"' + attachment.url + '\" style=\"max-width:240px; height:auto;\" />';
				});
				frame.open();
			});
		})();
		</script>";

		echo '</div>';
	}

	public function handle_news_update() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sin permisos.' );
		}
		$nonce = isset( $_POST['_mfu_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_mfu_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'mfu_news_update' ) ) {
			wp_die( 'Nonce invalido.' );
		}
		$url = isset( $_POST['news_url'] ) ? esc_url_raw( wp_unslash( $_POST['news_url'] ) ) : '';
		if ( $url === '' ) {
			wp_safe_redirect( admin_url( 'admin.php?page=mfu-news-update&mfu_error=URL%20invalida' ) );
			return;
		}

		$processor = new MFU_Processor();
		$result = $processor->process_news_url( $url );
		if ( is_wp_error( $result ) ) {
			$msg = rawurlencode( $result->get_error_message() );
			wp_safe_redirect( admin_url( 'admin.php?page=mfu-news-update&mfu_error=' . $msg ) );
			return;
		}

		$update_id = (int) $result;
		if ( $update_id > 0 ) {
			wp_safe_redirect( admin_url( 'admin.php?page=mfu-updates&update_id=' . $update_id ) );
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=mfu-news-update&mfu_msg=Procesado' ) );
	}

	public function handle_news_check() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sin permisos.' );
		}
		$nonce = isset( $_POST['_mfu_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_mfu_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'mfu_news_check' ) ) {
			wp_die( 'Nonce invalido.' );
		}
		$url = isset( $_POST['news_url'] ) ? esc_url_raw( wp_unslash( $_POST['news_url'] ) ) : '';
		if ( $url === '' ) {
			wp_safe_redirect( admin_url( 'admin.php?page=mfu-news-update&mfu_error=URL%20invalida' ) );
			return;
		}

		$processor = new MFU_Processor();
		$result = $processor->identify_news_festival( $url );
		if ( is_wp_error( $result ) ) {
			$msg = rawurlencode( $result->get_error_message() );
			wp_safe_redirect( admin_url( 'admin.php?page=mfu-news-update&mfu_error=' . $msg . '&news_url=' . rawurlencode( $url ) ) );
			return;
		}

		$festival_title = $result['festival_title'] ?? '';
		$confidence = $result['confidence'] ?? '';
		$params = array(
			'page' => 'mfu-news-update',
			'news_url' => $url,
			'festival_title' => $festival_title,
			'confidence' => $confidence,
			'mfu_msg' => 'Festival encontrado',
		);
		wp_safe_redirect( add_query_arg( array_map( 'rawurlencode', $params ), admin_url( 'admin.php' ) ) );
	}

	public function handle_news_extract() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sin permisos.' );
		}
		$nonce = isset( $_POST['_mfu_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_mfu_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'mfu_news_extract' ) ) {
			wp_die( 'Nonce invalido.' );
		}
		$url = isset( $_POST['news_url'] ) ? esc_url_raw( wp_unslash( $_POST['news_url'] ) ) : '';
		if ( $url === '' ) {
			wp_safe_redirect( admin_url( 'admin.php?page=mfu-news-update&mfu_error=URL%20invalida' ) );
			return;
		}
		$manual_id = isset( $_POST['manual_festival_id'] ) ? (int) $_POST['manual_festival_id'] : 0;

		$processor = new MFU_Processor();
		$result = $processor->process_news_url( $url, null, $manual_id );
		if ( is_wp_error( $result ) ) {
			$msg = rawurlencode( $result->get_error_message() );
			wp_safe_redirect( admin_url( 'admin.php?page=mfu-news-update&mfu_error=' . $msg ) );
			return;
		}

		$update_id = (int) $result;
		wp_safe_redirect( admin_url( 'admin.php?page=mfu-news-update&update_id=' . $update_id ) );
	}

	public function handle_news_dismiss() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sin permisos.' );
		}
		check_admin_referer( 'mfu_news_dismiss', '_mfu_nonce' );

		$url = isset( $_POST['news_url'] ) ? esc_url_raw( wp_unslash( $_POST['news_url'] ) ) : '';
		if ( $url === '' ) {
			wp_safe_redirect( admin_url( 'admin.php?page=mfu-news-update' ) );
			return;
		}

		$dismissed = get_option( 'mfu_news_dismissed', array() );
		if ( ! is_array( $dismissed ) ) {
			$dismissed = array();
		}
		if ( ! in_array( $url, $dismissed, true ) ) {
			$dismissed[] = $url;
			update_option( 'mfu_news_dismissed', $dismissed, false );
		}
		delete_transient( 'mfu_news_rss_cache' );
		wp_safe_redirect( admin_url( 'admin.php?page=mfu-news-update' ) );
	}

	public function handle_news_refresh() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sin permisos.' );
		}
		check_admin_referer( 'mfu_news_refresh', '_mfu_nonce' );
		delete_transient( 'mfu_news_rss_cache' );
		wp_safe_redirect( admin_url( 'admin.php?page=mfu-news-update' ) );
	}

	public function handle_add_festival() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sin permisos.' );
		}
		check_admin_referer( 'mfu_add_festival', '_mfu_nonce' );

			$name = isset( $_POST['festival_name'] ) ? sanitize_text_field( wp_unslash( $_POST['festival_name'] ) ) : '';
			$edition = isset( $_POST['festival_edition'] ) ? sanitize_text_field( wp_unslash( $_POST['festival_edition'] ) ) : '';
			$ticket_url = isset( $_POST['ticket_url'] ) ? esc_url_raw( wp_unslash( $_POST['ticket_url'] ) ) : '';
			$localidad_term_id = isset( $_POST['localidad_term_id'] ) ? (int) $_POST['localidad_term_id'] : 0;
			$localidad_new = isset( $_POST['localidad_new'] ) ? sanitize_text_field( wp_unslash( $_POST['localidad_new'] ) ) : '';
			$localidad_parent_term_id = isset( $_POST['localidad_parent_term_id'] ) ? (int) $_POST['localidad_parent_term_id'] : 0;
			$date_start_input = isset( $_POST['fecha_inicio'] ) ? sanitize_text_field( wp_unslash( $_POST['fecha_inicio'] ) ) : '';
			$date_end_input = isset( $_POST['fecha_fin'] ) ? sanitize_text_field( wp_unslash( $_POST['fecha_fin'] ) ) : '';
			$image_id = isset( $_POST['featured_image_id'] ) ? (int) $_POST['featured_image_id'] : 0;
			$use_ai = ! empty( $_POST['use_ai'] );

		if ( $name === '' ) {
			wp_safe_redirect( admin_url( 'admin.php?page=mfu-add-festival&mfu_error=Nombre%20obligatorio' ) );
			return;
		}

		$post_id = wp_insert_post(
			array(
				'post_title' => $name,
				'post_type' => 'festi',
				'post_status' => 'draft',
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			$msg = rawurlencode( $post_id->get_error_message() );
			wp_safe_redirect( admin_url( 'admin.php?page=mfu-add-festival&mfu_error=' . $msg ) );
			return;
		}
		$festival_id = (int) $post_id;

		if ( $image_id > 0 ) {
			set_post_thumbnail( $festival_id, $image_id );
		}

			if ( $ticket_url !== '' ) {
				update_post_meta( $festival_id, 'url_entradas', $ticket_url );
				update_post_meta( $festival_id, 'ticket_url', $ticket_url );
			}
			if ( $edition !== '' ) {
				$this->set_festival_meta( $festival_id, 'edicion', $edition );
			}

			$has_manual_dates = false;
			if ( $date_start_input !== '' ) {
				$normalized_start = $this->normalize_date_for_storage( $date_start_input );
				$display_start = $this->format_date_value( $normalized_start );
				if ( $display_start !== '' ) {
					$this->set_festival_meta( $festival_id, 'fecha_inicio', $display_start );
					$has_manual_dates = true;
				}
			}
			if ( $date_end_input !== '' ) {
				$normalized_end = $this->normalize_date_for_storage( $date_end_input );
				$display_end = $this->format_date_value( $normalized_end );
				if ( $display_end !== '' ) {
					$this->set_festival_meta( $festival_id, 'fecha_fin', $display_end );
					$has_manual_dates = true;
				}
			}
			if ( $has_manual_dates ) {
				$this->set_festival_meta( $festival_id, 'sin_fechas_confirmadas', '0' );
			}

			$this->assign_localidad_terms( $festival_id, $localidad_term_id, $localidad_new, $localidad_parent_term_id );

			if ( $use_ai ) {
				$this->populate_festival_with_ai( $festival_id, $name, $ticket_url, $edition );
			}

		wp_safe_redirect( admin_url( 'admin.php?page=mfu-add-festival&mfu_msg=Festival%20creado&festival_id=' . $festival_id ) );
	}

	private function populate_festival_with_ai( $festival_id, $festival_name, $ticket_url, $edition = '' ) {
		$this->log_add_festival_action( $festival_id, 'start', array( 'festival' => $festival_name ) );
		$ai = new MFU_AI();
		$options = get_option( MFU_OPTION_KEY, array() );
		$base_provider = isset( $options['base_query_provider'] ) ? (string) $options['base_query_provider'] : 'perplexity';
		if ( ! in_array( $base_provider, array( 'openai', 'perplexity' ), true ) ) {
			$base_provider = 'perplexity';
		}

		$edition = trim( (string) $edition );
		$query_seed = $festival_name . ( $edition !== '' ? ' ' . $edition : '' );
		$query = trim( $query_seed . ' festival fechas cartel ubicacion informacion actualizada -site:modofestival.es' );
		$this->log_add_festival_action( $festival_id, 'query', array( 'provider' => $base_provider, 'query' => $query ) );
		$answer_text = '';
		if ( $base_provider === 'openai' ) {
			if ( ! $ai->has_openai_key() ) {
				$this->log_add_festival_action( $festival_id, 'error', array( 'reason' => 'missing_openai_key' ) );
				return;
			}
			$model = isset( $options['model_write'] ) ? (string) $options['model_write'] : 'gpt-5-mini';
			$result = $ai->openai_web_search_answer( $query, 'es', 'ES', $model );
			if ( is_wp_error( $result ) ) {
				$this->log_add_festival_action( $festival_id, 'error', array( 'reason' => 'openai_web_search', 'error' => $result->get_error_message() ) );
				return;
			}
			$answer_text = is_array( $result ) ? (string) ( $result['text'] ?? '' ) : '';
		} else {
			if ( ! $ai->has_perplexity_key() ) {
				$this->log_add_festival_action( $festival_id, 'error', array( 'reason' => 'missing_perplexity_key' ) );
				return;
			}
			$result = $ai->perplexity_answer( $query, 'es' );
			if ( is_wp_error( $result ) ) {
				$this->log_add_festival_action( $festival_id, 'error', array( 'reason' => 'perplexity_answer', 'error' => $result->get_error_message() ) );
				return;
			}
			$answer_text = (string) $result;
		}
		if ( $answer_text === '' ) {
			$this->log_add_festival_action( $festival_id, 'error', array( 'reason' => 'empty_answer' ) );
			return;
		}

		$facts = $ai->extract_facts( $festival_name, $answer_text, $base_provider === 'openai' ? 'openai_web_search_answer' : 'perplexity_answer', $edition );
		$facts_data = is_array( $facts ) ? ( $facts['facts'] ?? $facts ) : array();
		$this->log_add_festival_action( $festival_id, 'facts', array( 'keys' => is_array( $facts_data ) ? array_keys( $facts_data ) : array() ) );

		$date_start = isset( $facts_data['date_start']['value'] ) ? (string) $facts_data['date_start']['value'] : '';
		$date_end = isset( $facts_data['date_end']['value'] ) ? (string) $facts_data['date_end']['value'] : '';
		$artists = isset( $facts_data['artists']['value'] ) ? $facts_data['artists']['value'] : array();

			if ( $date_start !== '' && (string) get_post_meta( $festival_id, 'fecha_inicio', true ) === '' ) {
				$normalized_start = $this->normalize_date_for_storage( $date_start );
				$display_start = $this->format_date_value( $normalized_start );
				$this->set_festival_meta( $festival_id, 'fecha_inicio', $display_start );
				$this->log_add_festival_action( $festival_id, 'fecha_inicio', array( 'raw' => $date_start, 'stored' => $display_start ) );
			}
			if ( $date_end !== '' && (string) get_post_meta( $festival_id, 'fecha_fin', true ) === '' ) {
				$normalized_end = $this->normalize_date_for_storage( $date_end );
				$display_end = $this->format_date_value( $normalized_end );
				$this->set_festival_meta( $festival_id, 'fecha_fin', $display_end );
				$this->log_add_festival_action( $festival_id, 'fecha_fin', array( 'raw' => $date_end, 'stored' => $display_end ) );
			}
		if ( $date_start !== '' || $date_end !== '' ) {
			$this->set_festival_meta( $festival_id, 'sin_fechas_confirmadas', '0' );
		}
		if ( is_array( $artists ) && ! empty( $artists ) ) {
			$artists_str = implode( ', ', array_map( 'trim', $artists ) );
			$this->set_festival_meta( $festival_id, 'mf_artistas', $artists_str );
		}
		// Solo rellenamos fechas y artistas desde IA para nuevos festivales.
	}

		private function set_festival_meta( $festival_id, $key, $value ) {
			if ( function_exists( 'update_field' ) ) {
				update_field( $key, $value, $festival_id );
			}
			update_post_meta( $festival_id, $key, $value );
		}

		private function assign_localidad_terms( $festival_id, $localidad_term_id, $localidad_new, $localidad_parent_term_id ) {
			$localidad_term_id = (int) $localidad_term_id;
			$localidad_parent_term_id = (int) $localidad_parent_term_id;
			$localidad_new = trim( (string) $localidad_new );

			if ( $localidad_term_id > 0 ) {
				wp_set_post_terms( $festival_id, array( $localidad_term_id ), 'localidad', false );
				return;
			}

			if ( $localidad_new === '' ) {
				return;
			}

			$target_term = $this->get_or_create_localidad_term( $localidad_new, $localidad_parent_term_id );
			if ( $target_term && ! empty( $target_term->term_id ) ) {
				wp_set_post_terms( $festival_id, array( (int) $target_term->term_id ), 'localidad', false );
			}
		}

		private function get_localidad_terms_for_select() {
			$terms = get_terms(
				array(
					'taxonomy' => 'localidad',
					'hide_empty' => false,
					'orderby' => 'name',
					'order' => 'ASC',
				)
			);
			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				return array();
			}

			$by_parent = array();
			foreach ( $terms as $term ) {
				$parent_id = (int) $term->parent;
				if ( ! isset( $by_parent[ $parent_id ] ) ) {
					$by_parent[ $parent_id ] = array();
				}
				$by_parent[ $parent_id ][] = $term;
			}

			$out = array();
			$walk = function( $parent_id, $depth ) use ( &$walk, &$by_parent, &$out ) {
				$parent_id = (int) $parent_id;
				if ( empty( $by_parent[ $parent_id ] ) ) {
					return;
				}
				foreach ( $by_parent[ $parent_id ] as $term ) {
					$prefix = $depth > 0 ? str_repeat( '— ', $depth ) : '';
					$out[] = array(
						'id' => (int) $term->term_id,
						'label' => $prefix . $term->name,
					);
					$walk( (int) $term->term_id, $depth + 1 );
				}
			};
			$walk( 0, 0 );

			return $out;
		}

		private function get_or_create_localidad_term( $name, $parent_id = 0 ) {
			$name = trim( (string) $name );
			$parent_id = (int) $parent_id;
			if ( $name === '' ) {
				return null;
			}

			$existing_terms = get_terms(
				array(
					'taxonomy' => 'localidad',
					'hide_empty' => false,
					'name' => $name,
				)
			);
			if ( ! is_wp_error( $existing_terms ) && ! empty( $existing_terms ) ) {
				foreach ( $existing_terms as $term ) {
					if ( (int) $term->parent === $parent_id ) {
						return $term;
					}
				}
				if ( $parent_id === 0 ) {
					return $existing_terms[0];
				}
			}

			$insert = wp_insert_term(
				$name,
				'localidad',
				array(
					'parent' => $parent_id,
				)
			);
			if ( is_wp_error( $insert ) ) {
				$fallback = get_term_by( 'name', $name, 'localidad' );
				return $fallback && ! is_wp_error( $fallback ) ? $fallback : null;
			}

			$term_id = isset( $insert['term_id'] ) ? (int) $insert['term_id'] : 0;
			if ( $term_id <= 0 ) {
				return null;
			}
			$term = get_term( $term_id, 'localidad' );
			return ( $term && ! is_wp_error( $term ) ) ? $term : null;
		}

	public function ajax_festival_search() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Sin permisos' ), 403 );
		}
		$query = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		if ( $query === '' ) {
			wp_send_json( array() );
		}
		$search = new WP_Query(
			array(
				'post_type' => 'festi',
				'post_status' => array( 'publish', 'draft' ),
				'posts_per_page' => 20,
				's' => $query,
				'fields' => 'ids',
			)
		);
		$items = array();
		if ( $search->have_posts() ) {
			foreach ( $search->posts as $post_id ) {
				$items[] = array(
					'id' => (int) $post_id,
					'title' => get_the_title( $post_id ),
				);
			}
		}
		wp_send_json( $items );
	}

	private function extract_city_from_location( $location ) {
		$location = trim( (string) $location );
		if ( $location === '' ) {
			return '';
		}
		if ( preg_match( '/\(([^\)]+)\)/', $location, $m ) ) {
			$candidate = trim( $m[1] );
			if ( $candidate !== '' ) {
				return $this->cleanup_city_candidate( $candidate );
			}
		}
		if ( preg_match( '/\ben\s+([^,\.]+)(?:,|\.|$)/i', $location, $m ) ) {
			$candidate = trim( $m[1] );
			if ( $candidate !== '' ) {
				return $this->cleanup_city_candidate( $candidate );
			}
		}
		$location = preg_replace( '/\s*\(.*?\)\s*/', '', $location );
		$parts = preg_split( '/\s*,\s*|\s+-\s+|\s+–\s+|\s+—\s+/', $location );
		$parts = array_values( array_filter( array_map( 'trim', $parts ) ) );
		$city = $parts ? $parts[ count( $parts ) - 1 ] : $location;
		return $this->cleanup_city_candidate( $city );
	}

	private function find_localidad_term_from_location( $location ) {
		$location = trim( (string) $location );
		if ( $location === '' ) {
			return null;
		}
		$terms = get_terms(
			array(
				'taxonomy' => 'localidad',
				'hide_empty' => false,
			)
		);
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return null;
		}
		$haystack = strtolower( remove_accents( $location ) );
		$best = null;
		$best_len = 0;
		$best_is_parent = true;
		foreach ( $terms as $term ) {
			if ( empty( $term->name ) ) {
				continue;
			}
			$name = strtolower( remove_accents( $term->name ) );
			if ( $name === '' ) {
				continue;
			}
			if ( strpos( $haystack, $name ) !== false ) {
				$len = strlen( $name );
				$is_parent = empty( $term->parent );
				if ( $len > $best_len || ( $len === $best_len && $best_is_parent && ! $is_parent ) ) {
					$best = $term;
					$best_len = $len;
					$best_is_parent = $is_parent;
				}
			}
		}
		return $best;
	}

		private function cleanup_city_candidate( $value ) {
		$value = trim( (string) $value );
		if ( $value === '' ) {
			return '';
		}
		$value = preg_replace( '/^(recinto|parque|sala|pabellon|auditorio|estadio|palacio|plaza|playa|camp|feria)\s+/i', '', $value );
			return trim( $value );
		}

		private function guess_localidad_from_location( $location ) {
			$location = trim( (string) $location );
			if ( $location === '' ) {
				return array(
					'term_id' => 0,
					'new_name' => '',
					'parent_term_id' => 0,
				);
			}

			$matched = $this->find_localidad_term_from_location( $location );
			if ( $matched && ! is_wp_error( $matched ) ) {
				return array(
					'term_id' => (int) $matched->term_id,
					'new_name' => '',
					'parent_term_id' => (int) $matched->parent,
				);
			}

			$parts = preg_split( '/\s*,\s*/', $location );
			$parts = array_values( array_filter( array_map( 'trim', (array) $parts ) ) );
			$new_name = '';
			$parent_term_id = 0;

			if ( count( $parts ) >= 2 ) {
				$new_name = $parts[0];
				$parent_candidate = $parts[1];
				$parent_term = get_term_by( 'name', $parent_candidate, 'localidad' );
				if ( $parent_term && ! is_wp_error( $parent_term ) ) {
					$parent_term_id = (int) $parent_term->term_id;
				}
			} else {
				$new_name = $this->extract_city_from_location( $location );
			}

			return array(
				'term_id' => 0,
				'new_name' => trim( (string) $new_name ),
				'parent_term_id' => $parent_term_id,
			);
		}

	private function build_festival_content_from_facts( $festival_name, $city, $date_start, $date_end, $artists, $ticket_url ) {
		$title_bits = $festival_name;
		$city_text = $city ? ' en ' . $city : '';
		$intro = '<p>' . esc_html( $title_bits ) . $city_text . ' es uno de los festivales a seguir esta temporada. Esta ficha recopila la informacion esencial disponible hasta ahora.</p>';

		$dates_block = '';
		if ( $date_start || $date_end ) {
			$dates_block = '<h2>Fechas</h2><p>';
			if ( $date_start && $date_end ) {
				$dates_block .= 'El festival se celebrara entre ' . esc_html( $date_start ) . ' y ' . esc_html( $date_end ) . '.';
			} elseif ( $date_start ) {
				$dates_block .= 'La fecha de inicio confirmada es ' . esc_html( $date_start ) . '.';
			} else {
				$dates_block .= 'La fecha de cierre confirmada es ' . esc_html( $date_end ) . '.';
			}
			$dates_block .= '</p>';
		}

		$artists_block = '';
		if ( is_array( $artists ) && ! empty( $artists ) ) {
			$top = array_slice( array_map( 'trim', $artists ), 0, 8 );
			$artists_block = '<h2>Cartel</h2><p>Entre los artistas confirmados destacan ' . esc_html( implode( ', ', $top ) ) . '.</p>';
		}

		$practical = '<h2>Como llegar y planificar la visita</h2><p>Si viajas desde fuera, planifica con antelacion transporte y alojamiento. Revisa la ficha oficial para cualquier novedad de accesos y horarios.</p>';

		$ticket_block = '';
		if ( $ticket_url !== '' ) {
			$ticket_block = '<h2>Entradas</h2><p>La venta de entradas suele activarse por tramos. Consulta la web oficial del festival para las opciones disponibles.</p>';
		}

		return $intro . $dates_block . $artists_block . $practical . $ticket_block;
	}

	private function count_words( $content ) {
		$plain = wp_strip_all_tags( (string) $content );
		$words = preg_split( '/\\s+/', trim( $plain ) );
		return is_array( $words ) ? count( array_filter( $words ) ) : 0;
	}

	private function log_add_festival_action( $festival_id, $step, $data = array() ) {
		if ( is_array( $data ) ) {
			$data['festival_id'] = (int) $festival_id;
		}
		MFU_Processor::log_action( 'add_festival.' . $step, $data );
	}

	private function render_news_rss_feed() {
		$sources = $this->get_news_rss_sources();
		if ( empty( $sources ) ) {
			return;
		}

		$items = get_transient( 'mfu_news_rss_cache' );
		if ( ! is_array( $items ) ) {
			$items = $this->fetch_news_rss_items( $sources );
			set_transient( 'mfu_news_rss_cache', $items, 10 * MINUTE_IN_SECONDS );
		}

		$dismissed = get_option( 'mfu_news_dismissed', array() );
		if ( ! is_array( $dismissed ) ) {
			$dismissed = array();
		}
		$processed = $this->get_processed_news_urls();
		if ( ! is_array( $processed ) ) {
			$processed = array();
		}

		$filtered = array();
		foreach ( $items as $item ) {
			if ( empty( $item['link'] ) ) {
				continue;
			}
			if ( in_array( $item['link'], $dismissed, true ) ) {
				continue;
			}
			if ( in_array( $item['link'], $processed, true ) ) {
				continue;
			}
			$filtered[] = $item;
			if ( count( $filtered ) >= 20 ) {
				break;
			}
		}

		echo '<hr />';
		echo '<h2>Noticias recientes (RSS)</h2>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-bottom:10px;">';
		wp_nonce_field( 'mfu_news_refresh', '_mfu_nonce' );
		echo '<input type="hidden" name="action" value="mfu_news_refresh" />';
		echo '<button type="submit" class="button">Actualizar</button>';
		echo '</form>';
		if ( empty( $filtered ) ) {
			echo '<p>No hay noticias nuevas en las fuentes configuradas.</p>';
			return;
		}

		echo '<div style="max-height:360px; overflow:auto; border:1px solid #dcdcde; background:#fff; padding:8px;">';
		echo '<table class="widefat striped">';
		echo '<thead><tr><th>Noticia</th><th>Medio</th><th>Acciones</th></tr></thead><tbody>';
		foreach ( $filtered as $item ) {
			$title = $item['title'] ? $item['title'] : $item['link'];
			$link = $item['link'];
			$source = $item['source'] ? $item['source'] : '-';
			$date_label = $item['date'] ? wp_date( 'l j \\d\\e F \\d\\e Y', $item['date'] ) : '';
			$title_html = '<a href="' . esc_url( $link ) . '" target="_blank" rel="noopener">' . esc_html( $title ) . '</a>';
			if ( $date_label !== '' ) {
				$title_html .= '<div style="color:#646970; font-size:12px; margin-top:2px;">' . esc_html( $date_label ) . '</div>';
			}

			echo '<tr>';
			echo '<td>' . $title_html . '</td>';
			echo '<td>' . esc_html( $source ) . '</td>';
			echo '<td style="white-space:nowrap;">';
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block; margin-right:6px;">';
			wp_nonce_field( 'mfu_news_check', '_mfu_nonce' );
			echo '<input type="hidden" name="action" value="mfu_news_check" />';
			echo '<input type="hidden" name="news_url" value="' . esc_attr( $link ) . '" />';
			echo '<button type="submit" class="button">Comprobar festival</button>';
			echo '</form>';
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block;">';
			wp_nonce_field( 'mfu_news_dismiss', '_mfu_nonce' );
			echo '<input type="hidden" name="action" value="mfu_news_dismiss" />';
			echo '<input type="hidden" name="news_url" value="' . esc_attr( $link ) . '" />';
			echo '<button type="submit" class="button">Descartar</button>';
			echo '</form>';
			echo '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
		echo '</div>';
	}

	private function get_news_rss_sources() {
		$raw = $this->get_settings_value( 'news_rss_sources', array() );
		if ( is_array( $raw ) ) {
			return $this->sanitize_news_rss_sources( $raw );
		}
		if ( ! is_string( $raw ) || trim( $raw ) === '' ) {
			return array();
		}
		$lines = preg_split( '/\\r\\n|\\r|\\n/', $raw );
		$entries = array();
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( $line === '' ) {
				continue;
			}
			$parts = array_map( 'trim', explode( '|', $line, 2 ) );
			$url = esc_url_raw( $parts[0] );
			if ( $url === '' ) {
				continue;
			}
			$keywords = array();
			if ( isset( $parts[1] ) && $parts[1] !== '' ) {
				$keywords = array_filter( array_map( 'trim', explode( ',', $parts[1] ) ) );
			}
			$entries[] = array(
				'url' => $url,
				'keywords' => $keywords,
			);
		}
		return array_values( array_filter( $entries ) );
	}

	private function sanitize_news_rss_sources( $sources ) {
		$clean = array();
		if ( ! is_array( $sources ) ) {
			return $clean;
		}
		foreach ( $sources as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$url = isset( $row['url'] ) ? esc_url_raw( (string) $row['url'] ) : '';
			if ( $url === '' ) {
				continue;
			}
			$keywords = isset( $row['keywords'] ) ? (string) $row['keywords'] : '';
			$clean[] = array(
				'url' => $url,
				'keywords' => $keywords,
			);
		}
		return $clean;
	}

	private function fetch_news_rss_items( $sources ) {
		if ( ! function_exists( 'fetch_feed' ) ) {
			require_once ABSPATH . WPINC . '/feed.php';
		}
		$items = array();
		foreach ( $sources as $source_url ) {
			if ( is_array( $source_url ) ) {
				$url = $source_url['url'] ?? '';
				$raw_keywords = isset( $source_url['keywords'] ) ? (string) $source_url['keywords'] : '';
				$keywords = $raw_keywords !== '' ? array_filter( array_map( 'trim', explode( ',', $raw_keywords ) ) ) : array();
			} else {
				$url = (string) $source_url;
				$keywords = array();
			}
			if ( $url === '' ) {
				continue;
			}
			$feed = fetch_feed( $url );
			if ( is_wp_error( $feed ) ) {
				continue;
			}
			$max = $feed->get_item_quantity( 5 );
			$entries = $feed->get_items( 0, $max );
			foreach ( $entries as $entry ) {
				$link = $entry->get_link();
				if ( ! $link ) {
					continue;
				}
				$title = $entry->get_title();
				$desc = $entry->get_description();
				if ( ! empty( $keywords ) ) {
					$haystack = strtolower( (string) $title . ' ' . wp_strip_all_tags( (string) $desc ) );
					$matched = false;
					foreach ( $keywords as $kw ) {
						$kw = strtolower( $kw );
						if ( $kw !== '' && strpos( $haystack, $kw ) !== false ) {
							$matched = true;
							break;
						}
					}
					if ( ! $matched ) {
						continue;
					}
				}
				$date = $entry->get_date( 'U' );
				$host = '';
				$parts = wp_parse_url( $link );
				if ( ! empty( $parts['host'] ) ) {
					$host = preg_replace( '/^www\\./', '', $parts['host'] );
				}
				$items[] = array(
					'title' => is_string( $title ) ? $title : '',
					'link' => (string) $link,
					'date' => $date ? (int) $date : 0,
					'source' => $host,
				);
			}
		}

		usort( $items, function ( $a, $b ) {
			return (int) ( $b['date'] ?? 0 ) <=> (int) ( $a['date'] ?? 0 );
		} );
		$dedup = array();
		$seen = array();
		foreach ( $items as $item ) {
			$link = $item['link'];
			if ( isset( $seen[ $link ] ) ) {
				continue;
			}
			$seen[ $link ] = true;
			$dedup[] = $item;
		}
		return $dedup;
	}

	private function get_processed_news_urls() {
		global $wpdb;
		$table = MFU_DB::table( 'updates' );
		$rows = $wpdb->get_results( "SELECT evidence_json FROM {$table} ORDER BY detected_at DESC LIMIT 200" );
		$urls = array();
		foreach ( $rows as $row ) {
			$evidence = $row->evidence_json ? json_decode( $row->evidence_json, true ) : array();
			if ( ! is_array( $evidence ) || ( $evidence['update_origin'] ?? '' ) !== 'news' ) {
				continue;
			}
			if ( empty( $evidence['sources'] ) || ! is_array( $evidence['sources'] ) ) {
				continue;
			}
			foreach ( $evidence['sources'] as $src ) {
				if ( ! is_array( $src ) || ( $src['type'] ?? '' ) !== 'news' ) {
					continue;
				}
				if ( ! empty( $src['url'] ) ) {
					$urls[] = (string) $src['url'];
				}
			}
		}
		return array_values( array_unique( $urls ) );
	}

	private function render_recent_news_updates() {
		global $wpdb;
		$table = MFU_DB::table( 'updates' );
		$rows = $wpdb->get_results( "SELECT id, festival_id, detected_at, status, evidence_json, diffs_json FROM {$table} ORDER BY detected_at DESC LIMIT 30" );

		$items = array();
		foreach ( $rows as $row ) {
			$evidence = $row->evidence_json ? json_decode( $row->evidence_json, true ) : array();
			if ( ! is_array( $evidence ) || ( $evidence['update_origin'] ?? '' ) !== 'news' ) {
				continue;
			}
			$diffs = $row->diffs_json ? json_decode( $row->diffs_json, true ) : array();
			$news_title = '';
			$news_url = '';
			if ( is_array( $evidence ) && ! empty( $evidence['sources'] ) && is_array( $evidence['sources'] ) ) {
				foreach ( $evidence['sources'] as $src ) {
					if ( ! is_array( $src ) || ( $src['type'] ?? '' ) !== 'news' ) {
						continue;
					}
					$news_url = isset( $src['url'] ) ? (string) $src['url'] : '';
					$news_title = isset( $src['news_title'] ) ? (string) $src['news_title'] : '';
					break;
				}
			}
			$fields = array();
			if ( is_array( $diffs ) ) {
				foreach ( array_keys( $diffs ) as $key ) {
					$fields[] = $key;
				}
			}
			$festival = get_post( (int) $row->festival_id );
			$title = $festival ? $festival->post_title : 'Festival #' . (int) $row->festival_id;
			$items[] = array(
				'id' => (int) $row->id,
				'title' => $title,
				'detected_at' => (string) $row->detected_at,
				'status' => (string) $row->status,
				'fields' => $fields,
				'news_title' => $news_title !== '' ? $news_title : ( $news_url !== '' ? $news_url : '' ),
				'news_url' => $news_url,
			);
			if ( count( $items ) >= 10 ) {
				break;
			}
		}

		echo '<hr />';
		echo '<h2>Ultimas noticias procesadas</h2>';
		if ( empty( $items ) ) {
			echo '<p>Sin noticias procesadas.</p>';
			return;
		}

		echo '<div style="max-height:320px; overflow:auto; border:1px solid #dcdcde; background:#fff; padding:8px;">';
		echo '<table class="widefat striped">';
		echo '<thead><tr><th>Noticia</th><th>Festival</th><th>Estado</th><th>Modificaciones</th></tr></thead><tbody>';
		foreach ( $items as $item ) {
			$mods = empty( $item['fields'] ) ? '-' : implode( ', ', array_map( 'esc_html', $item['fields'] ) );
			$view_url = admin_url( 'admin.php?page=mfu-updates&update_id=' . (int) $item['id'] );
			$detected_at = $item['detected_at'];
			$detected_label = $detected_at !== '' ? $detected_at : '-';
			if ( $detected_at !== '' ) {
				$ts = strtotime( $detected_at );
				if ( $ts ) {
					$detected_label = wp_date( 'l j \\d\\e F \\d\\e Y', $ts );
				}
			}
			echo '<tr>';
			$news_cell = $item['news_url'] ? '<a href="' . esc_url( $item['news_url'] ) . '" target="_blank" rel="noopener">' . esc_html( $item['news_title'] ) . '</a>' : '-';
			if ( $news_cell !== '-' ) {
				$news_cell .= '<div style="color:#646970; font-size:12px; margin-top:2px;">' . esc_html( $detected_label ) . '</div>';
			}
			echo '<td>' . $news_cell . '</td>';
			echo '<td><a href="' . esc_url( $view_url ) . '" target="_blank" rel="noopener">' . esc_html( $item['title'] ) . '</a></td>';
			echo '<td>' . esc_html( $item['status'] ) . '</td>';
			echo '<td>' . $mods . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
		echo '</div>';
	}

	private function render_queue_status( $is_ajax = false ) {
		global $wpdb;
		$table = MFU_DB::table( 'jobs' );
		$settings = get_option( MFU_OPTION_KEY, array() );
		$batch = isset( $settings['batch_size'] ) ? (int) $settings['batch_size'] : 5;
		if ( $batch < 1 ) {
			$batch = 1;
		}
		$rows = $wpdb->get_results(
			"SELECT festival_id,
				SUM(status='queued') AS queued,
				SUM(status='running') AS running,
				SUM(status='done') AS done,
				SUM(status='error') AS error
			FROM {$table}
			GROUP BY festival_id",
			ARRAY_A
		);

		$counts = array(
			'queued' => 0,
			'running' => 0,
			'done' => 0,
			'error' => 0,
		);
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$status = MFU_Cron::resolve_job_status_from_counts( $row );
				if ( isset( $counts[ $status ] ) ) {
					$counts[ $status ]++;
				}
			}
		}

		$total = array_sum( $counts );
		if ( $total > 0 ) {
			$completed = $counts['done'] + $counts['error'];
			$pending = $counts['queued'] + $counts['running'];
			$avg_seconds = null;
			if ( $pending > 0 ) {
				$avg_seconds = (int) $wpdb->get_var(
					"SELECT AVG(duration) FROM (
						SELECT TIMESTAMPDIFF(SECOND, started_at, finished_at) AS duration
						FROM {$table}
						WHERE status IN ('done','error') AND started_at IS NOT NULL AND finished_at IS NOT NULL
						ORDER BY finished_at DESC
						LIMIT 20
					) t"
				);
			}
			$eta_text = '';
			if ( $avg_seconds && $pending > 0 ) {
				$eta_total = $avg_seconds * $pending;
				$eta_minutes = (int) ceil( $eta_total / 60 );
				$eta_text = ' · ETA aprox: ' . $eta_minutes . ' min';
			}
			$percent = (int) round( ( $completed / max( 1, $total ) ) * 100 );
			echo '<div style="display:flex; gap:12px; flex-wrap:wrap; align-items:stretch; margin:10px 0 6px 0;">';
			echo '<div style="flex:1 1 320px; max-width:640px;">';
			echo '<div style="font-weight:600; margin-bottom:6px;">Estado de cola</div>';
			echo '<div style="background:#f0f0f1; border:1px solid #dcdcde; border-radius:6px; overflow:hidden; height:14px;">';
			echo '<div style="background:#2271b1; width:' . esc_attr( $percent ) . '%; height:14px;"></div>';
			echo '</div>';
			echo '<div style="margin-top:6px; color:#50575e;">' . esc_html( $percent ) . '% completado ('
				. esc_html( $completed ) . '/' . esc_html( $total ) . ')</div>';
			echo '<div style="margin-top:4px; color:#50575e;">Pendientes: ' . esc_html( $pending )
				. ' · Lote por ejecucion: ' . esc_html( $batch ) . esc_html( $eta_text ) . '</div>';
			echo '</div>';
			$this->render_review_stats( true );
			echo '</div>';
			$base_url = admin_url( 'admin.php?page=mfu-updates' );
			$updates_table = MFU_DB::table( 'updates' );

			echo '<div style="margin-top:10px; display:grid; grid-template-columns:repeat(4, minmax(120px, 1fr)); gap:10px; max-width:640px;">';
			echo $this->render_queue_badge( 'en cola', $counts['queued'], '#cfe2ff', '#084298', true, add_query_arg( 'mfu_job', 'queued', $base_url ) );
			echo $this->render_queue_badge( 'procesando', $counts['running'], '#fff3cd', '#664d03', true, add_query_arg( 'mfu_job', 'running', $base_url ) );
			echo $this->render_queue_badge( 'completado', $counts['done'], '#d6f5df', '#0f5132', true, add_query_arg( 'mfu_job', 'done', $base_url ) );
			echo $this->render_queue_badge( 'error', $counts['error'], '#f8d7da', '#842029', true, add_query_arg( 'mfu_job', 'error', $base_url ) );
			echo '</div>';
			echo '<div style="margin-top:8px; display:grid; grid-template-columns:repeat(4, minmax(120px, 1fr)); gap:10px; max-width:640px;">';
			echo '<div><a class="button button-primary" style="width:100%; text-align:center;" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mfu_process_now' ), 'mfu_process_now' ) ) . '">Procesar cola ahora</a></div>';
			echo '<div><a class="button" style="width:100%; text-align:center;" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mfu_clear_queue' ), 'mfu_clear_queue' ) ) . '" onclick="return confirm(\'¿Detener y limpiar la cola?\');">Stop cola</a></div>';
			echo '<div></div><div></div>';
			echo '</div>';

			$latest_rows = $wpdb->get_results(
				"SELECT u.status FROM {$updates_table} u
				 INNER JOIN (SELECT festival_id, MAX(id) AS max_id FROM {$updates_table} GROUP BY festival_id) t
				 ON t.max_id = u.id
				 WHERE u.status IN ('pending_review','auto_applied')",
			ARRAY_A
		);
		$pending_review = 0;
		$auto_applied = 0;
		if ( is_array( $latest_rows ) ) {
			foreach ( $latest_rows as $row ) {
				if ( ( $row['status'] ?? '' ) === 'pending_review' ) {
					$pending_review++;
				} elseif ( ( $row['status'] ?? '' ) === 'auto_applied' ) {
					$auto_applied++;
				}
			}
		}
		$pending_url = add_query_arg( 'mfu_status', 'pending_review', $base_url );
		$auto_url = add_query_arg( 'mfu_status', 'auto_applied', $base_url );
			echo '<div style="margin-top:10px; display:grid; grid-template-columns:repeat(2, minmax(160px, 1fr)); gap:10px; max-width:640px;">';
			echo $this->render_queue_badge( 'pendientes de revision', $pending_review, '#e7f1ff', '#0b408a', true, $pending_url );
			echo $this->render_queue_badge( 'autoaplicados', $auto_applied, '#e9f7ef', '#146c43', true, $auto_url );
			echo '</div>';
		}

		if ( ( $counts['queued'] > 0 || $counts['running'] > 0 ) && ! $is_ajax ) {
			echo '<p class="description">Procesando cola. Esta vista se actualiza automaticamente cada 10s.</p>';
			echo '<label style="display:inline-flex; align-items:center; gap:6px; margin:6px 0; font-size:12px;">';
			echo '<input type="checkbox" id="mfu-pause-refresh" /> Pausar auto-actualizacion';
			echo '</label>';
			echo '<script>
				(function(){
					var key = "mfu_pause_refresh";
					var checkbox = document.getElementById("mfu-pause-refresh");
					if (checkbox) {
						checkbox.checked = localStorage.getItem(key) === "1";
						checkbox.addEventListener("change", function(){
							localStorage.setItem(key, checkbox.checked ? "1" : "0");
						});
					}
					function shouldRefresh(){
						if (localStorage.getItem(key) === "1") { return false; }
						var selected = document.querySelectorAll("input[name=\'festival_ids[]\']:checked").length;
						return selected === 0;
					}
					function refreshStatus(){
						if (!shouldRefresh()) { return; }
						var data = new URLSearchParams();
						data.append("action", "mfu_queue_status");
						data.append("_ajax_nonce", "' . esc_js( wp_create_nonce( 'mfu_queue_status' ) ) . '");
						fetch("' . esc_url( admin_url( 'admin-ajax.php' ) ) . '", {
							method: "POST",
							headers: {"Content-Type": "application/x-www-form-urlencoded"},
							body: data.toString()
						}).then(function(res){ return res.json(); }).then(function(payload){
							var html = payload && payload.data ? payload.data.html : "";
							var container = document.getElementById("mfu-queue-status");
							if (container && html) { container.innerHTML = html; }
						});
					}
					setInterval(refreshStatus, 10000);
				})();
			</script>';
		}

		$progress = get_option( 'mfu_progress' );
		if ( is_array( $progress ) && ! empty( $progress['message'] ) ) {
			$updated_at = ! empty( $progress['updated_at'] ) ? $progress['updated_at'] : '';
			$progress_job_id = ! empty( $progress['job_id'] ) ? (int) $progress['job_id'] : 0;
			$progress_label = '';
			if ( $progress_job_id > 0 ) {
				$progress_row = $wpdb->get_row( $wpdb->prepare( "SELECT festival_id FROM {$table} WHERE id=%d", $progress_job_id ) );
				if ( $progress_row && ! empty( $progress_row->festival_id ) ) {
					$progress_title = get_the_title( (int) $progress_row->festival_id );
					$progress_label = $progress_title ? ' | ' . $progress_title : '';
				}
			}
			echo '<div style="padding:8px 12px; border-left:4px solid #2271b1; background:#f6f7f7; max-width:640px;">';
			echo '<div style="font-weight:600; margin-bottom:4px;">Progreso</div>';
			echo '<div>' . esc_html( $progress['message'] ) . '</div>';
			if ( $progress_job_id > 0 ) {
				echo '<div style="margin-top:4px; color:#50575e;">Job #' . esc_html( (string) $progress_job_id ) . esc_html( $progress_label ) . '</div>';
			}
			echo '</div>';
			if ( $updated_at ) {
				echo '<p class="description">Actualizado: ' . esc_html( $updated_at ) . '</p>';
			}
		}

		$last_job = $wpdb->get_row( "SELECT id, festival_id, status, finished_at FROM {$table} WHERE status IN ('done','error') AND finished_at IS NOT NULL ORDER BY finished_at DESC LIMIT 1" );
		if ( $last_job ) {
			$last_title = $last_job->festival_id ? get_the_title( (int) $last_job->festival_id ) : '';
			$last_label = $last_title ? ' | ' . $last_title : '';
			echo '<p class="description">Ultimo job completado: #' . esc_html( (string) $last_job->id ) . esc_html( $last_label ) .
				' · ' . esc_html( (string) $last_job->status ) . ' · ' . esc_html( (string) $last_job->finished_at ) . '</p>';
		}

			// Review stats are rendered inline with queue status.
		}

	private function render_queue_badge( $label, $count, $bg, $color, $large = false, $url = '' ) {
		if ( $large ) {
			$card = '<div style="padding:10px 12px; border-radius:10px; background:' . esc_attr( $bg ) . '; color:' . esc_attr( $color ) . '; text-align:center; font-weight:700;">' .
				'<div style="font-size:12px; letter-spacing:0.3px; text-transform:uppercase;">' . esc_html( $label ) . '</div>' .
				'<div style="font-size:26px; margin-top:4px;">' . esc_html( (int) $count ) . '</div>' .
			'</div>';
			if ( $url ) {
				return '<a href="' . esc_url( $url ) . '" style="text-decoration:none; display:block;">' . $card . '</a>';
			}
			return $card;
		}
		return '<span style="display:inline-flex; align-items:center; gap:6px; padding:3px 10px; border-radius:999px; background:' . esc_attr( $bg ) . '; color:' . esc_attr( $color ) . '; font-weight:600; font-size:12px;">' .
			esc_html( $label ) . ' <strong>' . esc_html( (int) $count ) . '</strong>' .
		'</span>';
	}

	public function handle_queue_status_ajax() {
		check_ajax_referer( 'mfu_queue_status' );
		ob_start();
		$this->render_queue_status( true );
		wp_send_json_success( array( 'html' => ob_get_clean() ) );
	}

		public function handle_verify_update_ajax() {
		check_ajax_referer( 'mfu_verify_update' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'No permitido' ) );
		}

		$update_id = isset( $_POST['update_id'] ) ? (int) $_POST['update_id'] : 0;
		if ( ! $update_id ) {
			wp_send_json_error( array( 'message' => 'Update invalido' ) );
		}

		global $wpdb;
		$table = MFU_DB::table( 'updates' );
		$update = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d", $update_id ) );
		if ( ! $update ) {
			wp_send_json_error( array( 'message' => 'Update no encontrado' ) );
		}

		$diffs = $update->diffs_json ? json_decode( $update->diffs_json, true ) : array();
		$evidence = $update->evidence_json ? json_decode( $update->evidence_json, true ) : array();
		$festival = get_post( (int) $update->festival_id );
		$edition = get_post_meta( (int) $update->festival_id, 'edicion', true );

		$ai = new MFU_AI();
		if ( ! $ai->has_key() ) {
			wp_send_json_error( array( 'message' => 'API key missing' ) );
		}
		$result = $ai->verify_update( $festival ? $festival->post_title : '', $edition, $diffs, $evidence );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

			wp_send_json_success( $result );
		}

		public function handle_ai_prefill_festival_ajax() {
			check_ajax_referer( 'mfu_ai_prefill_festival' );
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => 'No permitido' ), 403 );
			}

			$festival_name = isset( $_POST['festival_name'] ) ? sanitize_text_field( wp_unslash( $_POST['festival_name'] ) ) : '';
			$edition = isset( $_POST['festival_edition'] ) ? sanitize_text_field( wp_unslash( $_POST['festival_edition'] ) ) : '';
			if ( $festival_name === '' ) {
				wp_send_json_error( array( 'message' => 'Nombre de festival obligatorio.' ), 400 );
			}

			$ai = new MFU_AI();
			if ( ! $ai->has_openai_key() ) {
				wp_send_json_error( array( 'message' => 'OpenAI API key missing' ), 400 );
			}

			$prefill = $ai->prefill_localidad_fechas( $festival_name, $edition );
			if ( is_wp_error( $prefill ) ) {
				$msg = (string) $prefill->get_error_message();
				if ( stripos( $msg, 'cURL error 28' ) !== false ) {
					$msg = 'Timeout de OpenAI al autocompletar. Intenta de nuevo en unos segundos.';
				}
				wp_send_json_error( array( 'message' => $msg ), 500 );
			}

			$location_raw = trim( (string) ( $prefill['location'] ?? '' ) );
			$fecha_inicio = $this->normalize_date_to_input( $prefill['date_start'] ?? '' );
			$fecha_fin = $this->normalize_date_to_input( $prefill['date_end'] ?? '' );

			$localidad_term_id = 0;
			$localidad_new = '';
			$localidad_parent_term_id = 0;
			$localidad_guess = $this->guess_localidad_from_location( $location_raw );
			if ( ! empty( $localidad_guess['term_id'] ) ) {
				$localidad_term_id = (int) $localidad_guess['term_id'];
			} else {
				$localidad_new = (string) ( $localidad_guess['new_name'] ?? '' );
				$localidad_parent_term_id = (int) ( $localidad_guess['parent_term_id'] ?? 0 );
			}

				wp_send_json_success(
				array(
					'message' => 'Localidad y fechas sugeridas por IA cargadas. Revísalas antes de crear el festival.',
					'fecha_inicio' => $fecha_inicio,
					'fecha_fin' => $fecha_fin,
					'localidad_term_id' => $localidad_term_id,
					'localidad_new' => $localidad_new,
					'localidad_parent_term_id' => $localidad_parent_term_id,
					'location_raw' => $location_raw,
				)
			);
		}

		public function handle_verify_content_perplexity_ajax() {
		check_ajax_referer( 'mfu_verify_content_perplexity' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'No permitido' ) );
		}

		$update_id = isset( $_POST['update_id'] ) ? (int) $_POST['update_id'] : 0;
		if ( ! $update_id ) {
			wp_send_json_error( array( 'message' => 'Update invalido' ) );
		}

		global $wpdb;
		$table = MFU_DB::table( 'updates' );
		$update = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d", $update_id ) );
		if ( ! $update ) {
			wp_send_json_error( array( 'message' => 'Update no encontrado' ) );
		}

		$evidence = $update->evidence_json ? json_decode( $update->evidence_json, true ) : array();
		$updated_content = is_array( $evidence ) && isset( $evidence['updated_content'] ) ? (string) $evidence['updated_content'] : '';
		if ( $updated_content === '' ) {
			wp_send_json_error( array( 'message' => 'Sin contenido propuesto para verificar' ) );
		}

		$festival = get_post( (int) $update->festival_id );
		$edition = get_post_meta( (int) $update->festival_id, 'edicion', true );

		$ai = new MFU_AI();
		if ( ! $ai->has_perplexity_key() ) {
			wp_send_json_error( array( 'message' => 'Perplexity API key missing' ) );
		}

		$result = $ai->verify_content_with_perplexity(
			$festival ? $festival->post_title : '',
			$edition,
			$updated_content
		);
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		if ( ! is_array( $evidence ) ) {
			$evidence = array();
		}
		$evidence['content_verification_pplx'] = $result;
		if ( ! empty( $result['suggestions'] ) && is_array( $result['suggestions'] ) ) {
			$clean = array();
			foreach ( $result['suggestions'] as $suggestion ) {
				if ( ! is_array( $suggestion ) ) {
					continue;
				}
				$replace = (string) ( $suggestion['replace'] ?? '' );
				if ( $replace !== '' ) {
					$replace = preg_replace( '/\s*\[\d+\]/', '', $replace );
					$suggestion['replace'] = $replace;
				}
				$clean[] = $suggestion;
			}
			$evidence['content_suggestions'] = $clean;
		}

		$pplx_ok = ( $result['verdict'] ?? '' ) === 'ok';
		$internal_ok = false;
		$has_internal = false;
		foreach ( array( 'verification', 'content_verification' ) as $key ) {
			if ( ! empty( $evidence[ $key ] ) && is_array( $evidence[ $key ] ) ) {
				$has_internal = true;
				if ( ( $evidence[ $key ]['verdict'] ?? '' ) !== 'ok' ) {
					$internal_ok = false;
					break;
				}
				$internal_ok = true;
			}
		}
		if ( ! $has_internal ) {
			$internal_ok = false;
		}
		$next_status = ( $pplx_ok && $internal_ok ) ? 'verified' : (string) $update->status;
		$wpdb->update(
			$table,
			array(
				'evidence_json' => wp_json_encode( $evidence ),
				'status' => $next_status,
			),
			array( 'id' => $update_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

			$result['status'] = $next_status;
			wp_send_json_success( $result );
		}

		public function handle_rewrite_updated_content_seo_ajax() {
			check_ajax_referer( 'mfu_rewrite_updated_content_seo' );
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => 'No permitido' ) );
			}

			$update_id = isset( $_POST['update_id'] ) ? (int) $_POST['update_id'] : 0;
			if ( ! $update_id ) {
				wp_send_json_error( array( 'message' => 'Update invalido' ) );
			}

			global $wpdb;
			$table = MFU_DB::table( 'updates' );
			$update = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d", $update_id ) );
			if ( ! $update ) {
				wp_send_json_error( array( 'message' => 'Update no encontrado' ) );
			}

			$input_content = isset( $_POST['updated_content'] ) ? (string) wp_unslash( $_POST['updated_content'] ) : '';
			$evidence = $update->evidence_json ? json_decode( $update->evidence_json, true ) : array();
			if ( ! is_array( $evidence ) ) {
				$evidence = array();
			}
			if ( $input_content === '' && isset( $evidence['updated_content'] ) ) {
				$input_content = (string) $evidence['updated_content'];
			}
			if ( trim( $input_content ) === '' ) {
				wp_send_json_error( array( 'message' => 'Sin contenido propuesto para reescribir' ) );
			}

			$festival = get_post( (int) $update->festival_id );
			$edition = get_post_meta( (int) $update->festival_id, 'edicion', true );

			$ai = new MFU_AI();
			if ( ! $ai->has_openai_key() ) {
				wp_send_json_error( array( 'message' => 'OpenAI API key missing' ) );
			}

			$rewritten_content = $ai->rewrite_content_for_seo(
				$festival ? $festival->post_title : '',
				$edition,
				$input_content
			);
			if ( is_wp_error( $rewritten_content ) ) {
				wp_send_json_error( array( 'message' => $rewritten_content->get_error_message() ) );
			}

			$rewritten_content = $this->sanitize_ai_content( (string) $rewritten_content );
			if ( trim( $rewritten_content ) === '' ) {
				wp_send_json_error( array( 'message' => 'OpenAI devolvio contenido vacio' ) );
			}

			$evidence['updated_content'] = $rewritten_content;
			$evidence['seo_rewrite_at'] = current_time( 'mysql' );
			$evidence['seo_rewrite_by'] = get_current_user_id();
			$evidence['seo_rewrite_provider'] = 'openai';

			$wpdb->update(
				$table,
				array( 'evidence_json' => wp_json_encode( $evidence ) ),
				array( 'id' => $update_id ),
				array( '%s' ),
				array( '%d' )
			);

			wp_send_json_success(
				array(
					'message' => 'Contenido reescrito con OpenAI y guardado.',
					'rewritten_content' => $rewritten_content,
				)
			);
		}

	public function handle_apply_content_suggestion() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No permitido' );
		}

		$update_id = isset( $_GET['update_id'] ) ? (int) $_GET['update_id'] : 0;
		$index = isset( $_GET['index'] ) ? (int) $_GET['index'] : -1;
		check_admin_referer( 'mfu_apply_content_suggestion_' . $update_id . '_' . $index );
		if ( $update_id <= 0 || $index < 0 ) {
			wp_die( 'Parametros invalidos' );
		}

		global $wpdb;
		$table = MFU_DB::table( 'updates' );
		$update = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d", $update_id ) );
		if ( ! $update ) {
			wp_die( 'Update no encontrado' );
		}

		$evidence = $update->evidence_json ? json_decode( $update->evidence_json, true ) : array();
		$suggestions = is_array( $evidence ) && isset( $evidence['content_suggestions'] ) && is_array( $evidence['content_suggestions'] )
			? $evidence['content_suggestions']
			: array();
		if ( ! isset( $suggestions[ $index ] ) ) {
			wp_die( 'Sugerencia no encontrada' );
		}

		$updated_content = isset( $evidence['updated_content'] ) ? (string) $evidence['updated_content'] : '';
		$find = (string) ( $suggestions[ $index ]['find'] ?? '' );
		$replace = (string) ( $suggestions[ $index ]['replace'] ?? '' );
		if ( $replace !== '' ) {
			$replace = preg_replace( '/\s*\[\d+\]/', '', $replace );
		}
		if ( $updated_content === '' || $find === '' ) {
			wp_die( 'Contenido o sugerencia invalida' );
		}

		$count = 0;
		$updated_content = str_replace( $find, $replace, $updated_content, $count );
		if ( $count === 0 ) {
			$normalized_content = str_replace( array( "\r\n", "\r" ), "\n", $updated_content );
			$normalized_find = str_replace( array( "\r\n", "\r" ), "\n", $find );
			$normalized_replace = str_replace( array( "\r\n", "\r" ), "\n", $replace );
			$normalized_count = 0;
			$normalized_content = str_replace( $normalized_find, $normalized_replace, $normalized_content, $normalized_count );
			if ( $normalized_count > 0 ) {
				$updated_content = $normalized_content;
				$count = $normalized_count;
			}
		}
		if ( $count === 0 ) {
			wp_die( 'Texto a reemplazar no encontrado' );
		}
		$evidence['updated_content'] = $updated_content;
		unset( $suggestions[ $index ] );
		$evidence['content_suggestions'] = array_values( $suggestions );

		$wpdb->update(
			$table,
			array( 'evidence_json' => wp_json_encode( $evidence ) ),
			array( 'id' => $update_id ),
			array( '%s' ),
			array( '%d' )
		);

		wp_safe_redirect( admin_url( 'admin.php?page=mfu-updates&update_id=' . $update_id ) );
		exit;
	}

	public function handle_reject_content_suggestion() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No permitido' );
		}

		$update_id = isset( $_GET['update_id'] ) ? (int) $_GET['update_id'] : 0;
		$index = isset( $_GET['index'] ) ? (int) $_GET['index'] : -1;
		check_admin_referer( 'mfu_reject_content_suggestion_' . $update_id . '_' . $index );
		if ( $update_id <= 0 || $index < 0 ) {
			wp_die( 'Parametros invalidos' );
		}

		global $wpdb;
		$table = MFU_DB::table( 'updates' );
		$update = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d", $update_id ) );
		if ( ! $update ) {
			wp_die( 'Update no encontrado' );
		}

		$evidence = $update->evidence_json ? json_decode( $update->evidence_json, true ) : array();
		$suggestions = is_array( $evidence ) && isset( $evidence['content_suggestions'] ) && is_array( $evidence['content_suggestions'] )
			? $evidence['content_suggestions']
			: array();
		if ( ! isset( $suggestions[ $index ] ) ) {
			wp_die( 'Sugerencia no encontrada' );
		}

		unset( $suggestions[ $index ] );
		$evidence['content_suggestions'] = array_values( $suggestions );

		$wpdb->update(
			$table,
			array( 'evidence_json' => wp_json_encode( $evidence ) ),
			array( 'id' => $update_id ),
			array( '%s' ),
			array( '%d' )
		);

		wp_safe_redirect( admin_url( 'admin.php?page=mfu-updates&update_id=' . $update_id ) );
		exit;
	}

	public function handle_save_updated_content() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No permitido' );
		}

		$update_id = isset( $_POST['update_id'] ) ? (int) $_POST['update_id'] : 0;
		check_admin_referer( 'mfu_save_updated_content_' . $update_id );
		if ( $update_id <= 0 ) {
			wp_die( 'Parametros invalidos' );
		}

		$updated_content = isset( $_POST['updated_content'] ) ? (string) wp_unslash( $_POST['updated_content'] ) : '';
		if ( $updated_content === '' ) {
			wp_die( 'Contenido vacio' );
		}

		global $wpdb;
		$table = MFU_DB::table( 'updates' );
		$update = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d", $update_id ) );
		if ( ! $update ) {
			wp_die( 'Update no encontrado' );
		}

		$evidence = $update->evidence_json ? json_decode( $update->evidence_json, true ) : array();
		if ( ! is_array( $evidence ) ) {
			$evidence = array();
		}
		$evidence['updated_content'] = $updated_content;

		$wpdb->update(
			$table,
			array( 'evidence_json' => wp_json_encode( $evidence ) ),
			array( 'id' => $update_id ),
			array( '%s' ),
			array( '%d' )
		);

		$apply_now = ! empty( $_POST['apply_now'] );
		if ( $apply_now ) {
			$fresh_update = $this->get_update_row( $update_id );
			if ( $fresh_update ) {
				$this->apply_update_row( $fresh_update );
			}
			wp_safe_redirect( admin_url( 'admin.php?page=mfu-updates' ) );
			exit;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=mfu-updates&update_id=' . $update_id ) );
		exit;
	}

	private function render_active_filters_notice() {
		$status = isset( $_GET['mfu_status'] ) ? sanitize_text_field( wp_unslash( $_GET['mfu_status'] ) ) : '';
		$job = isset( $_GET['mfu_job'] ) ? sanitize_text_field( wp_unslash( $_GET['mfu_job'] ) ) : '';
		$recent = isset( $_GET['mfu_recent'] ) ? (int) $_GET['mfu_recent'] : 0;
		$stale = isset( $_GET['mfu_stale'] ) ? (int) $_GET['mfu_stale'] : 0;
		$sources = isset( $_GET['mfu_sources'] ) ? sanitize_text_field( wp_unslash( $_GET['mfu_sources'] ) ) : '';

		$labels = array();
		if ( $status !== '' ) {
			$labels[] = 'estado: ' . ( $status === 'auto_applied' ? 'autoaplicado' : $status );
		}
		if ( $job !== '' ) {
			$labels[] = 'cola: ' . $job;
		}
		if ( $recent > 0 ) {
			$labels[] = 'actualizados ult. ' . $recent . ' dias';
		}
		if ( $stale > 0 ) {
			$labels[] = 'sin actualizar +' . $stale . ' dias';
		}
		if ( $sources !== '' ) {
				$sources_labels = array(
					'issues' => 'con dudas',
					'web_dudosa' => 'web dudosa',
					'sin_web' => 'sin web',
					'sin_ig' => 'sin Instagram',
					'sin_fuentes' => 'sin fuentes (IA)',
				);
			$labels[] = 'fuentes: ' . ( $sources_labels[ $sources ] ?? $sources );
		}

		if ( empty( $labels ) ) {
			return;
		}

		$clear_url = admin_url( 'admin.php?page=mfu-updates' );
		echo '<div class="notice notice-info" style="margin:10px 0;"><p>';
		echo '<strong>Filtro activo:</strong> ' . esc_html( implode( ' | ', $labels ) );
		echo ' &nbsp; <a href="' . esc_url( $clear_url ) . '">Limpiar filtros</a>';
		echo '</p></div>';
	}

	private function render_review_stats( $inline = false ) {
		global $wpdb;
		$updates = MFU_DB::table( 'updates' );
		$posts = $wpdb->posts;

		$recent_days = 7;
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $recent_days * DAY_IN_SECONDS ) );

		$recent_count = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(1) FROM (
				SELECT p.ID, MAX(u.applied_at) AS last_applied
				FROM {$posts} p
				LEFT JOIN {$updates} u ON u.festival_id = p.ID AND u.status IN ('applied','auto_applied')
				WHERE p.post_type='festi' AND p.post_status IN ('publish','draft')
				GROUP BY p.ID
				HAVING last_applied IS NOT NULL AND last_applied >= %s
			) t",
			$cutoff
		) );

		$stale_count = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(1) FROM (
				SELECT p.ID, MAX(u.applied_at) AS last_applied
				FROM {$posts} p
				LEFT JOIN {$updates} u ON u.festival_id = p.ID AND u.status IN ('applied','auto_applied')
				WHERE p.post_type='festi' AND p.post_status IN ('publish','draft')
				GROUP BY p.ID
				HAVING last_applied IS NULL OR last_applied < %s
			) t",
			$cutoff
		) );

		$recent_url = admin_url( 'admin.php?page=mfu-updates&mfu_recent=' . $recent_days );
		$stale_url = admin_url( 'admin.php?page=mfu-updates&mfu_stale=' . $recent_days );

		$wrapper_style = $inline
			? 'display:flex; gap:12px; flex-wrap:wrap; margin:0; flex:1 1 320px;'
			: 'display:flex; gap:12px; flex-wrap:wrap; max-width:640px; margin:12px 0;';
		echo '<div style="' . esc_attr( $wrapper_style ) . '">';
		echo '<div style="flex:1 1 240px; padding:10px 12px; border:1px solid #dcdcde; border-left:4px solid #00a32a; background:#f6fffa;">';
		echo '<div style="font-weight:600; margin-bottom:4px;">Actualizados ultima semana</div>';
		echo '<div style="font-size:20px; font-weight:700; margin-bottom:6px;">' . esc_html( $recent_count ) . '</div>';
		echo '<a href="' . esc_url( $recent_url ) . '">Ver festivales</a>';
		echo '</div>';

		echo '<div style="flex:1 1 240px; padding:10px 12px; border:1px solid #dcdcde; border-left:4px solid #d63638; background:#fff8f8;">';
		echo '<div style="font-weight:600; margin-bottom:4px;">Sin actualizar +7 dias</div>';
		echo '<div style="font-size:20px; font-weight:700; margin-bottom:6px;">' . esc_html( $stale_count ) . '</div>';
		echo '<a href="' . esc_url( $stale_url ) . '">Ver festivales</a>';
		echo '</div>';
		echo '</div>';
	}

	private function render_clear_status_form() {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin:10px 0;">';
		echo '<input type="hidden" name="action" value="mfu_clear_by_status" />';
		wp_nonce_field( 'mfu_clear_by_status', '_mfu_nonce' );
		echo '<label for="mfu_clear_target" style="margin-right:8px;">Limpiar:</label>';
		echo '<select name="mfu_clear_target" id="mfu_clear_target">';
		echo '<optgroup label="Cola">';
		echo '<option value="jobs:queued">en cola</option>';
		echo '<option value="jobs:running">procesando</option>';
		echo '<option value="jobs:done">completado</option>';
		echo '<option value="jobs:error">error</option>';
		echo '<option value="jobs:all">todos</option>';
		echo '</optgroup>';
		echo '<optgroup label="Revisiones">';
		echo '<option value="updates:pending_review">pendiente de revision</option>';
		echo '<option value="updates:no_change">sin cambios</option>';
		echo '<option value="updates:applied">aplicado</option>';
		echo '<option value="updates:auto_applied">autoaplicado</option>';
		echo '<option value="updates:rejected">rechazado</option>';
		echo '<option value="updates:all">todos</option>';
		echo '</optgroup>';
		echo '</select> ';
		echo '<button class="button" type="submit">Limpiar</button>';
		echo '</form>';
	}

	public function handle_clear_error_log() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No permitido' );
		}

		check_admin_referer( 'mfu_clear_error_log', '_mfu_nonce' );

		update_option( 'mfu_error_log', array(), false );

		wp_redirect( admin_url( 'admin.php?page=mfu-errors' ) );
		exit;
	}

	public function handle_clear_by_status() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No permitido' );
		}
		check_admin_referer( 'mfu_clear_by_status', '_mfu_nonce' );

		$target = isset( $_POST['mfu_clear_target'] ) ? sanitize_text_field( wp_unslash( $_POST['mfu_clear_target'] ) ) : '';
		if ( $target === '' ) {
			wp_redirect( admin_url( 'admin.php?page=mfu-updates' ) );
			exit;
		}

		$parts = explode( ':', $target, 2 );
		$scope = $parts[0] ?? '';
		$status = $parts[1] ?? '';

		global $wpdb;
		if ( $scope === 'jobs' ) {
			$table = MFU_DB::table( 'jobs' );
			if ( $status === 'all' ) {
				$wpdb->query( "TRUNCATE TABLE {$table}" );
			} else {
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE status=%s", $status ) );
			}
		} elseif ( $scope === 'updates' ) {
			$table = MFU_DB::table( 'updates' );
			if ( $status === 'all' ) {
				$wpdb->query( "TRUNCATE TABLE {$table}" );
			} else {
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE status=%s", $status ) );
			}
		}

		wp_redirect( admin_url( 'admin.php?page=mfu-updates' ) );
		exit;
	}

	public function handle_enqueue_single() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No permitido' );
		}
		$festival_id = isset( $_GET['festival_id'] ) ? (int) $_GET['festival_id'] : 0;
		check_admin_referer( 'mfu_enqueue_single_' . $festival_id );
		if ( $festival_id > 0 ) {
			MFU_Cron::enqueue_job( $festival_id, 10, 'manual' );
		}
		wp_redirect( admin_url( 'admin.php?page=mfu-updates' ) );
		exit;
	}

	public function handle_dequeue_single() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No permitido' );
		}
		$festival_id = isset( $_GET['festival_id'] ) ? (int) $_GET['festival_id'] : 0;
		check_admin_referer( 'mfu_dequeue_single_' . $festival_id );
		if ( $festival_id > 0 ) {
			global $wpdb;
			$table = MFU_DB::table( 'jobs' );
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM {$table} WHERE festival_id=%d AND status='queued'",
				$festival_id
			) );
		}
		wp_redirect( admin_url( 'admin.php?page=mfu-updates' ) );
		exit;
	}

	public function handle_clear_queue_single() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No permitido' );
		}
		$festival_id = isset( $_GET['festival_id'] ) ? (int) $_GET['festival_id'] : 0;
		check_admin_referer( 'mfu_clear_queue_single_' . $festival_id );
		if ( $festival_id > 0 ) {
			global $wpdb;
			$table = MFU_DB::table( 'jobs' );
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM {$table} WHERE festival_id=%d",
				$festival_id
			) );
		}
		wp_redirect( admin_url( 'admin.php?page=mfu-updates' ) );
		exit;
	}

	public function handle_clear_all_single() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No permitido' );
		}
		$festival_id = isset( $_GET['festival_id'] ) ? (int) $_GET['festival_id'] : 0;
		check_admin_referer( 'mfu_clear_all_single_' . $festival_id );
		if ( $festival_id > 0 ) {
			global $wpdb;
			$jobs = MFU_DB::table( 'jobs' );
			$updates = MFU_DB::table( 'updates' );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$jobs} WHERE festival_id=%d", $festival_id ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$updates} WHERE festival_id=%d", $festival_id ) );

			$log = get_option( 'mfu_error_log', array() );
			if ( is_array( $log ) ) {
				$log = array_values(
					array_filter(
						$log,
						static function ( $row ) use ( $festival_id ) {
							return ! isset( $row['festival_id'] ) || (int) $row['festival_id'] !== $festival_id;
						}
					)
				);
				update_option( 'mfu_error_log', $log, false );
			}
		}
		wp_redirect( admin_url( 'admin.php?page=mfu-updates' ) );
		exit;
	}

	public function handle_enqueue_all() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No permitido' );
		}
		check_admin_referer( 'mfu_enqueue_all' );

		$query = new WP_Query(
			array(
				'post_type' => 'festi',
				'post_status' => array( 'publish', 'draft' ),
				'posts_per_page' => -1,
				'fields' => 'ids',
			)
		);
		foreach ( $query->posts as $festival_id ) {
			MFU_Cron::enqueue_job( (int) $festival_id, 10, 'manual' );
		}
		wp_redirect( admin_url( 'admin.php?page=mfu-updates' ) );
		exit;
	}

	public function handle_process_now() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No permitido' );
		}
		check_admin_referer( 'mfu_process_now' );
		MFU_Cron::process_queue();
		wp_redirect( admin_url( 'admin.php?page=mfu-updates' ) );
		exit;
	}

	public function handle_clear_queue() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No permitido' );
		}
		check_admin_referer( 'mfu_clear_queue' );
		global $wpdb;
		$table = MFU_DB::table( 'jobs' );
		$wpdb->query( "TRUNCATE TABLE {$table}" );
		wp_redirect( admin_url( 'admin.php?page=mfu-updates' ) );
		exit;
	}

	public function handle_bulk_reject() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No permitido' );
		}
		check_admin_referer( 'mfu_bulk_reject', '_mfu_nonce' );

		$festival_ids = isset( $_POST['festival_ids'] ) ? array_map( 'intval', (array) $_POST['festival_ids'] ) : array();
		if ( empty( $festival_ids ) ) {
			wp_redirect( admin_url( 'admin.php?page=mfu-updates' ) );
			exit;
		}

		global $wpdb;
		$table = MFU_DB::table( 'updates' );
		foreach ( $festival_ids as $festival_id ) {
			$update = $this->get_latest_pending_update( (int) $festival_id );
			if ( ! $update ) {
				continue;
			}
			$wpdb->update(
				$table,
				array( 'status' => 'rejected' ),
				array( 'id' => (int) $update->id ),
				array( '%s' ),
				array( '%d' )
			);
		}
		wp_redirect( admin_url( 'admin.php?page=mfu-updates' ) );
		exit;
	}

	public function handle_save_source() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No permitido' );
		}
		check_admin_referer( 'mfu_save_source', '_mfu_nonce' );

		$festival_id = isset( $_POST['festival_id'] ) ? (int) $_POST['festival_id'] : 0;
		$source_type = isset( $_POST['source_type'] ) ? sanitize_text_field( wp_unslash( $_POST['source_type'] ) ) : '';
		$url = isset( $_POST['source_url'] ) ? esc_url_raw( wp_unslash( $_POST['source_url'] ) ) : '';

		if ( $festival_id <= 0 || $url === '' ) {
			wp_redirect( admin_url( 'admin.php?page=mfu-test' ) );
			exit;
		}

		if ( $source_type === 'instagram' ) {
			update_post_meta( $festival_id, 'mf_instagram', $url );
		} elseif ( $source_type === 'web' ) {
			update_post_meta( $festival_id, 'mf_web_oficial', $url );
		}

		wp_redirect( admin_url( 'admin.php?page=mfu-test&saved=1' ) );
		exit;
	}

	public function handle_apply_content_preview() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No permitido' );
		}
		check_admin_referer( 'mfu_apply_content_preview', '_mfu_nonce' );

		$festival_id = isset( $_POST['festival_id'] ) ? (int) $_POST['festival_id'] : 0;
		$content = isset( $_POST['content'] ) ? wp_unslash( $_POST['content'] ) : '';
		if ( $festival_id <= 0 || $content === '' ) {
			wp_redirect( admin_url( 'admin.php?page=mfu-test' ) );
			exit;
		}

		$content = $this->strip_ticket_links( $content );
		$content = $this->strip_ticket_mentions( $content );

		wp_update_post(
			array(
				'ID' => $festival_id,
				'post_content' => $content,
			)
		);

		wp_redirect( admin_url( 'admin.php?page=mfu-test&applied=1' ) );
		exit;
	}

	public function handle_update_edicion() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No permitido' );
		}

		$festival_id = isset( $_POST['festival_id'] ) ? (int) $_POST['festival_id'] : 0;
		check_admin_referer( 'mfu_update_edicion_' . $festival_id );

		$edicion = isset( $_POST['edicion'] ) ? sanitize_text_field( wp_unslash( $_POST['edicion'] ) ) : '';
		if ( $festival_id > 0 ) {
			update_post_meta( $festival_id, 'edicion', $edicion );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=mfu-updates' ) );
		exit;
	}

	public function handle_apply_update() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No permitido' );
		}

		$update_id = isset( $_GET['update_id'] ) ? (int) $_GET['update_id'] : 0;
		check_admin_referer( 'mfu_apply_update_' . $update_id );
		if ( $update_id <= 0 ) {
			wp_redirect( admin_url( 'admin.php?page=mfu-updates' ) );
			exit;
		}

		$update = $this->get_update_row( $update_id );
		if ( ! $update ) {
			wp_redirect( admin_url( 'admin.php?page=mfu-updates' ) );
			exit;
		}

		$this->apply_update_row( $update );

		wp_redirect( admin_url( 'admin.php?page=mfu-updates' ) );
		exit;
	}

	public function handle_bulk_apply() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No permitido' );
		}

		check_admin_referer( 'mfu_bulk_apply', '_mfu_nonce' );

		$festival_ids = isset( $_POST['festival_ids'] ) ? array_map( 'intval', (array) $_POST['festival_ids'] ) : array();
		if ( empty( $festival_ids ) ) {
			wp_redirect( admin_url( 'admin.php?page=mfu-updates' ) );
			exit;
		}

		foreach ( $festival_ids as $festival_id ) {
			$update = $this->get_latest_pending_update( (int) $festival_id );
			if ( $update ) {
				$this->apply_update_row( $update );
			}
		}

		wp_redirect( admin_url( 'admin.php?page=mfu-updates' ) );
		exit;
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<div class="wrap">';
		echo '<h1>Ajustes - Festival Updates</h1>';
		echo '<form method="post" action="options.php">';
		settings_fields( 'mfu_settings_group' );
		do_settings_sections( 'mfu-settings' );
		submit_button();
		echo '</form>';
		echo '</div>';
	}

	public function render_debug_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action_log = get_option( 'mfu_action_log', array() );
		if ( ! is_array( $action_log ) ) {
			$action_log = array();
		}

		global $wpdb;
		$table = MFU_DB::table( 'updates' );
		$rows = $wpdb->get_results( "SELECT id, festival_id, detected_at, status, evidence_json, diffs_json FROM {$table} ORDER BY id DESC LIMIT 20" );

		echo '<div class="wrap">';
		echo '<h1>Debug - Ultimos procesos</h1>';
		if ( ! empty( $action_log ) ) {
			echo '<h2>Log de acciones recientes</h2>';
			echo '<table class="widefat striped" style="max-width:1200px;">';
			echo '<thead><tr><th>Fecha</th><th>Accion</th><th>Detalle</th></tr></thead><tbody>';
			foreach ( array_reverse( $action_log ) as $row ) {
				$time = isset( $row['time'] ) ? (string) $row['time'] : '';
				$action = isset( $row['action'] ) ? (string) $row['action'] : '';
				$data = isset( $row['data'] ) ? wp_json_encode( $row['data'] ) : '';
				echo '<tr>';
				echo '<td>' . esc_html( $time ) . '</td>';
				echo '<td>' . esc_html( $action ) . '</td>';
				echo '<td><code style="white-space:pre-wrap;">' . esc_html( $data ) . '</code></td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
		}
		echo '<p>Se muestran hasta 20 procesos recientes con logs si el modo debug estaba activado.</p>';
		echo '<p>';
		echo '<a class="button button-primary" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mfu_debug_download' ), 'mfu_debug_download' ) ) . '">Descargar debug (JSON)</a> ';
		echo '<a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mfu_debug_clear' ), 'mfu_debug_clear' ) ) . '" onclick="return confirm(\'¿Borrar todos los logs debug?\');">Limpiar debug</a>';
		echo '</p>';

		if ( empty( $rows ) ) {
			echo '<p>No hay procesos recientes.</p>';
			echo '</div>';
			return;
		}

		foreach ( $rows as $row ) {
			$payload = $row->evidence_json ? json_decode( $row->evidence_json, true ) : array();
			$debug = isset( $payload['debug_log'] ) ? $payload['debug_log'] : array();
			if ( empty( $debug ) || ! is_array( $debug ) ) {
				continue;
			}
			$festival = get_post( (int) $row->festival_id );
			$title = $festival ? $festival->post_title : 'Festival #' . (int) $row->festival_id;
			echo '<h3>' . esc_html( $title ) . ' - #' . (int) $row->id . ' (' . esc_html( $row->status ) . ') - ' . esc_html( $row->detected_at ) . '</h3>';
			$snapshot = null;
			foreach ( $debug as $event ) {
				if ( isset( $event['stage'] ) && $event['stage'] === 'festival_snapshot' && isset( $event['data'] ) && is_array( $event['data'] ) ) {
					$snapshot = $event['data'];
					break;
				}
			}
			if ( is_array( $snapshot ) ) {
				echo '<h4>Estado base del festival (antes de actualizar)</h4>';
				echo '<table class="widefat striped" style="max-width:900px; margin-bottom:10px;">';
				echo '<thead><tr><th>Campo</th><th>Valor</th></tr></thead><tbody>';
				foreach ( $snapshot as $key => $value ) {
					$display = is_scalar( $value ) ? (string) $value : wp_json_encode( $value );
					echo '<tr>';
					echo '<td><strong>' . esc_html( (string) $key ) . '</strong></td>';
					echo '<td>' . esc_html( $display ) . '</td>';
					echo '</tr>';
				}
				echo '</tbody></table>';
			}
			$pipeline = is_array( $payload ) && ! empty( $payload['pipeline_log'] ) && is_array( $payload['pipeline_log'] )
				? $payload['pipeline_log']
				: array();
			if ( ! empty( $pipeline ) ) {
				echo '<h4>Pipeline (pasos + resultados)</h4>';
				echo '<table class="widefat striped" style="max-width:900px; margin-bottom:10px;">';
				echo '<thead><tr><th>#</th><th>Hora</th><th>Paso</th><th>Resultado</th><th>Datos</th></tr></thead><tbody>';
				$idx = 1;
				foreach ( $pipeline as $event ) {
					$time = isset( $event['time'] ) ? (string) $event['time'] : '';
					$step = isset( $event['step'] ) ? (string) $event['step'] : '';
					$result = isset( $event['result'] ) ? (string) $event['result'] : '';
					$data = isset( $event['data'] ) ? $event['data'] : array();
					$data_display = is_scalar( $data ) ? (string) $data : ( $data ? wp_json_encode( $data ) : '' );
					echo '<tr>';
					echo '<td>' . esc_html( (string) $idx ) . '</td>';
					echo '<td>' . esc_html( $time ) . '</td>';
					echo '<td><strong>' . esc_html( $step ) . '</strong></td>';
					echo '<td>' . esc_html( $result ) . '</td>';
					echo '<td>' . esc_html( $data_display ) . '</td>';
					echo '</tr>';
					$idx++;
				}
				echo '</tbody></table>';
			}
			$diffs = $row->diffs_json ? json_decode( $row->diffs_json, true ) : array();
			if ( ! empty( $diffs ) && is_array( $diffs ) ) {
				echo '<h4>Campos detectados para actualizar</h4>';
				echo '<table class="widefat striped" style="max-width:900px; margin-bottom:10px;">';
				echo '<thead><tr><th>Campo</th><th>Antes</th><th>Despues</th></tr></thead><tbody>';
				foreach ( $diffs as $key => $diff ) {
					$before = is_array( $diff ) && array_key_exists( 'before', $diff ) ? $diff['before'] : '';
					$after = is_array( $diff ) && array_key_exists( 'after', $diff ) ? $diff['after'] : '';
					$before_display = $this->format_diff_value( $key, $before );
					$after_display = $this->format_diff_value( $key, $after );
					echo '<tr>';
					echo '<td><strong>' . esc_html( (string) $key ) . '</strong></td>';
					echo '<td>' . esc_html( is_scalar( $before_display ) ? (string) $before_display : wp_json_encode( $before_display ) ) . '</td>';
					echo '<td>' . esc_html( is_scalar( $after_display ) ? (string) $after_display : wp_json_encode( $after_display ) ) . '</td>';
					echo '</tr>';
				}
				echo '</tbody></table>';
			}
			if ( is_array( $payload ) && ! empty( $payload['facts'] ) && is_array( $payload['facts'] ) ) {
				$facts = $payload['facts']['facts'] ?? array();
				if ( is_array( $facts ) && ! empty( $facts ) ) {
					echo '<h4>Evidencia por campo (hechos detectados)</h4>';
					echo '<table class="widefat striped" style="max-width:900px; margin-bottom:10px;">';
					echo '<thead><tr><th>Campo</th><th>Valor</th><th>Evidencia</th></tr></thead><tbody>';
					foreach ( $facts as $key => $fact ) {
						$value = isset( $fact['value'] ) ? $fact['value'] : '';
						$evidence = isset( $fact['evidence'] ) && is_array( $fact['evidence'] ) ? $fact['evidence'] : array();
						$val_display = is_scalar( $value ) ? (string) $value : wp_json_encode( $value );
						$ev_lines = array();
						foreach ( $evidence as $ev ) {
							$snippet = isset( $ev['snippet'] ) ? (string) $ev['snippet'] : '';
							$url = isset( $ev['url'] ) ? (string) $ev['url'] : '';
							$line = trim( $snippet . ( $url ? ' — ' . $url : '' ) );
							if ( $line !== '' ) {
								$ev_lines[] = $line;
							}
						}
						echo '<tr>';
						echo '<td><strong>' . esc_html( (string) $key ) . '</strong></td>';
						echo '<td>' . esc_html( $val_display ) . '</td>';
						echo '<td>' . esc_html( $ev_lines ? implode( "\n", $ev_lines ) : '' ) . '</td>';
						echo '</tr>';
					}
					echo '</tbody></table>';
				}
			}
			if ( is_array( $payload ) && ! empty( $payload['updated_content'] ) && $festival ) {
				$current_content = (string) $festival->post_content;
				$updated_content = (string) $payload['updated_content'];
				$diff_html = wp_text_diff( $current_content, $updated_content, array( 'show_split_view' => false ) );
				if ( $diff_html ) {
					echo '<h4>Diff contenido (IA)</h4>';
					echo '<div style="border:1px solid #c3c4c7; background:#fff; padding:10px; margin-bottom:12px;">';
					echo wp_kses_post( $diff_html );
					echo '</div>';
				}
			}
			echo '<ol style="margin-left:18px;">';
			$index = 1;
			foreach ( $debug as $event ) {
				$stage = isset( $event['stage'] ) ? (string) $event['stage'] : 'event';
				$time = isset( $event['time'] ) ? (string) $event['time'] : '';
				$data = isset( $event['data'] ) ? $event['data'] : array();
				$label = $time !== '' ? $time . ' - ' . $stage : $stage;
				echo '<li style="margin:6px 0;">';
				echo '<strong>' . esc_html( $index . '. ' . $label ) . '</strong>';
				echo '<pre style="white-space: pre-wrap; background:#fff; border:1px solid #ccc; padding:10px; margin:6px 0 0;">';
				echo esc_html( wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
				echo '</pre>';
				echo '</li>';
				$index++;
			}
			echo '</ol>';
		}

		echo '</div>';
	}

	public function handle_debug_download() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No permitido' );
		}
		check_admin_referer( 'mfu_debug_download' );

		$payload = $this->build_debug_payload( 50 );
		$filename = 'mfu-debug-' . gmdate( 'Ymd-His' ) . '.json';

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		exit;
	}

	public function handle_debug_clear() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No permitido' );
		}
		check_admin_referer( 'mfu_debug_clear' );

		$this->clear_debug_logs();
		wp_redirect( admin_url( 'admin.php?page=mfu-debug' ) );
		exit;
	}

	private function build_debug_payload( $limit = 50 ) {
		global $wpdb;
		$table = MFU_DB::table( 'updates' );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, festival_id, detected_at, status, evidence_json, diffs_json FROM {$table} ORDER BY id DESC LIMIT %d", (int) $limit ) );
		$out = array();
		foreach ( $rows as $row ) {
			$payload = $row->evidence_json ? json_decode( $row->evidence_json, true ) : array();
			$debug = isset( $payload['debug_log'] ) ? $payload['debug_log'] : array();
			if ( empty( $debug ) || ! is_array( $debug ) ) {
				continue;
			}
			$festival = get_post( (int) $row->festival_id );
			$diffs = $row->diffs_json ? json_decode( $row->diffs_json, true ) : array();
			$out[] = array(
				'update_id' => (int) $row->id,
				'festival_id' => (int) $row->festival_id,
				'festival_title' => $festival ? $festival->post_title : '',
				'detected_at' => $row->detected_at,
				'status' => $row->status,
				'diffs' => $diffs,
				'debug_log' => $debug,
			);
		}
		return array(
			'generated_at' => current_time( 'mysql' ),
			'count' => count( $out ),
			'items' => $out,
		);
	}

	private function clear_debug_logs() {
		global $wpdb;
		$table = MFU_DB::table( 'updates' );
		$rows = $wpdb->get_results( "SELECT id, evidence_json FROM {$table} ORDER BY id DESC" );
		foreach ( $rows as $row ) {
			$payload = $row->evidence_json ? json_decode( $row->evidence_json, true ) : array();
			if ( ! is_array( $payload ) || empty( $payload['debug_log'] ) ) {
				continue;
			}
			unset( $payload['debug_log'] );
			$wpdb->update(
				$table,
				array( 'evidence_json' => wp_json_encode( $payload ) ),
				array( 'id' => (int) $row->id ),
				array( '%s' ),
				array( '%d' )
			);
		}
	}

	public function render_usage_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options = get_option( MFU_OPTION_KEY, array() );
		$cost_input = isset( $options['cost_input'] ) ? (float) $options['cost_input'] : 0.0;
		$cost_output = isset( $options['cost_output'] ) ? (float) $options['cost_output'] : 0.0;
		$cost_currency = isset( $options['cost_currency'] ) ? $options['cost_currency'] : 'EUR';
		$cost_extract_input = isset( $options['cost_extract_input'] ) ? (float) $options['cost_extract_input'] : 0.0;
		$cost_extract_output = isset( $options['cost_extract_output'] ) ? (float) $options['cost_extract_output'] : 0.0;
		$cost_write_input = isset( $options['cost_write_input'] ) ? (float) $options['cost_write_input'] : 0.0;
		$cost_write_output = isset( $options['cost_write_output'] ) ? (float) $options['cost_write_output'] : 0.0;
		$pplx_cost_input = isset( $options['pplx_cost_input'] ) ? (float) $options['pplx_cost_input'] : 0.0;
		$pplx_cost_output = isset( $options['pplx_cost_output'] ) ? (float) $options['pplx_cost_output'] : 0.0;
		$pplx_cost_extract_input = isset( $options['pplx_cost_extract_input'] ) ? (float) $options['pplx_cost_extract_input'] : 0.0;
		$pplx_cost_extract_output = isset( $options['pplx_cost_extract_output'] ) ? (float) $options['pplx_cost_extract_output'] : 0.0;
		$pplx_cost_write_input = isset( $options['pplx_cost_write_input'] ) ? (float) $options['pplx_cost_write_input'] : 0.0;
		$pplx_cost_write_output = isset( $options['pplx_cost_write_output'] ) ? (float) $options['pplx_cost_write_output'] : 0.0;
		$usage = get_option( 'mfu_usage_log', array() );
		$today = current_time( 'Y-m-d' );
		$days = isset( $usage['days'] ) && is_array( $usage['days'] ) ? $usage['days'] : array();
		$actions = isset( $usage['actions'] ) && is_array( $usage['actions'] ) ? $usage['actions'] : array();
		$providers = isset( $usage['providers'] ) && is_array( $usage['providers'] ) ? $usage['providers'] : array();
		$models = isset( $usage['models'] ) && is_array( $usage['models'] ) ? $usage['models'] : array();
		$last = isset( $usage['last'] ) && is_array( $usage['last'] ) ? $usage['last'] : array();

		$last_30 = array_slice( array_reverse( $days, true ), 0, 30, true );
		$totals = array(
			'requests' => 0,
			'input_tokens' => 0,
			'output_tokens' => 0,
			'cost' => 0.0,
		);

		foreach ( $last_30 as $day => $row ) {
			$totals['requests'] += (int) ( $row['requests'] ?? 0 );
			$totals['input_tokens'] += (int) ( $row['input_tokens'] ?? 0 );
			$totals['output_tokens'] += (int) ( $row['output_tokens'] ?? 0 );
			$totals['cost'] += (float) ( $row['cost'] ?? 0 );
		}

		echo '<div class="wrap">';
		echo '<h1>Consumo de APIs</h1>';
		echo '<p>Periodo: ultimos 30 dias. Hoy: ' . esc_html( $today ) . '.</p>';
		echo '<p class="description">Moneda: ' . esc_html( $cost_currency ) . '.</p>';
		echo '<p class="description">OpenAI (por 1M tokens): base ' . esc_html( number_format( $cost_input, 4, '.', '' ) ) . ' / ' . esc_html( number_format( $cost_output, 4, '.', '' ) ) . ', extraccion ' . esc_html( number_format( $cost_extract_input, 4, '.', '' ) ) . ' / ' . esc_html( number_format( $cost_extract_output, 4, '.', '' ) ) . ', redaccion ' . esc_html( number_format( $cost_write_input, 4, '.', '' ) ) . ' / ' . esc_html( number_format( $cost_write_output, 4, '.', '' ) ) . '.</p>';
		echo '<p class="description">Perplexity (por 1M tokens): base ' . esc_html( number_format( $pplx_cost_input, 4, '.', '' ) ) . ' / ' . esc_html( number_format( $pplx_cost_output, 4, '.', '' ) ) . ', extraccion ' . esc_html( number_format( $pplx_cost_extract_input, 4, '.', '' ) ) . ' / ' . esc_html( number_format( $pplx_cost_extract_output, 4, '.', '' ) ) . ', redaccion ' . esc_html( number_format( $pplx_cost_write_input, 4, '.', '' ) ) . ' / ' . esc_html( number_format( $pplx_cost_write_output, 4, '.', '' ) ) . '.</p>';
		if ( $cost_input <= 0 && $cost_output <= 0 && $cost_extract_input <= 0 && $cost_extract_output <= 0 && $cost_write_input <= 0 && $cost_write_output <= 0 && $pplx_cost_input <= 0 && $pplx_cost_output <= 0 && $pplx_cost_extract_input <= 0 && $pplx_cost_extract_output <= 0 && $pplx_cost_write_input <= 0 && $pplx_cost_write_output <= 0 ) {
			echo '<p class="description" style="color:#b32d2e;">El coste no se muestra porque los precios por token son 0. Configuralos en Ajustes.</p>';
		}
		echo '<h2>Totales</h2>';
		echo '<ul>';
		echo '<li><strong>Peticiones:</strong> ' . esc_html( $totals['requests'] ) . '</li>';
		echo '<li><strong>Tokens input (estimados):</strong> ' . esc_html( $totals['input_tokens'] ) . '</li>';
		echo '<li><strong>Tokens output (estimados):</strong> ' . esc_html( $totals['output_tokens'] ) . '</li>';
		echo '<li><strong>Coste estimado:</strong> ' . esc_html( number_format( $totals['cost'], 4, '.', '' ) ) . '</li>';
		echo '</ul>';

		if ( ! empty( $last ) ) {
			echo '<h2>Ultima llamada</h2>';
			echo '<ul>';
			echo '<li><strong>Accion:</strong> ' . esc_html( $last['action'] ?? '' ) . '</li>';
			echo '<li><strong>Proveedor:</strong> ' . esc_html( $last['provider'] ?? '' ) . '</li>';
			echo '<li><strong>Modelo:</strong> ' . esc_html( $last['model'] ?? '' ) . '</li>';
			echo '<li><strong>Input tokens:</strong> ' . esc_html( (int) ( $last['input_tokens'] ?? 0 ) ) . '</li>';
			echo '<li><strong>Output tokens:</strong> ' . esc_html( (int) ( $last['output_tokens'] ?? 0 ) ) . '</li>';
			echo '<li><strong>Coste:</strong> ' . esc_html( number_format( (float) ( $last['cost'] ?? 0 ), 4, '.', '' ) ) . '</li>';
			echo '<li><strong>Fecha:</strong> ' . esc_html( $last['when'] ?? '' ) . '</li>';
			echo '</ul>';
		}

		echo '<h2>Por proveedor</h2>';
		if ( empty( $providers ) ) {
			echo '<p>Sin datos.</p>';
		} else {
			echo '<table class="widefat fixed striped"><thead><tr>';
			echo '<th>Proveedor</th><th>Peticiones</th><th>Input</th><th>Output</th><th>Coste</th>';
			echo '</tr></thead><tbody>';
			foreach ( $providers as $provider => $row ) {
				echo '<tr>';
				echo '<td>' . esc_html( $provider ) . '</td>';
				echo '<td>' . esc_html( (int) ( $row['requests'] ?? 0 ) ) . '</td>';
				echo '<td>' . esc_html( (int) ( $row['input_tokens'] ?? 0 ) ) . '</td>';
				echo '<td>' . esc_html( (int) ( $row['output_tokens'] ?? 0 ) ) . '</td>';
				echo '<td>' . esc_html( number_format( (float) ( $row['cost'] ?? 0 ), 4, '.', '' ) ) . '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
		}

		echo '<h2>Por modelo</h2>';
		if ( empty( $models ) ) {
			echo '<p>Sin datos.</p>';
		} else {
			echo '<table class="widefat fixed striped"><thead><tr>';
			echo '<th>Modelo</th><th>Peticiones</th><th>Input</th><th>Output</th><th>Coste</th>';
			echo '</tr></thead><tbody>';
			foreach ( $models as $model => $row ) {
				echo '<tr>';
				echo '<td>' . esc_html( $model ) . '</td>';
				echo '<td>' . esc_html( (int) ( $row['requests'] ?? 0 ) ) . '</td>';
				echo '<td>' . esc_html( (int) ( $row['input_tokens'] ?? 0 ) ) . '</td>';
				echo '<td>' . esc_html( (int) ( $row['output_tokens'] ?? 0 ) ) . '</td>';
				echo '<td>' . esc_html( number_format( (float) ( $row['cost'] ?? 0 ), 4, '.', '' ) ) . '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
		}

		echo '<h2>Por accion</h2>';
		if ( empty( $actions ) ) {
			echo '<p>Sin datos.</p>';
		} else {
			echo '<table class="widefat fixed striped"><thead><tr>';
			echo '<th>Accion</th><th>Peticiones</th><th>Input</th><th>Output</th><th>Coste</th>';
			echo '</tr></thead><tbody>';
			foreach ( $actions as $action => $row ) {
				echo '<tr>';
				echo '<td>' . esc_html( $action ) . '</td>';
				echo '<td>' . esc_html( (int) ( $row['requests'] ?? 0 ) ) . '</td>';
				echo '<td>' . esc_html( (int) ( $row['input_tokens'] ?? 0 ) ) . '</td>';
				echo '<td>' . esc_html( (int) ( $row['output_tokens'] ?? 0 ) ) . '</td>';
				echo '<td>' . esc_html( number_format( (float) ( $row['cost'] ?? 0 ), 4, '.', '' ) ) . '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
		}

		echo '<h2>Detalle diario</h2>';
		if ( empty( $last_30 ) ) {
			echo '<p>Sin datos.</p>';
		} else {
			echo '<table class="widefat fixed striped"><thead><tr>';
			echo '<th>Fecha</th><th>Peticiones</th><th>Input</th><th>Output</th><th>Coste</th>';
			echo '</tr></thead><tbody>';
			foreach ( $last_30 as $day => $row ) {
				echo '<tr>';
				echo '<td>' . esc_html( $day ) . '</td>';
				echo '<td>' . esc_html( (int) ( $row['requests'] ?? 0 ) ) . '</td>';
				echo '<td>' . esc_html( (int) ( $row['input_tokens'] ?? 0 ) ) . '</td>';
				echo '<td>' . esc_html( (int) ( $row['output_tokens'] ?? 0 ) ) . '</td>';
				echo '<td>' . esc_html( number_format( (float) ( $row['cost'] ?? 0 ), 4, '.', '' ) ) . '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
		}

		echo '</div>';
	}

	public function render_test_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<div class="wrap">';
		echo '<h1>Testeo de fuentes</h1>';
		echo '<p>Esta seccion compara la respuesta de Perplexity y OpenAI (web search) para la misma query.</p>';

		$ai = new MFU_AI();
		$default_query = '101 Music Festival Costa del Sol 2026 fechas, cartel y novedades';
		$test_query = $default_query;
		$test_openai_model = $this->get_settings_value( 'model_write', 'gpt-5-mini' );
		$instagram_source = '';
		$result_pplx = null;
		$result_pplx_sources = null;
		$result_openai = null;
		$error_pplx = null;
		$error_pplx_sources = null;
		$error_openai = null;

		if ( isset( $_POST['mfu_test_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mfu_test_nonce'] ) ), 'mfu_test' ) ) {
			$test_query = sanitize_textarea_field( wp_unslash( $_POST['mfu_test_query'] ?? $default_query ) );
			$test_openai_model = sanitize_text_field( wp_unslash( $_POST['mfu_openai_model'] ?? $test_openai_model ) );
			$instagram_source = $this->normalize_instagram_source( wp_unslash( $_POST['mfu_instagram_source'] ?? '' ) );
			$test_query_for_ai = $instagram_source ? ( $test_query . "\n\nFuente adicional (Instagram): " . $instagram_source ) : $test_query;

			$result_pplx = $ai->perplexity_answer( $test_query_for_ai, 'es', 'ES' );
			if ( is_wp_error( $result_pplx ) ) {
				$error_pplx = $result_pplx->get_error_message();
				$result_pplx = null;
			}

			$result_pplx_sources = $ai->perplexity_search( $test_query, 8, 'es', 'ES' );
			if ( is_wp_error( $result_pplx_sources ) ) {
				$error_pplx_sources = $result_pplx_sources->get_error_message();
				$result_pplx_sources = null;
			}

			$result_openai = $ai->openai_web_search_answer( $test_query_for_ai, 'es', 'ES', $test_openai_model );
			if ( is_wp_error( $result_openai ) ) {
				$error_openai = $result_openai->get_error_message();
				$result_openai = null;
			}
		}

		echo '<h2>Comparativa</h2>';
		if ( ! $ai->has_perplexity_key() ) {
			echo '<p><strong>Falta Perplexity API Key.</strong> Configura la clave en Ajustes.</p>';
		}
		if ( ! $ai->has_openai_key() ) {
			echo '<p><strong>Falta OpenAI API Key.</strong> Configura la clave en Ajustes.</p>';
		}
		echo '<form method="post">';
		echo '<input type="hidden" name="mfu_test_nonce" value="' . esc_attr( wp_create_nonce( 'mfu_test' ) ) . '">';
		echo '<table class="form-table"><tbody>';
		echo '<tr><th scope="row"><label for="mfu_openai_model">Modelo OpenAI</label></th>';
		echo '<td><input name="mfu_openai_model" id="mfu_openai_model" type="text" class="regular-text" value="' . esc_attr( $test_openai_model ) . '"></td></tr>';
		echo '<tr><th scope="row"><label for="mfu_instagram_source">Fuente Instagram</label></th>';
		echo '<td><input name="mfu_instagram_source" id="mfu_instagram_source" type="text" class="regular-text" placeholder="https://www.instagram.com/usuario/" value="' . esc_attr( $instagram_source ) . '"></td></tr>';
		echo '<tr><th scope="row"><label for="mfu_test_query">Query</label></th>';
		echo '<td><textarea name="mfu_test_query" id="mfu_test_query" rows="3" class="large-text">' . esc_textarea( $test_query ) . '</textarea></td></tr>';
		echo '</tbody></table>';
		submit_button( 'Comparar respuestas' );
		echo '</form>';

		if ( $error_pplx ) {
			echo '<div class="notice notice-error"><p>Perplexity: ' . esc_html( $error_pplx ) . '</p></div>';
		}
		if ( $error_pplx_sources ) {
			echo '<div class="notice notice-error"><p>Perplexity fuentes: ' . esc_html( $error_pplx_sources ) . '</p></div>';
		}
		if ( $error_openai ) {
			echo '<div class="notice notice-error"><p>OpenAI: ' . esc_html( $error_openai ) . '</p></div>';
		}

		$pplx_text = is_string( $result_pplx ) ? $result_pplx : '';
		$openai_text = is_array( $result_openai ) ? (string) ( $result_openai['text'] ?? '' ) : '';
		$openai_sources = is_array( $result_openai ) ? ( $result_openai['sources'] ?? array() ) : array();
		$pplx_items = is_array( $result_pplx_sources ) ? ( $result_pplx_sources['items'] ?? array() ) : array();

		$openai_sources = $this->filter_sources_excluding_domain( $openai_sources, 'modofestival.es' );
		$pplx_items = $this->filter_items_excluding_domain( $pplx_items, 'modofestival.es' );

		if ( $instagram_source ) {
			$instagram_entry = array(
				'url' => $instagram_source,
				'title' => 'Instagram',
			);
			array_unshift( $openai_sources, $instagram_entry );
			$pplx_items = array_merge(
				array(
					array(
						'title' => 'Instagram',
						'url' => $instagram_source,
						'snippet' => '',
					),
				),
				$pplx_items
			);
		}
		$user_id = get_current_user_id();
		$download_url = '';

		if ( $user_id ) {
			$has_result = ( $pplx_text !== '' || $openai_text !== '' );
			$has_sources = ( ! empty( $pplx_items ) || ! empty( $openai_sources ) );
			$has_saved = (bool) get_user_meta( $user_id, 'mfu_last_test_result', true );

			if ( $has_result || $has_sources ) {
				$payload = array(
					'timestamp' => gmdate( 'c' ),
					'query' => $test_query,
					'instagram_source' => $instagram_source,
					'perplexity' => array(
						'text' => $pplx_text,
						'sources' => $pplx_items,
						'error' => $error_pplx,
						'sources_error' => $error_pplx_sources,
					),
					'openai' => array(
						'model' => $test_openai_model,
						'text' => $openai_text,
						'sources' => $openai_sources,
						'error' => $error_openai,
					),
				);
				update_user_meta( $user_id, 'mfu_last_test_result', wp_json_encode( $payload, JSON_PRETTY_PRINT ) );
			}

			if ( $has_result || $has_sources || $has_saved ) {
				$download_url = wp_nonce_url(
					admin_url( 'admin-post.php?action=mfu_download_test_result' ),
					'mfu_download_test_result',
					'_mfu_nonce'
				);
			}
		}

		if ( $pplx_text !== '' || $openai_text !== '' ) {
			echo '<h3>Respuestas</h3>';
			echo '<table class="widefat fixed striped"><thead><tr>';
			echo '<th>Perplexity</th><th>OpenAI (web)</th>';
			echo '</tr></thead><tbody><tr>';
			echo '<td style="vertical-align:top;">' . ( $pplx_text !== '' ? nl2br( esc_html( $pplx_text ) ) : '<em>Sin respuesta.</em>' ) . '</td>';
			echo '<td style="vertical-align:top;">' . ( $openai_text !== '' ? nl2br( esc_html( $openai_text ) ) : '<em>Sin respuesta.</em>' ) . '</td>';
			echo '</tr></tbody></table>';

			if ( $openai_text !== '' ) {
				$diff_openai = $this->diff_sentences( $pplx_text, $openai_text );
				echo '<h3>Diff (Perplexity vs OpenAI)</h3>';
				echo '<div style="display:flex;gap:24px;">';
				echo '<div style="flex:1;"><strong>Solo Perplexity</strong>';
				if ( empty( $diff_openai['pplx'] ) ) {
					echo '<p>Sin diferencias.</p>';
				} else {
					echo '<ul>';
					foreach ( $diff_openai['pplx'] as $sentence ) {
						echo '<li>' . esc_html( $sentence ) . '</li>';
					}
					echo '</ul>';
				}
				echo '</div>';
				echo '<div style="flex:1;"><strong>Solo OpenAI</strong>';
				if ( empty( $diff_openai['other'] ) ) {
					echo '<p>Sin diferencias.</p>';
				} else {
					echo '<ul>';
					foreach ( $diff_openai['other'] as $sentence ) {
						echo '<li>' . esc_html( $sentence ) . '</li>';
					}
					echo '</ul>';
				}
				echo '</div>';
				echo '</div>';
			}
		}

		echo '<h3>Fuentes</h3>';
		echo '<div style="display:flex;gap:24px;">';
		echo '<div style="flex:1;"><strong>Perplexity</strong>';
		if ( ! empty( $pplx_items ) ) {
			echo '<ul>';
			foreach ( $pplx_items as $source ) {
				$title = isset( $source['title'] ) ? (string) $source['title'] : '';
				$url = isset( $source['url'] ) ? (string) $source['url'] : '';
				$snippet = isset( $source['snippet'] ) ? (string) $source['snippet'] : '';
				$label = $title ? $title : $url;
				echo '<li>' . esc_html( $label ) . ' - <a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $url ) . '</a>';
				if ( $snippet ) {
					echo '<br><small>' . esc_html( $snippet ) . '</small>';
				}
				echo '</li>';
			}
			echo '</ul>';
		} else {
			echo '<p>Sin fuentes devueltas.</p>';
		}
		echo '</div>';
		echo '<div style="flex:1;"><strong>OpenAI</strong>';
		if ( ! empty( $openai_sources ) ) {
			echo '<ul>';
			foreach ( $openai_sources as $source ) {
				$title = isset( $source['title'] ) ? (string) $source['title'] : '';
				$url = isset( $source['url'] ) ? (string) $source['url'] : '';
				echo '<li>' . esc_html( $title ? $title : $url ) . ' - <a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $url ) . '</a></li>';
			}
			echo '</ul>';
		} else {
			echo '<p>Sin fuentes devueltas.</p>';
		}
		echo '</div>';
		echo '</div>';
		if ( $download_url ) {
			echo '<p><a class="button button-secondary" href="' . esc_url( $download_url ) . '">Descargar resultado (JSON)</a></p>';
		}
		echo '</div>';
	}

	private function diff_sentences( $pplx_text, $other_text ) {
		$pplx_sentences = $this->split_sentences( $pplx_text );
		$other_sentences = $this->split_sentences( $other_text );
		$pplx_map = $this->normalize_sentence_map( $pplx_sentences );
		$other_map = $this->normalize_sentence_map( $other_sentences );

		$pplx_only = array();
		foreach ( $pplx_sentences as $sentence ) {
			$key = $this->normalize_sentence( $sentence );
			if ( $key !== '' && ! isset( $other_map[ $key ] ) ) {
				$pplx_only[] = $sentence;
			}
		}
		$other_only = array();
		foreach ( $other_sentences as $sentence ) {
			$key = $this->normalize_sentence( $sentence );
			if ( $key !== '' && ! isset( $pplx_map[ $key ] ) ) {
				$other_only[] = $sentence;
			}
		}

		return array(
			'pplx' => $pplx_only,
			'other' => $other_only,
		);
	}

	private function split_sentences( $text ) {
		$text = trim( (string) $text );
		if ( $text === '' ) {
			return array();
		}
		$parts = preg_split( '/(?<=[\.\!\?])\s+/', $text );
		$sentences = array();
		foreach ( $parts as $part ) {
			$part = trim( (string) $part );
			if ( $part !== '' ) {
				$sentences[] = $part;
			}
		}
		return $sentences;
	}

	private function normalize_sentence_map( $sentences ) {
		$map = array();
		foreach ( $sentences as $sentence ) {
			$key = $this->normalize_sentence( $sentence );
			if ( $key !== '' ) {
				$map[ $key ] = true;
			}
		}
		return $map;
	}

	private function normalize_sentence( $sentence ) {
		$sentence = trim( (string) $sentence );
		if ( $sentence === '' ) {
			return '';
		}
		$sentence = preg_replace( '/\s+/', ' ', $sentence );
		$lower = function_exists( 'mb_strtolower' ) ? mb_strtolower( $sentence ) : strtolower( $sentence );
		return $lower;
	}

	private function highlight_sentences_html( $text, $diff_sentences ) {
		$text = trim( (string) $text );
		if ( $text === '' ) {
			return '<em>Sin respuesta.</em>';
		}
		$diff_map = $this->normalize_sentence_map( $diff_sentences );
		$sentences = $this->split_sentences( $text );
		$out = array();
		foreach ( $sentences as $sentence ) {
			$key = $this->normalize_sentence( $sentence );
			$escaped = esc_html( $sentence );
			if ( $key !== '' && isset( $diff_map[ $key ] ) ) {
				$out[] = '<mark>' . $escaped . '</mark>';
			} else {
				$out[] = $escaped;
			}
		}
		return nl2br( implode( ' ', $out ) );
	}

	private function normalize_instagram_source( $value ) {
		$value = trim( (string) $value );
		if ( $value === '' ) {
			return '';
		}
		if ( strpos( $value, 'http://' ) !== 0 && strpos( $value, 'https://' ) !== 0 ) {
			$value = 'https://' . ltrim( $value, '/' );
		}
		$parsed = wp_parse_url( $value );
		$host = isset( $parsed['host'] ) ? strtolower( $parsed['host'] ) : '';
		if ( $host === '' || strpos( $host, 'instagram.com' ) === false ) {
			return '';
		}
		$path = isset( $parsed['path'] ) ? trim( $parsed['path'], '/' ) : '';
		if ( $path === '' ) {
			return '';
		}
		$parts = explode( '/', $path );
		$user = $parts[0] ?? '';
		if ( $user === '' || in_array( $user, array( 'p', 'reel', 'reels', 'tv', 'explore', 'stories' ), true ) ) {
			return '';
		}
		return 'https://www.instagram.com/' . $user . '/';
	}

	private function filter_sources_excluding_domain( $sources, $domain ) {
		if ( ! is_array( $sources ) ) {
			return array();
		}
		$domain = strtolower( (string) $domain );
		if ( $domain === '' ) {
			return $sources;
		}
		$filtered = array();
		foreach ( $sources as $source ) {
			$url = isset( $source['url'] ) ? (string) $source['url'] : '';
			if ( $url === '' ) {
				continue;
			}
			$parsed = wp_parse_url( $url );
			$host = isset( $parsed['host'] ) ? strtolower( $parsed['host'] ) : '';
			if ( $host === $domain || substr( $host, - ( strlen( $domain ) + 1 ) ) === '.' . $domain ) {
				continue;
			}
			$filtered[] = $source;
		}
		return $filtered;
	}

	private function filter_items_excluding_domain( $items, $domain ) {
		if ( ! is_array( $items ) ) {
			return array();
		}
		$domain = strtolower( (string) $domain );
		if ( $domain === '' ) {
			return $items;
		}
		$filtered = array();
		foreach ( $items as $item ) {
			$url = isset( $item['url'] ) ? (string) $item['url'] : '';
			if ( $url === '' ) {
				continue;
			}
			$parsed = wp_parse_url( $url );
			$host = isset( $parsed['host'] ) ? strtolower( $parsed['host'] ) : '';
			if ( $host === $domain || substr( $host, - ( strlen( $domain ) + 1 ) ) === '.' . $domain ) {
				continue;
			}
			$filtered[] = $item;
		}
		return $filtered;
	}

	private function build_text_from_instagram_items( $items ) {
		$lines = array();
		foreach ( $items as $row ) {
			$text = isset( $row['text'] ) ? $row['text'] : '';
			$url = isset( $row['url'] ) ? $row['url'] : '';
			$lines[] = trim( $text . ' ' . $url );
		}
		return implode( "\n", $lines );
	}

	private function build_text_from_perplexity_items( $items ) {
		$lines = array();
		foreach ( $items as $row ) {
			$title = isset( $row['title'] ) ? $row['title'] : '';
			$snippet = isset( $row['snippet'] ) ? $row['snippet'] : '';
			$url = isset( $row['url'] ) ? $row['url'] : '';
			$lines[] = trim( $title . ' ' . $snippet . ' ' . $url );
		}
		return implode( "\n", $lines );
	}

	private function is_binary_content( $body, $content_type ) {
		if ( is_string( $content_type ) ) {
			$ct = strtolower( $content_type );
			if (
				strpos( $ct, 'image/' ) === 0 ||
				strpos( $ct, 'video/' ) === 0 ||
				strpos( $ct, 'audio/' ) === 0 ||
				strpos( $ct, 'application/pdf' ) === 0 ||
				strpos( $ct, 'application/octet-stream' ) === 0
			) {
				return true;
			}
		}

		$sample = substr( (string) $body, 0, 2000 );
		if ( $sample === '' ) {
			return false;
		}
		if ( strpos( $sample, "\x89PNG" ) === 0 || strpos( $sample, "\xFF\xD8\xFF" ) === 0 ) {
			return true;
		}

		$len = strlen( $sample );
		$printable = 0;
		for ( $i = 0; $i < $len; $i++ ) {
			$c = ord( $sample[ $i ] );
			if ( $c === 9 || $c === 10 || $c === 13 || ( $c >= 32 && $c <= 126 ) ) {
				$printable++;
			}
		}
		$ratio = $printable / max( 1, $len );
		return $ratio < 0.6;
	}

	private function fetch_text_from_url( $url ) {
		if ( ! $url ) {
			return array( 'text' => '', 'binary' => false, 'content_type' => '' );
		}
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'Accept' => 'text/html,application/xhtml+xml',
				),
				'user-agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
			)
		);
		if ( is_wp_error( $response ) ) {
			return array( 'text' => '', 'binary' => false, 'content_type' => '', 'error' => $response->get_error_message() );
		}
		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		$body = wp_remote_retrieve_body( $response );
		if ( $this->is_binary_content( $body, $content_type ) ) {
			return array( 'text' => '', 'binary' => true, 'content_type' => $content_type );
		}
		return array(
			'text' => $this->extract_text_simple( $body ),
			'binary' => false,
			'content_type' => $content_type,
		);
	}

	private function extract_first_link_from_html( $html ) {
		if ( ! $html ) {
			return '';
		}
		if ( preg_match_all( '/href=["\']([^"\']+)["\']/i', $html, $matches ) ) {
			foreach ( $matches[1] as $href ) {
				if ( stripos( $href, 'https://news.google.com' ) === 0 ) {
					continue;
				}
				return $href;
			}
		}
		return '';
	}

	private function is_google_news_url( $url ) {
		if ( ! $url ) {
			return false;
		}
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! $host ) {
			return false;
		}
		$host = strtolower( $host );
		return $host === 'news.google.com' || $host === 'www.news.google.com';
	}

	private function extract_article_url_from_html( $html ) {
		if ( ! $html ) {
			return '';
		}
		$limit = substr( $html, 0, 200000 );
		if ( preg_match( '/<meta[^>]+property=["\']og:url["\'][^>]+content=["\']([^"\']+)["\']/i', $limit, $m ) ) {
			return $m[1];
		}
		if ( preg_match( '/<link[^>]+rel=["\']canonical["\'][^>]+href=["\']([^"\']+)["\']/i', $limit, $m ) ) {
			return $m[1];
		}
		if ( preg_match( '/http-equiv=["\']refresh["\'][^>]*content=["\'][^;]+;\s*url=([^"\']+)["\']/i', $limit, $m ) ) {
			return $m[1];
		}
		if ( preg_match( '/window\.location\.replace\(["\']([^"\']+)["\']\)/i', $limit, $m ) ) {
			return $m[1];
		}
		if ( preg_match( '/window\.location\.href\s*=\s*["\']([^"\']+)["\']/i', $limit, $m ) ) {
			return $m[1];
		}
		if ( preg_match( '/data-n-au=["\'](https?:\/\/[^"\']+)["\']/i', $limit, $m ) ) {
			return $m[1];
		}
		if ( preg_match( '/url=(https%3A%2F%2F[^&"\']+)/i', $limit, $m ) ) {
			return urldecode( $m[1] );
		}
		if ( preg_match( '/"url":"(https?:\\\\\/\\\\\/[^"]+)"/i', $limit, $m ) ) {
			return stripslashes( $m[1] );
		}
		if ( preg_match_all( '/https?:\/\/[^"\'>\s]+/i', $limit, $matches ) ) {
			foreach ( $matches[0] as $url ) {
				if ( stripos( $url, 'news.google.com' ) !== false ) {
					continue;
				}
				if ( stripos( $url, 'google.com' ) !== false ) {
					continue;
				}
				return $url;
			}
		}
		return '';
	}

	private function resolve_gnews_article_url( $gnews_link, $hl = 'es', $gl = 'ES', $ceid = 'ES:es' ) {
		if ( ! $gnews_link ) {
			return '';
		}
		$parts = wp_parse_url( $gnews_link );
		if ( isset( $parts['host'] ) && stripos( $parts['host'], 'news.google.com' ) !== false ) {
			$query = array();
			if ( ! empty( $parts['query'] ) ) {
				parse_str( $parts['query'], $query );
			}
			if ( empty( $query['ucbcb'] ) ) {
				$query['ucbcb'] = '1';
			}
			if ( $hl && empty( $query['hl'] ) ) {
				$query['hl'] = $hl;
			}
			if ( $gl && empty( $query['gl'] ) ) {
				$query['gl'] = $gl;
			}
			if ( $ceid && empty( $query['ceid'] ) ) {
				$query['ceid'] = $ceid;
			}
			$gnews_link = add_query_arg( $query, $gnews_link );
			$parts = wp_parse_url( $gnews_link );
		}
		if ( ! empty( $parts['query'] ) ) {
			parse_str( $parts['query'], $q );
			if ( ! empty( $q['url'] ) ) {
				return $q['url'];
			}
		}
		$response = wp_remote_request(
			$gnews_link,
			array(
				'method' => 'GET',
				'timeout' => 10,
				'redirection' => 5,
				'headers' => array(
					'Accept' => 'text/html,application/xhtml+xml',
				),
				'user-agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
			)
		);
		if ( is_wp_error( $response ) ) {
			return $gnews_link;
		}
		$code = wp_remote_retrieve_response_code( $response );
		$location = wp_remote_retrieve_header( $response, 'location' );
		if ( is_array( $location ) ) {
			$location = reset( $location );
		}
		if ( is_string( $location ) && $location !== '' && stripos( $location, 'news.google.com' ) === false ) {
			return $location;
		}
		if ( $code >= 200 && $code < 300 ) {
			$body = wp_remote_retrieve_body( $response );
			$found = $this->extract_article_url_from_html( $body );
			if ( $found ) {
				return $found;
			}
		}
		$rendered = add_query_arg( 'output', '1', $gnews_link );
		$rendered_resp = wp_remote_get(
			$rendered,
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept' => 'text/html,application/xhtml+xml',
				),
				'user-agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
			)
		);
		if ( ! is_wp_error( $rendered_resp ) ) {
			$body = wp_remote_retrieve_body( $rendered_resp );
			$found = $this->extract_article_url_from_html( $body );
			if ( $found ) {
				return $found;
			}
		}
		return $gnews_link;
	}

private function test_apify_instagram( $token, $actor_id, $input_value, $max_posts, $raw_input = '', $filters = array(), $timeout = 120 ) {
		$actor_id = trim( $actor_id );
		if ( $actor_id === '' ) {
			return new WP_Error( 'mfu_apify_actor', 'Actor invalido.' );
		}
		$actor_id = str_replace( ' ', '', $actor_id );

		$input = array();
		if ( $raw_input ) {
			$decoded = json_decode( $raw_input, true );
			if ( is_array( $decoded ) ) {
				$input = $decoded;
			} else {
				return new WP_Error( 'mfu_apify_input', 'Input JSON invalido.' );
			}
		} else {
			$value = trim( $input_value );
			if ( stripos( $value, 'http://' ) === 0 || stripos( $value, 'https://' ) === 0 ) {
				$direct_url = $value;
			} else {
				$direct_url = 'https://www.instagram.com/' . ltrim( $value, '@' ) . '/';
			}
			$input['resultsLimit'] = $max_posts;
			if ( stripos( $actor_id, 'instagram-post-scraper' ) !== false ) {
				$input['username'] = array( $value );
			} elseif ( stripos( $actor_id, 'instagram-profile-scraper' ) !== false ) {
				$input['instagramUsernames'] = array( $value );
			} else {
				$input['directUrls'] = array( $direct_url );
			}
		}

		$base = 'https://api.apify.com/v2/acts/' . rawurlencode( $actor_id ) . '/run-sync-get-dataset-items';
		$url = add_query_arg(
			array(
				'format' => 'json',
				'clean' => '1',
			),
			$base
		);

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => max( 10, (int) $timeout ),
				'headers' => array(
					'Authorization' => 'Bearer ' . $token,
					'Content-Type' => 'application/json',
				),
				'body' => wp_json_encode( $input ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error( 'mfu_apify_http', 'HTTP ' . $code . ' - ' . substr( $body, 0, 300 ) );
		}
		$data = json_decode( $body, true );
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'mfu_apify_json', 'Respuesta JSON invalida' );
		}

		$items = array();
		$exclude = isset( $filters['exclude'] ) ? $this->split_terms( $filters['exclude'] ) : array();
		$include = isset( $filters['include'] ) ? $this->split_terms( $filters['include'] ) : array();
		$max_days = isset( $filters['max_days'] ) ? (int) $filters['max_days'] : 0;
		$min_len = isset( $filters['min_len'] ) ? (int) $filters['min_len'] : 0;
		$skip_empty = ! empty( $filters['skip_empty'] );
		$min_ts = $max_days > 0 ? ( time() - ( $max_days * DAY_IN_SECONDS ) ) : 0;
		foreach ( $data as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$caption = '';
			if ( isset( $row['caption'] ) ) {
				$caption = is_array( $row['caption'] ) ? (string) ( $row['caption']['text'] ?? '' ) : (string) $row['caption'];
			} elseif ( isset( $row['text'] ) ) {
				$caption = (string) $row['text'];
			}
			$created = 0;
			if ( isset( $row['timestamp'] ) ) {
				$created = (int) $row['timestamp'];
			} elseif ( isset( $row['takenAtTimestamp'] ) ) {
				$created = (int) $row['takenAtTimestamp'];
			} elseif ( isset( $row['createdAt'] ) ) {
				$created = strtotime( (string) $row['createdAt'] );
			}

			$text_lower = strtolower( $caption );
			$skip = false;
			if ( $skip_empty && trim( $caption ) === '' ) {
				$skip = true;
			}
			if ( ! $skip && $min_len > 0 && strlen( trim( $caption ) ) < $min_len ) {
				$skip = true;
			}
			if ( $min_ts && $created && $created < $min_ts ) {
				$skip = true;
			}
			if ( ! $skip && ! empty( $exclude ) ) {
				foreach ( $exclude as $term ) {
					if ( $term !== '' && strpos( $text_lower, $term ) !== false ) {
						$skip = true;
						break;
					}
				}
			}
			if ( ! $skip && ! empty( $include ) ) {
				$found = false;
				foreach ( $include as $term ) {
					if ( $term !== '' && strpos( $text_lower, $term ) !== false ) {
						$found = true;
						break;
					}
				}
				if ( ! $found ) {
					$skip = true;
				}
			}
			if ( $skip ) {
				continue;
			}

			$items[] = array(
				'username' => (string) ( $row['username'] ?? $input_value ),
				'post_id' => (string) ( $row['id'] ?? $row['shortCode'] ?? '' ),
				'text' => $caption,
				'url' => (string) ( $row['url'] ?? $row['postUrl'] ?? '' ),
				'created_at' => $created ? gmdate( 'Y-m-d H:i', $created ) : '',
			);
			if ( count( $items ) >= $max_posts ) {
				break;
			}
		}

		return array(
			'request_url' => $url,
			'count' => count( $items ),
			'items' => $items,
		);
	}

	private function store_last_test_result( $festival_name, $edition, $compare_result, $filtered_comparison ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}
		$payload = array(
			'timestamp' => gmdate( 'c' ),
			'festival' => $festival_name,
			'edition' => $edition,
			'current' => $compare_result['current'] ?? array(),
			'comparison' => $filtered_comparison,
			'facts' => $compare_result['facts'] ?? array(),
			'items' => $compare_result['items'] ?? array(),
			'updated_content' => $compare_result['updated_content'] ?? '',
		);
		update_user_meta( $user_id, 'mfu_last_test_result', wp_json_encode( $payload, JSON_PRETTY_PRINT ) );
	}

	private function pick_instagram_profile( $items ) {
		if ( ! is_array( $items ) ) {
			return '';
		}
		foreach ( $items as $row ) {
			$url = isset( $row['url'] ) ? (string) $row['url'] : '';
			if ( $url === '' ) {
				continue;
			}
			$parsed = wp_parse_url( $url );
			$host = isset( $parsed['host'] ) ? strtolower( $parsed['host'] ) : '';
			if ( $host !== 'www.instagram.com' && $host !== 'instagram.com' ) {
				continue;
			}
			$path = isset( $parsed['path'] ) ? trim( $parsed['path'], '/' ) : '';
			if ( $path === '' ) {
				continue;
			}
			$parts = explode( '/', $path );
			$user = $parts[0] ?? '';
			if ( $user === '' || in_array( $user, array( 'p', 'reel', 'reels', 'tv', 'explore', 'stories' ), true ) ) {
				continue;
			}
			return 'https://www.instagram.com/' . $user . '/';
		}
		return '';
	}

	private function pick_official_site( $items, $festival_title = '', &$meta = null ) {
		if ( ! is_array( $items ) ) {
			return '';
		}
		$blocked = array(
			'modofestival.es',
			'instagram.com',
			'www.instagram.com',
			'facebook.com',
			'www.facebook.com',
			'twitter.com',
			'x.com',
			'tiktok.com',
			'youtube.com',
			'www.youtube.com',
		);
		$press_domains = array(
			'elespanol.com',
			'elmundo.es',
			'elconfidencial.com',
			'elpais.com',
			'abc.es',
			'larazon.es',
			'20minutos.es',
			'europapress.es',
			'diariodearganda.es',
			'marketingdirecto.com',
			'metallegion.es',
			'tntradiorock.com',
			'ticketmaster.es',
			'livenation.es',
		);
		$ticket_domains = array(
			'entradas',
			'bticket',
			'ticket',
			'compralaentrada',
			'ticketrona',
			'feverup',
		);
		$non_official_keywords = array(
			'wikipedia',
			'turismo',
			'visit',
			'agenda',
			'ayuntamiento',
			'municipal',
			'cultura',
			'conciertos',
			'eventos',
			'noticias',
		);
		$title_norm = $this->normalize_headline_text( (string) $festival_title );
		$title_words = array();
		if ( $title_norm !== '' ) {
			foreach ( preg_split( '/\s+/', $title_norm ) as $word ) {
				if ( $word === '' ) {
					continue;
				}
				if ( strlen( $word ) < 4 ) {
					continue;
				}
				if ( in_array( $word, array( 'festival', 'fest', 'music', 'madrid', 'barcelona', 'alicante', 'pontevedra', 'coruna', 'coruña' ), true ) ) {
					continue;
				}
				$title_words[] = $word;
			}
		}
		$candidates = array();
		$found_non_press = false;
		foreach ( $items as $row ) {
			$url = isset( $row['url'] ) ? (string) $row['url'] : '';
			if ( $url === '' ) {
				continue;
			}
			$parsed = wp_parse_url( $url );
			$host = isset( $parsed['host'] ) ? strtolower( $parsed['host'] ) : '';
			if ( $host === '' || in_array( $host, $blocked, true ) ) {
				continue;
			}
			$score = 0;
			$is_press = in_array( $host, $press_domains, true );
			$is_ticket = false;
			$is_non_official = false;
			if ( strpos( $host, 'wikipedia' ) !== false ) {
				$is_non_official = true;
			}
			foreach ( $ticket_domains as $needle ) {
				if ( $needle !== '' && strpos( $host, $needle ) !== false ) {
					$is_ticket = true;
					break;
				}
			}
			foreach ( $non_official_keywords as $needle ) {
				if ( $needle !== '' && strpos( $host, $needle ) !== false ) {
					$is_non_official = true;
					break;
				}
			}
			if ( $is_press ) {
				$score -= 5;
			}
			if ( $is_ticket ) {
				$score -= 4;
			}
			if ( $is_non_official ) {
				$score -= 3;
			} else {
				$found_non_press = true;
			}
			$path = isset( $parsed['path'] ) ? (string) $parsed['path'] : '';
			$path = $path === '' ? '/' : $path;
			$path_depth = $path === '/' ? 0 : substr_count( trim( $path, '/' ), '/' ) + 1;
			if ( $path_depth === 0 ) {
				$score += 3;
			} else {
				$score -= min( 3, $path_depth );
			}
			$haystack = $this->normalize_headline_text( $host . ' ' . $path );
			$has_title_word = false;
			foreach ( $title_words as $word ) {
				if ( $word !== '' && strpos( $haystack, $word ) !== false ) {
					$score += 2;
					$has_title_word = true;
				}
			}
			$candidates[] = array(
				'url' => $url,
				'host' => $host,
				'score' => $score,
				'path_depth' => $path_depth,
				'is_press' => $is_press,
				'is_ticket' => $is_ticket,
				'is_non_official' => $is_non_official,
				'has_title_word' => $has_title_word,
			);
		}
		if ( empty( $candidates ) ) {
			return '';
		}
		$preferred = array();
		foreach ( $candidates as $candidate ) {
			if ( $candidate['has_title_word'] ) {
				$preferred[] = $candidate;
			}
		}
		$pool = $preferred ? $preferred : $candidates;
		$non_ticket = array();
		foreach ( $pool as $candidate ) {
			if ( ! $candidate['is_ticket'] ) {
				$non_ticket[] = $candidate;
			}
		}
		if ( $non_ticket ) {
			$pool = $non_ticket;
		}
		usort(
			$pool,
			function ( $a, $b ) {
				if ( $a['path_depth'] !== $b['path_depth'] ) {
					return $a['path_depth'] < $b['path_depth'] ? -1 : 1;
				}
				if ( $a['score'] === $b['score'] ) {
					return 0;
				}
				return ( $a['score'] > $b['score'] ) ? -1 : 1;
			}
		);
		$best = $pool[0];
		if ( ! $found_non_press && $best['score'] <= 0 ) {
			return '';
		}
		if ( is_array( $meta ) ) {
			$meta['official'] = ! $best['is_press'] && ! $best['is_ticket'] && ! $best['is_non_official'];
		}
		return $best['url'];
	}

	public function handle_download_test_result() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No autorizado.' );
		}
		check_admin_referer( 'mfu_download_test_result', '_mfu_nonce' );
		$user_id = get_current_user_id();
		$data = $user_id ? get_user_meta( $user_id, 'mfu_last_test_result', true ) : '';
		if ( ! $data ) {
			wp_die( 'No hay resultado para descargar.' );
		}
		nocache_headers();
		header( 'Content-Type: application/json; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="mfu-testeo-resultado.json"' );
		echo $data;
		exit;
	}

	public function run_fill_sources_batch( $per_batch = 50, $offset = 0 ) {
		$ai = new MFU_AI();
		$settings = get_option( MFU_OPTION_KEY, array() );
		$pplx_key = isset( $settings['pplx_api_key'] ) ? trim( (string) $settings['pplx_api_key'] ) : '';
		$apify_token = isset( $settings['apify_token'] ) ? trim( (string) $settings['apify_token'] ) : '';
		if ( $pplx_key === '' ) {
			return new WP_Error( 'mfu_pplx_no_key', 'Perplexity API key missing' );
		}

		$per_batch = max( 5, min( 200, (int) $per_batch ) );
		$offset = max( 0, (int) $offset );

		$query_args = array(
			'post_type' => 'festi',
			'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'posts_per_page' => $per_batch,
			'offset' => $offset,
			'fields' => 'ids',
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key' => 'mf_web_oficial',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key' => 'mf_web_oficial',
					'value' => '',
					'compare' => '=',
				),
				array(
					'key' => 'mf_instagram',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key' => 'mf_instagram',
					'value' => '',
					'compare' => '=',
				),
			),
		);

		$query = new WP_Query( $query_args );
		$filled = 0;
		$updated = array();
		$skipped = array();
		$errors = array();
		$processed_titles = array();
		$log = array();

		foreach ( $query->posts as $festival_id ) {
			$title = get_the_title( $festival_id );
			if ( ! $title ) {
				continue;
			}
			$processed_titles[] = $title;
			$current_web = get_post_meta( $festival_id, 'mf_web_oficial', true );
			$current_ig = get_post_meta( $festival_id, 'mf_instagram', true );
			if ( $current_web && $current_ig ) {
				continue;
			}

			$items = array();
			$web_items = array();
			$ig_items = array();
			$log_entry = array(
				'title' => $title,
				'web' => '',
				'ig' => '',
				'items' => array(),
				'error' => '',
			);
			$needs_sources = ( ! $current_web && ! $current_ig );
			$search_site = $ai->perplexity_search( $title . ' sitio oficial', 5, 'es' );
			if ( ! is_wp_error( $search_site ) && ! empty( $search_site['items'] ) ) {
				$web_items = array_merge( $web_items, $search_site['items'] );
			}
			if ( $apify_token !== '' ) {
				$queries = array(
					$title . ' instagram',
					$title . ' site:instagram.com',
				);
				$search_ig = $this->test_apify_google_search( $apify_token, 'apify/google-search-scraper', $queries, 'es', 'es' );
				if ( ! is_wp_error( $search_ig ) && ! empty( $search_ig['items'] ) ) {
					$ig_items = array_merge( $ig_items, $search_ig['items'] );
				} elseif ( is_wp_error( $search_ig ) ) {
					$log_entry['error'] = 'Instagram: ' . $search_ig->get_error_message();
				}
			} else {
				$log_entry['error'] = 'Instagram: falta token Apify';
			}

			if ( $needs_sources && empty( $web_items ) ) {
				$search_fallback = $ai->perplexity_search( $title . ' festival', 10, 'es' );
				if ( ! is_wp_error( $search_fallback ) && ! empty( $search_fallback['items'] ) ) {
					$web_items = array_merge( $web_items, $search_fallback['items'] );
				}
			}

			if ( $needs_sources && empty( $ig_items ) ) {
				$search_ig_fallback = $ai->perplexity_search( $title . ' instagram', 10, 'es' );
				if ( ! is_wp_error( $search_ig_fallback ) && ! empty( $search_ig_fallback['items'] ) ) {
					$ig_items = array_merge( $ig_items, $search_ig_fallback['items'] );
				}
			}

			$items = array_merge( $web_items, $ig_items );
			if ( empty( $items ) ) {
				$log_entry['error'] = $log_entry['error'] ? $log_entry['error'] . ' | ' : '';
				$log_entry['error'] .= 'Sin resultados';
				$log[] = $log_entry;
				$errors[] = $title;
				if ( $needs_sources ) {
					update_post_meta( $festival_id, 'mfu_web_status', 'unknown' );
					update_post_meta( $festival_id, 'mfu_ig_status', 'unknown' );
				}
				continue;
			}

			$web_meta = array();
			$new_web = $current_web ?: $this->pick_official_site( $web_items, $title, $web_meta );
			$new_ig = $current_ig ?: $this->pick_instagram_profile( $ig_items );
			$log_entry['web'] = $new_web ? $new_web : '';
			$log_entry['ig'] = $new_ig ? $new_ig : '';
			$web_status = '';
			if ( $new_web ) {
				$web_status = ( isset( $web_meta['official'] ) && ! $web_meta['official'] ) ? 'non_official' : 'official';
			} elseif ( ! $current_web ) {
				$web_status = $needs_sources ? 'unknown' : 'missing';
			}
			$ig_status = '';
			if ( $new_ig || $current_ig ) {
				$ig_status = 'found';
			} else {
				$ig_status = $needs_sources ? 'unknown' : 'missing';
			}
			if ( $web_status ) {
				update_post_meta( $festival_id, 'mfu_web_status', $web_status );
			}
			if ( $ig_status ) {
				update_post_meta( $festival_id, 'mfu_ig_status', $ig_status );
			}
			if ( $new_web && isset( $web_meta['official'] ) && ! $web_meta['official'] ) {
				$log_entry['error'] = $log_entry['error'] ? $log_entry['error'] . ' | ' : '';
				$log_entry['error'] .= 'Web: posible no oficial';
			} elseif ( ! $new_web && ! $current_web && ! empty( $web_items ) ) {
				$log_entry['error'] = $log_entry['error'] ? $log_entry['error'] . ' | ' : '';
				$log_entry['error'] .= 'Web: sin dominio oficial fiable';
			}
			foreach ( array_slice( $items, 0, 6 ) as $row ) {
				$log_entry['items'][] = array(
					'title' => (string) ( $row['title'] ?? '' ),
					'url' => (string) ( $row['url'] ?? '' ),
				);
			}
			$log[] = $log_entry;

			$changed = false;
			if ( $new_web && ! $current_web ) {
				if ( function_exists( 'update_field' ) ) {
					update_field( 'mf_web_oficial', $new_web, $festival_id );
				} else {
					update_post_meta( $festival_id, 'mf_web_oficial', $new_web );
				}
				$filled++;
				$changed = true;
			}
			if ( $new_ig && ! $current_ig ) {
				if ( function_exists( 'update_field' ) ) {
					update_field( 'mf_instagram', $new_ig, $festival_id );
				} else {
					update_post_meta( $festival_id, 'mf_instagram', $new_ig );
				}
				$filled++;
				$changed = true;
			}
			if ( $changed ) {
				$updated[] = $title;
			} else {
				$skipped[] = $title;
			}
		}

		$total_query = new WP_Query( array_merge( $query_args, array( 'posts_per_page' => -1, 'offset' => 0, 'fields' => 'ids' ) ) );
		$total = is_array( $total_query->posts ) ? count( $total_query->posts ) : 0;
		$next_offset = ( $offset + $per_batch ) < $total ? ( $offset + $per_batch ) : 0;

		return array(
			'filled' => $filled,
			'updated' => $updated,
			'skipped' => $skipped,
			'errors' => $errors,
			'processed' => $processed_titles,
			'log' => $log,
			'total' => $total,
			'next_offset' => $next_offset,
		);
	}

	public function handle_fill_sources() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No autorizado.' );
		}
		check_admin_referer( 'mfu_fill_sources' );

		$settings = get_option( MFU_OPTION_KEY, array() );
		$pplx_key = isset( $settings['pplx_api_key'] ) ? trim( (string) $settings['pplx_api_key'] ) : '';
		if ( $pplx_key === '' ) {
			wp_redirect( admin_url( 'admin.php?page=mfu-sources&filled=0&error=pplx' ) );
			exit;
		}

		$batch = isset( $_GET['batch'] ) ? max( 1, (int) $_GET['batch'] ) : 1;
		$per_batch = isset( $_GET['batch_size'] ) ? max( 5, min( 200, (int) $_GET['batch_size'] ) ) : 50;
		$offset = ( $batch - 1 ) * $per_batch;

		$result = $this->run_fill_sources_batch( $per_batch, $offset );
		if ( is_wp_error( $result ) ) {
			wp_redirect( admin_url( 'admin.php?page=mfu-sources&filled=0&error=pplx' ) );
			exit;
		}
		$filled = (int) ( $result['filled'] ?? 0 );
		$updated = $result['updated'] ?? array();
		$skipped = $result['skipped'] ?? array();
		$errors = $result['errors'] ?? array();
		$processed_titles = $result['processed'] ?? array();
		$log = $result['log'] ?? array();
		$total = (int) ( $result['total'] ?? 0 );
		$next = ! empty( $result['next_offset'] ) ? ( $batch + 1 ) : 0;

		$last_key = 'mfu_fill_sources_last_' . get_current_user_id();
		set_transient(
			$last_key,
			array(
				'processed' => array_slice( $processed_titles, 0, 12 ),
				'updated' => array_slice( $updated, 0, 12 ),
				'skipped' => array_slice( $skipped, 0, 12 ),
				'errors' => array_slice( $errors, 0, 12 ),
				'log' => array_slice( $log, 0, 12 ),
				'counts' => array(
					'updated' => count( $updated ),
					'skipped' => count( $skipped ),
					'errors' => count( $errors ),
				),
			),
			5 * MINUTE_IN_SECONDS
		);
		wp_redirect( admin_url( 'admin.php?page=mfu-sources&filled=' . (int) $filled . '&total=' . (int) $total . '&batch=' . (int) $batch . '&next=' . (int) $next . '&batch_size=' . (int) $per_batch ) );
		exit;
	}

	public function handle_download_missing_instagram() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No autorizado.' );
		}
		check_admin_referer( 'mfu_download_missing_instagram' );
		$query = new WP_Query(
			array(
				'post_type' => 'festi',
				'post_status' => array( 'publish', 'draft' ),
				'posts_per_page' => -1,
				'fields' => 'ids',
				'meta_query' => array(
					'relation' => 'OR',
					array(
						'key' => 'mf_instagram',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key' => 'mf_instagram',
						'value' => '',
						'compare' => '=',
					),
				),
			)
		);

		$rows = array();
		foreach ( $query->posts as $festival_id ) {
			$title = get_the_title( $festival_id );
			if ( ! $title ) {
				continue;
			}
			$rows[] = array(
				'id' => (int) $festival_id,
				'title' => $title,
			);
		}

		nocache_headers();
		header( 'Content-Type: application/json; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="mfu-missing-instagram.json"' );
		echo wp_json_encode( $rows );
		exit;
	}

	private function count_missing_field( $key ) {
		$key = sanitize_text_field( (string) $key );
		if ( $key === '' ) {
			return 0;
		}
		$query = new WP_Query(
			array(
				'post_type' => 'festi',
				'post_status' => array( 'publish', 'draft' ),
				'fields' => 'ids',
				'posts_per_page' => 1,
				'no_found_rows' => false,
				'meta_query' => array(
					'relation' => 'OR',
					array(
						'key' => $key,
						'compare' => 'NOT EXISTS',
					),
					array(
						'key' => $key,
						'value' => '',
						'compare' => '=',
					),
				),
			)
		);
		return (int) $query->found_posts;
	}

	private function count_missing_web_and_ig() {
		$query = new WP_Query(
			array(
				'post_type' => 'festi',
				'post_status' => array( 'publish', 'draft' ),
				'fields' => 'ids',
				'posts_per_page' => 1,
				'no_found_rows' => false,
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'relation' => 'OR',
						array(
							'key' => 'mf_web_oficial',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key' => 'mf_web_oficial',
							'value' => '',
							'compare' => '=',
						),
					),
					array(
						'relation' => 'OR',
						array(
							'key' => 'mf_instagram',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key' => 'mf_instagram',
							'value' => '',
							'compare' => '=',
						),
					),
				),
			)
		);
		return (int) $query->found_posts;
	}

	private function test_apify_google_search( $token, $actor_id, $queries, $country, $lang, &$debug_out = null ) {
		$actor_id = trim( $actor_id );
		if ( $actor_id === '' ) {
			return new WP_Error( 'mfu_apify_actor', 'Actor invalido.' );
		}
		$actor_id = str_replace( ' ', '', $actor_id );
		if ( is_array( $queries ) ) {
			$queries = array_values( array_filter( array_map( 'trim', $queries ) ) );
		} elseif ( is_string( $queries ) ) {
			$queries = trim( $queries );
		}
		if ( ( is_array( $queries ) && empty( $queries ) ) || ( is_string( $queries ) && $queries === '' ) ) {
			return new WP_Error( 'mfu_apify_query', 'Queries invalidas.' );
		}

		$terms = is_array( $queries ) ? $queries : array( $queries );
		$country_code = strtolower( $country );
		$lang_code = strtolower( $lang );
		$input = array(
			'countryCode' => $country_code,
			'languageCode' => $lang_code,
			'maxPagesPerQuery' => 1,
			'proxy' => array(
				'useApifyProxy' => true,
			),
		);
		if ( strpos( $actor_id, 'apidojo/google-search-scraper' ) !== false ) {
			$input['searchTerms'] = $terms;
			$input['countryCode'] = $country_code;
		} elseif ( strpos( $actor_id, 'apify/google-search-scraper' ) !== false ) {
			$input['queries'] = implode( "\n", $terms );
			$input['countryCode'] = $country_code;
		} else {
			$actor_id = 'apify/google-search-scraper';
			$input['queries'] = implode( "\n", $terms );
			$input['countryCode'] = $country_code;
		}

		$base = 'https://api.apify.com/v2/acts/' . rawurlencode( $actor_id ) . '/run-sync-get-dataset-items';
		$url = add_query_arg(
			array(
				'format' => 'json',
				'clean' => '1',
			),
			$base
		);

		$payload = wp_json_encode( $input );
		if ( is_array( $debug_out ) ) {
			$debug_out = array();
		}
		$debug_out = array(
			'actor' => $actor_id,
			'url' => $url,
			'input' => $input,
		);

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 60,
				'headers' => array(
					'Authorization' => 'Bearer ' . $token,
					'Content-Type' => 'application/json',
				),
				'body' => $payload,
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		if ( $code < 200 || $code >= 300 ) {
			$body_snippet = substr( $body, 0, 400 );
			if ( $code === 400 && stripos( $body_snippet, 'queries' ) !== false && strpos( $actor_id, 'apify/google-search-scraper' ) !== false ) {
				$alt_actor = 'apidojo/google-search-scraper';
				$alt_base = 'https://api.apify.com/v2/acts/' . rawurlencode( $alt_actor ) . '/run-sync-get-dataset-items';
				$alt_url = add_query_arg(
					array(
						'format' => 'json',
						'clean' => '1',
					),
					$alt_base
				);
				$alt_input = $input;
				unset( $alt_input['queries'] );
				$alt_input['searchTerms'] = $terms;
				$alt_payload = wp_json_encode( $alt_input );
				$alt_resp = wp_remote_post(
					$alt_url,
					array(
						'timeout' => 60,
						'headers' => array(
							'Authorization' => 'Bearer ' . $token,
							'Content-Type' => 'application/json',
						),
						'body' => $alt_payload,
					)
				);
				if ( ! is_wp_error( $alt_resp ) ) {
					$alt_code = wp_remote_retrieve_response_code( $alt_resp );
					$alt_body = wp_remote_retrieve_body( $alt_resp );
					if ( $alt_code >= 200 && $alt_code < 300 ) {
						$response = $alt_resp;
						$code = $alt_code;
						$body = $alt_body;
						$actor_id = $alt_actor;
					}
				}
			}
			if ( $code < 200 || $code >= 300 ) {
				$retry_input = $input;
				$retry_input['proxy'] = array(
					'useApifyProxy' => true,
					'apifyProxyGroups' => array( 'RESIDENTIAL' ),
				);
				$retry_payload = wp_json_encode( $retry_input );
				$retry_resp = wp_remote_post(
					$url,
					array(
						'timeout' => 60,
						'headers' => array(
							'Authorization' => 'Bearer ' . $token,
							'Content-Type' => 'application/json',
						),
						'body' => $retry_payload,
					)
				);
				if ( ! is_wp_error( $retry_resp ) ) {
					$retry_code = wp_remote_retrieve_response_code( $retry_resp );
					$retry_body = wp_remote_retrieve_body( $retry_resp );
					if ( $retry_code >= 200 && $retry_code < 300 ) {
						$response = $retry_resp;
						$code = $retry_code;
						$body = $retry_body;
					}
				}
			}
			if ( $code < 200 || $code >= 300 ) {
				return new WP_Error(
					'mfu_apify_http',
					'HTTP ' . $code . ' - ' . $body_snippet . ' | input: ' . substr( $payload, 0, 200 )
				);
			}
		}
		$data = json_decode( $body, true );
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'mfu_apify_json', 'Respuesta JSON invalida' );
		}
		if ( isset( $data[0]['demo'] ) && $data[0]['demo'] ) {
			return new WP_Error( 'mfu_apify_demo', 'La respuesta del actor está en modo demo y no devuelve resultados. Revisa el actor/token de Apify.' );
		}

		$items = array();
		foreach ( $data as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$query = '';
			if ( isset( $row['searchQuery'] ) ) {
				if ( is_array( $row['searchQuery'] ) && isset( $row['searchQuery']['term'] ) ) {
					$query = (string) $row['searchQuery']['term'];
				} else {
					$query = is_string( $row['searchQuery'] ) ? $row['searchQuery'] : '';
				}
			} elseif ( isset( $row['query'] ) ) {
				$query = is_string( $row['query'] ) ? $row['query'] : '';
			}

			if ( isset( $row['organicResults'] ) && is_array( $row['organicResults'] ) ) {
				foreach ( $row['organicResults'] as $res ) {
					if ( ! is_array( $res ) ) {
						continue;
					}
					$items[] = array(
						'query' => $query,
						'title' => (string) ( $res['title'] ?? '' ),
						'url' => (string) ( $res['url'] ?? $res['link'] ?? '' ),
						'snippet' => (string) ( $res['snippet'] ?? $res['description'] ?? '' ),
					);
					if ( count( $items ) >= 10 ) {
						break 2;
					}
				}
				continue;
			}

			$items[] = array(
				'query' => $query,
				'title' => (string) ( $row['title'] ?? '' ),
				'url' => (string) ( $row['url'] ?? $row['link'] ?? '' ),
				'snippet' => (string) ( $row['snippet'] ?? $row['description'] ?? '' ),
			);
			if ( count( $items ) >= 10 ) {
				break;
			}
		}

		return array(
			'items' => $items,
		);
	}

	private function is_trivial_location_change( $current, $proposed ) {
		$current = trim( (string) $current );
		$proposed = trim( (string) $proposed );
		if ( $current === '' || $proposed === '' ) {
			return false;
		}
		$cur_norm = $this->normalize_headline_text( $current );
		$pro_norm = $this->normalize_headline_text( $proposed );
		if ( $cur_norm === '' || $pro_norm === '' ) {
			return false;
		}
		if ( strpos( $pro_norm, $cur_norm ) !== false || strpos( $cur_norm, $pro_norm ) !== false ) {
			return true;
		}
		return false;
	}

	private function dedupe_headlines( $headlines, $max = 5 ) {
		if ( ! is_array( $headlines ) ) {
			return array();
		}
		$unique = array();
		$norms = array();
		foreach ( $headlines as $headline ) {
			if ( ! is_string( $headline ) ) {
				continue;
			}
			$headline = trim( $headline );
			if ( $headline === '' ) {
				continue;
			}
			$norm = $this->normalize_headline_text( $headline );
			if ( $norm === '' ) {
				continue;
			}
			$skip = false;
			foreach ( $norms as $existing ) {
				if ( $this->headline_similarity( $norm, $existing ) >= 0.6 ) {
					$skip = true;
					break;
				}
			}
			if ( $skip ) {
				continue;
			}
			$unique[] = $headline;
			$norms[] = $norm;
			if ( count( $unique ) >= $max ) {
				break;
			}
		}
		return $unique;
	}

	private function normalize_headline_text( $text ) {
		$text = strtolower( remove_accents( (string) $text ) );
		$text = preg_replace( '/[^a-z0-9\\s]/', ' ', $text );
		$text = preg_replace( '/\\s+/', ' ', $text );
		$stop = array( 'el', 'la', 'los', 'las', 'de', 'del', 'en', 'y', 'a', 'un', 'una', 'para', 'con', 'por', 'se', 'su', 'al', 'que' );
		$tokens = array_filter( explode( ' ', trim( $text ) ) );
		$tokens = array_values( array_filter( $tokens, function ( $t ) use ( $stop ) {
			return $t !== '' && ! in_array( $t, $stop, true );
		} ) );
		return implode( ' ', $tokens );
	}

	private function headline_similarity( $a, $b ) {
		$a_tokens = array_values( array_filter( explode( ' ', $a ) ) );
		$b_tokens = array_values( array_filter( explode( ' ', $b ) ) );
		if ( empty( $a_tokens ) || empty( $b_tokens ) ) {
			return 0.0;
		}
		$intersect = array_intersect( $a_tokens, $b_tokens );
		$union = array_unique( array_merge( $a_tokens, $b_tokens ) );
		return count( $union ) > 0 ? ( count( $intersect ) / count( $union ) ) : 0.0;
	}

	private function split_terms( $value ) {
		$terms = array();
		if ( ! $value ) {
			return $terms;
		}
		$parts = preg_split( '/[,;\\n]+/', $value );
		foreach ( $parts as $part ) {
			$part = trim( strtolower( $part ) );
			if ( $part !== '' ) {
				$terms[] = $part;
			}
		}
		return $terms;
	}

	private function maybe_save_apify_settings( $token, $actor_id ) {
		$options = get_option( MFU_OPTION_KEY, array() );
		$updated = false;
		if ( $token && ( ! isset( $options['apify_token'] ) || $options['apify_token'] !== $token ) ) {
			$options['apify_token'] = $token;
			$updated = true;
		}
		if ( $actor_id && ( ! isset( $options['apify_actor'] ) || $options['apify_actor'] !== $actor_id ) ) {
			$options['apify_actor'] = $actor_id;
			$updated = true;
		}
		if ( $updated ) {
			update_option( MFU_OPTION_KEY, $options, false );
		}
	}

	private function test_gnews_rss( $query, $hl, $gl, $ceid, $count, $use_ai = false, $festival = '', $edition = '', $ai_limit = 3 ) {
		if ( ! function_exists( 'fetch_feed' ) ) {
			require_once ABSPATH . WPINC . '/feed.php';
		}

		$base = 'https://news.google.com/rss/search';
		$params = array(
			'q' => $query,
			'hl' => $hl,
			'gl' => $gl,
			'ceid' => $ceid,
		);
		$url = add_query_arg( $params, $base );

		$feed = fetch_feed( $url );
		if ( is_wp_error( $feed ) ) {
			return $feed;
		}

		$maxitems = $feed->get_item_quantity( $count );
		$items = $feed->get_items( 0, $maxitems );
		$results = array();
		$ai = null;
		if ( $use_ai ) {
			$ai = new MFU_AI();
		}
		foreach ( $items as $item ) {
			$title = $item->get_title();
			$link = $item->get_link();
			$date = $item->get_date( 'Y-m-d H:i' );
			$source = '';
			$source_link = '';
			$source_tag = $item->get_item_tags( '', 'source' );
			if ( ! empty( $source_tag[0]['data'] ) ) {
				$source = $source_tag[0]['data'];
			}
			$description = $item->get_description();
			$source_link = $this->extract_first_link_from_html( $description );
			if ( ! $source_link ) {
				$source_link = $link;
			}
			if ( $this->is_google_news_url( $source_link ) ) {
				$source_link = $this->resolve_gnews_article_url( $source_link, $hl, $gl, $ceid );
			}
			$row = array(
				'title' => $title ? $title : '',
				'link' => $source_link ? $source_link : '',
				'gnews_link' => $link ? $link : '',
				'date' => $date ? $date : '',
				'source' => $source,
			);
			if ( $use_ai && $ai && $ai->has_key() && count( $results ) < $ai_limit ) {
				$text = '';
				if ( $source_link ) {
					if ( $this->is_google_news_url( $source_link ) ) {
						$fallback = trim( $title . ' ' . wp_strip_all_tags( (string) $description ) );
						if ( $fallback !== '' ) {
							$text = $fallback;
						} else {
							$row['facts'] = 'Descartado (link sigue siendo Google News y sin texto en RSS).';
						}
					}
					$fetched = $this->fetch_text_from_url( $source_link );
					if ( ! empty( $fetched['error'] ) ) {
						$row['facts'] = $fetched['error'];
					} elseif ( ! empty( $fetched['binary'] ) ) {
						$row['facts'] = 'Descartado (contenido no textual: ' . ( is_string( $fetched['content_type'] ) ? $fetched['content_type'] : 'desconocido' ) . ').';
					}
					if ( empty( $fetched['binary'] ) && ! empty( $fetched['text'] ) ) {
						$text = $fetched['text'];
					}
				}
				if ( $text !== '' ) {
					$name = $festival !== '' ? $festival : 'Festival';
					$ai_result = $ai->extract_facts( $name, $text, $source_link, $edition );
					if ( is_wp_error( $ai_result ) ) {
						$row['facts'] = $ai_result->get_error_message();
					} else {
						$row['facts'] = $ai_result;
					}
				}
			}
			$results[] = $row;
		}

		return array(
			'request_url' => $url,
			'count' => count( $results ),
			'items' => $results,
		);
	}

	private function apply_no_dates_notice( $festival_id, $content ) {
		$no_dates = get_post_meta( $festival_id, 'sin_fechas_confirmadas', true );
		if ( (string) $no_dates !== '1' ) {
			return $content;
		}

		$edicion = get_post_meta( $festival_id, 'edicion', true );
		$edicion_label = $edicion ? $edicion : 'esta edicion';
		$festival = get_post( $festival_id );

		$notice  = '<!-- mfu_no_dates_start -->';
		$notice .= '<h3>Sin fechas confirmadas</h3>';
		$notice .= '<p>A fecha de hoy no hay fechas confirmadas para ' . esc_html( $festival->post_title ) . ' (' . esc_html( $edicion_label ) . '). ';
		$notice .= 'Actualizaremos la ficha en cuanto exista comunicacion oficial.</p>';
		$notice .= $this->build_previous_edition_section( $festival_id, $edicion );
		$notice .= '<!-- mfu_no_dates_end -->';

		$content = preg_replace( '/<!-- mfu_no_dates_start -->(.*?)<!-- mfu_no_dates_end -->/s', '', $content );
		return $notice . "\n\n" . $content;
	}

	private function build_previous_edition_section( $festival_id, $edicion ) {
		$prev = '';
		$prev_label = '';
		if ( is_numeric( $edicion ) ) {
			$prev_label = (string) ( (int) $edicion - 1 );
		}
		if ( $prev_label === '' ) {
			$prev_label = 'edicion anterior';
		}

		$stored_prev_label = trim( (string) get_post_meta( $festival_id, 'mfu_prev_edition_label', true ) );
		if ( $stored_prev_label !== '' ) {
			$prev_label = $stored_prev_label;
		}

		$fecha_inicio = get_post_meta( $festival_id, 'mfu_prev_fecha_inicio', true );
		if ( $fecha_inicio === '' ) {
			$fecha_inicio = get_post_meta( $festival_id, 'fecha_inicio', true );
		}
		$fecha_fin = get_post_meta( $festival_id, 'mfu_prev_fecha_fin', true );
		if ( $fecha_fin === '' ) {
			$fecha_fin = get_post_meta( $festival_id, 'fecha_fin', true );
		}
		$artistas = get_post_meta( $festival_id, 'mfu_prev_artistas', true );
		if ( $artistas === '' ) {
			$artistas = get_post_meta( $festival_id, 'mf_artistas', true );
		}
		$fecha_inicio = $this->format_date_value( $fecha_inicio );
		$fecha_fin = $this->format_date_value( $fecha_fin );
		$localidad = $this->get_taxonomy_list( $festival_id, 'localidad' );
		$estilos = $this->get_taxonomy_list( $festival_id, 'estilo_musical' );

		$prev .= '<h4>Referencias de la ' . esc_html( $prev_label ) . '</h4>';
		if ( $fecha_inicio || $fecha_fin ) {
			$prev .= '<p>En la edicion anterior se celebro ';
			if ( $fecha_inicio && $fecha_fin ) {
				$prev .= 'entre ' . esc_html( $fecha_inicio ) . ' y ' . esc_html( $fecha_fin ) . '.';
			} elseif ( $fecha_inicio ) {
				$prev .= 'a partir de ' . esc_html( $fecha_inicio ) . '.';
			} else {
				$prev .= 'con fecha final ' . esc_html( $fecha_fin ) . '.';
			}
			$prev .= '</p>';
		}
		if ( $localidad ) {
			$prev .= '<p>La localidad habitual es: ' . esc_html( $localidad ) . '.</p>';
		}
		if ( $estilos ) {
			$prev .= '<p>Estilos principales: ' . esc_html( $estilos ) . '.</p>';
		}
		if ( $artistas ) {
			$prev .= '<p>Artistas destacados de la edicion anterior: ' . esc_html( $artistas ) . '.</p>';
		}

		return $prev;
	}

	private function ensure_min_word_count( $festival_id, $content ) {
		$no_dates = get_post_meta( $festival_id, 'sin_fechas_confirmadas', true );
		if ( (string) $no_dates !== '1' ) {
			return $content;
		}

		$plain = wp_strip_all_tags( $content );
		$words = preg_split( '/\s+/', trim( $plain ) );
		$count = is_array( $words ) ? count( array_filter( $words ) ) : 0;
		if ( $count >= 600 ) {
			return $content;
		}

		$festival = get_post( $festival_id );
		$localidad = $this->get_taxonomy_list( $festival_id, 'localidad' );
		$estilos = $this->get_taxonomy_list( $festival_id, 'estilo_musical' );
		$meses = $this->get_taxonomy_list( $festival_id, 'mes' );

		$extra  = '<h3>Contexto del festival</h3>';
		$extra .= '<p>' . esc_html( $festival->post_title ) . ' es un festival consolidado cuya programacion suele anunciarse por fases. ';
		if ( $localidad ) {
			$extra .= 'Se celebra habitualmente en ' . esc_html( $localidad ) . '. ';
		}
		if ( $estilos ) {
			$extra .= 'Su linea artistica suele estar vinculada a ' . esc_html( $estilos ) . '. ';
		}
		if ( $meses ) {
			$extra .= 'En ediciones anteriores se ha programado en los meses de ' . esc_html( $meses ) . '. ';
		}
		$extra .= 'Cuando la organizacion confirme fechas y cartel, actualizaremos esta ficha con la informacion oficial.</p>';

		$content .= "\n\n" . $extra;
		return $content;
	}

	private function get_update_row( $update_id ) {
		global $wpdb;
		$table = MFU_DB::table( 'updates' );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d", $update_id ) );
	}

	private function get_latest_pending_update( $festival_id ) {
		global $wpdb;
		$table = MFU_DB::table( 'updates' );
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE festival_id=%d AND status=%s ORDER BY id DESC LIMIT 1",
			$festival_id,
			'pending_review'
		) );
	}

	public function apply_update_by_id( $update_id, $applied_by = null ) {
		$update_id = (int) $update_id;
		if ( $update_id <= 0 ) {
			return false;
		}

		$update = $this->get_update_row( $update_id );
		if ( ! $update ) {
			return false;
		}

		$this->apply_update_row( $update, $applied_by );
		return true;
	}

	private function apply_update_row( $update, $applied_by = null ) {
		if ( ! $update || empty( $update->festival_id ) ) {
			return;
		}

		$diffs = json_decode( (string) $update->diffs_json, true );
		if ( ! is_array( $diffs ) || empty( $diffs ) ) {
			return;
		}

			$festival_id = (int) $update->festival_id;
			$this->apply_diffs_to_festival( $festival_id, $diffs );
			$payload = json_decode( (string) $update->evidence_json, true );
			$force_content_update = ( $applied_by === 0 );
			// Press releases are treated as authoritative input; apply proposed content even if global toggle is off.
			if ( is_array( $payload ) && ! empty( $payload['press_release'] ) ) {
				$force_content_update = true;
			}
			$this->maybe_update_content_from_diffs( $festival_id, $diffs, $update, $force_content_update );

			$updated_content = ( is_array( $payload ) && ! empty( $payload['updated_content'] ) ) ? (string) $payload['updated_content'] : '';
			$evidence = is_array( $payload ) ? $payload : array();
		if ( $applied_by === null ) {
			$applied_by = get_current_user_id();
		}
		if ( ! empty( $evidence ) ) {
			$manual_stamp = array(
				'manual_override' => true,
				'manual_override_by' => (int) $applied_by,
				'manual_override_at' => current_time( 'mysql' ),
			);
			foreach ( array( 'content_verification_pplx', 'content_verification', 'verification' ) as $key ) {
				if ( ! empty( $evidence[ $key ] ) && is_array( $evidence[ $key ] ) ) {
					$verdict = (string) ( $evidence[ $key ]['verdict'] ?? '' );
					if ( $verdict === 'needs_review' ) {
						$evidence[ $key ]['verdict'] = 'ok';
						$note = (string) ( $evidence[ $key ]['message'] ?? '' );
						$note = trim( $note );
						$evidence[ $key ]['message'] = trim( $note . ' Validado manualmente.' );
						$evidence[ $key ] = array_merge( $evidence[ $key ], $manual_stamp );
					}
				}
			}
		}
		MFU_News::maybe_create_news_post( $update, $festival_id, $diffs, $evidence, $updated_content );

		global $wpdb;
		$table = MFU_DB::table( 'updates' );
		$new_status = $applied_by ? 'applied' : 'auto_applied';
		$wpdb->update(
			$table,
			array(
				'status' => $new_status,
				'applied_by' => $applied_by,
				'applied_at' => current_time( 'mysql' ),
				'evidence_json' => wp_json_encode( $evidence ),
			),
			array( 'id' => (int) $update->id ),
			array( '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);
	}

	private function apply_diffs_to_festival( $festival_id, $diffs ) {
		foreach ( $diffs as $key => $diff ) {
			if ( ! isset( $diff['after'] ) ) {
				continue;
			}
			$after = $diff['after'];

			switch ( $key ) {
				case 'tickets_url':
				case 'ticket_url':
				case 'url_entradas':
					// El campo de entradas se controla manualmente.
					break;
				case 'sin_fechas_confirmadas':
					if ( is_string( $after ) ) {
						$after = trim( $after );
					}
					if ( $after === '' ) {
						$after = '0';
					}
					if ( $after !== '0' && $after !== '1' ) {
						$after = (string) (int) (bool) $after;
					}
					// fall through
				case 'fecha_inicio':
				case 'fecha_fin':
					if ( $key === 'fecha_inicio' || $key === 'fecha_fin' ) {
						$after = $this->normalize_date_for_storage( $after );
					}
					// fall through
				case 'edicion':
				case 'cancelado':
				case 'mf_artistas':
				case 'mf_cartel_completo':
				case 'mf_web_oficial':
				case 'mf_instagram':
					if ( function_exists( 'update_field' ) ) {
						update_field( $key, $after, $festival_id );
					} else {
						update_post_meta( $festival_id, $key, $after );
					}
					if ( $key === 'mf_web_oficial' && $after !== '' ) {
						update_post_meta( $festival_id, 'mfu_web_status', 'official' );
					}
					if ( $key === 'mf_instagram' && $after !== '' ) {
						update_post_meta( $festival_id, 'mfu_ig_status', 'found' );
					}
					break;
				case 'localidad':
					$term = get_term_by( 'name', (string) $after, 'localidad' );
					if ( $term && ! is_wp_error( $term ) ) {
						wp_set_post_terms( $festival_id, array( (int) $term->term_id ), 'localidad', false );
					}
					break;
				default:
					break;
			}
		}

		$has_dates = false;
		if ( isset( $diffs['fecha_inicio']['after'] ) && $diffs['fecha_inicio']['after'] !== '' ) {
			$has_dates = true;
		}
		if ( isset( $diffs['fecha_fin']['after'] ) && $diffs['fecha_fin']['after'] !== '' ) {
			$has_dates = true;
		}
		if ( $has_dates ) {
			update_post_meta( $festival_id, 'sin_fechas_confirmadas', '0' );
		}
	}

	private function maybe_update_content_from_diffs( $festival_id, $diffs, $update, $force_content_update = false ) {
		$options = get_option( MFU_OPTION_KEY, array() );
		if ( empty( $options['ai_content_update'] ) && ! $force_content_update ) {
			return;
		}

		$payload = json_decode( (string) $update->evidence_json, true );
		if ( is_array( $payload ) && ! empty( $payload['updated_content'] ) ) {
			$festival = get_post( $festival_id );
			if ( ! $festival ) {
				return;
			}
			$orig_len = strlen( (string) $payload['updated_content'] );
			MFU_Cron::add_error_log( $festival_id, 0, 'Aplicar: updated_content len=' . $orig_len );
			// Apply exactly what the preview shows (Propuesto IA).
			$content_to_apply = (string) $payload['updated_content'];
			$content_to_apply = $this->sanitize_ai_content( $content_to_apply );
			$sanitized_len = strlen( $content_to_apply );
			MFU_Cron::add_error_log( $festival_id, 0, 'Aplicar: sanitized len=' . $sanitized_len );
			$content_to_apply = $this->apply_no_dates_notice( $festival_id, $content_to_apply );
			$content_to_apply = $this->ensure_min_word_count( $festival_id, $content_to_apply );

			if ( $content_to_apply === '' ) {
				// Fallback: merge facts into existing content if proposed content is empty.
				$content_to_apply = $this->merge_content_with_facts( (string) $festival->post_content, $payload );
				$content_to_apply = $this->sanitize_ai_content( $content_to_apply );
				$content_to_apply = $this->strip_ticket_links( $content_to_apply );
				$content_to_apply = $this->strip_ticket_mentions( $content_to_apply );
				$content_to_apply = $this->apply_no_dates_notice( $festival_id, $content_to_apply );
				$content_to_apply = $this->ensure_min_word_count( $festival_id, $content_to_apply );
				MFU_Cron::add_error_log( $festival_id, 0, 'Aplicar: fallback len=' . strlen( $content_to_apply ) );
			}

			if ( $content_to_apply !== '' ) {
				wp_update_post(
					array(
						'ID' => $festival_id,
						'post_content' => $content_to_apply,
					)
				);
				MFU_Cron::add_error_log( $festival_id, 0, 'Aplicar: contenido propuesto aplicado.' );
			} else {
				MFU_Cron::add_error_log( $festival_id, 0, 'Aplicar: contenido propuesto vacío; no se aplicó.' );
			}
			return;
		}

		$ai = new MFU_AI();
		if ( ! $ai->has_key() ) {
			MFU_Cron::add_error_log( $festival_id, 0, 'Aplicar: falta API key para actualizar contenido.' );
			return;
		}

		$festival = get_post( $festival_id );
		if ( ! $festival ) {
			return;
		}

		$edition = get_post_meta( $festival_id, 'edicion', true );
		$current_content = (string) $festival->post_content;
		$evidence_list = $this->build_evidence_list( $update );

		$new_content = $ai->rewrite_festival_content(
			$festival->post_title,
			$edition,
			$current_content,
			$diffs,
			$evidence_list
		);
		if ( is_wp_error( $new_content ) ) {
			MFU_Cron::add_error_log( $festival_id, 0, 'Aplicar: ' . $new_content->get_error_message() );
			return;
		}

		$new_content = $this->sanitize_ai_content( $new_content );
		$new_content = $this->strip_ticket_links( $new_content );
		$new_content = $this->strip_ticket_mentions( $new_content );
		$new_content = $this->apply_no_dates_notice( $festival_id, $new_content );
		$new_content = $this->ensure_min_word_count( $festival_id, $new_content );

		wp_update_post(
			array(
				'ID' => $festival_id,
				'post_content' => $new_content,
			)
		);
	}

	private function build_evidence_list( $update ) {
		$list = array();
		$payload = json_decode( (string) $update->evidence_json, true );
		if ( is_array( $payload ) && ! empty( $payload['sources'] ) && is_array( $payload['sources'] ) ) {
			foreach ( $payload['sources'] as $row ) {
				$url = isset( $row['url'] ) ? trim( (string) $row['url'] ) : '';
				$summary = isset( $row['summary'] ) ? trim( (string) $row['summary'] ) : '';
				$line = trim( $url . ' ' . $summary );
				if ( $line !== '' ) {
					$list[] = $line;
				}
			}
		}
		if ( empty( $list ) && isset( $update->summary ) && $update->summary ) {
			$list[] = (string) $update->summary;
		}
		return $list;
	}

	private function sanitize_ai_content( $content ) {
		if ( $content === '' ) {
			return $content;
		}

		// Remove full HTML document wrappers (doctype/head/body) if present.
		$content = preg_replace( '/<!DOCTYPE[^>]*>/i', '', $content );
		$content = preg_replace( '/<\\/?html\\b[^>]*>/i', '', $content );
		$content = preg_replace( '/<head\\b[^>]*>.*?<\\/head>/is', '', $content );
		$content = preg_replace( '/<\\/?body\\b[^>]*>/i', '', $content );

		$content = preg_replace( '/```(?:html)?\\s*|```/i', '', $content );
		$content = preg_replace( '/^>\\s?/m', '', $content );
		$content = preg_replace( '/\\[(\\d+)\\]/', '', $content );
		$content = preg_replace( '/^#+\\s*/m', '', $content );

		$content = preg_replace_callback(
			'/\\[([^\\]]+)\\]\\((https?:\\/\\/[^)\\s]+)\\)/i',
			function ( $matches ) {
				$text = $matches[1];
				$url = $matches[2];
				return '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( $text ) . '</a>';
			},
			$content
		);

		$content = preg_replace( '/\\*\\*([^*]+)\\*\\*/', '<strong>$1</strong>', $content );
		$content = preg_replace( '/__([^_]+)__/', '<strong>$1</strong>', $content );
		$content = preg_replace( '/(^|\\s)\\*([^*]+)\\*(?=\\s|$)/', '$1<em>$2</em>', $content );
		$content = preg_replace( '/(^|\\s)_([^_]+)_(?=\\s|$)/', '$1<em>$2</em>', $content );

		// Remove any internal MFU update debug blocks accidentally injected.
		$content = preg_replace( '/<p>\\s*Actualizacion\\s*\\(MFU\\)\\s*<\\/p>[\\s\\S]*?(?=<p>|\\z)/i', '', $content );
		$content = preg_replace( '/Actualizacion\\s*\\(MFU\\)[\\s\\S]*?(?:\\n\\s*\\n|\\z)/i', '', $content );

		return trim( $content );
	}

	private function strip_ticket_links( $content ) {
		if ( $content === '' ) {
			return $content;
		}

		return preg_replace_callback(
			'/<a\\s[^>]*href=["\\\']([^"\\\']+)["\\\'][^>]*>(.*?)<\\/a>/is',
			function ( $matches ) {
				$url = isset( $matches[1] ) ? $matches[1] : '';
				if ( $this->is_ticket_url( $url ) ) {
					return $matches[2];
				}
				return $matches[0];
			},
			$content
		);
	}

	private function merge_content_with_facts( $content, $payload ) {
		if ( $content === '' || ! is_array( $payload ) ) {
			return $content;
		}

		$facts = $payload['facts']['facts'] ?? array();
		if ( ! is_array( $facts ) ) {
			return $content;
		}

		$date_start = $facts['date_start']['value'] ?? '';
		$date_end = $facts['date_end']['value'] ?? '';
		if ( $date_start && $date_end ) {
			$start_text = $this->format_spanish_date( $date_start, false );
			$end_text = $this->format_spanish_date( $date_end, true );
			if ( $start_text && $end_text ) {
				$replacement = 'entre el <strong>' . esc_html( $start_text ) . '</strong> y el <strong>' . esc_html( $end_text ) . '</strong>';
				$content = preg_replace( '/entre el\\s+<strong>.*?<\\/strong>\\s+y el\\s+<strong>.*?<\\/strong>/i', $replacement, $content, 1 );
			}
		}

		$artists = $facts['artists']['value'] ?? array();
		if ( is_array( $artists ) && ! empty( $artists ) ) {
			$evidence = $facts['artists']['evidence'] ?? array();
			$list_html = $this->build_artist_list_html( $artists, $evidence );
			if ( $list_html ) {
				if ( preg_match( '/(<h2>\\s*Cartel\\s*2026[^<]*<\\/h2>\\s*)(<ul>.*?<\\/ul>)/is', $content ) ) {
					$content = preg_replace( '/(<h2>\\s*Cartel\\s*2026[^<]*<\\/h2>\\s*)(<ul>.*?<\\/ul>)/is', '$1' . $list_html, $content, 1 );
				} elseif ( preg_match( '/<h2>\\s*Cartel\\s*2026[^<]*<\\/h2>/i', $content ) ) {
					$content = preg_replace( '/(<h2>\\s*Cartel\\s*2026[^<]*<\\/h2>)/i', '$1' . "\n" . $list_html, $content, 1 );
				}
			}
		}

		return $content;
	}

	private function format_spanish_date( $ymd, $include_year = true ) {
		$ymd = trim( (string) $ymd );
		if ( ! preg_match( '/^(\\d{4})-(\\d{2})-(\\d{2})$/', $ymd, $m ) ) {
			return '';
		}
		$year = $m[1];
		$month = (int) $m[2];
		$day = (int) $m[3];
		$months = array(
			1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio',
			7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
		);
		if ( ! isset( $months[ $month ] ) ) {
			return '';
		}
		$text = $day . ' de ' . $months[ $month ];
		if ( $include_year ) {
			$text .= ' de ' . $year;
		}
		return $text;
	}

	private function build_artist_list_html( $artists, $evidence ) {
		$dates = $this->extract_artist_dates( $evidence );
		$lines = array();
		foreach ( $artists as $name ) {
			$name = trim( (string) $name );
			if ( $name === '' ) {
				continue;
			}
			$date = $dates[ $name ] ?? '';
			if ( $date ) {
				$lines[] = '<li><strong>' . esc_html( $name ) . '</strong> — ' . esc_html( $date ) . '</li>';
			} else {
				$lines[] = '<li><strong>' . esc_html( $name ) . '</strong></li>';
			}
		}
		if ( empty( $lines ) ) {
			return '';
		}
		return "<ul>\n\t" . implode( "\n\t", $lines ) . "\n</ul>";
	}

	private function extract_artist_dates( $evidence ) {
		if ( ! is_array( $evidence ) ) {
			return array();
		}
		$months = array( 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre' );
		$month_regex = implode( '|', $months );
		$map = array();
		foreach ( $evidence as $row ) {
			$snippet = $row['snippet'] ?? '';
			if ( ! $snippet ) {
				continue;
			}
			$lines = preg_split( '/\\r?\\n/', (string) $snippet );
			foreach ( $lines as $line ) {
				$line = trim( $line );
				if ( $line === '' ) {
					continue;
				}
				if ( preg_match( '/-?\\s*(\\d{1,2})\\s*(?:de\\s*)?(' . $month_regex . ')(?:\\s*de\\s*(\\d{4}))?\\s*[:\\-–]\\s*(.+)/i', $line, $m ) ) {
					$day = $m[1];
					$month = strtolower( $m[2] );
					$name = trim( $m[4] );
					$name = preg_replace( '/\\s+\\(.+\\)$/', '', $name );
					$map[ $name ] = $day . ' de ' . $month;
					continue;
				}
				if ( preg_match( '/(\\d{1,2})\\s*de\\s*(' . $month_regex . ')\\s*de\\s*(\\d{4})\\s+([^;]+)/i', $line, $m ) ) {
					$day = $m[1];
					$month = strtolower( $m[2] );
					$name = trim( $m[4] );
					$name = preg_replace( '/\\s+\\(.+\\)$/', '', $name );
					$map[ $name ] = $day . ' de ' . $month;
				}
			}
		}
		return $map;
	}

	private function strip_ticket_mentions( $content ) {
		if ( $content === '' ) {
			return $content;
		}

		$keywords = array(
			'ticket',
			'tickets',
			'entradas',
			'venta de entradas',
			'venta de tickets',
			'comprar entradas',
			'compra de entradas',
			'taquilla',
			'ticketmaster',
			'seetickets',
			'eventbrite',
			'fever',
			'bticket',
			'compralaentrada',
			'ticketrona',
			'tiquetera',
			'tiqueteras',
			'ticketing',
		);

		$regex = implode(
			'|',
			array_map(
				static function ( $keyword ) {
					return preg_quote( $keyword, '/' );
				},
				$keywords
			)
		);

		if ( $regex === '' ) {
			return $content;
		}

		$content = preg_replace( '/<(p|li)[^>]*>.*?(?:' . $regex . ').*?<\\/\\1>/is', '', $content );
		$content = preg_replace( '/' . $regex . '/i', '', $content );
		$content = preg_replace( '/\\s{2,}/', ' ', $content );
		$content = preg_replace( '/\\s+([.,;:!?])/', '$1', $content );

		return trim( $content );
	}

	private function is_ticket_url( $url ) {
		$url = strtolower( (string) $url );
		if ( $url === '' ) {
			return false;
		}
		$needles = array(
			'ticket',
			'entradas',
			'bticket',
			'ticketmaster',
			'seetickets',
			'eventbrite',
			'fever',
			'compralaentrada',
			'ticketrona',
		);
		foreach ( $needles as $needle ) {
			if ( strpos( $url, $needle ) !== false ) {
				return true;
			}
		}
		return false;
	}

	private function get_taxonomy_list( $festival_id, $taxonomy ) {
		$terms = get_the_terms( $festival_id, $taxonomy );
		if ( ! is_array( $terms ) ) {
			return '';
		}
		$names = wp_list_pluck( $terms, 'name' );
		return implode( ', ', $names );
	}

	public function handle_reject_update() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No permitido' );
		}

		$update_id = isset( $_GET['update_id'] ) ? (int) $_GET['update_id'] : 0;
		check_admin_referer( 'mfu_reject_update_' . $update_id );

		global $wpdb;
		$table = MFU_DB::table( 'updates' );
		$wpdb->update(
			$table,
			array( 'status' => 'rejected' ),
			array( 'id' => $update_id ),
			array( '%s' ),
			array( '%d' )
		);

		wp_redirect( admin_url( 'admin.php?page=mfu-updates' ) );
		exit;
	}

	public function register_acf_fields() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			array(
				'key' => 'group_mfu_sources',
				'title' => 'Fuentes oficiales (MFU)',
					'fields' => array(
					array(
						'key' => 'field_mfu_web_oficial',
						'label' => 'Web oficial',
						'name' => 'mf_web_oficial',
						'type' => 'url',
					),
					array(
						'key' => 'field_mfu_instagram',
						'label' => 'Instagram',
						'name' => 'mf_instagram',
						'type' => 'url',
					),
						array(
							'key' => 'field_mfu_artistas',
							'label' => 'Artistas (MFU)',
							'name' => 'mf_artistas',
							'type' => 'textarea',
						),
						array(
							'key' => 'field_mfu_cartel_completo',
							'label' => 'Cartel completo (MFU)',
							'name' => 'mf_cartel_completo',
							'type' => 'true_false',
							'ui' => 1,
						),
						array(
							'key' => 'field_mfu_fuentes_extra',
							'label' => 'Fuentes extra',
							'name' => 'mf_fuentes_extra',
						'type' => 'repeater',
						'sub_fields' => array(
							array(
								'key' => 'field_mfu_fuente_url',
								'label' => 'URL',
								'name' => 'url',
								'type' => 'url',
							),
						),
					),
				),
				'location' => array(
					array(
						array(
							'param' => 'post_type',
							'operator' => '==',
							'value' => 'festi',
						),
					),
				),
			)
		);
	}
}
					if ( ! empty( $compare_result['items']['perplexity_search'] ) && is_array( $compare_result['items']['perplexity_search'] ) ) {
						echo '<h4>Perplexity (search)</h4>';
						echo '<table class="widefat fixed striped"><thead><tr>';
						echo '<th>Fecha</th><th>Titulo</th><th>Snippet</th><th>Fuente</th><th>URL</th>';
						echo '</tr></thead><tbody>';
						foreach ( $compare_result['items']['perplexity_search'] as $row ) {
							$host = '';
							if ( ! empty( $row['url'] ) ) {
								$host = wp_parse_url( $row['url'], PHP_URL_HOST );
							}
							echo '<tr>';
							echo '<td>' . esc_html( $row['date'] ?? '' ) . '</td>';
							echo '<td>' . esc_html( $row['title'] ?? '' ) . '</td>';
							echo '<td>' . esc_html( $row['snippet'] ?? '' ) . '</td>';
							echo '<td>' . esc_html( $host ) . '</td>';
							echo '<td>' . ( ! empty( $row['url'] ) ? '<a href="' . esc_url( $row['url'] ) . '" target="_blank" rel="noopener">abrir</a>' : '' ) . '</td>';
							echo '</tr>';
						}
						echo '</tbody></table>';
					}





