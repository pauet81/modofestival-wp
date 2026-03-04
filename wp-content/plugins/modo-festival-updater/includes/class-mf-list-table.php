<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class MFU_List_Table extends WP_List_Table {
	private $items_data = array();

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'festival',
				'plural' => 'festivals',
				'ajax' => false,
			)
		);
	}

	public function get_columns() {
		return array(
			'cb' => '<input type="checkbox" />',
			'id' => 'ID',
			'title' => 'Festival',
			'edicion' => 'Edicion',
			'fecha' => 'Fecha',
			'estado' => 'Estado revision',
			'diagnostico' => 'Diagnostico',
			'cola' => 'Cola',
			'cambios' => 'Cambios detectados',
			'last_review' => 'Ultima revision',
			'actions' => 'Acciones',
		);
	}

	public function get_sortable_columns() {
		return array(
			'id' => array( 'ID', false ),
			'title' => array( 'title', false ),
			'fecha' => array( 'fecha_inicio', false ),
			'estado' => array( 'estado', false ),
			'cola' => array( 'cola', false ),
			'last_review' => array( 'last_review', false ),
			'cambios' => array( 'cambios', false ),
		);
	}

	protected function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="festival_ids[]" value="%d" />', $item['ID'] );
	}

	public function get_bulk_actions() {
		return array(
			'bulk_update' => 'Buscar actualizaciones',
			'bulk_clear_queue' => 'Eliminar estado de cola',
			'bulk_apply' => 'Aplicar cambios',
			'bulk_reject' => 'Rechazar cambios',
		);
	}

	public function extra_tablenav( $which ) {
		if ( $which !== 'top' ) {
			return;
		}

		$status = isset( $_REQUEST['mfu_status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['mfu_status'] ) ) : '';
		$sources = isset( $_REQUEST['mfu_sources'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['mfu_sources'] ) ) : '';

		echo '<div class="alignleft actions">';

		echo '<label class="screen-reader-text" for="mfu_status">Estado revision</label>';
		echo '<select name="mfu_status" id="mfu_status">';
		echo '<option value="">Estado revision</option>';
		foreach ( $this->get_update_status_options() as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '"' . selected( $status, $value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';

		echo '<label class="screen-reader-text" for="mfu_sources">Fuentes</label>';
		echo '<select name="mfu_sources" id="mfu_sources">';
		echo '<option value="">Fuentes</option>';
		foreach ( $this->get_sources_filter_options() as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '"' . selected( $sources, $value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';

		submit_button( 'Filtrar', 'secondary', 'filter_action', false );
		echo '</div>';
	}

	public function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->process_bulk_action();

		$per_page = 20;
		$current_page = $this->get_pagenum();
		$offset = ( $current_page - 1 ) * $per_page;

		$orderby = isset( $_REQUEST['orderby'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'title';
		$order = isset( $_REQUEST['order'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'asc';
		$order = strtolower( $order ) === 'desc' ? 'DESC' : 'ASC';
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';

		$filtered_ids = $this->get_filtered_ids();
		if ( is_array( $filtered_ids ) && empty( $filtered_ids ) ) {
			$this->items = array();
			$this->set_pagination_args(
				array(
					'total_items' => 0,
					'per_page' => $per_page,
					'total_pages' => 0,
				)
			);
			return;
		}

		$use_last_review_order = ( $orderby === 'last_review' );
		$ordered_ids = null;
		$total_items_override = null;
		if ( $use_last_review_order ) {
			$ordered_ids = $this->get_ids_ordered_by_last_review( $order );
			if ( is_array( $filtered_ids ) ) {
				$ordered_ids = array_values( array_intersect( $ordered_ids, $filtered_ids ) );
			}
			if ( $search !== '' ) {
				$search_ids = $this->get_ids_by_search( $search );
				$ordered_ids = array_values( array_intersect( $ordered_ids, $search_ids ) );
			}
			$total_items_override = count( $ordered_ids );
			$ordered_ids = array_slice( $ordered_ids, $offset, $per_page );
			if ( empty( $ordered_ids ) ) {
				$this->items = array();
				$this->set_pagination_args(
					array(
						'total_items' => $total_items_override,
						'per_page' => $per_page,
						'total_pages' => (int) ceil( $total_items_override / $per_page ),
					)
				);
				return;
			}
		}

		$args = array(
			'post_type' => 'festi',
			'post_status' => array( 'publish', 'draft' ),
			'posts_per_page' => $per_page,
			'offset' => $offset,
			'orderby' => 'title',
			'order' => $order,
		);

		if ( $use_last_review_order ) {
			$args['post__in'] = $ordered_ids;
			$args['orderby'] = 'post__in';
			$args['offset'] = 0;
		} elseif ( $orderby === 'fecha_inicio' ) {
			$args['meta_key'] = 'fecha_inicio';
			$args['orderby'] = 'meta_value';
		} elseif ( $orderby === 'ID' ) {
			$args['orderby'] = 'ID';
		} elseif ( $orderby === 'title' ) {
			$args['orderby'] = 'title';
		}
		if ( $search !== '' && ! $use_last_review_order ) {
			$args['s'] = $search;
		}
		if ( is_array( $filtered_ids ) && ! $use_last_review_order ) {
			$args['post__in'] = $filtered_ids;
		}

		$query = new WP_Query( $args );

		$this->items_data = array();
			$job_statuses = $this->get_latest_job_statuses( wp_list_pluck( $query->posts, 'ID' ) );
			foreach ( $query->posts as $post ) {
				$latest = MFU_Processor::get_latest_update( $post->ID );
				$diffs = $latest && $latest->diffs_json ? json_decode( $latest->diffs_json, true ) : array();
				$change_labels = $this->get_change_labels( $diffs );
				$verification = '';
				$diagnostico = '';
				$update_origin = '';
				if ( $latest && ! empty( $latest->evidence_json ) ) {
					$evidence = json_decode( $latest->evidence_json, true );
					if ( is_array( $evidence ) ) {
						if ( ! empty( $evidence['update_origin'] ) ) {
							$update_origin = (string) $evidence['update_origin'];
						}
						$pplx_verdict = ! empty( $evidence['content_verification_pplx']['verdict'] )
							? (string) $evidence['content_verification_pplx']['verdict']
							: '';
						$internal_verdicts = array();
						foreach ( array( 'verification', 'content_verification' ) as $key ) {
							if ( ! empty( $evidence[ $key ]['verdict'] ) ) {
								$internal_verdicts[] = (string) $evidence[ $key ]['verdict'];
							}
						}
						$internal_has = ! empty( $internal_verdicts );
						$internal_ok = $internal_has && ! in_array( 'needs_review', $internal_verdicts, true ) && ! in_array( 'error', $internal_verdicts, true );
						if ( $pplx_verdict !== '' ) {
							if ( $pplx_verdict === 'ok' && $internal_has && ! $internal_ok ) {
								$verification = 'needs_review';
							} else {
								$verification = $pplx_verdict;
							}
						} elseif ( $internal_has ) {
							$verification = $internal_ok ? 'ok' : 'needs_review';
						}
					}
					if ( is_array( $evidence ) && ! empty( $evidence['errors'] ) && is_array( $evidence['errors'] ) ) {
						$first = (string) $evidence['errors'][0];
						$count = count( $evidence['errors'] );
						$diagnostico = $count > 1 ? $first . ' (+' . ( $count - 1 ) . ')' : $first;
					} elseif ( is_array( $evidence ) && ! empty( $evidence['content_verification_pplx']['verdict'] ) ) {
						$verdict = (string) $evidence['content_verification_pplx']['verdict'];
						$message = (string) ( $evidence['content_verification_pplx']['message'] ?? '' );
						$diagnostico = trim( 'content_verify: ' . $verdict . ' ' . $message );
					} elseif ( is_array( $evidence ) && ! empty( $evidence['content_verification']['verdict'] ) ) {
						$verdict = (string) $evidence['content_verification']['verdict'];
						$message = (string) ( $evidence['content_verification']['message'] ?? '' );
						$diagnostico = trim( 'content_verify: ' . $verdict . ' ' . $message );
					}
				}
				$this->items_data[] = array(
					'ID' => $post->ID,
					'id' => $post->ID,
					'title' => $post->post_title,
					'edicion' => get_post_meta( $post->ID, 'edicion', true ),
					'fecha' => $this->format_date_range( $post->ID ),
					'estado' => $latest ? $latest->status : 'sin revisar',
					'diagnostico' => $diagnostico,
					'cola' => isset( $job_statuses[ $post->ID ] ) ? $job_statuses[ $post->ID ] : '-',
					'cambios' => $change_labels,
					'cambios_count' => count( $change_labels ),
					'last_review' => $latest ? $latest->detected_at : '-',
					'update_id' => $latest ? $latest->id : 0,
					'verification' => $verification,
					'origin' => $update_origin,
				);
			}

		if ( in_array( $orderby, array( 'estado', 'cambios', 'cola' ), true ) ) {
			$direction = $order === 'DESC' ? -1 : 1;
			usort(
				$this->items_data,
				function ( $a, $b ) use ( $orderby, $direction ) {
					$va = $a[ $orderby ];
					$vb = $b[ $orderby ];
					if ( $orderby === 'cambios' ) {
						$va = isset( $a['cambios_count'] ) ? (int) $a['cambios_count'] : ( is_array( $va ) ? count( $va ) : (int) $va );
						$vb = isset( $b['cambios_count'] ) ? (int) $b['cambios_count'] : ( is_array( $vb ) ? count( $vb ) : (int) $vb );
					} elseif ( $orderby === 'cola' ) {
						$va = $this->get_job_status_rank( $va );
						$vb = $this->get_job_status_rank( $vb );
					}
					if ( $va === $vb ) {
						return 0;
					}
					return ( $va < $vb ? -1 : 1 ) * $direction;
				}
			);
		}

		$this->items = $this->items_data;

		$this->set_pagination_args(
			array(
				'total_items' => $total_items_override !== null ? $total_items_override : $query->found_posts,
				'per_page' => $per_page,
				'total_pages' => $total_items_override !== null ? (int) ceil( $total_items_override / $per_page ) : $query->max_num_pages,
			)
		);
	}

	private function get_ids_ordered_by_last_review( $order = 'DESC' ) {
		global $wpdb;
		$order = strtoupper( $order ) === 'ASC' ? 'ASC' : 'DESC';
		$posts = $wpdb->posts;
		$updates = MFU_DB::table( 'updates' );
		$rows = $wpdb->get_results(
			"SELECT p.ID, u.last_review
			FROM {$posts} p
			LEFT JOIN (
				SELECT festival_id, MAX(detected_at) AS last_review
				FROM {$updates}
				GROUP BY festival_id
			) u ON u.festival_id = p.ID
			WHERE p.post_type='festi' AND p.post_status IN ('publish','draft')
			ORDER BY (u.last_review IS NULL) ASC, u.last_review {$order}",
			ARRAY_A
		);
		$ids = array();
		foreach ( $rows as $row ) {
			$ids[] = (int) $row['ID'];
		}
		return $ids;
	}

	private function get_ids_by_search( $search ) {
		$query = new WP_Query(
			array(
				'post_type' => 'festi',
				'post_status' => array( 'publish', 'draft' ),
				'fields' => 'ids',
				'posts_per_page' => -1,
				's' => $search,
			)
		);
		return is_array( $query->posts ) ? $query->posts : array();
	}

	public function process_bulk_action() {
		$action = $this->current_action();
		if ( ! in_array( $action, array( 'bulk_update', 'bulk_apply', 'bulk_enqueue', 'bulk_dequeue', 'bulk_clear_queue', 'bulk_reject' ), true ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$ids = isset( $_REQUEST['festival_ids'] ) ? array_map( 'intval', (array) $_REQUEST['festival_ids'] ) : array();

		if ( $action === 'bulk_update' || $action === 'bulk_enqueue' ) {
			$nonce = isset( $_REQUEST['_mfu_nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_mfu_nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'mfu_bulk_enqueue' ) ) {
				return;
			}
			foreach ( $ids as $festival_id ) {
				MFU_Cron::enqueue_job( (int) $festival_id, 10, 'manual' );
			}
			$this->safe_redirect( admin_url( 'admin.php?page=mfu-updates' ) );
		}

		if ( $action === 'bulk_dequeue' ) {
			$nonce = isset( $_REQUEST['_mfu_nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_mfu_nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'mfu_bulk_enqueue' ) ) {
				return;
			}
			global $wpdb;
			$table = MFU_DB::table( 'jobs' );
			foreach ( $ids as $festival_id ) {
				$wpdb->query( $wpdb->prepare(
					"DELETE FROM {$table} WHERE festival_id=%d AND status IN ('queued','running')",
					(int) $festival_id
				) );
			}
			$this->safe_redirect( admin_url( 'admin.php?page=mfu-updates' ) );
		}

		if ( $action === 'bulk_clear_queue' ) {
			$nonce = isset( $_REQUEST['_mfu_nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_mfu_nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'mfu_bulk_enqueue' ) ) {
				return;
			}
			global $wpdb;
			$table = MFU_DB::table( 'jobs' );
			foreach ( $ids as $festival_id ) {
				$wpdb->query( $wpdb->prepare(
					"DELETE FROM {$table} WHERE festival_id=%d",
					(int) $festival_id
				) );
			}
			$this->safe_redirect( admin_url( 'admin.php?page=mfu-updates' ) );
		}

		if ( $action === 'bulk_apply' ) {
			$nonce = isset( $_REQUEST['_mfu_nonce_apply'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_mfu_nonce_apply'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'mfu_bulk_apply' ) ) {
				return;
			}
			$form = array(
				'action' => 'mfu_bulk_apply',
				'_mfu_nonce' => wp_create_nonce( 'mfu_bulk_apply' ),
				'festival_ids' => $ids,
			);
			foreach ( $form as $key => $value ) {
				$_POST[ $key ] = $value;
			}
			do_action( 'admin_post_mfu_bulk_apply' );
			exit;
		}

		if ( $action === 'bulk_reject' ) {
			$nonce = isset( $_REQUEST['_mfu_nonce_apply'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_mfu_nonce_apply'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'mfu_bulk_apply' ) ) {
				return;
			}
			$form = array(
				'action' => 'mfu_bulk_reject',
				'_mfu_nonce' => wp_create_nonce( 'mfu_bulk_reject' ),
				'festival_ids' => $ids,
			);
			foreach ( $form as $key => $value ) {
				$_POST[ $key ] = $value;
			}
			do_action( 'admin_post_mfu_bulk_reject' );
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

	private function get_change_labels( $diffs ) {
		if ( ! is_array( $diffs ) || empty( $diffs ) ) {
			return array();
		}

		$has_dates = false;
		$has_location = false;
		$has_artists = false;
		$has_cancelled = false;
		$has_content = false;
		$has_other = false;

		foreach ( array_keys( $diffs ) as $key ) {
			switch ( $key ) {
				case 'fecha_inicio':
				case 'fecha_fin':
				case 'sin_fechas_confirmadas':
					$has_dates = true;
					break;
				case 'localidad':
					$has_location = true;
					break;
				case 'mf_artistas':
				case 'mf_cartel_completo':
					$has_artists = true;
					break;
				case 'cancelado':
					$has_cancelled = true;
					break;
				case 'content':
					$has_content = true;
					break;
				default:
					$has_other = true;
					break;
			}
		}

		$labels = array();
		if ( $has_dates ) {
			$labels[] = 'Fechas';
		}
		if ( $has_location ) {
			$labels[] = 'Localidad';
		}
		if ( $has_artists ) {
			$labels[] = 'Artistas';
		}
		if ( $has_cancelled ) {
			$labels[] = 'Cancelado';
		}
		if ( $has_content ) {
			$labels[] = 'Contenido';
		}
		if ( $has_other ) {
			$labels[] = 'Otros';
		}

		return $labels;
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
				return (int) $item['id'];
			case 'title':
				$permalink = get_permalink( $item['ID'] );
				$icon = '';
				if ( $permalink ) {
					$icon = ' <a href="' . esc_url( $permalink ) . '" target="_blank" rel="noopener noreferrer" title="Ver ficha"><span class="dashicons dashicons-external"></span></a>';
				}
				$title = esc_html( $item['title'] );
				if ( isset( $item['estado'] ) && $item['estado'] === 'pending_review' && ! empty( $item['update_id'] ) ) {
					$review_url = admin_url( 'admin.php?page=mfu-updates&update_id=' . (int) $item['update_id'] );
					$title = '<a href="' . esc_url( $review_url ) . '" style="display:inline-block; padding:2px 8px; border-radius:999px; background:#fff3cd; color:#664d03; font-weight:600; text-decoration:none;">' . $title . '</a>';
				} elseif ( isset( $item['estado'] ) && $item['estado'] === 'pending_review' ) {
					$title = '<span style="display:inline-block; padding:2px 8px; border-radius:999px; background:#fff3cd; color:#664d03; font-weight:600;">' . $title . '</span>';
				}
				return $title . $icon;
			case 'edicion':
				return $this->render_edicion_input( $item );
			case 'fecha':
				return esc_html( $item['fecha'] );
			case 'estado':
				$badge = $this->render_status_badge( $item['estado'], 'estado' );
				if ( ! empty( $item['verification'] ) ) {
					$badge .= ' ' . $this->render_verify_badge( $item['verification'] );
				}
				if ( ! empty( $item['origin'] ) && $item['origin'] === 'news' ) {
					$badge .= ' ' . $this->render_origin_badge( 'news' );
				}
				return $badge;
			case 'diagnostico':
				$value = isset( $item['diagnostico'] ) ? trim( (string) $item['diagnostico'] ) : '';
				if ( $value === '' ) {
					return '<span style="color:#6c757d;">-</span>';
				}
				if ( strlen( $value ) > 140 ) {
					$value = substr( $value, 0, 140 ) . '...';
				}
				return esc_html( $value );
			case 'cola':
				return $this->render_status_badge( $item['cola'], 'cola' );
			case 'cambios':
				$labels = isset( $item['cambios'] ) ? $item['cambios'] : array();
				if ( empty( $labels ) || ! is_array( $labels ) ) {
					return '<span style="color:#6c757d;">-</span>';
				}
				$lines = array();
				foreach ( $labels as $label ) {
					$lines[] = '- ' . $label;
				}
				return implode( '<br>', array_map( 'esc_html', $lines ) );
			case 'last_review':
				$value = isset( $item['last_review'] ) ? (string) $item['last_review'] : '';
				if ( $value === '' || $value === '-' ) {
					return esc_html( $value );
				}
				$ts = strtotime( $value );
				if ( ! $ts ) {
					return esc_html( $value );
				}
				return esc_html( wp_date( 'l j \\d\\e F \\d\\e Y H:i', $ts ) );
			case 'actions':
				return $this->render_actions( $item );
			default:
				return '';
		}
	}

	private function render_edicion_input( $item ) {
		$nonce = wp_create_nonce( 'mfu_update_edicion_' . $item['ID'] );
		$value = isset( $item['edicion'] ) ? $item['edicion'] : '';
		$id = (int) $item['ID'];
		$html  = '<input type="text" id="mfu-edicion-' . $id . '" value="' . esc_attr( $value ) . '" size="6" />';
		$html .= ' <button class="button button-small" type="button" onclick="mfuSubmitEdicion(' . $id . ', \'' . esc_attr( $nonce ) . '\')">Guardar</button>';

		static $script_rendered = false;
		if ( ! $script_rendered ) {
			$script_rendered = true;
			$action = admin_url( 'admin-post.php' );
			$html .= '<script>
				function mfuSubmitEdicion(id, nonce){
					var input = document.getElementById("mfu-edicion-" + id);
					if (!input) { return; }
					var form = document.createElement("form");
					form.method = "post";
					form.action = "' . esc_js( $action ) . '";
					var fields = {
						action: "mfu_update_edicion",
						festival_id: id,
						edicion: input.value,
						_wpnonce: nonce
					};
					for (var key in fields){
						var f = document.createElement("input");
						f.type = "hidden";
						f.name = key;
						f.value = fields[key];
						form.appendChild(f);
					}
					document.body.appendChild(form);
					form.submit();
				}
			</script>';
		}
		return $html;
	}

	private function render_actions( $item ) {
		$enqueue_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=mfu_enqueue_single&festival_id=' . $item['ID'] ),
			'mfu_enqueue_single_' . $item['ID']
		);
		$clear_queue_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=mfu_clear_queue_single&festival_id=' . $item['ID'] ),
			'mfu_clear_queue_single_' . $item['ID']
		);
		$clear_all_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=mfu_clear_all_single&festival_id=' . $item['ID'] ),
			'mfu_clear_all_single_' . $item['ID']
		);

		$view_url = '';
		if ( $item['update_id'] ) {
			$view_url = admin_url( 'admin.php?page=mfu-updates&update_id=' . $item['update_id'] );
		}

		$actions = '<a href="' . esc_url( $enqueue_url ) . '">Buscar actualizaciones</a>';
		$actions .= ' | <a href="' . esc_url( $clear_queue_url ) . '">Eliminar estado de cola</a>';
		$actions .= ' | <a href="' . esc_url( $clear_all_url ) . '">Limpiar todo</a>';
		if ( $view_url ) {
			$actions .= ' | <a href="' . esc_url( $view_url ) . '" target="_blank" rel="noopener">Ver cambios</a>';
		}
		if ( isset( $item['estado'] ) && (string) $item['estado'] === 'rejected' ) {
			$actions .= ' | <a href="' . esc_url( $enqueue_url ) . '">Volver a la cola</a>';
		}

		return $actions;
	}

	private function render_status_badge( $value, $type ) {
		$value = $value !== '' ? (string) $value : '-';
		$labels_estado = array(
			'pending_review' => 'pendiente de revision',
			'no_change' => 'sin cambios',
			'no_data' => 'sin datos',
			'applied' => 'aplicado',
			'auto_applied' => 'autoaplicado',
			'rejected' => 'rechazado',
			'sin revisar' => 'sin revisar',
		);
		$labels_cola = array(
			'queued' => 'en cola',
			'running' => 'procesando',
			'done' => 'completado',
			'error' => 'error',
			'-' => '-',
		);

		$styles = array(
			'default' => 'background:#f0f0f1; color:#2c3338;',
			'green' => 'background:#d6f5df; color:#0f5132;',
			'red' => 'background:#f8d7da; color:#842029;',
			'orange' => 'background:#fff3cd; color:#664d03;',
			'blue' => 'background:#cfe2ff; color:#084298;',
			'gray' => 'background:#e2e3e5; color:#41464b;',
		);

		$color = 'default';
		if ( $type === 'estado' ) {
			switch ( $value ) {
				case 'pending_review':
					$color = 'orange';
					break;
				case 'applied':
					$color = 'green';
					break;
				case 'auto_applied':
					$color = 'green';
					break;
				case 'rejected':
					$color = 'red';
					break;
				case 'no_change':
					$color = 'gray';
					break;
				case 'no_data':
					$color = 'orange';
					break;
				case 'sin revisar':
					$color = 'gray';
					break;
			}
		} else {
			switch ( $value ) {
				case 'queued':
					$color = 'blue';
					break;
				case 'running':
					$color = 'orange';
					break;
				case 'done':
					$color = 'green';
					break;
				case 'error':
					$color = 'red';
					break;
				case '-':
					$color = 'gray';
					break;
			}
		}

		$style = isset( $styles[ $color ] ) ? $styles[ $color ] : $styles['default'];
		$label = $value;
		if ( $type === 'estado' && isset( $labels_estado[ $value ] ) ) {
			$label = $labels_estado[ $value ];
		}
		if ( $type !== 'estado' && isset( $labels_cola[ $value ] ) ) {
			$label = $labels_cola[ $value ];
		}

		$spinner = '';
		if ( $type !== 'estado' && $value === 'running' ) {
			$spinner = '<span style="display:inline-block; width:10px; height:10px; margin-right:6px; border:2px solid rgba(0,0,0,0.2); border-top-color:rgba(0,0,0,0.6); border-radius:50%; animation:mfu-spin 0.8s linear infinite;"></span>';
			$spinner .= '<style>@keyframes mfu-spin{to{transform:rotate(360deg);}}</style>';
		}

		return '<span style="display:inline-flex; align-items:center; padding:2px 8px; border-radius:999px; font-size:12px; font-weight:600; ' . esc_attr( $style ) . '">' . $spinner . esc_html( $label ) . '</span>';
	}

	private function render_verify_badge( $verdict ) {
		$verdict = (string) $verdict;
		if ( $verdict === 'ok' ) {
			return $this->render_badge( 'verificado', 'background:#d6f5df; color:#0f5132;' );
		}
		if ( $verdict === 'needs_review' ) {
			return $this->render_badge( 'verificar', 'background:#fff3cd; color:#664d03;' );
		}
		return '';
	}

	private function render_badge( $label, $style ) {
		return '<span style="display:inline-flex; align-items:center; padding:2px 8px; border-radius:999px; font-size:12px; font-weight:600; ' . esc_attr( $style ) . '">' . esc_html( $label ) . '</span>';
	}

	private function render_origin_badge( $origin ) {
		$origin = (string) $origin;
		$label = $origin === 'news' ? 'actualizado por news' : $origin;
		$style = 'background:#e7f1ff; color:#0b408a;';
		return '<span style="display:inline-flex; align-items:center; padding:2px 8px; border-radius:999px; font-size:12px; font-weight:600; ' . esc_attr( $style ) . '">' . esc_html( $label ) . '</span>';
	}

	private function format_date_range( $festival_id ) {
		$start = $this->normalize_date( get_post_meta( $festival_id, 'fecha_inicio', true ) );
		$end = $this->normalize_date( get_post_meta( $festival_id, 'fecha_fin', true ) );
		if ( $start && $end ) {
			return $start . ' - ' . $end;
		}
		return $start ?: ( $end ?: '-' );
	}

	private function normalize_date( $value ) {
		$value = trim( (string) $value );
		if ( $value === '' ) {
			return '';
		}
		if ( preg_match( '/^\d{8}$/', $value ) ) {
			$dt = DateTime::createFromFormat( 'Ymd', $value );
			return $dt ? $dt->format( 'd/m/Y' ) : $value;
		}
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
			$dt = DateTime::createFromFormat( 'Y-m-d', $value );
			return $dt ? $dt->format( 'd/m/Y' ) : $value;
		}
		return $value;
	}

	private function get_latest_job_statuses( $festival_ids ) {
		if ( empty( $festival_ids ) ) {
			return array();
		}
		global $wpdb;
		$table = MFU_DB::table( 'jobs' );
		$ids_sql = implode( ',', array_map( 'intval', $festival_ids ) );
		$rows = $wpdb->get_results(
			"SELECT festival_id,
				SUM(status='queued') AS queued,
				SUM(status='running') AS running,
				SUM(status='done') AS done,
				SUM(status='error') AS error
			FROM {$table}
			WHERE festival_id IN ({$ids_sql})
			GROUP BY festival_id",
			ARRAY_A
		);
		$map = array();
		foreach ( $rows as $row ) {
			$map[ (int) $row['festival_id'] ] = MFU_Cron::resolve_job_status_from_counts( $row );
		}
		return $map;
	}

	private function get_job_status_rank( $status ) {
		switch ( (string) $status ) {
			case 'running':
				return 4;
			case 'queued':
				return 3;
			case 'error':
				return 2;
			case 'done':
				return 1;
			default:
				return 0;
		}
	}

	private function get_update_status_options() {
		return array(
			'pending_review' => 'pendiente de revision',
			'no_change' => 'sin cambios',
			'no_data' => 'sin datos',
			'applied' => 'aplicado',
			'auto_applied' => 'autoaplicado',
			'rejected' => 'rechazado',
		);
	}

	private function get_job_status_options() {
		return array(
			'queued' => 'en cola',
			'running' => 'procesando',
			'done' => 'completado',
			'error' => 'error',
		);
	}

	private function get_sources_filter_options() {
		return array(
			'' => 'Sin filtro',
			'issues' => 'con dudas',
			'web_dudosa' => 'web dudosa',
			'sin_web' => 'sin web',
			'sin_ig' => 'sin Instagram',
			'sin_fuentes' => 'sin fuentes (IA)',
		);
	}

	private function get_filtered_ids() {
		$status = isset( $_REQUEST['mfu_status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['mfu_status'] ) ) : '';
		$job = isset( $_REQUEST['mfu_job'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['mfu_job'] ) ) : '';
		$sources = isset( $_REQUEST['mfu_sources'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['mfu_sources'] ) ) : '';

		if ( $status === '' && $sources === '' && $job === '' ) {
			return null;
		}

		$ids = null;
		if ( $status !== '' ) {
			$ids = $this->get_latest_update_ids_by_status( $status );
		}
		if ( $job !== '' ) {
			$job_ids = $this->get_ids_by_job_status( $job );
			$ids = is_array( $ids ) ? array_values( array_intersect( $ids, $job_ids ) ) : $job_ids;
		}
		if ( $sources !== '' ) {
			$sources_ids = $this->get_ids_by_sources_filter( $sources );
			$ids = is_array( $ids ) ? array_values( array_intersect( $ids, $sources_ids ) ) : $sources_ids;
		}

		return is_array( $ids ) ? array_values( array_unique( $ids ) ) : null;
	}

	private function get_ids_by_job_status( $status ) {
		$status = sanitize_text_field( (string) $status );
		if ( ! in_array( $status, array( 'queued', 'running', 'done', 'error' ), true ) ) {
			return array();
		}
		global $wpdb;
		$table = MFU_DB::table( 'jobs' );
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
		$ids = array();
		foreach ( $rows as $row ) {
			$resolved = MFU_Cron::resolve_job_status_from_counts( $row );
			if ( $resolved === $status ) {
				$ids[] = (int) $row['festival_id'];
			}
		}
		return $ids;
	}

	private function get_ids_by_sources_filter( $filter ) {
		$filter = sanitize_text_field( (string) $filter );
		$args = array(
			'post_type' => 'festival',
			'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'fields' => 'ids',
			'posts_per_page' => -1,
		);

		if ( $filter === 'web_dudosa' ) {
			$args['meta_query'] = array(
				array(
					'key' => 'mfu_web_status',
					'value' => 'non_official',
				),
			);
		} elseif ( $filter === 'sin_web' ) {
			$args['meta_query'] = array(
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
			);
		} elseif ( $filter === 'sin_ig' ) {
			$args['meta_query'] = array(
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
			);
		} elseif ( $filter === 'sin_fuentes' ) {
			$args['meta_query'] = array(
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
			);
		} else {
			$args['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key' => 'mfu_web_status',
					'value' => 'non_official',
				),
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
			);
		}

		$query = new WP_Query( $args );
		return is_array( $query->posts ) ? $query->posts : array();
	}

	private function get_latest_update_ids_by_status( $status ) {
		global $wpdb;
		$table = MFU_DB::table( 'updates' );
		$status = sanitize_text_field( $status );
		return $wpdb->get_col( $wpdb->prepare(
			"SELECT u.festival_id FROM {$table} u INNER JOIN (SELECT festival_id, MAX(id) AS max_id FROM {$table} GROUP BY festival_id) t ON t.max_id = u.id WHERE u.status = %s",
			$status
		) );
	}

	private function get_latest_job_ids_by_status( $status ) {
		global $wpdb;
		$table = MFU_DB::table( 'jobs' );
		$status = sanitize_text_field( $status );
		return $wpdb->get_col( $wpdb->prepare(
			"SELECT j.festival_id FROM {$table} j INNER JOIN (SELECT festival_id, MAX(id) AS max_id FROM {$table} GROUP BY festival_id) t ON t.max_id = j.id WHERE j.status = %s",
			$status
		) );
	}

	private function get_ids_by_review_age( $days, $mode ) {
		global $wpdb;
		$updates = MFU_DB::table( 'updates' );
		$posts = $wpdb->posts;

		$days = max( 1, (int) $days );
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

		if ( $mode === 'recent' ) {
			return $wpdb->get_col( $wpdb->prepare(
				"SELECT p.ID FROM {$posts} p
				LEFT JOIN {$updates} u ON u.festival_id = p.ID AND u.status='applied'
				WHERE p.post_type='festi' AND p.post_status IN ('publish','draft')
				GROUP BY p.ID
				HAVING MAX(u.applied_at) IS NOT NULL AND MAX(u.applied_at) >= %s",
				$cutoff
			) );
		}

		return $wpdb->get_col( $wpdb->prepare(
			"SELECT p.ID FROM {$posts} p
			LEFT JOIN {$updates} u ON u.festival_id = p.ID AND u.status='applied'
			WHERE p.post_type='festi' AND p.post_status IN ('publish','draft')
			GROUP BY p.ID
			HAVING MAX(u.applied_at) IS NULL OR MAX(u.applied_at) < %s",
			$cutoff
		) );
	}
}

class MFU_Sources_Table extends WP_List_Table {
	private $items_data = array();

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'festival',
				'plural' => 'festivals',
				'ajax' => false,
			)
		);
	}

	public function get_columns() {
		return array(
			'id' => 'ID',
			'title' => 'Festival',
			'web' => 'Web oficial',
			'instagram' => 'Instagram',
			'actions' => 'Acciones',
		);
	}

	public function get_sortable_columns() {
		return array(
			'id' => array( 'ID', false ),
			'title' => array( 'title', false ),
		);
	}

	public function extra_tablenav( $which ) {
		if ( $which !== 'top' ) {
			return;
		}

		$sources = isset( $_REQUEST['mfu_sources'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['mfu_sources'] ) ) : '';
		echo '<div class="alignleft actions">';
		echo '<label class="screen-reader-text" for="mfu_sources">Fuentes</label>';
		echo '<select name="mfu_sources" id="mfu_sources">';
		echo '<option value="">Fuentes</option>';
		foreach ( $this->get_sources_filter_options() as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '"' . selected( $sources, $value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		submit_button( 'Filtrar', 'secondary', 'filter_action', false );
		echo '</div>';
	}

	public function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$per_page = 20;
		$current_page = $this->get_pagenum();
		$offset = ( $current_page - 1 ) * $per_page;

		$orderby = isset( $_REQUEST['orderby'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'title';
		$order = isset( $_REQUEST['order'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'asc';
		$order = strtolower( $order ) === 'desc' ? 'DESC' : 'ASC';
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		$sources = isset( $_REQUEST['mfu_sources'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['mfu_sources'] ) ) : '';

		$args = array(
			'post_type' => 'festi',
			'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'posts_per_page' => $per_page,
			'offset' => $offset,
			'orderby' => $orderby === 'ID' ? 'ID' : 'title',
			'order' => $order,
		);

		if ( $search !== '' ) {
			$args['s'] = $search;
		}
		if ( $sources !== '' ) {
			$args['post__in'] = $this->get_ids_by_sources_filter( $sources );
		}

		$query = new WP_Query( $args );

		$this->items_data = array();
		foreach ( $query->posts as $post ) {
			$this->items_data[] = array(
				'ID' => $post->ID,
				'id' => $post->ID,
				'title' => $post->post_title,
			);
		}

		$this->items = $this->items_data;

		$this->set_pagination_args(
			array(
				'total_items' => $query->found_posts,
				'per_page' => $per_page,
				'total_pages' => $query->max_num_pages,
			)
		);
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
				return (int) $item['id'];
			case 'title':
				return esc_html( $item['title'] );
			case 'web':
			case 'instagram':
				$web_value = trim( (string) get_post_meta( $item['ID'], 'mf_web_oficial', true ) );
				$ig_value = trim( (string) get_post_meta( $item['ID'], 'mf_instagram', true ) );
				$value = $column_name === 'web' ? $web_value : $ig_value;
				$field = $column_name === 'web' ? 'mf_web_oficial' : 'mf_instagram';
				$nonce = wp_create_nonce( 'mfu_save_sources_' . $item['ID'] );
				$html  = '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:flex; gap:6px; align-items:center;">';
				$html .= '<input type="hidden" name="action" value="mfu_save_sources_fields" />';
				$html .= '<input type="hidden" name="festival_id" value="' . (int) $item['ID'] . '" />';
				$html .= '<input type="hidden" name="field" value="' . esc_attr( $field ) . '" />';
				$html .= '<input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '" />';
				$html .= '<input type="text" name="value" value="' . esc_attr( $value ) . '" class="regular-text" style="max-width:280px;" />';
				$html .= '<button class="button button-small">Guardar</button>';
				$html .= '</form>';
				return $html;
			case 'actions':
				return '';
			default:
				return '';
		}
	}

	private function get_sources_filter_options() {
		return array(
			'' => 'Sin filtro',
			'web_dudosa' => 'Web dudosa',
			'sin_web' => 'Sin web',
			'sin_ig' => 'Sin Instagram',
			'sin_fuentes' => 'Sin fuentes (IA)',
			'con_dudas' => 'Con dudas',
		);
	}

	private function get_ids_by_sources_filter( $filter ) {
		$filter = sanitize_text_field( (string) $filter );
		$args = array(
			'post_type' => 'festi',
			'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'fields' => 'ids',
			'posts_per_page' => -1,
		);

		if ( $filter === 'web_dudosa' ) {
			$args['meta_query'] = array(
				array(
					'key' => 'mfu_web_status',
					'value' => 'non_official',
				),
			);
		} elseif ( $filter === 'sin_web' ) {
			$args['meta_query'] = array(
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
			);
		} elseif ( $filter === 'sin_ig' ) {
			$args['meta_query'] = array(
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
			);
		} elseif ( $filter === 'sin_fuentes' ) {
			$args['meta_query'] = array(
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
			);
		} else {
			$args['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key' => 'mfu_web_status',
					'value' => 'non_official',
				),
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
			);
		}

		$query = new WP_Query( $args );
		return is_array( $query->posts ) ? $query->posts : array();
	}
}
