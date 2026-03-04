<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MFU_News {
	public static function maybe_create_news_post( $update, $festival_id, $diffs, $evidence, $updated_content ) {
		$options = get_option( MFU_OPTION_KEY, array() );
		if ( empty( $options['news_enabled'] ) ) {
			return;
		}

		if ( ! $update || empty( $update->id ) ) {
			return;
		}

		if ( ! self::is_relevant_update( $diffs, $evidence, $updated_content ) ) {
			return;
		}

		if ( ! empty( $update->news_post_id ) ) {
			return;
		}

		if ( self::existing_news_post( (int) $update->id ) ) {
			return;
		}

		$post_id = self::create_news_post( $festival_id, $diffs, $evidence );
		if ( is_wp_error( $post_id ) ) {
			return;
		}

		add_post_meta( $post_id, 'mf_update_id', (int) $update->id, true );

		global $wpdb;
		$table = MFU_DB::table( 'updates' );
		$wpdb->update(
			$table,
			array( 'news_post_id' => (int) $post_id ),
			array( 'id' => (int) $update->id ),
			array( '%d' ),
			array( '%d' )
		);

		if ( is_array( $evidence ) ) {
			$debug = isset( $evidence['debug_log'] ) && is_array( $evidence['debug_log'] ) ? $evidence['debug_log'] : array();
			$debug[] = array(
				'stage' => 'news_post_created',
				'time' => current_time( 'mysql' ),
				'data' => array(
					'festival_id' => (int) $festival_id,
					'update_id' => (int) $update->id,
					'post_id' => (int) $post_id,
					'title' => get_the_title( $post_id ),
				),
			);
			$evidence['debug_log'] = $debug;
			$wpdb->update(
				$table,
				array( 'evidence_json' => wp_json_encode( $evidence ) ),
				array( 'id' => (int) $update->id ),
				array( '%s' ),
				array( '%d' )
			);
		}
	}

	public static function create_news_post( $festival_id, $diffs, $evidence ) {
		$festival = get_post( $festival_id );
		if ( ! $festival ) {
			return new WP_Error( 'mfu_news_festival', 'Festival not found' );
		}

		$festival_url = get_permalink( $festival_id );
		$evidence_list = self::evidence_to_list( $evidence );
		$edition = get_post_meta( $festival_id, 'edicion', true );
		$added_artists = self::extract_added_artists( $diffs );
		$dates = self::extract_dates_from_diffs( $diffs );
		$dates_announced = self::dates_announced_from_diffs( $diffs );
		$change_type = self::classify_change_type( $diffs, $added_artists, $dates_announced );
		$other_changes = self::extract_other_changes( $diffs );

		$ai = new MFU_AI();
		$title = 'Novedades en ' . $festival->post_title;
		$content = self::fallback_content( $festival->post_title, $festival_url, $diffs, $edition );
		$meta_title = '';
		$meta_description = '';

		if ( $ai->has_key() ) {
			$draft = $ai->write_news(
				$festival->post_title,
				$festival_url,
				$edition,
				$change_type,
				$added_artists,
				$dates['start'],
				$dates['end'],
				$other_changes,
				$evidence_list
			);
			if ( ! is_wp_error( $draft ) ) {
				$title = $draft['title'];
				$content = $draft['content'];
				$meta_title = $draft['meta_title'] ?? '';
				$meta_description = $draft['meta_description'] ?? '';
			}
		}

		$title = self::adjust_title_for_diffs( $title, $festival->post_title, $edition, $diffs, $added_artists, $change_type, $dates_announced );
		$content = self::emphasize_artists_when_applicable( $content, $festival->post_title, $edition, $diffs );
		$meta_title = self::sanitize_meta_text( $meta_title );
		$meta_description = self::sanitize_meta_text( $meta_description );

		$content = self::strip_ticket_links( $content );
		$content = self::strip_ticket_mentions( $content );
		$content = self::enhance_news_content( $content, $festival->post_title, $festival_url, $diffs, $edition );

		$post_id = wp_insert_post(
			array(
				'post_title' => wp_strip_all_tags( $title ),
				'post_content' => wp_kses_post( $content ),
				'post_status' => 'draft',
				'post_type' => 'post',
				'post_author' => self::resolve_news_author_id(),
			)
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		add_post_meta( $post_id, 'mf_festival_id', (int) $festival_id, true );
		if ( class_exists( 'MFU_Festival_Taxonomy' ) ) {
			MFU_Festival_Taxonomy::assign_festival_term_to_post( (int) $post_id, (int) $festival_id );
		}
		self::sync_ticket_url_from_festival( $festival_id, $post_id );
		if ( $meta_title !== '' ) {
			update_post_meta( $post_id, '_yoast_wpseo_title', $meta_title );
		}
		if ( $meta_description !== '' ) {
			update_post_meta( $post_id, '_yoast_wpseo_metadesc', $meta_description );
		}

		$category = get_term_by( 'slug', 'actualidad', 'category' );
		if ( $category ) {
			wp_set_post_categories( $post_id, array( $category->term_id ), true );
		}

		$thumb_id = get_post_thumbnail_id( $festival_id );
		if ( $thumb_id ) {
			set_post_thumbnail( $post_id, $thumb_id );
		}

		return $post_id;
	}

	private static function adjust_title_for_diffs( $title, $festival_name, $edition, $diffs, $added_artists, $change_type, $dates_announced ) {
		$title = trim( (string) $title );
		if ( $title === '' || self::is_generic_news_title( $title ) ) {
			$title = 'Novedades en ' . $festival_name;
		}

		$has_dates = self::diffs_include_dates( $diffs );
		$has_artists = self::diffs_include_artists( $diffs );
		$added_artists = is_array( $added_artists ) ? $added_artists : array();
		$title_lc = strtolower( $title );
		$title_mentions_dates = strpos( $title_lc, 'fecha' ) !== false;
		$title_has_artist = false;
		foreach ( $added_artists as $artist ) {
			if ( $artist !== '' && stripos( $title, $artist ) !== false ) {
				$title_has_artist = true;
				break;
			}
		}

		if ( $change_type === 'artists' && ! $title_has_artist && ! empty( $added_artists ) ) {
			$location = self::extract_location_from_diffs( $diffs );
			$artists = array_slice( $added_artists, 0, 4 );
			$base = $edition ? $festival_name . ' ' . $edition : $festival_name;
			if ( $location ) {
				$base .= ' en ' . $location;
			}
			$title = $base . ' suma a ' . implode( ', ', $artists ) . ' a su cartel';
		} elseif ( $dates_announced && ! $title_mentions_dates ) {
			$base = $edition ? $festival_name . ' ' . $edition : $festival_name;
			$title = $base . ' anuncia fechas para la edicion ' . ( $edition ? $edition : '2026' );
		} elseif ( ! $has_dates && $title_mentions_dates && $has_artists ) {
			$location = self::extract_location_from_diffs( $diffs );
			$artists = self::extract_artists_from_diffs( $diffs, 3 );
			$base = $edition ? $festival_name . ' ' . $edition : $festival_name;
			if ( $location ) {
				$base .= ' en ' . $location;
			}
			if ( ! empty( $artists ) ) {
				$title = $base . ' anuncia nuevas bandas como ' . implode( ', ', $artists );
			} else {
				$title = $base . ' anuncia nuevas incorporaciones al cartel';
			}
		}

		return $title;
	}

	private static function is_generic_news_title( $title ) {
		$title = strtolower( trim( (string) $title ) );
		if ( $title === '' ) {
			return true;
		}

		$patterns = array(
			'/\\bficha\\s+actualizada\\b/u',
			'/\\binformaci[oó]n\\s+actualizada\\b/u',
			'/\\bnovedades\\s+en\\s+la\\s+ficha\\b/u',
			'/\\bcambios\\s+en\\s+la\\s+ficha\\b/u',
			'/\\binformaci[oó]n\\s+de\\s+la\\s+ficha\\b/u',
			'/\\bficha\\b/u',
		);
		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $title ) === 1 ) {
				return true;
			}
		}
		return false;
	}

	private static function emphasize_artists_when_applicable( $content, $festival_name, $edition, $diffs ) {
		$content = trim( (string) $content );
		if ( $content === '' ) {
			return $content;
		}

		$has_dates = self::diffs_include_dates( $diffs );
		$has_artists = self::diffs_include_artists( $diffs );
		if ( $has_dates || ! $has_artists ) {
			return $content;
		}

		$artists = self::extract_artists_from_diffs( $diffs, 6 );
		if ( empty( $artists ) ) {
			return $content;
		}

		$title_bits = $edition ? esc_html( $festival_name . ' ' . $edition ) : esc_html( $festival_name );
		$artists_text = esc_html( implode( ', ', $artists ) );
		$intro = '<p>' . $title_bits . ' incorpora nuevas bandas al cartel. Entre ellas: ' . $artists_text . '.</p>';

		return $intro . "\n" . $content;
	}

	private static function evidence_to_list( $evidence ) {
		$list = array();
		if ( ! empty( $evidence['facts']['summary'] ) ) {
			$list[] = $evidence['facts']['summary'];
		}
		if ( ! empty( $evidence['sources'] ) && is_array( $evidence['sources'] ) ) {
			foreach ( $evidence['sources'] as $src ) {
				if ( ! empty( $src['url'] ) ) {
					$list[] = $src['url'];
				}
			}
		}
		return $list;
	}

	private static function fallback_content( $festival_name, $festival_url, $diffs, $edition ) {
		$title_bits = $edition ? esc_html( $festival_name . ' ' . $edition ) : esc_html( $festival_name );
		$artists = self::extract_added_artists( $diffs );
		$dates = self::extract_dates_from_diffs( $diffs );
		$dates_announced = self::dates_announced_from_diffs( $diffs );
		$other_changes = self::extract_other_changes( $diffs );

		$lead = 'Se han detectado novedades en la ficha de ' . $title_bits . '.';
		if ( ! empty( $artists ) ) {
			$lead = $title_bits . ' actualiza su cartel con nuevas incorporaciones: ' . esc_html( implode( ', ', array_slice( $artists, 0, 4 ) ) ) . '.';
		} elseif ( $dates_announced && ( $dates['start'] || $dates['end'] ) ) {
			if ( $dates['start'] && $dates['end'] ) {
				$lead = $title_bits . ' anuncia sus fechas para esta edicion: del ' . esc_html( $dates['start'] ) . ' al ' . esc_html( $dates['end'] ) . '.';
			} elseif ( $dates['start'] ) {
				$lead = $title_bits . ' anuncia su fecha de inicio: ' . esc_html( $dates['start'] ) . '.';
			} else {
				$lead = $title_bits . ' anuncia su fecha de finalizacion: ' . esc_html( $dates['end'] ) . '.';
			}
		} elseif ( ! empty( $other_changes ) ) {
			$lead = $title_bits . ' registra cambios confirmados en ' . esc_html( $other_changes[0] ) . '.';
		}

		$content = '<p>' . $lead . '</p>';
		if ( ! empty( $artists ) ) {
			$content .= '<h2>Cartel actualizado</h2>';
			$content .= '<p>Entre las nuevas confirmaciones destacan ' . esc_html( implode( ', ', array_slice( $artists, 0, 6 ) ) ) . '.</p>';
		}
		if ( $dates_announced && ( $dates['start'] || $dates['end'] ) ) {
			$content .= '<h2>Fechas</h2>';
			if ( $dates['start'] && $dates['end'] ) {
				$content .= '<p>La edicion queda programada entre ' . esc_html( $dates['start'] ) . ' y ' . esc_html( $dates['end'] ) . '.</p>';
			} elseif ( $dates['start'] ) {
				$content .= '<p>La fecha de inicio confirmada es ' . esc_html( $dates['start'] ) . '.</p>';
			} else {
				$content .= '<p>La fecha de cierre confirmada es ' . esc_html( $dates['end'] ) . '.</p>';
			}
		}
		if ( ! empty( $other_changes ) ) {
			$content .= '<h2>Lo que se sabe ahora</h2>';
			$content .= '<p>Otras novedades detectadas: ' . esc_html( implode( '; ', array_slice( $other_changes, 0, 3 ) ) ) . '.</p>';
		}
		$content .= '<p>Consulta la ficha del festival para detalles actualizados: <a href="' . esc_url( $festival_url ) . '">' . esc_html( $festival_name ) . '</a>.</p>';

		return $content;
	}

	private static function enhance_news_content( $content, $festival_name, $festival_url, $diffs, $edition ) {
		$content = trim( (string) $content );
		if ( $content === '' ) {
			return $content;
		}

		$content = self::sanitize_news_content( $content );
		$content = self::normalize_bold_usage( $content );

		$has_festival_link = strpos( $content, $festival_url ) !== false;
		if ( ! $has_festival_link ) {
			$title_bits = $edition ? esc_html( $festival_name . ' ' . $edition ) : esc_html( $festival_name );
			$content .= '<p>Consulta la ficha del festival para detalles actualizados: <a href="' . esc_url( $festival_url ) . '">' . $title_bits . '</a>.</p>';
		}

		return $content;
	}

	private static function sanitize_news_content( $content ) {
		$patterns = array(
			'/<h2>\\s*Resumen de cambios\\s*<\\/h2>.*?(?=<h2>|\\z)/is',
			'/^\\s*Resumen de cambios\\s*$/mi',
			'/<h[23]>\\s*(Resumen|En resumen|Conclusiones?|Conclusion|Para cerrar|Cierre|En sintesis)\\s*<\\/h[23]>.*?(?=<h[23]>|\\z)/is',
			'/^\\s*mf_[a-z0-9_]+\\s*:\\s*.+$/mi',
			'/^\\s*(sin_fechas_confirmadas|content|mf_cartel_completo)\\s*:\\s*.+$/mi',
			'/Resumen de cambios[\\s\\S]*?(?=\\n\\n|\\z)/i',
			'/<p>\\s*(En resumen|Resumen|Conclusiones?|Conclusion|Para cerrar|En sintesis|En definitiva|Para concluir)\\b[^<]*<\\/p>/i',
			'/^\\s*(En resumen|Resumen|Conclusiones?|Conclusion|Para cerrar|En sintesis|En definitiva|Para concluir)\\b.*$/mi',
			'/^\\s*Segun la informacion disponible,\\s*el contenido\\s*.+?IA\\.?\\s*$/mi',
			'/IA\\b/i',
			'/inteligencia artificial/i',
		);

		$content = preg_replace( $patterns, '', $content );
		$content = preg_replace( '/\\n{3,}/', "\n\n", $content );

		return trim( $content );
	}

	private static function normalize_bold_usage( $content ) {
		$content = str_replace( array( '<b>', '</b>' ), array( '<strong>', '</strong>' ), $content );
		$content = preg_replace_callback(
			'/<strong>(.*?)<\\/strong>/is',
			static function ( $matches ) {
				$text = trim( wp_strip_all_tags( (string) $matches[1] ) );
				if ( $text === '' ) {
					return '';
				}
				$length = function_exists( 'mb_strlen' ) ? mb_strlen( $text ) : strlen( $text );
				$too_long = $length > 90;
				$looks_like_sentence = preg_match( '/[\\.;:!?]/u', $text ) === 1;
				if ( $too_long || $looks_like_sentence ) {
					return esc_html( $text );
				}
				return '<strong>' . esc_html( $text ) . '</strong>';
			},
			$content
		);

		return trim( (string) $content );
	}

	private static function diffs_include_dates( $diffs ) {
		if ( ! is_array( $diffs ) ) {
			return false;
		}
		foreach ( array( 'fecha_inicio', 'fecha_fin', 'sin_fechas_confirmadas' ) as $key ) {
			if ( array_key_exists( $key, $diffs ) ) {
				return true;
			}
		}
		return false;
	}

	private static function diffs_include_artists( $diffs ) {
		if ( ! is_array( $diffs ) ) {
			return false;
		}
		return array_key_exists( 'mf_artistas', $diffs ) || array_key_exists( 'mf_cartel_completo', $diffs );
	}

	private static function extract_location_from_diffs( $diffs ) {
		if ( ! is_array( $diffs ) || empty( $diffs['localidad']['after'] ) ) {
			return '';
		}
		return trim( (string) $diffs['localidad']['after'] );
	}

	private static function extract_artists_from_diffs( $diffs, $limit = 5 ) {
		if ( ! is_array( $diffs ) || empty( $diffs['mf_artistas']['after'] ) ) {
			return array();
		}
		$raw = (string) $diffs['mf_artistas']['after'];
		$parts = preg_split( '/\\s*,\\s*/', $raw );
		$parts = array_filter( array_map( 'trim', $parts ) );
		if ( empty( $parts ) ) {
			return array();
		}
		return array_slice( array_values( $parts ), 0, max( 1, (int) $limit ) );
	}

	private static function diffs_to_list_html( $diffs ) {
		if ( ! is_array( $diffs ) || empty( $diffs ) ) {
			return '';
		}

		$items = '';
		foreach ( $diffs as $key => $diff ) {
			if ( ! isset( $diff['after'] ) ) {
				continue;
			}
			$items .= '<li><strong>' . esc_html( $key ) . ':</strong> ' . esc_html( $diff['after'] ) . '</li>';
		}

		return $items !== '' ? '<ul>' . $items . '</ul>' : '';
	}

	private static function sync_ticket_url_from_festival( $festival_id, $post_id ) {
		$ticket_url = '';
		foreach ( array( 'url_entradas', 'ticket_url', 'tickets_url' ) as $key ) {
			$value = get_post_meta( $festival_id, $key, true );
			if ( is_string( $value ) && trim( $value ) !== '' ) {
				$ticket_url = trim( $value );
				break;
			}
		}

		if ( $ticket_url === '' ) {
			return;
		}

		if ( function_exists( 'update_field' ) ) {
			update_field( 'url_entradas', $ticket_url, $post_id );
		}
		update_post_meta( $post_id, 'url_entradas', $ticket_url );
		update_post_meta( $post_id, 'enlace_entradas', $ticket_url );
	}

	private static function resolve_news_author_id() {
		$lucas_id = 33;
		if ( $lucas_id > 0 ) {
			return $lucas_id;
		}

		$candidates = array( 'lucas', 'Lucas' );
		foreach ( $candidates as $candidate ) {
			$user = get_user_by( 'login', $candidate );
			if ( $user && ! is_wp_error( $user ) ) {
				return (int) $user->ID;
			}
			$user = get_user_by( 'slug', $candidate );
			if ( $user && ! is_wp_error( $user ) ) {
				return (int) $user->ID;
			}
		}

		$users = get_users(
			array(
				'search' => 'Lucas',
				'search_columns' => array( 'display_name' ),
				'number' => 1,
				'fields' => array( 'ID' ),
			)
		);
		if ( ! empty( $users ) ) {
			return (int) $users[0]->ID;
		}

		return get_current_user_id();
	}

	private static function is_relevant_update( $diffs, $evidence, $updated_content ) {
		if ( ! is_array( $diffs ) || empty( $diffs ) ) {
			return false;
		}

		$added_artists = self::extract_added_artists( $diffs );
		if ( ! empty( $added_artists ) ) {
			return true;
		}

		if ( self::dates_announced_from_diffs( $diffs ) ) {
			return true;
		}

		$other = self::extract_other_changes( $diffs );
		return ! empty( $other );
	}

	private static function existing_news_post( $update_id ) {
		$existing = get_posts(
			array(
				'post_type' => 'post',
				'post_status' => 'any',
				'posts_per_page' => 1,
				'fields' => 'ids',
				'meta_key' => 'mf_update_id',
				'meta_value' => (int) $update_id,
			)
		);

		return ! empty( $existing );
	}

	private static function strip_ticket_links( $content ) {
		if ( $content === '' ) {
			return $content;
		}

		return preg_replace_callback(
			'/<a\\s[^>]*href=["\\\']([^"\\\']+)["\\\'][^>]*>(.*?)<\\/a>/is',
			function ( $matches ) {
				$url = isset( $matches[1] ) ? $matches[1] : '';
				if ( self::is_ticket_url( $url ) ) {
					return $matches[2];
				}
				return $matches[0];
			},
			$content
		);
	}

	private static function strip_ticket_mentions( $content ) {
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

	private static function is_ticket_url( $url ) {
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

	private static function extract_added_artists( $diffs ) {
		if ( ! is_array( $diffs ) || empty( $diffs['mf_artistas'] ) ) {
			return array();
		}
		$before = isset( $diffs['mf_artistas']['before'] ) ? (string) $diffs['mf_artistas']['before'] : '';
		$after = isset( $diffs['mf_artistas']['after'] ) ? (string) $diffs['mf_artistas']['after'] : '';
		if ( $after === '' ) {
			return array();
		}
		$before_list = array_filter( array_map( 'trim', preg_split( '/[\\r\\n,;]+/', $before ) ) );
		$after_list = array_filter( array_map( 'trim', preg_split( '/[\\r\\n,;]+/', $after ) ) );
		$before_norm = array();
		foreach ( $before_list as $artist ) {
			$before_norm[] = strtolower( remove_accents( $artist ) );
		}
		$added = array();
		foreach ( $after_list as $artist ) {
			$norm = strtolower( remove_accents( $artist ) );
			if ( ! in_array( $norm, $before_norm, true ) ) {
				$added[] = $artist;
			}
		}
		return $added;
	}

	private static function extract_dates_from_diffs( $diffs ) {
		$start = '';
		$end = '';
		if ( is_array( $diffs ) && ! empty( $diffs['fecha_inicio']['after'] ) ) {
			$start = (string) $diffs['fecha_inicio']['after'];
		}
		if ( is_array( $diffs ) && ! empty( $diffs['fecha_fin']['after'] ) ) {
			$end = (string) $diffs['fecha_fin']['after'];
		}
		return array( 'start' => $start, 'end' => $end );
	}

	private static function dates_announced_from_diffs( $diffs ) {
		if ( ! is_array( $diffs ) ) {
			return false;
		}
		$start_before = isset( $diffs['fecha_inicio']['before'] ) ? trim( (string) $diffs['fecha_inicio']['before'] ) : '';
		$end_before = isset( $diffs['fecha_fin']['before'] ) ? trim( (string) $diffs['fecha_fin']['before'] ) : '';
		$start_after = isset( $diffs['fecha_inicio']['after'] ) ? trim( (string) $diffs['fecha_inicio']['after'] ) : '';
		$end_after = isset( $diffs['fecha_fin']['after'] ) ? trim( (string) $diffs['fecha_fin']['after'] ) : '';
		$no_dates_after = isset( $diffs['sin_fechas_confirmadas']['after'] ) ? (string) $diffs['sin_fechas_confirmadas']['after'] : '';

		$previously_empty = $start_before === '' && $end_before === '';
		$now_set = ( $start_after !== '' || $end_after !== '' );
		if ( $previously_empty && $now_set ) {
			return true;
		}
		if ( $no_dates_after === '0' && $now_set ) {
			return true;
		}
		return false;
	}

	private static function classify_change_type( $diffs, $added_artists, $dates_announced ) {
		$added_artists = is_array( $added_artists ) ? $added_artists : array();
		if ( ! empty( $added_artists ) ) {
			return 'artists';
		}
		if ( $dates_announced ) {
			return 'dates';
		}
		return 'other';
	}

	private static function extract_other_changes( $diffs ) {
		if ( ! is_array( $diffs ) ) {
			return array();
		}
		$skip = array( 'fecha_inicio', 'fecha_fin', 'sin_fechas_confirmadas', 'mf_artistas', 'mf_cartel_completo', 'mf_web_oficial', 'mf_instagram', 'tickets_url', 'ticket_url', 'url_entradas' );
		$items = array();
		foreach ( $diffs as $key => $diff ) {
			if ( in_array( $key, $skip, true ) ) {
				continue;
			}
			if ( isset( $diff['after'] ) ) {
				$items[] = $key . ': ' . $diff['after'];
			}
		}
		return $items;
	}

	private static function sanitize_meta_text( $text ) {
		$text = trim( wp_strip_all_tags( (string) $text ) );
		$text = preg_replace( '/\\s{2,}/', ' ', $text );
		return $text;
	}
}
