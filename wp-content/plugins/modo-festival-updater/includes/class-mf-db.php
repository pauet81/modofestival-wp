<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MFU_DB {
	public static function table( $name ) {
		global $wpdb;
		return $wpdb->prefix . MFU_TABLE_PREFIX . $name;
	}

	public static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$jobs = self::table( 'jobs' );
		$sources = self::table( 'sources' );
		$snapshots = self::table( 'snapshots' );
		$updates = self::table( 'updates' );

		$sql = array();

		$sql[] = "CREATE TABLE {$jobs} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			festival_id BIGINT UNSIGNED NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'queued',
			source VARCHAR(20) NOT NULL DEFAULT 'manual',
			priority INT NOT NULL DEFAULT 10,
			error TEXT NULL,
			created_at DATETIME NOT NULL,
			started_at DATETIME NULL,
			finished_at DATETIME NULL,
			PRIMARY KEY  (id),
			KEY festival_id (festival_id),
			KEY status (status)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$sources} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			festival_id BIGINT UNSIGNED NOT NULL,
			type VARCHAR(20) NOT NULL,
			url TEXT NOT NULL,
			active TINYINT(1) NOT NULL DEFAULT 1,
			last_checked DATETIME NULL,
			etag VARCHAR(255) NULL,
			last_modified VARCHAR(255) NULL,
			last_hash CHAR(64) NULL,
			PRIMARY KEY  (id),
			KEY festival_id (festival_id),
			KEY type (type)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$snapshots} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			festival_id BIGINT UNSIGNED NOT NULL,
			source_id BIGINT UNSIGNED NOT NULL,
			fetched_at DATETIME NOT NULL,
			text_hash CHAR(64) NOT NULL,
			extracted_text LONGTEXT NULL,
			structured_json LONGTEXT NULL,
			PRIMARY KEY  (id),
			KEY festival_id (festival_id),
			KEY source_id (source_id)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$updates} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			festival_id BIGINT UNSIGNED NOT NULL,
			detected_at DATETIME NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'pending_review',
			diffs_json LONGTEXT NULL,
			evidence_json LONGTEXT NULL,
			summary TEXT NULL,
			applied_by BIGINT UNSIGNED NULL,
			applied_at DATETIME NULL,
			news_post_id BIGINT UNSIGNED NULL,
			PRIMARY KEY  (id),
			KEY festival_id (festival_id),
			KEY status (status)
		) {$charset_collate};";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}
	}
}
