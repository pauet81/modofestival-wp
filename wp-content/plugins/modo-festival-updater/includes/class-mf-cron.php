<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MFU_Cron {
	public static function resolve_job_status_from_counts( $row ) {
		$running = isset( $row['running'] ) ? (int) $row['running'] : 0;
		$queued = isset( $row['queued'] ) ? (int) $row['queued'] : 0;
		$error = isset( $row['error'] ) ? (int) $row['error'] : 0;
		$done = isset( $row['done'] ) ? (int) $row['done'] : 0;

		if ( $running > 0 ) {
			return 'running';
		}
		if ( $queued > 0 ) {
			return 'queued';
		}
		if ( $error > 0 ) {
			return 'error';
		}
		if ( $done > 0 ) {
			return 'done';
		}
		return '-';
	}
	public static function register_schedules() {
		add_filter( 'cron_schedules', array( __CLASS__, 'add_schedule' ) );
	}

	public static function add_schedule( $schedules ) {
		$settings = get_option( MFU_OPTION_KEY, array() );
		$interval = isset( $settings['cron_interval'] ) ? (int) $settings['cron_interval'] : 86400;
		if ( $interval < 900 ) {
			$interval = 900;
		}
		$schedules['mfu_custom'] = array(
			'interval' => $interval,
			'display' => 'MFU Custom Interval',
		);
		return $schedules;
	}

	public static function maybe_schedule() {
		if ( ! wp_next_scheduled( MFU_CRON_HOOK ) ) {
			wp_schedule_event( time() + 60, 'mfu_custom', MFU_CRON_HOOK );
		}
	}

	public static function enqueue_job( $festival_id, $priority = 10, $source = 'manual' ) {
		global $wpdb;
		$table = MFU_DB::table( 'jobs' );

		$wpdb->insert(
			$table,
			array(
				'festival_id' => (int) $festival_id,
				'status' => 'queued',
				'source' => $source,
				'priority' => (int) $priority,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%d', '%s' )
		);
	}

	public static function set_progress( $job_id, $message ) {
		$payload = array(
			'job_id' => (int) $job_id,
			'message' => sanitize_text_field( (string) $message ),
			'updated_at' => current_time( 'mysql' ),
		);
		update_option( 'mfu_progress', $payload, false );
	}

	public static function add_error_log( $festival_id, $job_id, $message, $context = array() ) {
		$log = get_option( 'mfu_error_log', array() );
		if ( ! is_array( $log ) ) {
			$log = array();
		}

		$entry = array(
			'time' => current_time( 'mysql' ),
			'festival_id' => (int) $festival_id,
			'job_id' => (int) $job_id,
			'message' => sanitize_text_field( (string) $message ),
			'context' => is_array( $context ) ? $context : array(),
		);

		array_unshift( $log, $entry );
		if ( count( $log ) > 200 ) {
			$log = array_slice( $log, 0, 200 );
		}

		update_option( 'mfu_error_log', $log, false );
	}

	public static function process_queue() {
		global $wpdb;
		$table = MFU_DB::table( 'jobs' );
		$settings = get_option( MFU_OPTION_KEY, array() );
		$batch = isset( $settings['batch_size'] ) ? (int) $settings['batch_size'] : 5;
		if ( $batch < 1 ) {
			$batch = 1;
		}

		// Mark stuck running jobs as error to avoid permanent "running" state.
		$wpdb->query(
			"UPDATE {$table} SET status='error', error='timeout', finished_at=NOW()
			WHERE status='running' AND started_at IS NOT NULL AND TIMESTAMPDIFF(SECOND, started_at, NOW()) > 300"
		);

		$jobs = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE status='queued' ORDER BY priority ASC, id ASC LIMIT %d",
			$batch
		) );

		if ( empty( $jobs ) ) {
			return;
		}

		$processor = new MFU_Processor();

		foreach ( $jobs as $job ) {
			self::set_progress( $job->id, 'Iniciando trabajo' );
			$wpdb->update(
				$table,
				array(
					'status' => 'running',
					'started_at' => current_time( 'mysql' ),
				),
				array( 'id' => $job->id ),
				array( '%s', '%s' ),
				array( '%d' )
			);

			$progress_cb = function( $message ) use ( $job ) {
				MFU_Cron::set_progress( $job->id, $message );
			};
			$result = $processor->process_festival( (int) $job->festival_id, $progress_cb );

			if ( is_wp_error( $result ) ) {
				$wpdb->update(
					$table,
					array(
						'status' => 'error',
						'error' => $result->get_error_message(),
						'finished_at' => current_time( 'mysql' ),
					),
					array( 'id' => $job->id ),
					array( '%s', '%s', '%s' ),
					array( '%d' )
				);
				self::add_error_log( (int) $job->festival_id, (int) $job->id, $result->get_error_message(), array( 'code' => $result->get_error_code() ) );
				self::set_progress( $job->id, 'Error: ' . $result->get_error_message() );
			} else {
				$wpdb->update(
					$table,
					array(
						'status' => 'done',
						'finished_at' => current_time( 'mysql' ),
					),
					array( 'id' => $job->id ),
					array( '%s', '%s' ),
					array( '%d' )
				);
				self::set_progress( $job->id, 'Trabajo completado' );
			}
		}
	}
}
