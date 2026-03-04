<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MFU_Festival_Taxonomy {
	const TAXONOMY = 'festival_relacionado';
	const TERM_META_FESTI_ID = 'mfu_festi_id';
	private static $sync_context = false;
	private static $allow_content_injection = false;

	public static function bootstrap() {
		add_action( 'init', array( __CLASS__, 'register_taxonomy' ), 5 );
		add_action( 'init', array( __CLASS__, 'maybe_initial_sync' ), 20 );
		add_filter( 'pre_insert_term', array( __CLASS__, 'block_manual_term_creation' ), 10, 2 );
		add_filter( 'default_hidden_meta_boxes', array( __CLASS__, 'force_metabox_visible' ), 10, 2 );
		add_filter( 'term_link', array( __CLASS__, 'filter_term_link_to_festi' ), 10, 3 );
		add_filter( 'the_content', array( __CLASS__, 'strip_terms_block_outside_single' ), 1 );
		add_filter( 'the_excerpt', array( __CLASS__, 'strip_terms_block_markup' ), 1 );
		add_filter( 'get_the_excerpt', array( __CLASS__, 'strip_terms_block_markup' ), 1 );
		add_filter( 'the_posts', array( __CLASS__, 'sanitize_posts_on_non_single' ), 20, 2 );
		add_action( 'wp', array( __CLASS__, 'conditionally_hook_content_injection' ) );
		add_action( 'template_redirect', array( __CLASS__, 'setup_content_injection_context' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'redirect_tax_archive_to_festi' ), 1 );

		add_action( 'save_post_festi', array( __CLASS__, 'handle_save_festi' ), 10, 3 );
		add_action( 'trashed_post', array( __CLASS__, 'handle_trashed_post' ), 10, 1 );
		add_action( 'untrashed_post', array( __CLASS__, 'handle_untrashed_post' ), 10, 1 );
		add_action( 'before_delete_post', array( __CLASS__, 'handle_before_delete_post' ), 10, 1 );
	}

	public static function register_taxonomy() {
		$labels = array(
			'name' => __( 'Festivales', 'modo-festival-updater' ),
			'singular_name' => __( 'Festival', 'modo-festival-updater' ),
			'search_items' => __( 'Buscar festivales', 'modo-festival-updater' ),
			'all_items' => __( 'Todos los festivales', 'modo-festival-updater' ),
			'edit_item' => __( 'Editar festival', 'modo-festival-updater' ),
			'update_item' => __( 'Actualizar festival', 'modo-festival-updater' ),
			'add_new_item' => __( 'Anadir festival', 'modo-festival-updater' ),
			'new_item_name' => __( 'Nuevo festival', 'modo-festival-updater' ),
			'menu_name' => __( 'Festivales', 'modo-festival-updater' ),
		);

		register_taxonomy(
			self::TAXONOMY,
			array( 'post' ),
			array(
				'labels' => $labels,
				'public' => true,
				'show_ui' => true,
				'show_admin_column' => true,
				'show_in_quick_edit' => true,
				'show_in_rest' => true,
				'hierarchical' => false,
				'query_var' => true,
				'rewrite' => array( 'slug' => 'festivales' ),
			)
		);
	}

	public static function block_manual_term_creation( $term, $taxonomy ) {
		if ( $taxonomy !== self::TAXONOMY ) {
			return $term;
		}

		if ( self::$sync_context ) {
			return $term;
		}

		return new WP_Error(
			'mfu_festival_terms_locked',
			__( 'Los terminos de Festivales se sincronizan automaticamente desde las fichas de festival.', 'modo-festival-updater' )
		);
	}

	public static function force_metabox_visible( $hidden, $screen ) {
		if ( ! is_array( $hidden ) || ! $screen || ! isset( $screen->base, $screen->post_type ) ) {
			return $hidden;
		}
		if ( $screen->base !== 'post' || $screen->post_type !== 'post' ) {
			return $hidden;
		}

		$key = 'tagsdiv-' . self::TAXONOMY;
		return array_values( array_diff( $hidden, array( $key ) ) );
	}

	public static function filter_term_link_to_festi( $url, $term, $taxonomy ) {
		if ( $taxonomy !== self::TAXONOMY || ! $term instanceof WP_Term ) {
			return $url;
		}

		$festi_id = self::get_festi_id_for_term( (int) $term->term_id );
		if ( $festi_id <= 0 ) {
			return $url;
		}

		$festi_url = get_permalink( $festi_id );
		return $festi_url ? $festi_url : $url;
	}

	public static function redirect_tax_archive_to_festi() {
		if ( ! is_tax( self::TAXONOMY ) ) {
			return;
		}

		$term = get_queried_object();
		if ( ! $term instanceof WP_Term ) {
			return;
		}

		$festi_id = self::get_festi_id_for_term( (int) $term->term_id );
		if ( $festi_id <= 0 ) {
			return;
		}

		$target = get_permalink( $festi_id );
		if ( ! $target ) {
			return;
		}

		wp_safe_redirect( $target, 301 );
		exit;
	}

	public static function inject_terms_in_post_content( $content ) {
		if ( is_admin() || ! self::$allow_content_injection || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return $content;
		}
		if ( get_post_type( $post_id ) !== 'post' ) {
			return $content;
		}
		if ( (int) get_queried_object_id() !== (int) $post_id ) {
			return $content;
		}

		$terms = wp_get_post_terms( (int) $post_id, self::TAXONOMY );
		if ( is_wp_error( $terms ) || empty( $terms ) || ! is_array( $terms ) ) {
			return $content;
		}

		$links = array();
		foreach ( $terms as $term ) {
			if ( ! $term instanceof WP_Term ) {
				continue;
			}
			$url = get_term_link( $term, self::TAXONOMY );
			if ( is_wp_error( $url ) || ! is_string( $url ) || $url === '' ) {
				continue;
			}
			$links[] = '<a class="mfu-festival-term-link" href="' . esc_url( $url ) . '">' . esc_html( $term->name ) . '</a>';
		}

		if ( empty( $links ) ) {
			return $content;
		}

		// Avoid duplicate render when legacy markup already exists in content.
		$content = self::strip_terms_block_markup( $content );

		$block = '<div class="mfu-festival-terms"><strong>Festivales relacionados:</strong> ' . implode( ', ', $links ) . '</div>';
		return $content . $block;
	}

	public static function conditionally_hook_content_injection() {
		if ( is_singular( 'post' ) ) {
			add_filter( 'the_content', array( __CLASS__, 'inject_terms_in_post_content' ), 20 );
		}
	}

	public static function strip_terms_block_outside_single( $content ) {
		if ( is_singular( 'post' ) ) {
			return $content;
		}

		return self::strip_terms_block_markup( $content );
	}

	public static function strip_terms_block_markup( $content ) {
		$content = (string) $content;
		$content = preg_replace( '/<div\\s+class=\"mfu-festival-terms\"[^>]*>.*?<\\/div>/is', '', $content );
		$content = preg_replace( '/<p[^>]*>\\s*(?:<strong>)?\\s*Festivales\\s+relacionados:\\s*(?:<\\/strong>)?.*?<\\/p>/is', '', $content );
		return $content;
	}

	public static function sanitize_posts_on_non_single( $posts, $query ) {
		if ( is_admin() || is_singular( 'post' ) || empty( $posts ) || ! is_array( $posts ) ) {
			return $posts;
		}

		foreach ( $posts as $index => $post ) {
			if ( ! ( $post instanceof WP_Post ) ) {
				continue;
			}

			$posts[ $index ]->post_content = self::strip_terms_block_markup( $post->post_content );
			$posts[ $index ]->post_excerpt = self::strip_terms_block_markup( $post->post_excerpt );
		}

		return $posts;
	}

	public static function setup_content_injection_context() {
		self::$allow_content_injection = is_singular( 'post' );
	}

	public static function maybe_initial_sync() {
		$flag = get_option( 'mfu_festival_terms_initial_sync_done', '' );
		if ( $flag === '1' ) {
			return;
		}

		self::sync_all_terms();
		update_option( 'mfu_festival_terms_initial_sync_done', '1', false );
	}

	public static function sync_all_terms() {
		$festi_ids = get_posts(
			array(
				'post_type' => 'festi',
				'post_status' => array( 'publish', 'future', 'draft', 'pending', 'private' ),
				'posts_per_page' => -1,
				'fields' => 'ids',
				'orderby' => 'ID',
				'order' => 'ASC',
				'no_found_rows' => true,
			)
		);

		if ( empty( $festi_ids ) || ! is_array( $festi_ids ) ) {
			self::cleanup_terms_against_festi( array() );
			return;
		}

		foreach ( $festi_ids as $festi_id ) {
			self::sync_term_from_festi( (int) $festi_id );
		}

		self::cleanup_terms_against_festi( array_map( 'intval', $festi_ids ) );
	}

	public static function handle_save_festi( $post_id, $post, $update ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( ! $post || $post->post_type !== 'festi' ) {
			return;
		}
		if ( $post->post_status === 'trash' ) {
			self::delete_term_for_festi( (int) $post_id );
			return;
		}
		self::sync_term_from_festi( (int) $post_id );
	}

	public static function handle_trashed_post( $post_id ) {
		if ( get_post_type( $post_id ) !== 'festi' ) {
			return;
		}
		self::delete_term_for_festi( (int) $post_id );
	}

	public static function handle_untrashed_post( $post_id ) {
		if ( get_post_type( $post_id ) !== 'festi' ) {
			return;
		}
		self::sync_term_from_festi( (int) $post_id );
	}

	public static function handle_before_delete_post( $post_id ) {
		if ( get_post_type( $post_id ) !== 'festi' ) {
			return;
		}
		self::delete_term_for_festi( (int) $post_id );
	}

	public static function assign_festival_term_to_post( $post_id, $festi_id ) {
		$post_id = (int) $post_id;
		$festi_id = (int) $festi_id;
		if ( $post_id <= 0 || $festi_id <= 0 ) {
			return;
		}

		$term_id = self::sync_term_from_festi( $festi_id );
		if ( $term_id <= 0 ) {
			return;
		}

		$current = wp_get_object_terms( $post_id, self::TAXONOMY, array( 'fields' => 'ids' ) );
		if ( is_wp_error( $current ) ) {
			$current = array();
		}
		$current = is_array( $current ) ? array_map( 'intval', $current ) : array();
		$current[] = $term_id;
		$current = array_values( array_unique( $current ) );

		wp_set_object_terms( $post_id, $current, self::TAXONOMY, false );
	}

	public static function sync_term_from_festi( $festi_id ) {
		self::$sync_context = true;
		try {
		$festi_id = (int) $festi_id;
		if ( $festi_id <= 0 ) {
			return 0;
		}

		$post = get_post( $festi_id );
		if ( ! $post || $post->post_type !== 'festi' ) {
			return 0;
		}
		if ( $post->post_status === 'trash' ) {
			return 0;
		}

		$name = trim( wp_strip_all_tags( $post->post_title ) );
		if ( $name === '' ) {
			$name = 'Festival ' . $festi_id;
		}

		$term = self::get_term_by_festi_id( $festi_id );
		$slug = self::build_term_slug( $post );

		if ( $term ) {
			$update_args = array();
			if ( $term->name !== $name ) {
				$update_args['name'] = $name;
			}
			if ( $term->slug !== $slug ) {
				$update_args['slug'] = $slug;
			}
			if ( ! empty( $update_args ) ) {
				$updated = wp_update_term( (int) $term->term_id, self::TAXONOMY, $update_args );
				if ( is_wp_error( $updated ) ) {
					return 0;
				}
			}
			self::set_term_festi_meta( (int) $term->term_id, $festi_id );
			return (int) $term->term_id;
		}

		$inserted = wp_insert_term(
			$name,
			self::TAXONOMY,
			array(
				'slug' => $slug,
			)
		);

		if ( is_wp_error( $inserted ) ) {
			if ( $inserted->get_error_code() === 'term_exists' ) {
				$term_id = (int) $inserted->get_error_data();
				if ( $term_id > 0 ) {
					self::set_term_festi_meta( $term_id, $festi_id );
					return $term_id;
				}
			}
			return 0;
		}

		$term_id = isset( $inserted['term_id'] ) ? (int) $inserted['term_id'] : 0;
		if ( $term_id > 0 ) {
			self::set_term_festi_meta( $term_id, $festi_id );
		}
		return $term_id;
		} finally {
			self::$sync_context = false;
		}
	}

	private static function delete_term_for_festi( $festi_id ) {
		$term = self::get_term_by_festi_id( (int) $festi_id );
		if ( ! $term ) {
			return;
		}
		wp_delete_term( (int) $term->term_id, self::TAXONOMY );
	}

	private static function get_term_by_festi_id( $festi_id ) {
		$terms = get_terms(
			array(
				'taxonomy' => self::TAXONOMY,
				'hide_empty' => false,
				'number' => 1,
				'meta_query' => array(
					array(
						'key' => self::TERM_META_FESTI_ID,
						'value' => (string) (int) $festi_id,
						'compare' => '=',
					),
				),
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) || ! is_array( $terms ) ) {
			return null;
		}

		return $terms[0];
	}

	private static function get_festi_id_for_term( $term_id ) {
		$festi_id = (int) get_term_meta( (int) $term_id, self::TERM_META_FESTI_ID, true );
		if ( $festi_id <= 0 ) {
			return 0;
		}
		$post = get_post( $festi_id );
		if ( ! $post || $post->post_type !== 'festi' || $post->post_status === 'trash' ) {
			return 0;
		}
		return $festi_id;
	}

	private static function build_term_slug( $post ) {
		$base = sanitize_title( $post->post_name );
		if ( $base === '' ) {
			$base = sanitize_title( $post->post_title );
		}
		if ( $base === '' ) {
			$base = 'festival';
		}
		return $base . '-' . (int) $post->ID;
	}

	private static function set_term_festi_meta( $term_id, $festi_id ) {
		$term_id = (int) $term_id;
		$festi_id = (int) $festi_id;
		if ( $term_id <= 0 || $festi_id <= 0 ) {
			return;
		}

		delete_term_meta( $term_id, self::TERM_META_FESTI_ID );
		add_term_meta( $term_id, self::TERM_META_FESTI_ID, $festi_id, true );
	}

	private static function cleanup_terms_against_festi( $valid_festi_ids ) {
		$valid_festi_ids = is_array( $valid_festi_ids ) ? array_map( 'intval', $valid_festi_ids ) : array();
		$valid_lookup = array_fill_keys( $valid_festi_ids, true );
		$seen = array();

		$terms = get_terms(
			array(
				'taxonomy' => self::TAXONOMY,
				'hide_empty' => false,
				'fields' => 'all',
				'number' => 0,
			)
		);

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return;
		}

		foreach ( $terms as $term ) {
			$meta_values = get_term_meta( (int) $term->term_id, self::TERM_META_FESTI_ID, false );
			$meta_values = is_array( $meta_values ) ? array_filter( array_map( 'intval', $meta_values ) ) : array();
			$festi_id = ! empty( $meta_values ) ? (int) reset( $meta_values ) : 0;

			$is_invalid = ( $festi_id <= 0 ) || ( ! isset( $valid_lookup[ $festi_id ] ) );
			$is_duplicate = ( $festi_id > 0 && isset( $seen[ $festi_id ] ) );

			if ( $is_invalid || $is_duplicate ) {
				wp_delete_term( (int) $term->term_id, self::TAXONOMY );
				continue;
			}

			$seen[ $festi_id ] = (int) $term->term_id;
		}
	}
}
