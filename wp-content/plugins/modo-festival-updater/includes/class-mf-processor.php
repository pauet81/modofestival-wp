<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MFU_Processor {
	public static function log_action( $action, $data = array() ) {
		$log = get_option( 'mfu_action_log', array() );
		if ( ! is_array( $log ) ) {
			$log = array();
		}
		$log[] = array(
			'time' => current_time( 'mysql' ),
			'action' => (string) $action,
			'data' => is_array( $data ) ? $data : array( 'value' => (string) $data ),
		);
		if ( count( $log ) > 200 ) {
			$log = array_slice( $log, -200 );
		}
		update_option( 'mfu_action_log', $log, false );
	}

	public function process_festival( $festival_id, $progress_cb = null ) {
		$festival = get_post( $festival_id );
		if ( ! $festival || $festival->post_type !== 'festi' ) {
			return new WP_Error( 'mfu_invalid_festival', 'Invalid festival' );
		}

		$this->progress( $progress_cb, 'Revisando festival: ' . $festival->post_title );

		$sources = array();
		$pipeline = array();
		$this->add_pipeline_event( $pipeline, 'start', 'ok', array( 'festival_id' => (int) $festival_id ) );

		$ai = new MFU_AI();

		// Nuevo flujo: respuesta base + Apify Instagram (2 ultimos posts)
		$all_facts = array();
		$evidence = array();
		$errors = array();
		$debug = array();
		$timeline = array();
		$phase_usage = array();
		$edition = get_post_meta( $festival_id, 'edicion', true );
		$updated_content = '';
		$content_verification = null;
		$options = get_option( MFU_OPTION_KEY, array() );
		$ai_content_enabled = ! empty( $options['ai_content_update'] );
		$auto_apply_verified = ! empty( $options['auto_apply_when_verified'] );
		$debug_enabled = ! empty( $options['debug_enabled'] );
		$base_provider = isset( $options['base_query_provider'] ) ? (string) $options['base_query_provider'] : 'perplexity';
		$verification_provider = isset( $options['verification_provider'] ) ? (string) $options['verification_provider'] : 'perplexity';
		if ( ! in_array( $base_provider, array( 'openai', 'perplexity' ), true ) ) {
			$base_provider = 'perplexity';
		}
		if ( ! in_array( $verification_provider, array( 'openai', 'perplexity' ), true ) ) {
			$verification_provider = 'perplexity';
		}
		$needs_pplx = in_array( 'perplexity', array( $base_provider, $verification_provider ), true );
		if ( $needs_pplx && ! $ai->has_perplexity_key() ) {
			$this->add_pipeline_event( $pipeline, 'validate_keys', 'error', array( 'provider' => 'perplexity', 'error' => 'missing_key' ) );
			return new WP_Error( 'mfu_no_pplx_key', 'Perplexity API key missing' );
		}
		if ( ! $ai->has_openai_key() ) {
			$this->add_pipeline_event( $pipeline, 'validate_keys', 'error', array( 'provider' => 'openai', 'error' => 'missing_key' ) );
			return new WP_Error( 'mfu_no_openai_key', 'OpenAI API key missing' );
		}
		$this->add_pipeline_event(
			$pipeline,
			'validate_keys',
			'ok',
			array(
				'base_provider' => $base_provider,
				'verification_provider' => $verification_provider,
			)
		);
		$base_answer_source = $base_provider === 'openai' ? 'openai_web_search_answer' : 'perplexity_answer';
		$base_answer_label = $base_provider === 'openai' ? 'OpenAI (web search)' : 'Perplexity';
		$answer_sources = array();

		if ( $debug_enabled ) {
			$this->add_debug_event( $debug, 'festival_snapshot', $this->build_festival_snapshot( $festival_id ) );
		}

		$this->progress( $progress_cb, 'Consultando ' . $base_answer_label . ' (respuesta directa)' );
		$answer_queries = array(
			trim( $festival->post_title . ' ' . $edition . ' fechas cartel informacion actualizada -site:modofestival.es' ),
		);
		$answers = array();
		if ( $debug_enabled ) {
			$this->add_debug_event( $debug, 'api_use', array( 'api' => $base_provider, 'action' => $base_answer_source ) );
			$this->add_debug_event( $debug, 'base_answer_queries', array( 'queries' => $answer_queries ) );
			$this->add_timeline_event( $timeline, '1.base_answer.start', array( 'queries' => $answer_queries, 'provider' => $base_provider ) );
		}
		foreach ( $answer_queries as $idx => $answer_query ) {
			$answer_query = trim( (string) $answer_query );
			if ( $answer_query === '' ) {
				continue;
			}
			$this->progress( $progress_cb, $base_answer_label . ' query #' . ( $idx + 1 ) );
			if ( $base_provider === 'openai' ) {
				$openai_model = isset( $options['model_write'] ) ? (string) $options['model_write'] : 'gpt-5';
				$result = $ai->openai_web_search_answer( $answer_query, 'es', 'ES', $openai_model );
				if ( is_wp_error( $result ) ) {
					$errors[] = 'base_answer[' . ( $idx + 1 ) . ']: ' . $result->get_error_message();
					$this->add_pipeline_event( $pipeline, 'base_answer', 'error', array( 'query' => $answer_query, 'error' => $result->get_error_message() ) );
					if ( $debug_enabled ) {
						$this->add_debug_event( $debug, 'base_answer_error', array( 'query' => $answer_query, 'error' => $result->get_error_message() ) );
					}
					continue;
				}
				$answer_text = is_array( $result ) ? (string) ( $result['text'] ?? '' ) : '';
				$answers[] = $answer_text;
				$this->add_phase_usage( $phase_usage, 'base_answer' );
				if ( is_array( $result ) && ! empty( $result['sources'] ) && is_array( $result['sources'] ) ) {
					$answer_sources = array_merge( $answer_sources, $result['sources'] );
				}
				if ( $debug_enabled ) {
					$this->add_debug_event( $debug, 'base_answer_response', array( 'query' => $answer_query, 'text' => $this->truncate_debug_text( $answer_text ) ) );
				}
			} else {
				$answer = $ai->perplexity_answer( $answer_query, 'es' );
				if ( is_wp_error( $answer ) ) {
					$errors[] = 'base_answer[' . ( $idx + 1 ) . ']: ' . $answer->get_error_message();
					$this->add_pipeline_event( $pipeline, 'base_answer', 'error', array( 'query' => $answer_query, 'error' => $answer->get_error_message() ) );
					if ( $debug_enabled ) {
						$this->add_debug_event( $debug, 'base_answer_error', array( 'query' => $answer_query, 'error' => $answer->get_error_message() ) );
					}
					continue;
				}
				$answers[] = $answer;
				$this->add_phase_usage( $phase_usage, 'base_answer' );
				if ( $debug_enabled ) {
					$this->add_debug_event( $debug, 'base_answer_response', array( 'query' => $answer_query, 'text' => $this->truncate_debug_text( $answer ) ) );
				}
			}
		}

		$answer = implode( "\n\n", $answers );
		if ( $answer === '' ) {
			$errors[] = 'base_answer: sin respuestas';
			$this->add_pipeline_event( $pipeline, 'base_answer', 'empty' );
			if ( $debug_enabled ) {
				$this->add_debug_event( $debug, 'base_answer_empty', array( 'queries' => $answer_queries ) );
				$this->add_timeline_event( $timeline, '1.base_answer.done', array( 'ok' => false, 'chars' => 0 ) );
			}
		} else {
			$this->add_pipeline_event( $pipeline, 'base_answer', 'ok', array( 'chars' => strlen( $answer ) ) );
			if ( $debug_enabled ) {
				$this->add_debug_event( $debug, 'base_answer_combined', array( 'text' => $this->truncate_debug_text( $answer ) ) );
				$this->add_timeline_event( $timeline, '1.base_answer.done', array( 'ok' => true, 'chars' => strlen( $answer ) ) );
			}
			if ( $ai_content_enabled ) {
				if ( $debug_enabled ) {
					$this->add_timeline_event( $timeline, '2.rewrite_content.start' );
				}
				$current_content = (string) $festival->post_content;
				$answer_for_rewrite = $this->sanitize_answer_for_rewrite( $answer );
				if ( $debug_enabled ) {
					$this->add_debug_event( $debug, 'api_use', array( 'api' => 'openai', 'action' => 'rewrite_content_answer' ) );
					$this->add_debug_event(
						$debug,
						'openai_rewrite_input',
						$ai->build_rewrite_prompt_from_answer(
							$festival->post_title,
							$edition,
							$current_content,
							$answer_for_rewrite
						)
					);
				}
				$updated_content = $ai->rewrite_festival_content_from_answer(
					$festival->post_title,
					$edition,
					$current_content,
					$answer_for_rewrite
				);
				if ( is_wp_error( $updated_content ) ) {
					$errors[] = 'content_update: ' . $updated_content->get_error_message();
					$this->add_pipeline_event( $pipeline, 'content_update', 'error', array( 'error' => $updated_content->get_error_message() ) );
					if ( $debug_enabled ) {
						$this->add_debug_event( $debug, 'content_update_error', array( 'error' => $updated_content->get_error_message() ) );
					}
					$updated_content = '';
					if ( $debug_enabled ) {
						$this->add_timeline_event( $timeline, '2.rewrite_content.done', array( 'ok' => false, 'error' => $errors[ count( $errors ) - 1 ] ?? '' ) );
					}
				} else {
					$this->add_phase_usage( $phase_usage, 'rewrite_content' );
					$this->add_pipeline_event( $pipeline, 'content_update', 'ok', array( 'chars' => strlen( (string) $updated_content ) ) );
					if ( $debug_enabled ) {
						$this->add_debug_event( $debug, 'content_update_output', array( 'content' => (string) $updated_content ) );
						$this->add_timeline_event( $timeline, '2.rewrite_content.done', array( 'ok' => true, 'chars' => strlen( (string) $updated_content ) ) );
						$this->add_timeline_event( $timeline, '3.verify_content.start' );
					}
					if ( $debug_enabled ) {
						$this->add_debug_event( $debug, 'api_use', array( 'api' => 'openai', 'action' => 'verify_content_strict' ) );
					}
					$content_verification = $ai->verify_content_strict(
						$festival->post_title,
						$edition,
						$current_content,
						$updated_content,
						$answer_for_rewrite
					);
					if ( is_wp_error( $content_verification ) ) {
						$errors[] = 'content_verify: ' . $content_verification->get_error_message();
						$this->add_pipeline_event( $pipeline, 'content_verify', 'error', array( 'error' => $content_verification->get_error_message() ) );
						if ( $debug_enabled ) {
							$this->add_debug_event( $debug, 'content_verify_error', array( 'error' => $content_verification->get_error_message() ) );
						}
						$content_verification = null;
					} else {
						$this->add_phase_usage( $phase_usage, 'verify_content_strict' );
						$this->add_pipeline_event( $pipeline, 'content_verify', 'ok', array( 'verdict' => $content_verification['verdict'] ?? '' ) );
						if ( $debug_enabled ) {
							$this->add_debug_event( $debug, 'content_verify_result', $content_verification );
							$this->add_timeline_event( $timeline, '3.verify_content.done', array( 'verdict' => $content_verification['verdict'] ?? '' ) );
						}
						$verdict = is_array( $content_verification ) ? (string) ( $content_verification['verdict'] ?? '' ) : '';
						$issues = is_array( $content_verification ) ? ( $content_verification['issues'] ?? array() ) : array();
						if ( $verdict !== 'ok' && ! empty( $issues ) ) {
							if ( $debug_enabled ) {
								$this->add_debug_event( $debug, 'api_use', array( 'api' => 'openai', 'action' => 'rewrite_content_fix_issues' ) );
							}
							$fixed_content = $ai->rewrite_content_fix_issues(
								$festival->post_title,
								$edition,
								$current_content,
								$updated_content,
								$answer_for_rewrite,
								$issues
							);
							if ( is_wp_error( $fixed_content ) ) {
								$errors[] = 'content_fix: ' . $fixed_content->get_error_message();
								$this->add_pipeline_event( $pipeline, 'content_fix', 'error', array( 'error' => $fixed_content->get_error_message() ) );
								if ( $debug_enabled ) {
									$this->add_debug_event( $debug, 'content_fix_error', array( 'error' => $fixed_content->get_error_message() ) );
								}
							} else {
								$this->add_phase_usage( $phase_usage, 'content_fix' );
								$updated_content = $fixed_content;
								$this->add_pipeline_event( $pipeline, 'content_fix', 'ok', array( 'chars' => strlen( (string) $updated_content ) ) );
								if ( $debug_enabled ) {
									$this->add_debug_event( $debug, 'content_fix_output', array( 'content' => $this->truncate_debug_text( $updated_content ) ) );
								}
							}
						}
					}
				}
			} else {
				$this->add_pipeline_event( $pipeline, 'content_update', 'skipped' );
				if ( $debug_enabled ) {
					$this->add_timeline_event( $timeline, '2.rewrite_content.skipped' );
					$this->add_timeline_event( $timeline, '3.verify_content.skipped' );
				}
			}

			if ( $debug_enabled ) {
				$this->add_debug_event( $debug, 'api_use', array( 'api' => 'openai', 'action' => 'extract_facts' ) );
				$this->add_debug_event( $debug, 'openai_extract_input', array( 'source' => 'base_answer_combined' ) );
				$this->add_timeline_event( $timeline, '4.extract_facts.start' );
			}
			$facts = $ai->extract_facts( $festival->post_title, $answer, $base_answer_source, $edition );
			if ( ! is_wp_error( $facts ) && $this->facts_have_value( $facts ) ) {
				$this->add_phase_usage( $phase_usage, 'extract_facts' );
				$this->add_pipeline_event( $pipeline, 'extract_facts', 'ok' );
				$all_facts[] = $facts;
				$evidence_row = array(
					'url' => $base_answer_source,
					'summary' => isset( $facts['summary'] ) ? $facts['summary'] : '',
				);
				if ( $base_provider === 'openai' && ! empty( $answer_sources ) ) {
					$evidence_row['sources'] = $answer_sources;
				}
				$evidence[] = $evidence_row;
				if ( $debug_enabled ) {
					$this->add_debug_event( $debug, 'extract_facts_result', $facts );
					$this->add_timeline_event( $timeline, '4.extract_facts.done', array( 'ok' => true ) );
				}
			} else {
				$this->add_pipeline_event( $pipeline, 'extract_facts', 'empty', array( 'error' => is_wp_error( $facts ) ? $facts->get_error_message() : 'no facts' ) );
				$errors[] = 'base_answer: no facts';
				if ( $debug_enabled ) {
					$this->add_debug_event( $debug, 'extract_facts_empty', array( 'error' => is_wp_error( $facts ) ? $facts->get_error_message() : 'no facts' ) );
					$this->add_timeline_event( $timeline, '4.extract_facts.done', array( 'ok' => false ) );
				}
			}
		}

		// Instagram via Apify disabled by request.

		if ( empty( $all_facts ) && $updated_content === '' ) {
			$pipeline_extra = array( 'pipeline_log' => $pipeline );
			if ( $debug_enabled ) {
				$this->append_usage_debug( $debug, $phase_usage );
				$pipeline_extra['debug_log'] = $debug;
			}
			$this->store_update(
				$festival_id,
				'no_data',
				array(),
				array( 'summary' => 'Sin datos verificables', 'facts' => array() ),
				$evidence,
				$errors,
				null,
				$pipeline_extra
			);
			return true;
		}

		if ( empty( $all_facts ) && $updated_content !== '' ) {
			$extra = array(
				'updated_content' => $updated_content,
				'pipeline_log' => $pipeline,
			);
			if ( $content_verification ) {
				$extra['content_verification'] = $content_verification;
			}
			if ( $debug_enabled ) {
				$this->append_usage_debug( $debug, $phase_usage );
				$extra['debug_log'] = $debug;
			}
			$this->store_update(
				$festival_id,
				'pending_review',
				array( 'content' => array( 'before' => '', 'after' => 'Contenido actualizado con IA' ) ),
				array( 'summary' => 'Contenido actualizado con IA', 'facts' => array() ),
				$evidence,
				$errors,
				null,
				$extra
			);
			return true;
		}

		$this->progress( $progress_cb, 'Comparando con la ficha' );
		if ( $debug_enabled ) {
			$this->add_timeline_event( $timeline, '5.merge_and_diffs.start' );
		}
		$merged = $this->merge_facts( $all_facts );
		$this->add_pipeline_event( $pipeline, 'merge_facts', 'ok' );
		if ( $debug_enabled ) {
			$this->add_debug_event( $debug, 'facts_merged', $merged );
		}
		$date_conflicts = $this->detect_date_conflicts( $all_facts, $edition );
		if ( $date_conflicts ) {
			$this->add_pipeline_event( $pipeline, 'date_conflicts', 'detected', $date_conflicts );
		} else {
			$this->add_pipeline_event( $pipeline, 'date_conflicts', 'none' );
		}
		if ( $debug_enabled && $date_conflicts ) {
			$this->add_debug_event( $debug, 'date_conflict', $date_conflicts );
		}
		$diffs = $this->build_diffs( $festival_id, $merged );
		$this->add_pipeline_event( $pipeline, 'build_diffs', empty( $diffs ) ? 'empty' : 'ok', array( 'count' => is_array( $diffs ) ? count( $diffs ) : 0 ) );
		if ( $date_conflicts ) {
			unset( $diffs['fecha_inicio'], $diffs['fecha_fin'] );
		}
		if ( $debug_enabled ) {
			$this->add_debug_event( $debug, 'diffs_detected', $diffs );
			$this->add_timeline_event( $timeline, '5.merge_and_diffs.done', array( 'diffs' => is_array( $diffs ) ? count( $diffs ) : 0 ) );
		}

		if ( $updated_content !== '' ) {
			$updated_content = $this->ensure_artists_in_updated_content( $updated_content, $diffs );
			$updated_content = $this->ensure_content_includes_facts( $updated_content, $merged );
			$updated_content = $this->sanitize_ai_html_wrapper( $updated_content );
			$updated_content = $this->remove_mfu_update_block( $updated_content );
		}

		if ( $updated_content !== '' && empty( $diffs['content'] ) ) {
			$diffs['content'] = array( 'before' => '', 'after' => 'Contenido actualizado con IA' );
		}

		$status = empty( $diffs ) ? 'no_change' : 'pending_review';
		if ( $updated_content !== '' ) {
			$status = 'pending_review';
		}

		$verification = null;
		if ( ! empty( $diffs ) ) {
			$verify_label = $verification_provider === 'openai' ? 'OpenAI (web search)' : 'Perplexity';
			$verify_event = $verification_provider === 'openai' ? 'verify_openai_web_search' : 'verify_perplexity';
			$this->progress( $progress_cb, 'Veredicto final (' . $verify_label . ')' );
			if ( $debug_enabled ) {
				$this->add_debug_event( $debug, 'api_use', array( 'api' => $verification_provider, 'action' => $verify_event ) );
				$this->add_timeline_event( $timeline, '6.verify_final.start', array( 'provider' => $verification_provider ) );
			}
			if ( $verification_provider === 'openai' ) {
				$verification = $ai->verify_update_with_openai_web_search( $festival->post_title, $edition, $diffs, array( 'facts' => $merged, 'sources' => $evidence ) );
			} else {
				$verification = $ai->verify_update_with_perplexity( $festival->post_title, $edition, $diffs, array( 'facts' => $merged, 'sources' => $evidence ) );
			}
			if ( is_wp_error( $verification ) ) {
				$verify_error = $verification->get_error_message();
				$errors[] = 'verify_final: ' . $verify_error;
				$this->add_pipeline_event( $pipeline, $verify_event, 'error', array( 'error' => $verify_error ) );
				if ( $debug_enabled ) {
					$this->add_debug_event( $debug, 'verify_final_error', array( 'error' => $verify_error ) );
					$this->add_timeline_event( $timeline, '6.verify_final.done', array( 'ok' => false, 'error' => $verify_error ) );
				}
				$verification = null;
			} else {
				$this->add_phase_usage( $phase_usage, 'verify_final' );
				$this->add_pipeline_event( $pipeline, $verify_event, 'ok', array( 'verdict' => $verification['verdict'] ?? '' ) );
				if ( $debug_enabled ) {
					$this->add_debug_event( $debug, 'verify_final_result', $verification );
					$this->add_timeline_event( $timeline, '6.verify_final.done', array( 'ok' => true, 'verdict' => $verification['verdict'] ?? '' ) );
				}
			}
		} else {
			$verify_event = $verification_provider === 'openai' ? 'verify_openai_web_search' : 'verify_perplexity';
			$this->add_pipeline_event( $pipeline, $verify_event, 'skipped' );
			if ( $debug_enabled ) {
				$this->add_timeline_event( $timeline, '6.verify_final.skipped', array( 'provider' => $verification_provider ) );
			}
		}

		$curr_start = get_post_meta( $festival_id, 'fecha_inicio', true );
		$curr_end = get_post_meta( $festival_id, 'fecha_fin', true );
		$has_existing_dates = $this->existing_dates_match_edition( $curr_start, $curr_end, $edition );
		$dates_confirmed = $this->dates_are_confirmed( $merged, $edition, $date_conflicts );
		if ( $has_existing_dates ) {
			$dates_confirmed = true;
		}
		$answer_has_no_dates = $this->answer_mentions_no_dates( $answer );
		if ( $answer_has_no_dates && ! $has_existing_dates ) {
			$dates_confirmed = false;
		}
		$curr_no_dates = (string) get_post_meta( $festival_id, 'sin_fechas_confirmadas', true );
		$desired_no_dates = $dates_confirmed ? '0' : '1';
		if ( $curr_no_dates !== $desired_no_dates ) {
			$diffs['sin_fechas_confirmadas'] = array( 'before' => $curr_no_dates, 'after' => $desired_no_dates );
		}

		$this->progress( $progress_cb, 'Guardando resultado: ' . $status );
		$this->add_pipeline_event( $pipeline, 'store_update', 'ok', array( 'status' => $status ) );
		if ( $debug_enabled ) {
			$this->add_timeline_event( $timeline, '7.store_update', array( 'status' => $status ) );
		}
		$extra = $updated_content !== '' ? array( 'updated_content' => $updated_content ) : array();
		$extra['pipeline_log'] = $pipeline;
		if ( $content_verification ) {
			$extra['content_verification'] = $content_verification;
		}
		if ( $debug_enabled ) {
			$this->append_usage_debug( $debug, $phase_usage );
			$debug['timeline'] = $timeline;
			$extra['debug_log'] = $debug;
		}
		$update_id = $this->store_update(
			$festival_id,
			$status,
			$diffs,
			$merged,
			$evidence,
			$errors,
			$verification,
			$extra
		);
		if ( ! $update_id ) {
			return new WP_Error( 'mfu_update_store', 'Failed to store update' );
		}

		if ( $auto_apply_verified && is_array( $verification ) && ( $verification['verdict'] ?? '' ) === 'ok' && ! empty( $diffs ) ) {
			if ( class_exists( 'MFU_Admin' ) ) {
				$admin = new MFU_Admin();
				$admin->apply_update_by_id( $update_id, 0 );
			}
		}

		return true;
	}

	public function process_news_url( $url, $progress_cb = null, $forced_festival_id = 0 ) {
		$url = trim( (string) $url );
		if ( $url === '' ) {
			return new WP_Error( 'mfu_news_url', 'URL invalida' );
		}
		self::log_action( 'news.process.start', array( 'url' => $url ) );

		$options = get_option( MFU_OPTION_KEY, array() );
		$debug_enabled = ! empty( $options['debug_enabled'] );
		$ai_content_enabled = ! empty( $options['ai_content_update'] );
		$auto_apply_verified = ! empty( $options['auto_apply_when_verified'] );
		$verification_provider = isset( $options['verification_provider'] ) ? (string) $options['verification_provider'] : 'perplexity';
		if ( ! in_array( $verification_provider, array( 'openai', 'perplexity' ), true ) ) {
			$verification_provider = 'perplexity';
		}

		$debug = array();
		if ( $debug_enabled ) {
			$this->add_debug_event( $debug, 'news_url', array( 'url' => $url ) );
		}

		$this->progress( $progress_cb, 'Descargando noticia' );
		$news_payload = $this->fetch_news_text( $url );
		if ( is_wp_error( $news_payload ) ) {
			self::log_action( 'news.process.fetch_error', array( 'url' => $url, 'error' => $news_text->get_error_message() ) );
			return $news_payload;
		}
		$news_text = is_array( $news_payload ) ? (string) ( $news_payload['text'] ?? '' ) : (string) $news_payload;
		$news_title = is_array( $news_payload ) ? (string) ( $news_payload['title'] ?? '' ) : '';

		$ai = new MFU_AI();
		if ( ! $ai->has_openai_key() && ! $ai->has_perplexity_key() ) {
			return new WP_Error( 'mfu_no_ai_key', 'AI API key missing' );
		}

		$festival = null;
		$festival_id = (int) $forced_festival_id;
		$festival_name = '';
		$edition = '';
		$confidence = 0.0;
		if ( $festival_id > 0 ) {
			$festival = get_post( $festival_id );
			if ( ! $festival || $festival->post_type !== 'festi' ) {
				return new WP_Error( 'mfu_news_festival', 'Festival no encontrado en la base de datos.' );
			}
			$festival_name = $festival->post_title;
			$edition = trim( (string) get_post_meta( $festival_id, 'edicion', true ) );
			self::log_action( 'news.process.forced_festival', array( 'url' => $url, 'festival_id' => $festival_id ) );
		} else {
			$this->progress( $progress_cb, 'Identificando festival' );
			$ident = $ai->identify_festival_from_news( $news_text, $url );
			if ( is_wp_error( $ident ) ) {
				self::log_action( 'news.process.identify_error', array( 'url' => $url, 'error' => $ident->get_error_message() ) );
				return $ident;
			}
			$festival_name = trim( (string) ( $ident['festival_name'] ?? '' ) );
			$edition = trim( (string) ( $ident['edition'] ?? '' ) );
			$confidence = isset( $ident['confidence'] ) ? (float) $ident['confidence'] : 0.0;
			if ( $festival_name === '' || $confidence <= 0 ) {
				self::log_action( 'news.process.identify_empty', array( 'url' => $url, 'confidence' => $confidence ) );
				return new WP_Error( 'mfu_news_festival', 'No se pudo identificar el festival en la noticia' );
			}

			$festival_match = $this->find_festival_by_name( $festival_name );
			if ( empty( $festival_match['id'] ) ) {
				self::log_action( 'news.process.not_found', array( 'url' => $url, 'festival_name' => $festival_name ) );
				$suggest = ! empty( $festival_match['suggestions'] ) ? ' Sugerencias: ' . implode( ', ', $festival_match['suggestions'] ) : '';
				return new WP_Error( 'mfu_news_festival', 'Festival no encontrado en la base de datos.' . $suggest );
			}
			$festival_id = (int) $festival_match['id'];
			$festival = get_post( $festival_id );
			if ( ! $festival || $festival->post_type !== 'festi' ) {
				return new WP_Error( 'mfu_news_festival', 'Festival no encontrado en la base de datos.' );
			}

			if ( $edition === '' ) {
				$edition = trim( (string) get_post_meta( $festival_id, 'edicion', true ) );
			}

			if ( $debug_enabled ) {
				$this->add_debug_event( $debug, 'festival_match', array( 'id' => $festival_id, 'title' => $festival->post_title, 'confidence' => $confidence ) );
			}
		}

		$this->progress( $progress_cb, 'Extrayendo hechos de la noticia' );
		$facts = $ai->extract_facts( $festival->post_title, $news_text, $url, $edition );
		if ( is_wp_error( $facts ) ) {
			self::log_action( 'news.process.extract_error', array( 'url' => $url, 'festival_id' => $festival_id, 'error' => $facts->get_error_message() ) );
			return $facts;
		}

		$all_facts = array( $facts );
		$merged = $this->merge_facts( $all_facts );
		$diffs = $this->build_diffs( $festival_id, $merged );

		$evidence = array(
			array(
				'url' => $url,
				'summary' => isset( $facts['summary'] ) ? (string) $facts['summary'] : '',
				'type' => 'news',
				'news_title' => $news_title,
			),
		);

		$updated_content = '';
		if ( $ai_content_enabled && ! empty( $diffs ) ) {
			$evidence_list = array( $url );
			$updated_content = $ai->rewrite_festival_content( $festival->post_title, $edition, (string) $festival->post_content, $diffs, $evidence_list );
			if ( is_wp_error( $updated_content ) ) {
				$updated_content = '';
			}
		}

		$status = empty( $diffs ) ? 'no_change' : 'pending_review';
		$verification = null;
		if ( ! empty( $diffs ) ) {
			if ( $verification_provider === 'openai' ) {
				$verification = $ai->verify_update_with_openai_web_search( $festival->post_title, $edition, $diffs, array( 'facts' => $merged, 'sources' => $evidence ) );
			} else {
				$verification = $ai->verify_update_with_perplexity( $festival->post_title, $edition, $diffs, array( 'facts' => $merged, 'sources' => $evidence ) );
			}
			if ( is_wp_error( $verification ) ) {
				$verification = null;
			}
		}

		$extra = $updated_content !== '' ? array( 'updated_content' => $updated_content ) : array();
		$extra['update_origin'] = 'news';
		$extra['pipeline_log'] = array(
			array( 'step' => 'news_url', 'result' => 'ok', 'data' => array( 'url' => $url ) ),
		);
		if ( $debug_enabled ) {
			$extra['debug_log'] = $debug;
		}

		$update_id = $this->store_update(
			$festival_id,
			$status,
			$diffs,
			$merged,
			$evidence,
			array(),
			$verification,
			$extra
		);
		if ( ! $update_id ) {
			self::log_action( 'news.process.store_error', array( 'url' => $url, 'festival_id' => $festival_id ) );
			return new WP_Error( 'mfu_news_store', 'No se pudo guardar la actualizacion' );
		}
		self::log_action( 'news.process.done', array( 'url' => $url, 'festival_id' => $festival_id, 'update_id' => $update_id ) );

		if ( $auto_apply_verified && is_array( $verification ) && ( $verification['verdict'] ?? '' ) === 'ok' && ! empty( $diffs ) ) {
			if ( class_exists( 'MFU_Admin' ) ) {
				$admin = new MFU_Admin();
				$admin->apply_update_by_id( $update_id, 0 );
			}
		}

		return $update_id;
	}

	public function identify_news_festival( $url ) {
		$url = trim( (string) $url );
		if ( $url === '' ) {
			return new WP_Error( 'mfu_news_url', 'URL invalida' );
		}
		self::log_action( 'news.check.start', array( 'url' => $url ) );

		$news_payload = $this->fetch_news_text( $url );
		if ( is_wp_error( $news_payload ) ) {
			self::log_action( 'news.check.fetch_error', array( 'url' => $url, 'error' => $news_payload->get_error_message() ) );
			return $news_payload;
		}
		$news_text = is_array( $news_payload ) ? (string) ( $news_payload['text'] ?? '' ) : (string) $news_payload;

		$ai = new MFU_AI();
		if ( ! $ai->has_openai_key() && ! $ai->has_perplexity_key() ) {
			return new WP_Error( 'mfu_no_ai_key', 'AI API key missing' );
		}

		$ident = $ai->identify_festival_from_news( $news_text, $url );
		if ( is_wp_error( $ident ) ) {
			self::log_action( 'news.check.identify_error', array( 'url' => $url, 'error' => $ident->get_error_message() ) );
			return $ident;
		}
		$festival_name = trim( (string) ( $ident['festival_name'] ?? '' ) );
		$confidence = isset( $ident['confidence'] ) ? (float) $ident['confidence'] : 0.0;
		if ( $festival_name === '' || $confidence <= 0 ) {
			self::log_action( 'news.check.identify_empty', array( 'url' => $url, 'confidence' => $confidence ) );
			return new WP_Error( 'mfu_news_festival', 'No se pudo identificar el festival en la noticia' );
		}

		$festival_match = $this->find_festival_by_name( $festival_name );
		if ( empty( $festival_match['id'] ) ) {
			self::log_action( 'news.check.not_found', array( 'url' => $url, 'festival_name' => $festival_name ) );
			$suggest = ! empty( $festival_match['suggestions'] ) ? ' Sugerencias: ' . implode( ', ', $festival_match['suggestions'] ) : '';
			return new WP_Error( 'mfu_news_festival', 'Festival no encontrado en la base de datos.' . $suggest );
		}

		$festival_id = (int) $festival_match['id'];
		$festival = get_post( $festival_id );
		if ( ! $festival || $festival->post_type !== 'festi' ) {
			self::log_action( 'news.check.invalid_post', array( 'url' => $url, 'festival_id' => $festival_id ) );
			return new WP_Error( 'mfu_news_festival', 'Festival no encontrado en la base de datos.' );
		}
		self::log_action( 'news.check.found', array( 'url' => $url, 'festival_id' => $festival_id, 'festival_title' => $festival->post_title, 'confidence' => $confidence ) );

		return array(
			'id' => $festival_id,
			'festival_title' => $festival->post_title,
			'confidence' => $confidence,
		);
	}

	private function truncate_debug_text( $text, $limit = 8000 ) {
		$text = (string) $text;
		if ( strlen( $text ) <= $limit ) {
			return $text;
		}
		return substr( $text, 0, $limit ) . '...';
	}

	private function fetch_news_text( $url ) {
		$options = get_option( MFU_OPTION_KEY, array() );
		$timeout = isset( $options['timeout'] ) ? max( 5, (int) $options['timeout'] ) : 15;
		$timeout = max( 30, $timeout ); // news pages can be slow; avoid short timeouts
		$args = array(
			'timeout' => $timeout,
			'headers' => array(
				'User-Agent' => 'MFU/1.0 (+news)',
				'Accept' => 'text/html,application/xhtml+xml',
			),
		);
		$response = wp_remote_get( $url, $args );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		if ( $code < 200 || $code >= 300 || $body === '' ) {
			return new WP_Error( 'mfu_news_fetch', 'No se pudo descargar la noticia' );
		}

		$title = '';
		if ( preg_match( '/<title[^>]*>(.*?)<\\/title>/is', $body, $m ) ) {
			$title = trim( wp_strip_all_tags( $m[1] ) );
		}
		$body = preg_replace( '/<script\\b[^>]*>.*?<\\/script>/is', ' ', $body );
		$body = preg_replace( '/<style\\b[^>]*>.*?<\\/style>/is', ' ', $body );
		$text = trim( wp_strip_all_tags( $body ) );
		$text = preg_replace( '/\\s+/', ' ', $text );
		return array(
			'title' => $title,
			'text' => $text,
		);
	}

	private function find_festival_by_name( $name ) {
		$name = $this->normalize_festival_name( $name );
		if ( $name === '' ) {
			return array();
		}
		$posts = get_posts(
			array(
				'post_type' => 'festi',
				'post_status' => array( 'publish', 'draft' ),
				'numberposts' => -1,
				'fields' => array( 'ID', 'post_title' ),
			)
		);
		$best_id = 0;
		$best_score = 0.0;
		$suggestions = array();
		foreach ( $posts as $post ) {
			$title = $this->normalize_festival_name( $post->post_title );
			if ( $title === '' ) {
				continue;
			}
			$score = 0.0;
			if ( strpos( $title, $name ) !== false || strpos( $name, $title ) !== false ) {
				$score = 100.0;
			} else {
				similar_text( $name, $title, $score );
			}
			if ( $score > $best_score ) {
				$best_score = $score;
				$best_id = (int) $post->ID;
			}
		}
		if ( $best_id > 0 && $best_score >= 45 ) {
			return array( 'id' => $best_id, 'score' => $best_score );
		}
		if ( ! empty( $posts ) ) {
			foreach ( array_slice( $posts, 0, 5 ) as $post ) {
				$suggestions[] = $post->post_title;
			}
		}
		return array( 'suggestions' => $suggestions );
	}

	private function normalize_festival_name( $name ) {
		$name = remove_accents( (string) $name );
		$name = strtolower( $name );
		$name = preg_replace( '/[^a-z0-9\\s]/', ' ', $name );
		$name = preg_replace( '/\\s+/', ' ', $name );
		return trim( $name );
	}

	private function answer_mentions_no_dates( $answer_text ) {
		$text = strtolower( (string) $answer_text );
		if ( $text === '' ) {
			return false;
		}
		return (bool) preg_match( '/no\\s+.*fechas\\s+confirmad/i', $text )
			|| (bool) preg_match( '/no\\s+hay\\s+fechas/i', $text );
	}

	private function ensure_content_includes_facts( $updated_content, $merged ) {
		return $updated_content;
	}

	private function add_timeline_event( array &$timeline, $step, $details = null ) {
		$event = array(
			'step' => (string) $step,
			'time' => current_time( 'mysql', true ),
		);
		if ( $details !== null ) {
			$event['details'] = $details;
		}
		$timeline[] = $event;
	}

	private function sanitize_ai_html_wrapper( $content ) {
		if ( $content === '' ) {
			return $content;
		}

		$content = preg_replace( '/<!DOCTYPE[^>]*>/i', '', $content );
		$content = preg_replace( '/<\\/?html\\b[^>]*>/i', '', $content );
		$content = preg_replace( '/<head\\b[^>]*>.*?<\\/head>/is', '', $content );
		$content = preg_replace( '/<\\/?body\\b[^>]*>/i', '', $content );
		$content = preg_replace( '/<meta\\b[^>]*>/i', '', $content );
		$content = preg_replace( '/<title\\b[^>]*>.*?<\\/title>/is', '', $content );

		return trim( $content );
	}

	private function remove_mfu_update_block( $content ) {
		if ( $content === '' ) {
			return $content;
		}
		$content = preg_replace( '/<p>\\s*Actualizacion\\s*\\(MFU\\)\\s*<\\/p>[\\s\\S]*?(?=<p>|\\z)/i', '', $content );
		$content = preg_replace( '/Actualizacion\\s*\\(MFU\\)[\\s\\S]*?(?:\\n\\s*\\n|\\z)/i', '', $content );
		return trim( $content );
	}

	private function ensure_artists_in_updated_content( $updated_content, $diffs ) {
		if ( $updated_content === '' || ! is_array( $diffs ) ) {
			return $updated_content;
		}

		$after = $diffs['mf_artistas']['after'] ?? '';
		if ( ! is_string( $after ) ) {
			return $updated_content;
		}

		$raw_artists = array_filter( array_map( 'trim', preg_split( '/[\\r\\n,;]+/', $after ) ) );
		if ( empty( $raw_artists ) ) {
			return $updated_content;
		}

		$content_lower = strtolower( $updated_content );
		foreach ( $raw_artists as $artist ) {
			if ( $artist !== '' && strpos( $content_lower, strtolower( $artist ) ) !== false ) {
				return $updated_content;
			}
		}

		$list_items = array();
		foreach ( $raw_artists as $artist ) {
			$list_items[] = '<li><strong>' . esc_html( $artist ) . '</strong></li>';
		}
		$list_html = "<h2>Cartel 2026 (confirmados)</h2>\n<ul>\n\t" . implode( "\n\t", $list_items ) . "\n</ul>\n";

		if ( preg_match( '/(<h2>\\s*Cartel\\s*2026[^<]*<\\/h2>\\s*)(<ul>.*?<\\/ul>)/is', $updated_content ) ) {
			return preg_replace( '/(<h2>\\s*Cartel\\s*2026[^<]*<\\/h2>\\s*)(<ul>.*?<\\/ul>)/is', '$1' . $list_html, $updated_content, 1 );
		}
		if ( preg_match( '/<h2>\\s*Cartel\\s*2026[^<]*<\\/h2>/i', $updated_content ) ) {
			return preg_replace( '/(<h2>\\s*Cartel\\s*2026[^<]*<\\/h2>)/i', '$1' . "\n" . $list_html, $updated_content, 1 );
		}
		if ( preg_match( '/<h2>\\s*¿?Cómo conseguir entradas\\??\\s*<\\/h2>/i', $updated_content ) ) {
			return preg_replace( '/(<h2>\\s*¿?Cómo conseguir entradas\\??\\s*<\\/h2>)/i', $list_html . '$1', $updated_content, 1 );
		}

		return trim( $updated_content ) . "\n\n" . $list_html;
	}

	private function build_festival_snapshot( $festival_id ) {
		$festival = get_post( $festival_id );
		$localidad_terms = get_the_terms( $festival_id, 'localidad' );
		$localidad = '';
		$localidad_parent = '';
		if ( is_array( $localidad_terms ) && ! empty( $localidad_terms ) ) {
			$term = null;
			foreach ( $localidad_terms as $candidate ) {
				if ( ! empty( $candidate->parent ) ) {
					$term = $candidate;
					break;
				}
			}
			if ( ! $term ) {
				$term = $localidad_terms[0];
			}

			$localidad = $term->name ?? '';
			if ( ! empty( $term->parent ) ) {
				$parent = get_term( (int) $term->parent, 'localidad' );
				if ( $parent && ! is_wp_error( $parent ) ) {
					$localidad_parent = $parent->name ?? '';
				}
			}
		}

		$content = $festival ? (string) $festival->post_content : '';
		$fecha_inicio = $this->normalize_date_value( get_post_meta( $festival_id, 'fecha_inicio', true ) );
		$fecha_fin = $this->normalize_date_value( get_post_meta( $festival_id, 'fecha_fin', true ) );

		return array(
			'festival_id' => (int) $festival_id,
			'title' => $festival ? (string) $festival->post_title : '',
			'edicion' => (string) get_post_meta( $festival_id, 'edicion', true ),
			'fecha_inicio' => $fecha_inicio,
			'fecha_fin' => $fecha_fin,
			'cancelado' => (string) get_post_meta( $festival_id, 'cancelado', true ),
			'mf_artistas' => (string) get_post_meta( $festival_id, 'mf_artistas', true ),
			'mf_cartel_completo' => (string) get_post_meta( $festival_id, 'mf_cartel_completo', true ),
			'sin_fechas_confirmadas' => (string) get_post_meta( $festival_id, 'sin_fechas_confirmadas', true ),
			'localidad' => $localidad,
			'localidad_parent' => $localidad_parent,
			'post_content_length' => strlen( $content ),
			'post_content_snippet' => $this->truncate_debug_text( $content, 1200 ),
		);
	}

	private function add_debug_event( &$debug, $stage, $data ) {
		if ( ! is_array( $debug ) ) {
			$debug = array();
		}
		$debug[] = array(
			'time' => current_time( 'mysql' ),
			'stage' => (string) $stage,
			'data' => $data,
		);
	}

	private function append_usage_debug( &$debug, array $phase_usage ) {
		$this->append_last_usage_debug( $debug );
		if ( ! empty( $phase_usage ) ) {
			$this->add_debug_event( $debug, 'usage_by_phase', $phase_usage );
		}
	}

	private function append_last_usage_debug( &$debug ) {
		$usage = get_option( 'mfu_usage_log', array() );
		if ( ! is_array( $usage ) ) {
			return;
		}
		$last = isset( $usage['last'] ) && is_array( $usage['last'] ) ? $usage['last'] : null;
		if ( ! $last ) {
			return;
		}
		$settings = get_option( MFU_OPTION_KEY, array() );
		$currency = isset( $settings['cost_currency'] ) ? (string) $settings['cost_currency'] : 'EUR';
		$last['currency'] = $currency;
		$this->add_debug_event( $debug, 'usage_last', $last );
	}

	private function add_phase_usage( array &$phase_usage, $phase ) {
		$usage = get_option( 'mfu_usage_log', array() );
		if ( ! is_array( $usage ) ) {
			return;
		}
		$last = isset( $usage['last'] ) && is_array( $usage['last'] ) ? $usage['last'] : null;
		if ( ! $last ) {
			return;
		}
		$settings = get_option( MFU_OPTION_KEY, array() );
		$currency = isset( $settings['cost_currency'] ) ? (string) $settings['cost_currency'] : 'EUR';
		if ( empty( $phase_usage[ $phase ] ) ) {
			$phase_usage[ $phase ] = array(
				'requests' => 0,
				'input_tokens' => 0,
				'output_tokens' => 0,
				'cost' => 0.0,
				'currency' => $currency,
				'models' => array(),
				'providers' => array(),
			);
		}
		$phase_usage[ $phase ]['requests'] += 1;
		$phase_usage[ $phase ]['input_tokens'] += (int) ( $last['input_tokens'] ?? 0 );
		$phase_usage[ $phase ]['output_tokens'] += (int) ( $last['output_tokens'] ?? 0 );
		$phase_usage[ $phase ]['cost'] += (float) ( $last['cost'] ?? 0 );
		$phase_usage[ $phase ]['currency'] = $currency;
		$model = isset( $last['model'] ) ? (string) $last['model'] : '';
		if ( $model !== '' ) {
			if ( empty( $phase_usage[ $phase ]['models'][ $model ] ) ) {
				$phase_usage[ $phase ]['models'][ $model ] = 0;
			}
			$phase_usage[ $phase ]['models'][ $model ] += 1;
		}
		$provider = isset( $last['provider'] ) ? (string) $last['provider'] : '';
		if ( $provider !== '' ) {
			if ( empty( $phase_usage[ $phase ]['providers'][ $provider ] ) ) {
				$phase_usage[ $phase ]['providers'][ $provider ] = 0;
			}
			$phase_usage[ $phase ]['providers'][ $provider ] += 1;
		}
	}

	private function add_pipeline_event( &$pipeline, $step, $result, $data = array() ) {
		if ( ! is_array( $pipeline ) ) {
			$pipeline = array();
		}
		$entry = array(
			'time' => current_time( 'mysql' ),
			'step' => (string) $step,
			'result' => (string) $result,
		);
		if ( ! empty( $data ) ) {
			$entry['data'] = $data;
		}
		$pipeline[] = $entry;
	}

	private function progress( $callback, $message ) {
		if ( is_callable( $callback ) ) {
			call_user_func( $callback, $message );
		}
	}

	private function collect_sources( $festival_id ) {
		$sources = array();

		if ( function_exists( 'get_field' ) ) {
			$web = get_field( 'mf_web_oficial', $festival_id );
			$instagram = get_field( 'mf_instagram', $festival_id );
			$extra = get_field( 'mf_fuentes_extra', $festival_id );

			if ( $web ) {
				$sources[] = array( 'type' => 'web', 'url' => esc_url_raw( $web ) );
			}
			if ( $instagram ) {
				$sources[] = array( 'type' => 'instagram', 'url' => esc_url_raw( $instagram ) );
			}
			if ( is_array( $extra ) ) {
				foreach ( $extra as $row ) {
					if ( ! empty( $row['url'] ) ) {
						$sources[] = array( 'type' => 'extra', 'url' => esc_url_raw( $row['url'] ) );
					}
				}
			}
		}

		return array_values( array_filter( $sources ) );
	}

	private function discover_sources_if_needed( $festival_id, $festival_name, $sources ) {
		if ( ! empty( $sources ) ) {
			return $sources;
		}

		$ai = new MFU_AI();
		if ( ! $ai->has_key() ) {
			return $sources;
		}
		if ( ! $ai->supports_web_search() ) {
			return $sources;
		}

		$discovered = $ai->discover_sources( $festival_name );
		if ( is_wp_error( $discovered ) ) {
			return $discovered;
		}

		$urls = array();
		if ( ! empty( $discovered['official'] ) && is_array( $discovered['official'] ) ) {
			$urls = array_merge( $urls, $discovered['official'] );
		}
		if ( ! empty( $discovered['news'] ) && is_array( $discovered['news'] ) ) {
			$urls = array_merge( $urls, $discovered['news'] );
		}

		$urls = array_unique( array_filter( array_map( 'esc_url_raw', $urls ) ) );
		$urls = array_slice( $urls, 0, 5 );
		if ( empty( $urls ) ) {
			return new WP_Error( 'mfu_no_sources', 'No sources discovered by AI' );
		}

		foreach ( $urls as $url ) {
			$sources[] = array( 'type' => 'discovered', 'url' => $url );
		}

		return $sources;
	}

	private function upsert_source( $festival_id, $type, $url ) {
		global $wpdb;
		$table = MFU_DB::table( 'sources' );

		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$table} WHERE festival_id=%d AND type=%s AND url=%s LIMIT 1",
			$festival_id,
			$type,
			$url
		) );

		if ( $existing ) {
			$wpdb->update(
				$table,
				array( 'last_checked' => current_time( 'mysql' ) ),
				array( 'id' => $existing ),
				array( '%s' ),
				array( '%d' )
			);
			return (int) $existing;
		}

		$wpdb->insert(
			$table,
			array(
				'festival_id' => (int) $festival_id,
				'type' => $type,
				'url' => $url,
				'active' => 1,
				'last_checked' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%d', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	private function get_source_cache( $source_id ) {
		if ( ! $source_id ) {
			return array();
		}
		global $wpdb;
		$table = MFU_DB::table( 'sources' );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT etag, last_modified, last_hash FROM {$table} WHERE id=%d", $source_id ), ARRAY_A );
		return is_array( $row ) ? $row : array();
	}

	private function update_source_cache( $source_id, $fetch, $hash = null ) {
		if ( ! $source_id ) {
			return;
		}
		global $wpdb;
		$table = MFU_DB::table( 'sources' );
		$data = array(
			'last_checked' => current_time( 'mysql' ),
		);
		if ( ! empty( $fetch['etag'] ) ) {
			$data['etag'] = $fetch['etag'];
		}
		if ( ! empty( $fetch['last_modified'] ) ) {
			$data['last_modified'] = $fetch['last_modified'];
		}
		if ( $hash ) {
			$data['last_hash'] = $hash;
		}

		$wpdb->update(
			$table,
			$data,
			array( 'id' => $source_id ),
			array_fill( 0, count( $data ), '%s' ),
			array( '%d' )
		);
	}

	private function store_snapshot( $festival_id, $source_id, $text, $hash ) {
		if ( ! $source_id ) {
			return;
		}
		global $wpdb;
		$table = MFU_DB::table( 'snapshots' );

		$wpdb->insert(
			$table,
			array(
				'festival_id' => (int) $festival_id,
				'source_id' => (int) $source_id,
				'fetched_at' => current_time( 'mysql' ),
				'text_hash' => $hash,
				'extracted_text' => $text,
			),
			array( '%d', '%d', '%s', '%s', '%s' )
		);
	}

	private function fetch_source( $url, $cache = array() ) {
		$settings = get_option( MFU_OPTION_KEY, array() );
		$timeout = isset( $settings['timeout'] ) ? (int) $settings['timeout'] : 15;

		$headers = array();
		if ( ! empty( $cache['etag'] ) ) {
			$headers['If-None-Match'] = $cache['etag'];
		}
		if ( ! empty( $cache['last_modified'] ) ) {
			$headers['If-Modified-Since'] = $cache['last_modified'];
		}

		$response = wp_remote_get( $url, array( 'timeout' => $timeout, 'headers' => $headers ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code === 304 ) {
			return array( 'not_modified' => true );
		}
		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error( 'mfu_fetch', 'Fetch failed: ' . $code );
		}

		return array(
			'body' => wp_remote_retrieve_body( $response ),
			'etag' => wp_remote_retrieve_header( $response, 'etag' ),
			'last_modified' => wp_remote_retrieve_header( $response, 'last-modified' ),
		);
	}

	private function extract_text( $html ) {
		$html = preg_replace( '#<script(.*?)>(.*?)</script>#is', '', $html );
		$html = preg_replace( '#<style(.*?)>(.*?)</style>#is', '', $html );
		$text = wp_strip_all_tags( $html );
		$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5 );
		$text = preg_replace( '/\s+/', ' ', $text );
		$text = trim( $text );

		if ( strlen( $text ) > 12000 ) {
			$text = substr( $text, 0, 12000 );
		}

		return $text;
	}

	private function build_text_from_perplexity_items( $items ) {
		$lines = array();
		if ( ! is_array( $items ) ) {
			return '';
		}
		foreach ( $items as $row ) {
			$title = isset( $row['title'] ) ? $row['title'] : '';
			$snippet = isset( $row['snippet'] ) ? $row['snippet'] : '';
			$url = isset( $row['url'] ) ? $row['url'] : '';
			$lines[] = trim( $title . ' ' . $snippet . ' ' . $url );
		}
		return implode( "\n", $lines );
	}

	private function sanitize_instagram_single_date( $facts ) {
		if ( ! is_array( $facts ) || empty( $facts['facts'] ) || ! is_array( $facts['facts'] ) ) {
			return $facts;
		}

		$payload = $facts['facts'];
		$date_start = isset( $payload['date_start']['value'] ) ? (string) $payload['date_start']['value'] : '';
		$date_end = isset( $payload['date_end']['value'] ) ? (string) $payload['date_end']['value'] : '';
		if ( $date_start === '' || $date_end === '' || $date_start !== $date_end ) {
			return $facts;
		}

		$snippets = array();
		foreach ( array( 'date_start', 'date_end' ) as $field ) {
			if ( empty( $payload[ $field ]['evidence'] ) || ! is_array( $payload[ $field ]['evidence'] ) ) {
				continue;
			}
			foreach ( $payload[ $field ]['evidence'] as $ev ) {
				if ( ! empty( $ev['snippet'] ) ) {
					$snippets[] = (string) $ev['snippet'];
				}
			}
		}

		$text = strtolower( implode( ' ', $snippets ) );
		$has_range = ( strpos( $text, ' del ' ) !== false )
			|| ( strpos( $text, ' al ' ) !== false )
			|| ( strpos( $text, ' hasta ' ) !== false )
			|| ( strpos( $text, ' - ' ) !== false )
			|| ( strpos( $text, ' to ' ) !== false );

		if ( $has_range ) {
			return $facts;
		}

		$note = 'Fecha mencionada en Instagram: ' . $date_start . '.';
		$existing = isset( $payload['notes'] ) && is_string( $payload['notes'] ) ? trim( $payload['notes'] ) : '';
		$payload['notes'] = $existing !== '' ? $existing . ' ' . $note : $note;
		$payload['date_start']['value'] = null;
		$payload['date_end']['value'] = null;
		$facts['facts'] = $payload;
		return $facts;
	}

	private function build_text_from_instagram_items( $items ) {
		$lines = array();
		if ( ! is_array( $items ) ) {
			return '';
		}
		foreach ( $items as $row ) {
			$text = isset( $row['text'] ) ? $row['text'] : '';
			$url = isset( $row['url'] ) ? $row['url'] : '';
			$lines[] = trim( $text . ' ' . $url );
		}
		return implode( "\n", $lines );
	}

	private function fetch_apify_instagram_posts( $token, $instagram_url, $max_posts = 2 ) {
		$actor_id = 'apify/instagram-post-scraper';
		$value = trim( (string) $instagram_url );
		$input = array(
			'resultsLimit' => max( 1, min( 10, (int) $max_posts ) ),
		);
		$username = '';
		if ( stripos( $value, 'http://' ) === 0 || stripos( $value, 'https://' ) === 0 ) {
			$parsed = wp_parse_url( $value );
			$path = isset( $parsed['path'] ) ? trim( $parsed['path'], '/' ) : '';
			if ( $path !== '' ) {
				$parts = explode( '/', $path );
				$candidate = isset( $parts[0] ) ? $parts[0] : '';
				if ( $candidate && ! in_array( $candidate, array( 'p', 'reel', 'tv', 'stories' ), true ) ) {
					$username = $candidate;
				}
			}
		} else {
			$username = ltrim( $value, '@' );
		}
		if ( $username ) {
			$input['username'] = array( $username );
		} else {
			$input['directUrls'] = array( $value );
		}

		$base = 'https://api.apify.com/v2/acts/' . rawurlencode( $actor_id ) . '/run-sync-get-dataset-items';
		$url = add_query_arg( array( 'format' => 'json', 'clean' => '1' ), $base );
		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 120,
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
		foreach ( $data as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$caption = '';
			if ( isset( $row['caption'] ) && is_string( $row['caption'] ) ) {
				$caption = $row['caption'];
			} elseif ( isset( $row['captionText'] ) && is_string( $row['captionText'] ) ) {
				$caption = $row['captionText'];
			} elseif ( isset( $row['text'] ) && is_string( $row['text'] ) ) {
				$caption = $row['text'];
			}
			$items[] = array(
				'text' => $caption,
				'url' => (string) ( $row['url'] ?? $row['postUrl'] ?? '' ),
			);
			if ( count( $items ) >= $max_posts ) {
				break;
			}
		}
		return $items;
	}

	private function filter_internal_items( $items ) {
		if ( ! is_array( $items ) ) {
			return $items;
		}
		$home = wp_parse_url( home_url() );
		$host = isset( $home['host'] ) ? strtolower( $home['host'] ) : '';
		$blocked = array_filter( array( $host, 'modofestival.es' ) );
		$filtered = array();
		foreach ( $items as $row ) {
			$url = isset( $row['url'] ) ? $row['url'] : '';
			$parsed = wp_parse_url( $url );
			$src_host = isset( $parsed['host'] ) ? strtolower( $parsed['host'] ) : '';
			if ( $src_host && in_array( $src_host, $blocked, true ) ) {
				continue;
			}
			$filtered[] = $row;
		}
		return $filtered;
	}

	private function merge_facts( $facts_list ) {
		$merged = array(
			'summary' => '',
			'facts' => array(
				'date_start' => array( 'value' => null, 'evidence' => array() ),
				'date_end' => array( 'value' => null, 'evidence' => array() ),
				'location' => array( 'value' => null, 'evidence' => array() ),
				'tickets_url' => array( 'value' => null, 'evidence' => array() ),
				'artists' => array( 'value' => array(), 'evidence' => array() ),
				'lineup_complete' => array( 'value' => null, 'evidence' => array() ),
				'canceled' => array( 'value' => null, 'evidence' => array() ),
				'notes' => '',
			),
		);

		foreach ( $facts_list as $facts ) {
			if ( ! is_array( $facts ) || empty( $facts['facts'] ) || ! is_array( $facts['facts'] ) ) {
				continue;
			}
			if ( ! empty( $facts['summary'] ) ) {
				$merged['summary'] .= ( $merged['summary'] ? ' ' : '' ) . $facts['summary'];
			}

			foreach ( $merged['facts'] as $key => $current ) {
				if ( $key === 'tickets_url' ) {
					continue;
				}
				if ( ! isset( $facts['facts'][ $key ] ) || ! is_array( $facts['facts'][ $key ] ) ) {
					continue;
				}

				$value = isset( $facts['facts'][ $key ]['value'] ) ? $facts['facts'][ $key ]['value'] : null;
				if ( $value !== null && $value !== '' && ( is_array( $value ) ? ! empty( $value ) : true ) ) {
					$merged['facts'][ $key ]['value'] = $value;
				}

				if ( ! empty( $facts['facts'][ $key ]['evidence'] ) && is_array( $facts['facts'][ $key ]['evidence'] ) ) {
					$merged['facts'][ $key ]['evidence'] = array_merge(
						$merged['facts'][ $key ]['evidence'],
						$facts['facts'][ $key ]['evidence']
					);
				}
			}
		}

		return $merged;
	}

	private function facts_have_value( $facts ) {
		if ( empty( $facts['facts'] ) || ! is_array( $facts['facts'] ) ) {
			return false;
		}
		foreach ( $facts['facts'] as $key => $fact ) {
			if ( ! isset( $fact['value'] ) ) {
				continue;
			}
			$value = $fact['value'];
			if ( is_array( $value ) && ! empty( $value ) ) {
				return true;
			}
			if ( ! is_array( $value ) && $value !== null && $value !== '' ) {
				return true;
			}
		}
		return false;
	}

	private function sanitize_answer_for_rewrite( $text ) {
		$text = (string) $text;
		if ( $text === '' ) {
			return $text;
		}

		$text = preg_replace( '/\[(\d+\s*,?\s*)+\]/', '', $text );
		$text = str_replace( array( '**', '__' ), '', $text );

		$lines = preg_split( '/\R/', $text );
		$out = array();
		$skip = false;
		foreach ( $lines as $line ) {
			$raw = $line;
			$line = trim( $line );
			if ( $line === '' ) {
				$skip = false;
				$out[] = $raw;
				continue;
			}

			$is_heading = preg_match( '/^#{1,6}\s+/', $line ) === 1;
			$has_ticket = preg_match( '/\b(entradas?|abonos?|tickets?|ticketing|venta|taquilla|precio|precios|euros?|€|tidd\.ly|tiquet)\b/i', $line ) === 1;

			if ( $is_heading && $has_ticket ) {
				$skip = true;
				continue;
			}
			if ( $skip && $is_heading ) {
				$skip = false;
			}
			if ( $skip ) {
				continue;
			}
			if ( $has_ticket ) {
				continue;
			}

			$out[] = $raw;
		}

		$clean = trim( implode( "\n", $out ) );
		return $clean === '' ? $text : $clean;
	}

	private function build_diffs( $festival_id, $facts ) {
		$diffs = array();

		$curr_start = get_post_meta( $festival_id, 'fecha_inicio', true );
		$curr_end = get_post_meta( $festival_id, 'fecha_fin', true );
		$curr_cancel = get_post_meta( $festival_id, 'cancelado', true );
		$curr_artists = get_post_meta( $festival_id, 'mf_artistas', true );
		$curr_lineup = get_post_meta( $festival_id, 'mf_cartel_completo', true );
		$content = (string) get_post_field( 'post_content', $festival_id );

		$edition = get_post_meta( $festival_id, 'edicion', true );

		$new_start = $this->fact_value_if_edition_matches( $facts['facts']['date_start'] ?? array(), $edition );
		$new_start = $this->format_date( $new_start );
		$new_end = $this->fact_value_if_edition_matches( $facts['facts']['date_end'] ?? array(), $edition );
		$new_end = $this->format_date( $new_end );
		$new_cancel = $this->fact_value_if_edition_matches( $facts['facts']['canceled'] ?? array(), $edition );
		$new_artists = $this->fact_value_if_edition_matches( $facts['facts']['artists'] ?? array(), $edition );
		if ( $new_artists === null && $this->fact_has_perplexity_answer_evidence( $facts['facts']['artists'] ?? array() ) ) {
			$new_artists = $facts['facts']['artists']['value'] ?? null;
		}
		$new_lineup = $this->fact_value_if_edition_matches( $facts['facts']['lineup_complete'] ?? array(), $edition );

		$curr_start_norm = $this->normalize_date_value( $curr_start );
		$curr_end_norm = $this->normalize_date_value( $curr_end );

		if ( $new_start && $new_start !== $curr_start_norm && ! $this->content_has_value( $content, $new_start ) ) {
			$diffs['fecha_inicio'] = array( 'before' => $curr_start, 'after' => $new_start );
		}
		if ( $new_end && $new_end !== $curr_end_norm && ! $this->content_has_value( $content, $new_end ) ) {
			$diffs['fecha_fin'] = array( 'before' => $curr_end, 'after' => $new_end );
		}
		if ( $new_cancel !== null && (string) $new_cancel !== (string) $curr_cancel ) {
			$diffs['cancelado'] = array( 'before' => $curr_cancel, 'after' => $new_cancel ? '1' : '0' );
		}
		if ( is_array( $new_artists ) && ! empty( $new_artists ) ) {
			$artists_str = implode( ', ', array_map( 'trim', $new_artists ) );
			$curr_artists_trim = trim( (string) $curr_artists );
			$should_update = $curr_artists_trim === '' || ! $this->artists_already_present( $new_artists, $curr_artists, $content );
			if ( $should_update && $artists_str && $artists_str !== $curr_artists ) {
				$diffs['mf_artistas'] = array( 'before' => $curr_artists, 'after' => $artists_str );
			}
		}
		if ( $new_lineup !== null && (string) $new_lineup !== (string) $curr_lineup ) {
			$diffs['mf_cartel_completo'] = array( 'before' => $curr_lineup, 'after' => $new_lineup ? '1' : '0' );
		}

		$loc_value = $this->fact_value_if_edition_matches( $facts['facts']['location'] ?? array(), $edition );
		if ( ! empty( $loc_value ) ) {
			$term = $this->find_localidad_term( $loc_value );
			if ( $term ) {
				$current_terms = get_the_terms( $festival_id, 'localidad' );
				$current_names = is_array( $current_terms ) ? wp_list_pluck( $current_terms, 'name' ) : array();
				if ( ! in_array( $term->name, $current_names, true ) ) {
					$diffs['localidad'] = array( 'before' => implode( ', ', $current_names ), 'after' => $term->name );
				}
			}
		}

		return $diffs;
	}

	private function add_source_diffs( $festival_id, $sources, $diffs ) {
		$options = get_option( MFU_OPTION_KEY, array() );
		if ( empty( $options['update_source_urls'] ) ) {
			return $diffs;
		}

		if ( ! is_array( $sources ) || empty( $sources ) ) {
			return $diffs;
		}

		$curr_web = get_post_meta( $festival_id, 'mf_web_oficial', true );
		$curr_instagram = get_post_meta( $festival_id, 'mf_instagram', true );

		$web_url = '';
		$instagram_url = '';

		foreach ( $sources as $source ) {
			if ( empty( $source['url'] ) ) {
				continue;
			}
			$url = esc_url_raw( $source['url'] );
			if ( ! $web_url && ! empty( $source['type'] ) && $source['type'] === 'web' ) {
				$web_url = $url;
			}
			if ( ! $instagram_url && ! empty( $source['type'] ) && $source['type'] === 'instagram' ) {
				$instagram_url = $url;
			}
			if ( ! $instagram_url && strpos( $url, 'instagram.com' ) !== false ) {
				$instagram_url = $url;
			}
			if ( ! $web_url && $source['type'] === 'discovered' ) {
				$web_url = $url;
			}
		}

		if ( $web_url && trim( (string) $curr_web ) === '' ) {
			$diffs['mf_web_oficial'] = array( 'before' => $curr_web, 'after' => $web_url );
		}
		if ( $instagram_url && trim( (string) $curr_instagram ) === '' ) {
			$diffs['mf_instagram'] = array( 'before' => $curr_instagram, 'after' => $instagram_url );
		}

		return $diffs;
	}

	private function fact_value_if_edition_matches( $fact, $edition ) {
		if ( ! is_array( $fact ) || ! array_key_exists( 'value', $fact ) ) {
			return null;
		}
		if ( $this->evidence_matches_edition( $fact, $edition ) ) {
			return $fact['value'];
		}
		return null;
	}

	private function fact_has_perplexity_answer_evidence( $fact ) {
		if ( ! is_array( $fact ) ) {
			return false;
		}
		$allowed = array( 'perplexity_answer', 'openai_web_search_answer' );
		$evidence = isset( $fact['evidence'] ) && is_array( $fact['evidence'] ) ? $fact['evidence'] : array();
		foreach ( $evidence as $item ) {
			if ( isset( $item['url'] ) && is_string( $item['url'] ) && in_array( $item['url'], $allowed, true ) ) {
				return true;
			}
		}
		return false;
	}

	private function fact_has_perplexity_search_evidence( $fact ) {
		if ( ! is_array( $fact ) ) {
			return false;
		}
		$evidence = isset( $fact['evidence'] ) && is_array( $fact['evidence'] ) ? $fact['evidence'] : array();
		foreach ( $evidence as $item ) {
			if ( isset( $item['url'] ) && is_string( $item['url'] ) && $item['url'] === 'perplexity_search' ) {
				return true;
			}
		}
		return false;
	}

	private function evidence_matches_edition( $fact, $edition ) {
		$edition = trim( (string) $edition );
		if ( $edition === '' ) {
			return true;
		}

		$target_year = '';
		if ( preg_match( '/\b(19|20)\d{2}\b/', $edition, $matches ) ) {
			$target_year = $matches[0];
		}
		$edition_lc = strtolower( $edition );

		$evidence = isset( $fact['evidence'] ) && is_array( $fact['evidence'] ) ? $fact['evidence'] : array();
		if ( empty( $evidence ) ) {
			if ( $target_year ) {
				$value = $fact['value'] ?? null;
				$value_text = is_array( $value ) ? implode( ' ', array_map( 'strval', $value ) ) : (string) $value;
				if ( $value_text !== '' && strpos( $value_text, $target_year ) !== false ) {
					return true;
				}
			}
			return false;
		}

		foreach ( $evidence as $item ) {
			$haystack = '';
			if ( isset( $item['snippet'] ) && is_string( $item['snippet'] ) ) {
				$haystack .= ' ' . $item['snippet'];
			}
			if ( isset( $item['url'] ) && is_string( $item['url'] ) ) {
				$haystack .= ' ' . $item['url'];
			}
			$haystack = strtolower( $haystack );

			$years_found = array();
			if ( preg_match_all( '/\b(19|20)\d{2}\b/', $haystack, $year_matches ) ) {
				$years_found = array_unique( $year_matches[0] );
			}

			// Si hay aÃ±o objetivo, la evidencia debe incluirlo.
			if ( $target_year ) {
				if ( ! empty( $years_found ) && ! in_array( $target_year, $years_found, true ) ) {
					continue;
				}
				if ( strpos( $haystack, $target_year ) !== false ) {
					return true;
				}
			}

			// Si no hay aÃ±o objetivo, exigir que aparezca la ediciÃ³n textual.
			if ( $edition_lc !== '' && strpos( $haystack, $edition_lc ) !== false ) {
				return true;
			}
		}

		return false;
	}

	private function format_date( $iso ) {
		if ( empty( $iso ) ) {
			return null;
		}
		try {
			$dt = new DateTime( $iso );
			return $dt->format( 'd/m/Y' );
		} catch ( Exception $e ) {
			return null;
		}
	}

	private function find_localidad_term( $value ) {
		$value = trim( (string) $value );
		if ( $value === '' ) {
			return null;
		}
		$slug = sanitize_title( $value );
		$term = get_term_by( 'slug', $slug, 'localidad' );
		if ( $term && ! is_wp_error( $term ) ) {
			return $term;
		}
		$term = get_term_by( 'name', $value, 'localidad' );
		if ( $term && ! is_wp_error( $term ) ) {
			return $term;
		}
		return null;
	}

	private function normalize_date_value( $value ) {
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

	private function extract_year_from_date( $value ) {
		$value = trim( (string) $value );
		if ( $value === '' ) {
			return '';
		}

		foreach ( array( 'Y-m-d', 'd/m/Y', 'Ymd' ) as $format ) {
			$dt = DateTime::createFromFormat( $format, $value );
			if ( $dt instanceof DateTime ) {
				return $dt->format( 'Y' );
			}
		}

		if ( preg_match( '/\b(19|20)\d{2}\b/', $value, $matches ) ) {
			return $matches[0];
		}

		return '';
	}

	private function existing_dates_match_edition( $start, $end, $edition ) {
		$start = trim( (string) $start );
		$end = trim( (string) $end );
		if ( $start === '' || $end === '' ) {
			return false;
		}

		$edition = trim( (string) $edition );
		$edition_year = '';
		if ( $edition !== '' && preg_match( '/\b(19|20)\d{2}\b/', $edition, $matches ) ) {
			$edition_year = $matches[0];
		}
		if ( $edition_year === '' ) {
			return true;
		}

		$start_year = $this->extract_year_from_date( $start );
		$end_year = $this->extract_year_from_date( $end );
		if ( $start_year === '' || $end_year === '' ) {
			return false;
		}

		return ( $start_year === $edition_year && $end_year === $edition_year );
	}

	private function content_has_value( $content, $value ) {
		$content = strtolower( wp_strip_all_tags( (string) $content ) );
		$value = strtolower( (string) $value );
		if ( $value === '' ) {
			return false;
		}
		return strpos( $content, $value ) !== false;
	}

	private function detect_date_conflicts( $facts_list, $edition ) {
		$starts = array();
		$ends = array();

		foreach ( $facts_list as $facts ) {
			if ( ! is_array( $facts ) || empty( $facts['facts'] ) || ! is_array( $facts['facts'] ) ) {
				continue;
			}
			$start = $this->fact_value_if_edition_matches( $facts['facts']['date_start'] ?? array(), $edition );
			$end = $this->fact_value_if_edition_matches( $facts['facts']['date_end'] ?? array(), $edition );
			$start = $this->format_date( $start );
			$end = $this->format_date( $end );
			if ( $start ) {
				$starts[] = $start;
			}
			if ( $end ) {
				$ends[] = $end;
			}
		}

		$starts = array_values( array_unique( $starts ) );
		$ends = array_values( array_unique( $ends ) );

		if ( count( $starts ) > 1 || count( $ends ) > 1 ) {
			return array(
				'starts' => $starts,
				'ends' => $ends,
			);
		}

		return null;
	}

	private function dates_are_confirmed( $merged, $edition, $date_conflicts ) {
		if ( $date_conflicts ) {
			return false;
		}
		$start = $this->fact_value_if_edition_matches( $merged['facts']['date_start'] ?? array(), $edition );
		$end = $this->fact_value_if_edition_matches( $merged['facts']['date_end'] ?? array(), $edition );
		if ( empty( $start ) || empty( $end ) ) {
			return false;
		}
		return true;
	}

	private function artists_already_present( $artists, $curr_artists, $content ) {
		if ( empty( $artists ) ) {
			return true;
		}
		$content = strtolower( wp_strip_all_tags( (string) $content ) );
		$curr = strtolower( (string) $curr_artists );
		foreach ( $artists as $artist ) {
			$name = strtolower( trim( (string) $artist ) );
			if ( $name === '' ) {
				continue;
			}
			if ( strpos( $curr, $name ) === false && strpos( $content, $name ) === false ) {
				return false;
			}
		}
		return true;
	}

	private function filter_internal_sources( $sources ) {
		if ( ! is_array( $sources ) ) {
			return $sources;
		}
		$home = wp_parse_url( home_url() );
		$host = isset( $home['host'] ) ? strtolower( $home['host'] ) : '';
		if ( $host === '' ) {
			return $sources;
		}
		$filtered = array();
		foreach ( $sources as $source ) {
			$url = isset( $source['url'] ) ? $source['url'] : '';
			$parsed = wp_parse_url( $url );
			$src_host = isset( $parsed['host'] ) ? strtolower( $parsed['host'] ) : '';
			if ( $src_host && ( $src_host === $host || substr( $src_host, -strlen( '.' . $host ) ) === '.' . $host ) ) {
				continue;
			}
			$filtered[] = $source;
		}
		return $filtered;
	}

	private function store_update( $festival_id, $status, $diffs, $facts, $evidence, $errors = array(), $verification = null, $extra = array() ) {
		global $wpdb;
		$table = MFU_DB::table( 'updates' );
		$payload = array(
			'facts' => $facts,
			'sources' => $evidence,
			'errors' => is_array( $errors ) ? $errors : array(),
		);
		if ( is_array( $extra ) && ! empty( $extra ) ) {
			$payload = array_merge( $payload, $extra );
		}
		if ( $verification ) {
			$payload['verification'] = $verification;
		}

		$wpdb->insert(
			$table,
			array(
				'festival_id' => (int) $festival_id,
				'detected_at' => current_time( 'mysql' ),
				'status' => $status,
				'diffs_json' => wp_json_encode( $diffs ),
				'evidence_json' => wp_json_encode( $payload ),
				'summary' => isset( $facts['summary'] ) ? $facts['summary'] : '',
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		return $wpdb->insert_id;
	}

	public static function get_latest_update( $festival_id ) {
		global $wpdb;
		$table = MFU_DB::table( 'updates' );
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE festival_id=%d ORDER BY id DESC LIMIT 1",
			$festival_id
		) );
	}
}

