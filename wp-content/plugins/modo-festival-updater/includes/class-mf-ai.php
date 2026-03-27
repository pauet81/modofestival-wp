<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MFU_AI {
	private $openai_key;
	private $pplx_key;
	private $model_extract;
	private $model_write;
	private $provider;
	private $base_url_openai;
	private $base_url_perplexity;
	private $http_timeout;

	public function __construct() {
		$settings = get_option( MFU_OPTION_KEY, array() );
		$this->pplx_key = isset( $settings['pplx_api_key'] ) ? trim( (string) $settings['pplx_api_key'] ) : '';
		$this->openai_key = isset( $settings['api_key'] ) ? trim( (string) $settings['api_key'] ) : '';
		$legacy_provider = isset( $settings['ai_provider'] ) ? $settings['ai_provider'] : '';
		$this->provider = $legacy_provider ? $legacy_provider : 'openai';
		$this->model_extract = isset( $settings['model_extract'] ) ? $settings['model_extract'] : 'sonar';
		$this->model_write = isset( $settings['model_write'] ) ? $settings['model_write'] : 'sonar-pro';
		$this->base_url_openai = 'https://api.openai.com/v1/responses';
		$this->base_url_perplexity = 'https://api.perplexity.ai/chat/completions';
		$this->http_timeout = isset( $settings['timeout'] ) ? max( 5, (int) $settings['timeout'] ) : 30;
	}

	public function has_key() {
		return $this->openai_key !== '';
	}

	public function has_openai_key() {
		return $this->openai_key !== '';
	}

	public function has_perplexity_key() {
		return $this->pplx_key !== '';
	}


	public function supports_web_search() {
		return $this->openai_key !== '';
	}

	private function request( $model, $messages, $schema = null, $temperature = 0.2, $tools = null, $action = 'unknown' ) {
		$provider = $this->select_provider( $action );
		$api_key = $this->get_key_for_provider( $provider );
		if ( $api_key === '' ) {
			return new WP_Error( 'mfu_no_key', 'API key missing' );
		}
		$model = $this->normalize_model_for_provider( $model, $provider, $action );

		$body = array(
			'model' => $model,
		);
		if ( $provider === 'perplexity' ) {
			$body['temperature'] = $temperature;
		}

		if ( $provider === 'perplexity' ) {
			$body['messages'] = $this->normalize_messages_for_chat( $messages );
		} else {
			$body['input'] = $this->normalize_messages_for_responses( $messages );
		}

		if ( $schema && $provider !== 'perplexity' ) {
			$schema_name = isset( $schema['name'] ) ? $schema['name'] : 'mfu_schema';
			$schema_body = isset( $schema['schema'] ) && is_array( $schema['schema'] ) ? $schema['schema'] : $schema;
			$body['text'] = array(
				'format' => array(
					'type' => 'json_schema',
					'name' => $schema_name,
					'schema' => $schema_body,
					'strict' => true,
				),
			);
		}
		if ( $tools && $provider !== 'perplexity' ) {
			$body['tools'] = $tools;
		}

		$args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type' => 'application/json',
			),
			'body' => wp_json_encode( $body ),
			'timeout' => $this->http_timeout,
		);

		$base_url = $provider === 'perplexity' ? $this->base_url_perplexity : $this->base_url_openai;
		$response = wp_remote_post( $base_url, $args );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$raw = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );
		if ( $code < 200 || $code >= 300 ) {
			$message = 'AI request failed';
			if ( is_array( $data ) && isset( $data['error']['message'] ) ) {
				$message .= ': ' . $data['error']['message'];
			}
			return new WP_Error( 'mfu_ai_http', $message, array( 'status' => $code, 'body' => $raw ) );
		}

		$text = $this->extract_text_from_response( $data );

		if ( ! $text ) {
			$snippet = $raw ? substr( $raw, 0, 400 ) : '';
			return new WP_Error( 'mfu_ai_no_text', 'No output text from API' . ( $snippet ? ' (' . $snippet . ')' : '' ) );
		}

		$this->log_usage( $model, $messages, $text, $action, $provider );

		return $text;
	}

			private function select_provider( $action ) {
				$action = (string) $action;
					$write_actions = array(
						'write_news',
						'rewrite_content',
						'rewrite_content_answer',
						'rewrite_content_seo',
						'rewrite_press_release',
						'rewrite_external_news',
						'press_release_festival_update',
						'external_news_festival_update',
						'prefill_localidad_fechas',
						'verify_update',
						'verify_update_web_search',
						'verify_content_strict',
						'compare_sources',
			'generate_headlines',
			'generate_headlines_by_change',
			'identify_festival',
			'extract_facts',
			'discover_sources',
		);

		if ( in_array( $action, $write_actions, true ) ) {
			return $this->openai_key !== '' ? 'openai' : ( $this->pplx_key !== '' ? 'perplexity' : $this->provider );
		}

		return $this->provider;
	}

	private function get_key_for_provider( $provider ) {
		return $provider === 'perplexity' ? $this->pplx_key : $this->openai_key;
	}

		private function normalize_model_for_provider( $model, $provider, $action ) {
			$model = trim( (string) $model );
			$action = (string) $action;
			$is_extract = in_array( $action, array( 'identify_festival', 'extract_facts', 'verify_update', 'verify_content_strict', 'discover_sources', 'prefill_localidad_fechas' ), true );

		if ( $provider === 'openai' ) {
			if ( $model === '' || strpos( $model, 'sonar' ) === 0 ) {
				return $is_extract ? 'gpt-4o-mini' : 'gpt-4o';
			}
			return $model;
		}

		if ( $model === '' || strpos( $model, 'gpt-' ) === 0 || strpos( $model, 'o' ) === 0 ) {
			return $is_extract ? 'sonar' : 'sonar-pro';
		}

		return $model;
	}

	private function normalize_messages_for_chat( $messages ) {
		$normalized = array();
		foreach ( $messages as $msg ) {
			$role = isset( $msg['role'] ) ? $msg['role'] : 'user';
			$content = '';
			if ( isset( $msg['content'] ) && is_array( $msg['content'] ) ) {
				$parts = array();
				foreach ( $msg['content'] as $part ) {
					if ( isset( $part['text'] ) && is_string( $part['text'] ) ) {
						$parts[] = $part['text'];
					}
				}
				$content = implode( "\n", $parts );
			} elseif ( isset( $msg['content'] ) && is_string( $msg['content'] ) ) {
				$content = $msg['content'];
			}
			$normalized[] = array(
				'role' => $role,
				'content' => $content,
			);
		}
		return $normalized;
	}

	private function normalize_messages_for_responses( $messages ) {
		$normalized = array();
		foreach ( $messages as $msg ) {
			$role = isset( $msg['role'] ) ? $msg['role'] : 'user';
			$content = array();
			if ( isset( $msg['content'] ) && is_array( $msg['content'] ) ) {
				foreach ( $msg['content'] as $part ) {
					if ( isset( $part['text'] ) && is_string( $part['text'] ) ) {
						$content[] = array(
							'type' => 'input_text',
							'text' => $part['text'],
						);
					}
				}
			} elseif ( isset( $msg['content'] ) && is_string( $msg['content'] ) ) {
				$content[] = array(
					'type' => 'input_text',
					'text' => $msg['content'],
				);
			}
			$normalized[] = array(
				'role' => $role,
				'content' => $content,
			);
		}
		return $normalized;
	}

			private function decode_json_from_text( $text ) {
				if ( ! is_string( $text ) ) {
					return null;
				}

			$trimmed = trim( $text );
			if ( $trimmed === '' ) {
				return null;
			}

			$decoded = json_decode( $trimmed, true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}

			if ( preg_match( '/```(?:json)?\\s*(\\{.*\\})\\s*```/s', $trimmed, $matches ) ) {
				$decoded = json_decode( $matches[1], true );
				if ( is_array( $decoded ) ) {
					return $decoded;
				}
			}

			$start = strpos( $trimmed, '{' );
			$end = strrpos( $trimmed, '}' );
			if ( $start !== false && $end !== false && $end > $start ) {
				$candidate = substr( $trimmed, $start, $end - $start + 1 );
				$decoded = json_decode( $candidate, true );
				if ( is_array( $decoded ) ) {
					return $decoded;
				}
			}

				return null;
			}

			public function rewrite_external_news_to_post( $source_url, $article_text, $style_catalog = array(), $internal_links = array(), $original_title = '' ) {
				$source_url = trim( (string) $source_url );
				$article_text = is_string( $article_text ) ? trim( $article_text ) : '';
				$original_title = is_string( $original_title ) ? trim( $original_title ) : '';

				if ( $source_url === '' || $article_text === '' ) {
					return new WP_Error( 'mfu_external_news_missing', 'Source URL or article text missing' );
				}

				$internal_hint = '';
				if ( is_array( $internal_links ) && ! empty( $internal_links ) ) {
					$lines = array();
					foreach ( $internal_links as $label => $url ) {
						$label = trim( (string) $label );
						$url = trim( (string) $url );
						if ( $label !== '' && $url !== '' ) {
							$lines[] = "- {$label}: {$url}";
						}
					}
						if ( ! empty( $lines ) ) {
							$internal_hint = "Enlaces internos permitidos (usa 1-3 si encaja, sin forzar):\n" . implode( "\n", $lines );
						}
					}

				$styles_hint = "Estilos musicales permitidos (slugs): country, dance, electronica, experimental, flamenco, folk, funk, indie, indiea, jazz, metal, pop, punk, reggaeton, rock, techno, urbana.\n"
					. "Devuelve 1-3 estilos relevantes en style_slugs.\n";
				$style_links_hint = '';
				if ( is_array( $style_catalog ) && ! empty( $style_catalog ) ) {
					$lines = array();
					foreach ( $style_catalog as $slug => $url ) {
						$slug = sanitize_title( (string) $slug );
						$url = trim( (string) $url );
						if ( $slug !== '' && $url !== '' ) {
							$lines[] = "- {$slug}: {$url}";
						}
					}
						if ( ! empty( $lines ) ) {
							$style_links_hint = "Enlaces internos de estilos (si encaja, enlaza a 1 estilo con su URL real):\n" . implode( "\n", $lines );
						}
					}

				$system = "Eres un redactor SEO para un medio musical. Tu tarea es reescribir una noticia (texto ya extraido o pegado) para publicarla como articulo 100% original.\n"
					. "Reglas:\n"
					. "- No copies frases ni estructuras literales del texto original.\n"
					. "- El contenido debe empezar con un parrafo (no empieces con un H2).\n"
					. "- No incluyas H1 (el H1 lo pone el titulo del post). Usa H2/H3.\n"
					. "- No incluyas un parrafo de resumen etiquetado como \\\"Resumen\\\".\n"
					. "- Usa negritas para nombres propios relevantes (artistas, festival, ciudad, recinto, fechas).\n"
					. "- Evita un tono corporativo (no uses \\\"segun el comunicado\\\", \\\"proximos pasos\\\", etc.).\n"
					. "- Nunca menciones ni cites el medio/fuente original. No incluyas enlaces externos.\n"
					. "- Si se mencionan artistas, intenta incluirlos en un listado (bullets) para facilitar lectura.\n"
					. "- Incluye exactamente 2 enlaces internos contextuales (sin forzar):\n"
					. "  - 1 enlace a la agenda.\n"
					. "  - 1 enlace a un estilo musical relevante de los que te doy.\n"
					. "- Tono periodistico, natural, sin repeticion.\n"
					. "- Minimo 600 palabras (objetivo 650-900). Si faltan datos concretos, amplia con contexto evergreen util sin inventar hechos.\n";
				if ( $internal_hint !== '' ) {
					$system .= "\n" . $internal_hint . "\n";
				}
				if ( $style_links_hint !== '' ) {
					$system .= "\n" . $style_links_hint . "\n";
				}
				$system .= "\n" . $styles_hint;

				$schema = array(
					'name' => 'mfu_external_news_post',
					'schema' => array(
						'type' => 'object',
						'additionalProperties' => false,
						'required' => array( 'title', 'slug', 'excerpt', 'content_html', 'yoast_title', 'yoast_metadesc', 'focus_keyphrase', 'style_slugs' ),
						'properties' => array(
							'title' => array( 'type' => 'string' ),
							'slug' => array( 'type' => 'string' ),
							'excerpt' => array( 'type' => 'string' ),
							'content_html' => array( 'type' => 'string' ),
							'yoast_title' => array( 'type' => 'string' ),
							'yoast_metadesc' => array( 'type' => 'string' ),
							'focus_keyphrase' => array( 'type' => 'string' ),
							'style_slugs' => array(
								'type' => 'array',
								'items' => array( 'type' => 'string' ),
							),
						),
					),
				);

				$title_hint = $original_title !== '' ? "TITULAR ORIGINAL (referencia, reescribe sin calcar): {$original_title}\n\n" : '';
				$user = "URL ORIGINAL (solo referencia interna, NO la menciones ni enlaces): {$source_url}\n\n"
					. $title_hint
					. "NOTICIA ORIGINAL (texto extraido o pegado):\n\n{$article_text}\n";

				$messages = array(
					array( 'role' => 'system', 'content' => array( array( 'type' => 'text', 'text' => $system ) ) ),
					array( 'role' => 'user', 'content' => array( array( 'type' => 'text', 'text' => $user ) ) ),
				);

				$text = $this->request( $this->model_write, $messages, $schema, 0.2, null, 'rewrite_external_news' );
				if ( is_wp_error( $text ) ) {
					return $text;
				}

				$decoded = $this->decode_json_from_text( $text );
				if ( ! is_array( $decoded ) ) {
					return new WP_Error( 'mfu_external_news_bad_json', 'Invalid JSON from AI' );
				}
				return $decoded;
			}

			public function external_news_festival_update_payload( $festival_name, $edition, $current_content, $current_fields, $source_url, $article_text ) {
				$festival_name = trim( (string) $festival_name );
				$edition = trim( (string) $edition );
				$current_content = is_string( $current_content ) ? $current_content : '';
				$current_fields = is_array( $current_fields ) ? $current_fields : array();
				$source_url = trim( (string) $source_url );
				$article_text = is_string( $article_text ) ? trim( $article_text ) : '';

				if ( $festival_name === '' || $source_url === '' || $article_text === '' ) {
					return new WP_Error( 'mfu_external_news_missing', 'Festival name, source URL or article text missing' );
				}

				$system = "Eres un editor de fichas de festival. A partir de una noticia externa (normalmente veraz), propone actualizaciones para la ficha del festival.\n"
					. "Reglas:\n"
					. "- No inventes datos. Si un dato no aparece claramente en el texto, devuelve null para ese campo.\n"
					. "- No incluyas H1 en el contenido (usa H2/H3).\n"
					. "- Nombres de artistas en negrita.\n"
					. "- Evita frases tipo \"segun el comunicado\" o metacomentarios sobre versiones previas.\n"
					. "- Fechas: devuelve fecha_inicio/fecha_fin en formato YYYYMMDD.\n"
					. "- mf_artistas: devuelve una lista en una sola linea separada por comas.\n";

				$schema = array(
					'name' => 'mfu_external_news_festival_update',
					'schema' => array(
						'type' => 'object',
						'additionalProperties' => false,
						'required' => array( 'summary', 'fields', 'updated_content_html' ),
						'properties' => array(
							'summary' => array( 'type' => 'string' ),
							'fields' => array(
								'type' => 'object',
								'additionalProperties' => false,
								'required' => array(
									'fecha_inicio',
									'fecha_fin',
									'mf_artistas',
									'mf_web_oficial',
									'mf_instagram',
									'mf_cartel_completo',
									'cancelado',
									'sin_fechas_confirmadas',
								),
								'properties' => array(
									'fecha_inicio' => array( 'type' => array( 'string', 'null' ) ),
									'fecha_fin' => array( 'type' => array( 'string', 'null' ) ),
									'mf_artistas' => array( 'type' => array( 'string', 'null' ) ),
									'mf_web_oficial' => array( 'type' => array( 'string', 'null' ) ),
									'mf_instagram' => array( 'type' => array( 'string', 'null' ) ),
									'mf_cartel_completo' => array( 'type' => array( 'string', 'null' ) ),
									'cancelado' => array( 'type' => array( 'string', 'null' ) ),
									'sin_fechas_confirmadas' => array( 'type' => array( 'string', 'null' ) ),
								),
							),
							'updated_content_html' => array( 'type' => 'string' ),
						),
					),
				);

				$current_fields_lines = array();
				foreach ( $current_fields as $k => $v ) {
					if ( is_scalar( $v ) ) {
						$current_fields_lines[] = $k . ': ' . (string) $v;
					}
				}
				$current_fields_text = implode( "\n", $current_fields_lines );
				$edition_note = $edition !== '' ? "Edicion objetivo: {$edition}." : '';
				$user = "FESTIVAL: {$festival_name}\n{$edition_note}\n\nFUENTE: {$source_url}\n\nCAMPOS ACTUALES (referencia):\n{$current_fields_text}\n\nCONTENIDO ACTUAL (HTML):\n{$current_content}\n\nNOTICIA EXTERNA (texto):\n{$article_text}";

				$messages = array(
					array( 'role' => 'system', 'content' => array( array( 'type' => 'text', 'text' => $system ) ) ),
					array( 'role' => 'user', 'content' => array( array( 'type' => 'text', 'text' => $user ) ) ),
				);

				$text = $this->request( $this->model_write, $messages, $schema, 0.2, null, 'external_news_festival_update' );
				if ( is_wp_error( $text ) ) {
					return $text;
				}
				$decoded = $this->decode_json_from_text( $text );
				if ( ! is_array( $decoded ) ) {
					return new WP_Error( 'mfu_external_news_festi_bad_json', 'Invalid JSON from AI' );
				}
				return $decoded;
			}

			public function rewrite_press_release_to_post( $press_text, $internal_links = array(), $original_title = '' ) {
				$press_text = is_string( $press_text ) ? trim( $press_text ) : '';
				if ( $press_text === '' ) {
					return new WP_Error( 'mfu_press_empty', 'Press release text is empty' );
			}
			$original_title = is_string( $original_title ) ? trim( $original_title ) : '';

			$links_hint = '';
			if ( is_array( $internal_links ) && ! empty( $internal_links ) ) {
				$lines = array();
				foreach ( $internal_links as $label => $url ) {
					$label = trim( (string) $label );
					$url = trim( (string) $url );
					if ( $label !== '' && $url !== '' ) {
						$lines[] = "- {$label}: {$url}";
					}
				}
				if ( ! empty( $lines ) ) {
					$links_hint = "Enlaces internos permitidos (usa solo si encaja, sin forzar):\n" . implode( "\n", $lines );
				}
			}

			$system = "Eres un redactor SEO para un medio musical. Tu tarea es reescribir una nota de prensa para publicarla como articulo 100% original.\n"
				. "Reglas:\n"
				. "- No copies frases ni estructuras literales del texto original.\n"
				. "- Mantén el titular muy cercano al original (cambia solo lo imprescindible para hacerlo unico).\n"
				. "- No incluyas H1 (el H1 lo pone el titulo del post). Usa H2/H3.\n"
				. "- No incluyas un parrafo de resumen etiquetado como \"Resumen\".\n"
				. "- Usa negritas para nombres propios relevantes (artistas, festival, ciudad, recinto, fechas).\n"
				. "- Evita un tono corporativo; escribe con tono periodistico.\n"
				. "- No incluyas enlaces externos.\n"
				. "- Si aportas contexto, que sea util y no repetitivo.\n";
			if ( $links_hint !== '' ) {
				$system .= "\n" . $links_hint . "\n";
			}
			$system .= "\nEstilos musicales permitidos (slugs): country, dance, electronica, experimental, flamenco, folk, funk, indie, jazz, metal, pop, punk, reggaeton, rock, techno, urbana.\n"
				. "Devuelve 1-3 estilos relevantes en style_slugs.\n";

			$schema = array(
				'name' => 'mfu_press_release_post',
				'schema' => array(
					'type' => 'object',
					'additionalProperties' => false,
					// OpenAI strict JSON schema requires all properties to be listed in `required`.
					// Use empty strings when a value is not applicable.
					'required' => array( 'title', 'excerpt', 'content_html', 'yoast_title', 'yoast_metadesc', 'focus_keyphrase', 'style_slugs' ),
					'properties' => array(
						'title' => array( 'type' => 'string' ),
						'excerpt' => array( 'type' => 'string' ),
						'content_html' => array( 'type' => 'string' ),
						'yoast_title' => array( 'type' => 'string' ),
						'yoast_metadesc' => array( 'type' => 'string' ),
						'focus_keyphrase' => array( 'type' => 'string' ),
						'style_slugs' => array(
							'type' => 'array',
							'items' => array( 'type' => 'string' ),
						),
					),
				),
			);

			$title_hint = $original_title !== '' ? "TITULAR ORIGINAL (referencia): {$original_title}\n\n" : '';
			$messages = array(
				array( 'role' => 'system', 'content' => array( array( 'type' => 'text', 'text' => $system ) ) ),
				array( 'role' => 'user', 'content' => array( array( 'type' => 'text', 'text' => $title_hint . "NOTA DE PRENSA (texto original):\n\n" . $press_text ) ) ),
			);

			$text = $this->request( $this->model_write, $messages, $schema, 0.2, null, 'rewrite_press_release' );
			if ( is_wp_error( $text ) ) {
				return $text;
			}

			$decoded = $this->decode_json_from_text( $text );
			if ( ! is_array( $decoded ) ) {
				return new WP_Error( 'mfu_press_bad_json', 'Invalid JSON from AI' );
			}
			return $decoded;
		}

		public function press_release_festival_update_payload( $festival_name, $edition, $current_content, $current_fields, $press_text ) {
			$festival_name = trim( (string) $festival_name );
			$edition = trim( (string) $edition );
			$press_text = is_string( $press_text ) ? trim( $press_text ) : '';
			$current_content = is_string( $current_content ) ? $current_content : '';
			$current_fields = is_array( $current_fields ) ? $current_fields : array();

			if ( $festival_name === '' || $press_text === '' ) {
				return new WP_Error( 'mfu_press_missing', 'Festival name or press release text missing' );
			}

			$system = "Eres un editor de fichas de festival. A partir de una nota de prensa (siempre veraz), propone actualizaciones para la ficha del festival.\n"
				. "Reglas:\n"
				. "- No inventes datos. Si un dato no aparece en la nota, devuelve null para ese campo.\n"
				. "- No incluyas H1 en el contenido (usa H2/H3).\n"
				. "- Nombres de artistas en negrita.\n"
				. "- Evita frases tipo \"segun el comunicado\" o metacomentarios sobre versiones previas.\n"
				. "- Fechas: devuelve fecha_inicio/fecha_fin en formato YYYYMMDD.\n"
				. "- mf_artistas: devuelve una lista en una sola linea separada por comas.\n";

			$schema = array(
				'name' => 'mfu_press_release_festival_update',
				'schema' => array(
					'type' => 'object',
					'additionalProperties' => false,
					'required' => array( 'summary', 'fields', 'updated_content_html' ),
					'properties' => array(
						'summary' => array( 'type' => 'string' ),
						'fields' => array(
							'type' => 'object',
							'additionalProperties' => false,
							// OpenAI strict JSON schema requires all properties to be listed in `required` at this level.
							// Use null when a value is not applicable or not present in the press release.
							'required' => array(
								'fecha_inicio',
								'fecha_fin',
								'mf_artistas',
								'mf_web_oficial',
								'mf_instagram',
								'mf_cartel_completo',
								'cancelado',
								'sin_fechas_confirmadas',
							),
							'properties' => array(
								'fecha_inicio' => array( 'type' => array( 'string', 'null' ) ),
								'fecha_fin' => array( 'type' => array( 'string', 'null' ) ),
								'mf_artistas' => array( 'type' => array( 'string', 'null' ) ),
								'mf_web_oficial' => array( 'type' => array( 'string', 'null' ) ),
								'mf_instagram' => array( 'type' => array( 'string', 'null' ) ),
								'mf_cartel_completo' => array( 'type' => array( 'string', 'null' ) ),
								'cancelado' => array( 'type' => array( 'string', 'null' ) ),
								'sin_fechas_confirmadas' => array( 'type' => array( 'string', 'null' ) ),
							),
						),
						'updated_content_html' => array( 'type' => 'string' ),
					),
				),
			);

			$current_fields_lines = array();
			foreach ( $current_fields as $k => $v ) {
				if ( is_scalar( $v ) ) {
					$current_fields_lines[] = $k . ': ' . (string) $v;
				}
			}
			$current_fields_text = implode( "\n", $current_fields_lines );

			$edition_note = $edition !== '' ? "Edicion objetivo: {$edition}." : '';
			$user = "FESTIVAL: {$festival_name}\n{$edition_note}\n\nCAMPOS ACTUALES (referencia):\n{$current_fields_text}\n\nCONTENIDO ACTUAL (HTML):\n{$current_content}\n\nNOTA DE PRENSA:\n{$press_text}";

			$messages = array(
				array( 'role' => 'system', 'content' => array( array( 'type' => 'text', 'text' => $system ) ) ),
				array( 'role' => 'user', 'content' => array( array( 'type' => 'text', 'text' => $user ) ) ),
			);

			$text = $this->request( $this->model_write, $messages, $schema, 0.2, null, 'press_release_festival_update' );
			if ( is_wp_error( $text ) ) {
				return $text;
			}
			$decoded = $this->decode_json_from_text( $text );
			if ( ! is_array( $decoded ) ) {
				return new WP_Error( 'mfu_press_festi_bad_json', 'Invalid JSON from AI' );
			}
			return $decoded;
		}

			public function extract_facts( $festival_name, $source_text, $source_url, $edition = '', $allow_web_search = true ) {
			$edition = trim( (string) $edition );
			$edition_note = $edition ? "Edicion objetivo: {$edition}. Solo acepta hechos que correspondan a esa edicion o al mismo anio." : "No se indica edicion; no asumas anio.";
		$system = "Eres un analista editorial. Extrae solo hechos verificables del texto. No inventes nada. {$edition_note} "
			. "Si el texto referencia explicitamente otra edicion o anio distinto, ignora esos datos. "
			. "Fechas: si hay rango (\"del X al Y\"), rellena date_start y date_end. Si hay una sola fecha, usa la misma para ambos. "
			. "Si el texto no indica anio pero menciona la edicion (p. ej. 2026), usa ese anio para normalizar. Si no hay anio, deja null. "
			. "Normaliza fechas a formato YYYY-MM-DD. Si no hay evidencia, deja el valor en null. "
			. "Responde solo con JSON valido, sin Markdown ni texto adicional.";

		$schema_hint = "Formato JSON requerido (ejemplo de estructura, respeta claves):\n"
			. "{\n"
			. "  \"summary\": \"...\",\n"
			. "  \"facts\": {\n"
			. "    \"date_start\": {\"value\": \"YYYY-MM-DD\", \"evidence\": [\"...\"]},\n"
			. "    \"date_end\": {\"value\": \"YYYY-MM-DD\", \"evidence\": [\"...\"]},\n"
			. "    \"location\": {\"value\": \"...\", \"evidence\": [\"...\"]},\n"
			. "    \"tickets_url\": {\"value\": \"...\", \"evidence\": [\"...\"]},\n"
			. "    \"artists\": {\"value\": [\"...\"], \"evidence\": [\"...\"]},\n"
			. "    \"lineup_complete\": {\"value\": true, \"evidence\": [\"...\"]},\n"
			. "    \"canceled\": {\"value\": false, \"evidence\": [\"...\"]},\n"
			. "    \"notes\": \"...\"\n"
			. "  }\n"
			. "}\n";

		$user = "Festival: {$festival_name}\nEdicion: {$edition}\nFuente: {$source_url}\n\nTexto:\n{$source_text}\n\nDevuelve JSON con hechos y evidencia por cada hecho.\n\n{$schema_hint}";

		$schema = array(
			'name' => 'festival_update',
			'strict' => true,
			'schema' => array(
				'type' => 'object',
				'additionalProperties' => false,
				'properties' => array(
					'summary' => array( 'type' => 'string' ),
					'facts' => array(
						'type' => 'object',
						'additionalProperties' => false,
						'properties' => array(
							'date_start' => $this->fact_schema(),
							'date_end' => $this->fact_schema(),
							'location' => $this->fact_schema(),
							'tickets_url' => $this->fact_schema(),
							'artists' => array(
								'type' => 'object',
								'additionalProperties' => false,
								'properties' => array(
									'value' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
									'evidence' => $this->evidence_schema(),
								),
								'required' => array( 'value', 'evidence' ),
							),
							'lineup_complete' => $this->fact_schema( array( 'type' => array( 'boolean', 'null' ) ) ),
							'canceled' => $this->fact_schema( array( 'type' => array( 'boolean', 'null' ) ) ),
							'notes' => array( 'type' => 'string' ),
						),
						'required' => array( 'date_start', 'date_end', 'location', 'tickets_url', 'artists', 'lineup_complete', 'canceled', 'notes' ),
					),
				),
				'required' => array( 'summary', 'facts' ),
			),
		);

		$messages = array(
			array( 'role' => 'system', 'content' => array( array( 'type' => 'text', 'text' => $system ) ) ),
			array( 'role' => 'user', 'content' => array( array( 'type' => 'text', 'text' => $user ) ) ),
		);

			$tools = ( $allow_web_search && $this->supports_web_search() ) ? array( array( 'type' => 'web_search' ) ) : null;
		$text = $this->request( $this->model_extract, $messages, $schema, 0.1, $tools, 'extract_facts' );
		if ( is_wp_error( $text ) ) {
			return $text;
		}

		$decoded = $this->decode_json_from_text( $text );
		if ( ! is_array( $decoded ) ) {
			return new WP_Error( 'mfu_ai_json', 'Invalid JSON from AI' );
		}

		return $decoded;
	}

	public function identify_festival_from_news( $news_text, $url = '' ) {
		$news_text = trim( (string) $news_text );
		$url = trim( (string) $url );

		$system = "Eres analista editorial. Identifica el festival del que habla la noticia y, si aparece, su edicion. "
			. "Usa el nombre oficial del festival (no el medio). Si no es posible identificarlo, devuelve festival_name vacio y confidence 0.";
		$user = "URL: {$url}\n\nTexto:\n{$news_text}";

		$schema = array(
			'name' => 'festival_identification',
			'strict' => true,
			'schema' => array(
				'type' => 'object',
				'additionalProperties' => false,
				'properties' => array(
					'festival_name' => array( 'type' => 'string' ),
					'edition' => array( 'type' => 'string' ),
					'confidence' => array( 'type' => 'number' ),
				),
				'required' => array( 'festival_name', 'edition', 'confidence' ),
			),
		);

		$messages = array(
			array( 'role' => 'system', 'content' => array( array( 'type' => 'text', 'text' => $system ) ) ),
			array( 'role' => 'user', 'content' => array( array( 'type' => 'text', 'text' => $user ) ) ),
		);

		$text = $this->request( $this->model_extract, $messages, $schema, 0.2, null, 'identify_festival' );
		if ( is_wp_error( $text ) ) {
			return $text;
		}

		$decoded = $this->decode_json_from_text( $text );
		if ( ! is_array( $decoded ) ) {
			return new WP_Error( 'mfu_ai_json', 'Invalid JSON from AI' );
		}

		return $decoded;
	}

	public function write_news( $festival_name, $festival_url, $edition, $change_type, $added_artists, $date_start, $date_end, $other_changes, $evidence_list ) {
		$festival_name = trim( (string) $festival_name );
		$edition = trim( (string) $edition );
		$change_type = trim( (string) $change_type );
		$added_artists = is_array( $added_artists ) ? $added_artists : array();
		$other_changes = is_array( $other_changes ) ? $other_changes : array();

			$system = 'Eres redactor periodistico SEO senior para Google Discover. Redacta una noticia editorial, informativa y clara, sin opiniones ni promociones. Solo hechos verificados. '
				. 'Longitud obligatoria: minimo 600 palabras reales en el contenido final. '
				. 'No incluyas enlaces ni menciones a compra de entradas, tickets o tiqueteras. '
				. 'No cites medios ni fuentes concretas. '
				. 'Optimiza SEO on-page: menciona el festival y la edicion en el titulo y en el primer parrafo, usa subtitulos H2/H3 descriptivos, parrafos cortos y buena escaneabilidad. '
				. 'Incluye al menos un enlace interno a la ficha del festival usando la URL proporcionada. '
				. 'Enfoca el contenido a Discover con lenguaje informativo, actual y preciso. '
				. 'No menciones IA, automatizaciones, ni campos internos como mf_artistas, sin_fechas_confirmadas, content, etc. '
				. 'Evita frases vagas o genericas como "estado del cartel", "proximos anuncios" o "habra nuevas incorporaciones" si no estan verificadas. '
				. 'Foco editorial obligatorio: prioriza y desarrolla el tipo de cambio detectado (change_type) como eje principal del texto; no diluyas el tema en contenido accesorio. '
				. 'Si el cambio es de cartel, debes decir explicitamente que se actualiza/anuncia el cartel y nombrar los artistas nuevos (1-4). '
				. 'Si el cambio son fechas, debes decir que se anuncian fechas y desarrollar su contexto practico. '
				. 'La estructura NO debe ser fija ni repetitiva entre noticias: varia enfoque, subtitulos y orden narrativo segun el caso, manteniendo siempre claridad SEO. '
				. 'No repitas el mismo titular o la misma frase en el cuerpo. '
				. 'Prohibido usar titulares o enfoques genericos tipo "Ficha actualizada", "Informacion actualizada", "Novedades en la ficha", "Cambios en la ficha" o equivalentes. '
				. 'El titular y el primer parrafo deben nombrar de forma concreta el cambio detectado (artistas, fechas, ubicacion, cancelacion, etc.). '
				. 'Nunca incluyas parrafos de cierre tipo resumen (por ejemplo: "En resumen", "Resumen", "Conclusiones", "Para cerrar", "En sintesis"). Cierra con informacion concreta y nueva, no con recapitulaciones. '
				. 'Usa negritas de forma editorial y moderada: solo para terminos clave puntuales (nombres propios, fechas o conceptos clave), nunca para frases completas, listas enteras o parrafos completos. '
				. 'Devuelve tambien meta_title (<= 60 caracteres) y meta_description (140-160 caracteres).';

		$artists_text = $added_artists ? implode( ', ', $added_artists ) : '';
		$other_text = $other_changes ? implode( '; ', $other_changes ) : '';
		$evidence_text = implode( "\n", $evidence_list );

			$user = "Festival: {$festival_name}\nEdicion: {$edition}\nURL: {$festival_url}\n"
				. "Tipo de cambio: {$change_type}\n"
				. "Artistas nuevos (si aplica): {$artists_text}\n"
				. "Fechas (si aplica): inicio={$date_start}, fin={$date_end}\n"
				. "Otros cambios relevantes: {$other_text}\n"
				. "Evidencia:\n{$evidence_text}\n\n"
				. "Requisitos:\n"
				. "- El contenido debe tener minimo 600 palabras.\n"
				. "- El enfoque principal debe centrarse en el tipo de cambio detectado.\n"
				. "- Estructura SEO robusta con H2/H3, pero no uses una plantilla fija repetida.\n"
				. "- Si hay artistas nuevos, el titular debe nombrar 1-4 artistas.\n"
				. "- Si el cambio son fechas, el titular debe mencionar que se anuncian fechas.\n"
				. "- Varía la estructura del titular, no uses siempre la misma formula.\n"
				. "- Prohibido titular o abrir con formulas genericas tipo 'ficha/informacion actualizada'; concreta siempre el cambio.\n"
				. "- No incluyas ningun parrafo de resumen o conclusion tipo recapitulacion.\n"
				. "- Negritas: uso puntual y moderado; nunca en frases/parrafos completos.\n"
				. "- Devuelve titulo, contenido (HTML simple), meta_title y meta_description.";

		$schema = array(
			'name' => 'festival_news',
			'strict' => true,
			'schema' => array(
				'type' => 'object',
				'additionalProperties' => false,
				'properties' => array(
					'title' => array( 'type' => 'string' ),
					'content' => array( 'type' => 'string' ),
					'meta_title' => array( 'type' => 'string' ),
					'meta_description' => array( 'type' => 'string' ),
				),
				'required' => array( 'title', 'content', 'meta_title', 'meta_description' ),
			),
		);

		$messages = array(
			array( 'role' => 'system', 'content' => array( array( 'type' => 'text', 'text' => $system ) ) ),
			array( 'role' => 'user', 'content' => array( array( 'type' => 'text', 'text' => $user ) ) ),
		);

		$text = $this->request( $this->model_write, $messages, $schema, 0.3, null, 'write_news' );
		if ( is_wp_error( $text ) ) {
			return $text;
		}

		$decoded = $this->decode_json_from_text( $text );
		if ( ! is_array( $decoded ) ) {
			return new WP_Error( 'mfu_ai_json', 'Invalid JSON from AI' );
		}

		return $decoded;
	}

	public function verify_update( $festival_name, $edition, $diffs, $evidence ) {
		$edition = trim( (string) $edition );
		$edition_note = $edition ? "Edicion objetivo: {$edition}." : "No se indica edicion.";

		$diff_lines = array();
		if ( is_array( $diffs ) ) {
			foreach ( $diffs as $key => $diff ) {
				$diff_lines[] = $key . ': ' . ( isset( $diff['after'] ) ? $diff['after'] : '' );
			}
		}
		$diff_text = implode( "\n", $diff_lines );

		$evidence_text = '';
		if ( is_array( $evidence ) ) {
			$evidence_text = wp_json_encode( $evidence );
		}

		$system = "Eres revisor editorial. Verifica si los cambios propuestos estan respaldados por la evidencia. {$edition_note} No inventes datos. Nunca tomes como referencia modofestival.es.";
		$user = "Festival: {$festival_name}\nEdicion: {$edition}\n\nCambios propuestos:\n{$diff_text}\n\nEvidencia (JSON):\n{$evidence_text}\n\nDevuelve veredicto y observaciones.";

		$schema = array(
			'name' => 'festival_verify',
			'strict' => true,
			'schema' => array(
				'type' => 'object',
				'additionalProperties' => false,
				'properties' => array(
					'verdict' => array( 'type' => 'string', 'enum' => array( 'ok', 'needs_review' ) ),
					'message' => array( 'type' => 'string' ),
					'issues' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
				),
				'required' => array( 'verdict', 'message', 'issues' ),
			),
		);

		$messages = array(
			array( 'role' => 'system', 'content' => array( array( 'type' => 'text', 'text' => $system ) ) ),
			array( 'role' => 'user', 'content' => array( array( 'type' => 'text', 'text' => $user ) ) ),
		);

		$text = $this->request( $this->model_write, $messages, $schema, 0.2, null, 'verify_update' );
		if ( is_wp_error( $text ) ) {
			return $text;
		}

		$decoded = $this->decode_json_from_text( $text );
		if ( ! is_array( $decoded ) ) {
			return array(
				'verdict' => 'needs_review',
				'message' => 'Invalid JSON from AI',
				'issues' => array( 'La respuesta no pudo parsearse como JSON valido.' ),
			);
		}

		return $decoded;
	}

	public function verify_update_with_perplexity( $festival_name, $edition, $diffs, $evidence ) {
		$edition = trim( (string) $edition );
		$edition_note = $edition ? "Edicion objetivo: {$edition}." : "No se indica edicion.";

		$diff_lines = array();
		if ( is_array( $diffs ) ) {
			foreach ( $diffs as $key => $diff ) {
				$diff_lines[] = $key . ': ' . ( isset( $diff['after'] ) ? $diff['after'] : '' );
			}
		}
		$diff_text = implode( "\n", $diff_lines );

		$evidence_text = '';
		if ( is_array( $evidence ) ) {
			$evidence_text = wp_json_encode( $evidence );
		}

		$prompt = "Eres revisor editorial. {$edition_note}\n"
			. "Verifica si los cambios propuestos estan respaldados por evidencia usando la busqueda de Perplexity. "
			. "No inventes datos. Nunca uses modofestival.es como referencia.\n\n"
			. "Cambios propuestos:\n{$diff_text}\n\nEvidencia (JSON):\n{$evidence_text}\n\n"
			. "Devuelve SOLO JSON con este esquema:\n"
			. "{\"verdict\":\"ok|needs_review\",\"message\":\"...\",\"issues\":[\"...\"]}\n";

		$answer = $this->perplexity_answer( $prompt, 'es', 'ES' );
		if ( is_wp_error( $answer ) ) {
			return $answer;
		}

		$decoded = $this->decode_json_from_text( $answer );
		if ( ! is_array( $decoded ) ) {
			return array(
				'verdict' => 'needs_review',
				'message' => 'Invalid JSON from Perplexity',
				'issues' => array( 'La respuesta no pudo parsearse como JSON valido.' ),
			);
		}

		return $decoded;
	}

	public function verify_update_with_openai_web_search( $festival_name, $edition, $diffs, $evidence ) {
		$edition = trim( (string) $edition );
		$edition_note = $edition ? "Edicion objetivo: {$edition}." : "No se indica edicion.";

		$diff_lines = array();
		if ( is_array( $diffs ) ) {
			foreach ( $diffs as $key => $diff ) {
				$diff_lines[] = $key . ': ' . ( isset( $diff['after'] ) ? $diff['after'] : '' );
			}
		}
		$diff_text = implode( "\n", $diff_lines );

		$evidence_text = '';
		if ( is_array( $evidence ) ) {
			$evidence_text = wp_json_encode( $evidence );
		}

		$system = "Eres revisor editorial. {$edition_note} Verifica si los cambios propuestos estan respaldados por evidencia usando busqueda web. "
			. "No inventes datos. Nunca uses modofestival.es como referencia.";
		$user = "Festival: {$festival_name}\nEdicion: {$edition}\n\nCambios propuestos:\n{$diff_text}\n\nEvidencia (JSON):\n{$evidence_text}\n\nDevuelve veredicto y observaciones.";

		$schema = array(
			'name' => 'festival_verify',
			'strict' => true,
			'schema' => array(
				'type' => 'object',
				'additionalProperties' => false,
				'properties' => array(
					'verdict' => array( 'type' => 'string', 'enum' => array( 'ok', 'needs_review' ) ),
					'message' => array( 'type' => 'string' ),
					'issues' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
				),
				'required' => array( 'verdict', 'message', 'issues' ),
			),
		);

		$messages = array(
			array( 'role' => 'system', 'content' => array( array( 'type' => 'text', 'text' => $system ) ) ),
			array( 'role' => 'user', 'content' => array( array( 'type' => 'text', 'text' => $user ) ) ),
		);

		$tools = array( array( 'type' => 'web_search' ) );
		$text = $this->request( $this->model_write, $messages, $schema, 0.2, $tools, 'verify_update_web_search' );
		if ( is_wp_error( $text ) ) {
			return $text;
		}

		$decoded = $this->decode_json_from_text( $text );
		if ( ! is_array( $decoded ) ) {
			return array(
				'verdict' => 'needs_review',
				'message' => 'Invalid JSON from OpenAI',
				'issues' => array( 'La respuesta no pudo parsearse como JSON valido.' ),
			);
		}

		return $decoded;
	}

	public function verify_content_strict( $festival_name, $edition, $current_content, $updated_content, $answer_text ) {
		$edition = trim( (string) $edition );
		$edition_note = $edition ? "Edicion objetivo: {$edition}." : "No se indica edicion.";

		$system = "Eres revisor editorial MUY estricto. Verifica si el contenido actualizado solo incluye hechos verificables "
			. "presentes en la respuesta. {$edition_note} No inventes datos. Marca como 'needs_review' si hay cualquier dato "
			. "no sustentado o potencialmente inventado.";
		$user = "Festival: {$festival_name}\nEdicion: {$edition}\n\nRespuesta con hechos:\n{$answer_text}\n\nContenido actual (HTML):\n{$current_content}\n\nContenido actualizado (HTML):\n{$updated_content}\n\nDevuelve veredicto y observaciones.";

		$schema = array(
			'name' => 'festival_content_verify',
			'strict' => true,
			'schema' => array(
				'type' => 'object',
				'additionalProperties' => false,
				'properties' => array(
					'verdict' => array( 'type' => 'string', 'enum' => array( 'ok', 'needs_review' ) ),
					'message' => array( 'type' => 'string' ),
					'issues' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
				),
				'required' => array( 'verdict', 'message', 'issues' ),
			),
		);

		$messages = array(
			array( 'role' => 'system', 'content' => array( array( 'type' => 'text', 'text' => $system ) ) ),
			array( 'role' => 'user', 'content' => array( array( 'type' => 'text', 'text' => $user ) ) ),
		);

		$text = $this->request( $this->model_write, $messages, $schema, 0.2, null, 'verify_content_strict' );
		if ( is_wp_error( $text ) ) {
			return $text;
		}

		$decoded = $this->decode_json_from_text( $text );
		if ( ! is_array( $decoded ) ) {
			return array(
				'verdict' => 'needs_review',
				'message' => 'Invalid JSON from AI',
				'issues' => array( 'La respuesta no pudo parsearse como JSON valido.' ),
			);
		}

		return $decoded;
	}

	public function rewrite_content_fix_issues( $festival_name, $edition, $current_content, $updated_content, $answer_text, $issues ) {
		$edition = trim( (string) $edition );
		$edition_note = $edition ? "Edicion objetivo: {$edition}. No mezcles otras ediciones." : "No se indica edicion; no asumas anio.";

		$issues_text = is_array( $issues ) ? implode( "\n- ", $issues ) : (string) $issues;
		$issues_text = $issues_text ? "- " . $issues_text : '';

		$system = "Eres editor. Corrige SOLO las partes problematicas del contenido actualizado. "
			. "{$edition_note} Usa exclusivamente hechos verificables presentes en la respuesta. "
			. "MantÃ©n estructura, tono y estilo; no elimines informaciÃ³n correcta. "
			. "No aÃ±adas enlaces de compra de entradas ni precios, ni menciones a venta de entradas o tiqueteras. "
			. "Devuelve HTML completo actualizado.";
		$user = "Festival: {$festival_name}\nEdicion: {$edition}\n\nProblemas detectados:\n{$issues_text}\n\nRespuesta con hechos:\n{$answer_text}\n\nContenido actual (HTML):\n{$current_content}\n\nContenido actualizado (HTML):\n{$updated_content}";

		$schema = array(
			'name' => 'festival_content_fix_issues',
			'strict' => true,
			'schema' => array(
				'type' => 'object',
				'additionalProperties' => false,
				'properties' => array(
					'content' => array( 'type' => 'string' ),
				),
				'required' => array( 'content' ),
			),
		);

		$messages = array(
			array( 'role' => 'system', 'content' => array( array( 'type' => 'text', 'text' => $system ) ) ),
			array( 'role' => 'user', 'content' => array( array( 'type' => 'text', 'text' => $user ) ) ),
		);

		$text = $this->request( $this->model_write, $messages, $schema, 0.2, null, 'rewrite_content_fix_issues' );
		if ( is_wp_error( $text ) ) {
			return $text;
		}

		$decoded = $this->decode_json_from_text( $text );
		if ( ! is_array( $decoded ) || empty( $decoded['content'] ) ) {
			return new WP_Error( 'mfu_ai_json', 'Invalid JSON from AI' );
		}

		return $decoded['content'];
	}

	public function perplexity_search( $query, $max_results = 10, $lang = 'es', $country = 'ES' ) {
		$key = $this->pplx_key;
		if ( $key === '' ) {
			return new WP_Error( 'mfu_pplx_no_key', 'Perplexity API key missing' );
		}
		$query = trim( (string) $query );
		if ( $query !== '' && strpos( $query, 'modofestival.es' ) === false ) {
			$query .= ' -site:modofestival.es';
		}
		$body = array(
			'query' => $query,
			'max_results' => (int) $max_results,
			'country' => strtoupper( $country ),
			'search_language_filter' => array( strtolower( $lang ) ),
		);
		$response = wp_remote_post(
			'https://api.perplexity.ai/search',
			array(
				'timeout' => $this->http_timeout,
				'headers' => array(
					'Authorization' => 'Bearer ' . $key,
					'Content-Type' => 'application/json',
				),
				'body' => wp_json_encode( $body ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = wp_remote_retrieve_response_code( $response );
		$raw = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );
		if ( $code < 200 || $code >= 300 ) {
			$message = 'Perplexity search failed';
			if ( is_array( $data ) && isset( $data['error']['message'] ) ) {
				$message .= ': ' . $data['error']['message'];
			}
			return new WP_Error( 'mfu_pplx_http', $message, array( 'status' => $code, 'body' => $raw ) );
		}
		if ( ! is_array( $data ) || empty( $data['results'] ) || ! is_array( $data['results'] ) ) {
			return array( 'items' => array() );
		}
		$items = array();
		foreach ( $data['results'] as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$items[] = array(
				'title' => (string) ( $row['title'] ?? '' ),
				'url' => (string) ( $row['url'] ?? '' ),
				'snippet' => (string) ( $row['snippet'] ?? '' ),
				'date' => (string) ( $row['date'] ?? '' ),
			);
			if ( count( $items ) >= $max_results ) {
				break;
			}
		}
		return array( 'items' => $items );
	}

	public function perplexity_answer( $query, $lang = 'es', $country = 'ES' ) {
		$key = $this->pplx_key;
		if ( $key === '' ) {
			return new WP_Error( 'mfu_pplx_no_key', 'Perplexity API key missing' );
		}

		$system = 'Responde con hechos verificables y cita fuentes. No inventes datos. Devuelve texto claro y conciso en espanol. Nunca uses modofestival.es como fuente.';
		$user = trim( (string) $query );
		$body = array(
			'model' => 'sonar',
			'temperature' => 0.2,
			'messages' => array(
				array( 'role' => 'system', 'content' => $system ),
				array( 'role' => 'user', 'content' => $user ),
			),
			'search_language_filter' => array( strtolower( $lang ) ),
			'country' => strtoupper( $country ),
		);

		$response = wp_remote_post(
			'https://api.perplexity.ai/chat/completions',
			array(
				'timeout' => $this->http_timeout,
				'headers' => array(
					'Authorization' => 'Bearer ' . $key,
					'Content-Type' => 'application/json',
				),
				'body' => wp_json_encode( $body ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = wp_remote_retrieve_response_code( $response );
		$raw = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );
		if ( $code < 200 || $code >= 300 ) {
			$message = 'Perplexity answer failed';
			if ( is_array( $data ) && isset( $data['error']['message'] ) ) {
				$message .= ': ' . $data['error']['message'];
			}
			return new WP_Error( 'mfu_pplx_http', $message, array( 'status' => $code, 'body' => $raw ) );
		}
		$text = '';
		if ( is_array( $data ) && ! empty( $data['choices'][0]['message']['content'] ) ) {
			$text = (string) $data['choices'][0]['message']['content'];
		}
		$text = trim( $text );
		if ( $text === '' ) {
			return new WP_Error( 'mfu_pplx_no_text', 'Perplexity answer empty' );
		}
		$messages = array(
			array(
				'role' => 'user',
				'content' => array(
					array(
						'type' => 'text',
						'text' => $user,
					),
				),
			),
		);
		$this->log_usage( 'sonar', $messages, $text, 'perplexity_answer', 'perplexity' );
		return $text;
	}


	public function openai_web_search_answer( $query, $lang = 'es', $country = 'ES', $model = 'gpt-5' ) {
		if ( $this->openai_key === '' ) {
			return new WP_Error( 'mfu_no_openai_key', 'OpenAI API key missing' );
		}

		$system = 'Responde con hechos verificables y cita fuentes. No inventes datos. Devuelve texto claro y conciso en espanol. Nunca uses modofestival.es como fuente.';
		$messages = array(
			array( 'role' => 'system', 'content' => array( array( 'type' => 'text', 'text' => $system ) ) ),
			array( 'role' => 'user', 'content' => array( array( 'type' => 'text', 'text' => $query ) ) ),
		);

		$body = array(
			'model' => $model,
			'input' => $this->normalize_messages_for_responses( $messages ),
			'tools' => array(
				array( 'type' => 'web_search' ),
			),
			'include' => array( 'web_search_call.action.sources' ),
		);

		$args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->openai_key,
				'Content-Type' => 'application/json',
			),
			'body' => wp_json_encode( $body ),
			'timeout' => $this->http_timeout,
		);

		$response = wp_remote_post( $this->base_url_openai, $args );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$raw = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );
		if ( $code < 200 || $code >= 300 ) {
			$message = 'OpenAI request failed';
			if ( is_array( $data ) && isset( $data['error']['message'] ) ) {
				$message .= ': ' . $data['error']['message'];
			}
			return new WP_Error( 'mfu_openai_http', $message, array( 'status' => $code, 'body' => $raw ) );
		}

		$text = $this->extract_text_from_response( $data );
		if ( ! $text ) {
			$snippet = $raw ? substr( $raw, 0, 400 ) : '';
			return new WP_Error( 'mfu_openai_no_text', 'No output text from OpenAI' . ( $snippet ? ' (' . $snippet . ')' : '' ) );
		}

		$sources = $this->extract_sources_from_openai( $data );
		$sources = $this->filter_sources_excluding_domain( $sources, 'modofestival.es' );

		$this->log_usage( $model, $messages, $text, 'openai_web_search_answer', 'openai' );

		return array(
			'text' => $text,
			'sources' => $sources,
		);
	}

	private function extract_sources_from_openai( $data ) {
		if ( ! is_array( $data ) ) {
			return array();
		}

		$sources = array();
		if ( isset( $data['output'] ) && is_array( $data['output'] ) ) {
			foreach ( $data['output'] as $item ) {
				if ( isset( $item['type'] ) && $item['type'] === 'web_search_call' && isset( $item['action']['sources'] ) && is_array( $item['action']['sources'] ) ) {
					foreach ( $item['action']['sources'] as $source ) {
						$url = isset( $source['url'] ) ? (string) $source['url'] : '';
						$title = isset( $source['title'] ) ? (string) $source['title'] : '';
						if ( $url === '' ) {
							continue;
						}
						$sources[] = array(
							'url' => $url,
							'title' => $title,
						);
					}
				}
			}
		}

		return $sources;
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


	public function verify_content_with_perplexity( $festival_name, $edition, $content ) {
		$festival_name = trim( (string) $festival_name );
		$edition = trim( (string) $edition );
		$content = trim( (string) $content );
		if ( $content === '' ) {
			return new WP_Error( 'mfu_pplx_empty_content', 'Contenido vacio' );
		}

		$edition_note = $edition ? "Edicion objetivo: {$edition}." : 'No se indica edicion.';
		$prompt = "Eres verificador editorial. {$edition_note}\n"
			. "Comprueba si los datos del contenido son veraces usando tu conocimiento y la bÃºsqueda integrada de Perplexity. "
			. "No necesitas que el usuario aporte fuentes. Nunca uses modofestival.es como referencia. "
			. "IMPORTANTE: Solo marca REVISAR si hay evidencia clara y dominante de que el contenido es falso. "
			. "Si hay fuentes contradictorias pero el contenido coincide con la mayoria o las mas recientes, marca OK. "
			. "Ignora referencias de otros anos/ediciones si no coinciden con la edicion objetivo. "
			. "No marques REVISAR por omisiones, mejoras de contexto o informaciÃ³n adicional que no aparezca.\n\n"
			. "Devuelve SOLO JSON con este esquema:\n"
			. "{\"verdict\":\"ok|needs_review\",\"observations\":\"...\",\"suggestions\":[{\"find\":\"...\",\"replace\":\"...\",\"note\":\"...\"}]}\n"
			. "Si verdict=needs_review debes proponer al menos 1 sugerencia concreta con find/replace exactos del contenido. "
			. "Si no hay cambios, usa [] y observations='Sin observaciones'.\n\n"
			. "Contenido a verificar:\n{$content}";

		$answer = $this->perplexity_answer( $prompt, 'es', 'ES' );
		if ( is_wp_error( $answer ) ) {
			return $answer;
		}

		$decoded = $this->decode_json_from_text( $answer );
		$verdict = '';
		$message = '';
		$suggestions = array();
		if ( is_array( $decoded ) ) {
			$verdict = (string) ( $decoded['verdict'] ?? '' );
			$message = (string) ( $decoded['observations'] ?? '' );
			if ( isset( $decoded['suggestions'] ) && is_array( $decoded['suggestions'] ) ) {
				$suggestions = $decoded['suggestions'];
			}
		}
		if ( $verdict !== 'ok' && $verdict !== 'needs_review' ) {
			$verdict = 'needs_review';
		}
		if ( $message === '' ) {
			$message = 'Sin observaciones';
		}
		if ( $verdict === 'needs_review' && empty( $suggestions ) ) {
			$suggestion_prompt = "A partir de este contenido y las observaciones, propone cambios concretos para que el texto sea 100% veraz.\n"
				. "Nunca uses modofestival.es como referencia.\n"
				. "Devuelve SOLO JSON con este esquema:\n"
				. "{\"suggestions\":[{\"find\":\"...\",\"replace\":\"...\",\"note\":\"...\"}]}\n"
				. "Los campos find/replace deben ser fragmentos exactos del contenido para poder reemplazar.\n\n"
				. "Observaciones:\n{$message}\n\nContenido:\n{$content}";
			$suggest_answer = $this->perplexity_answer( $suggestion_prompt, 'es', 'ES' );
			if ( ! is_wp_error( $suggest_answer ) ) {
				$suggest_decoded = $this->decode_json_from_text( $suggest_answer );
				if ( is_array( $suggest_decoded ) && isset( $suggest_decoded['suggestions'] ) && is_array( $suggest_decoded['suggestions'] ) ) {
					$suggestions = $suggest_decoded['suggestions'];
				}
			}
		}

		if ( $verdict === 'needs_review' && empty( $suggestions ) ) {
			$snippet = substr( $content, 0, 160 );
			if ( $snippet !== '' ) {
				$suggestions = array(
					array(
						'find' => $snippet,
						'replace' => "Nota: algunos datos de esta ficha no han podido verificarse con fuentes independientes recientes.\n\n" . $snippet,
						'note' => 'AÃ±ade una nota de cautela cuando la verificaciÃ³n no puede confirmar datos.'
					),
				);
			}
		}

		return array(
			'verdict' => $verdict,
			'message' => $message,
			'suggestions' => $suggestions,
			'raw' => $answer,
		);
	}

	public function compare_current_vs_sources( $festival_name, $edition, $current, $facts_by_source, $content ) {
		$edition = trim( (string) $edition );
		$edition_note = $edition ? "Edicion objetivo: {$edition}." : "No se indica edicion.";
		$system = "Eres editor. Compara hechos detectados con los datos actuales. {$edition_note} No inventes datos. Devuelve solo cambios reales.";

		$user = "Festival: {$festival_name}\nEdicion: {$edition}\n\nDatos actuales (JSON):\n"
			. wp_json_encode( $current ) . "\n\nHechos por fuente (JSON):\n"
			. wp_json_encode( $facts_by_source ) . "\n\nContenido actual (texto):\n"
			. $content . "\n\nDevuelve cambios sugeridos.";

		$schema = array(
			'name' => 'festival_compare',
			'strict' => true,
			'schema' => array(
				'type' => 'object',
				'additionalProperties' => false,
				'properties' => array(
					'summary' => array( 'type' => 'string' ),
					'changes' => array(
						'type' => 'array',
						'items' => array(
							'type' => 'object',
							'additionalProperties' => false,
							'properties' => array(
								'field' => array( 'type' => 'string' ),
								'current' => array( 'type' => array( 'string', 'null' ) ),
								'proposed' => array( 'type' => array( 'string', 'null' ) ),
								'sources' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
								'note' => array( 'type' => 'string' ),
							),
							'required' => array( 'field', 'current', 'proposed', 'sources', 'note' ),
						),
					),
					'missing_in_content' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
				),
				'required' => array( 'summary', 'changes', 'missing_in_content' ),
			),
		);

		$messages = array(
			array( 'role' => 'system', 'content' => array( array( 'type' => 'text', 'text' => $system ) ) ),
			array( 'role' => 'user', 'content' => array( array( 'type' => 'text', 'text' => $user ) ) ),
		);

		$text = $this->request( $this->model_write, $messages, $schema, 0.2, null, 'compare_sources' );
		if ( is_wp_error( $text ) ) {
			return $text;
		}

		$decoded = $this->decode_json_from_text( $text );
		if ( ! is_array( $decoded ) ) {
			return new WP_Error( 'mfu_ai_json', 'Invalid JSON from AI' );
		}

		return $decoded;
	}

	public function generate_news_headlines( $festival_name, $edition, $comparison, $facts_by_source ) {
		$edition = trim( (string) $edition );
		$edition_note = $edition ? "Edicion objetivo: {$edition}." : "No se indica edicion.";
		$system = "Eres editor. Genera 5 titulares potenciales basados SOLO en los cambios reales detectados frente al contenido anterior. {$edition_note} En espanol, tono periodistico, sin promociones ni opiniones. No inventes datos ni aÃƒÂ±os. Si no hay cambios relevantes, devuelve lista vacia.";

		$changes_only = array(
			'changes' => $comparison['changes'] ?? array(),
			'missing_in_content' => $comparison['missing_in_content'] ?? array(),
		);
		$user = "Festival: {$festival_name}\nEdicion: {$edition}\n\nCambios detectados (JSON):\n"
			. wp_json_encode( $changes_only ) . "\n\nHechos por fuente (JSON, solo contexto):\n"
			. wp_json_encode( $facts_by_source ) . "\n\nDevuelve 5 titulares.";

		$schema = array(
			'name' => 'festival_headlines',
			'strict' => true,
			'schema' => array(
				'type' => 'object',
				'additionalProperties' => false,
				'properties' => array(
					'headlines' => array(
						'type' => 'array',
						'items' => array( 'type' => 'string' ),
					),
				),
				'required' => array( 'headlines' ),
			),
		);

		$messages = array(
			array( 'role' => 'system', 'content' => array( array( 'type' => 'text', 'text' => $system ) ) ),
			array( 'role' => 'user', 'content' => array( array( 'type' => 'text', 'text' => $user ) ) ),
		);

		$text = $this->request( $this->model_write, $messages, $schema, 0.3, null, 'generate_headlines' );
		if ( is_wp_error( $text ) ) {
			return $text;
		}

		$decoded = $this->decode_json_from_text( $text );
		if ( ! is_array( $decoded ) ) {
			return new WP_Error( 'mfu_ai_json', 'Invalid JSON from AI' );
		}

		$headlines = $decoded['headlines'] ?? array();
		if ( ! is_array( $headlines ) ) {
			$headlines = array();
		}

		return array( 'headlines' => $headlines );
	}

	public function generate_headlines_for_changes( $festival_name, $edition, $changes ) {
		$edition = trim( (string) $edition );
		$edition_note = $edition ? "Edicion objetivo: {$edition}." : "No se indica edicion.";
		$system = "Eres editor. Genera UN titular por cada cambio relevante. {$edition_note} En espanol, tono periodistico, sin promociones ni opiniones. No inventes datos ni aÃƒÂ±os. Si un cambio es trivial, devuelve titular vacio para ese item.";

		$user = "Festival: {$festival_name}\nEdicion: {$edition}\n\nCambios (JSON):\n"
			. wp_json_encode( $changes ) . "\n\nDevuelve una lista de titulares, en el mismo orden.";

		$schema = array(
			'name' => 'festival_headlines_by_change',
			'strict' => true,
			'schema' => array(
				'type' => 'object',
				'additionalProperties' => false,
				'properties' => array(
					'headlines' => array(
						'type' => 'array',
						'items' => array( 'type' => 'string' ),
					),
				),
				'required' => array( 'headlines' ),
			),
		);

		$messages = array(
			array( 'role' => 'system', 'content' => array( array( 'type' => 'text', 'text' => $system ) ) ),
			array( 'role' => 'user', 'content' => array( array( 'type' => 'text', 'text' => $user ) ) ),
		);

		$text = $this->request( $this->model_write, $messages, $schema, 0.2, null, 'generate_headlines_by_change' );
		if ( is_wp_error( $text ) ) {
			return $text;
		}

		$decoded = $this->decode_json_from_text( $text );
		if ( ! is_array( $decoded ) ) {
			return new WP_Error( 'mfu_ai_json', 'Invalid JSON from AI' );
		}

		$headlines = $decoded['headlines'] ?? array();
		if ( ! is_array( $headlines ) ) {
			$headlines = array();
		}

		return array( 'headlines' => $headlines );
	}

	public function rewrite_festival_content( $festival_name, $edition, $current_content, $diffs, $evidence_list ) {
		$edition = trim( (string) $edition );
		$edition_note = $edition ? "Edicion objetivo: {$edition}. No mezcles otras ediciones." : "No se indica edicion; no asumas aÃƒÂ±o.";

		$changes_summary = array();
		foreach ( $diffs as $key => $diff ) {
			$changes_summary[] = $key . ': ' . $diff['after'];
		}
		$changes_text = implode( "\n", $changes_summary );
		$evidence_text = implode( "\n", $evidence_list );

			$system = "Eres editor SEO senior para fichas de festivales. Integra cambios verificados en el contenido existente manteniendo una ficha completa, util y coherente. {$edition_note} "
				. "Manten estructura, tono y estilo editorial. No inventes datos. Si un cambio ya esta reflejado, no lo dupliques. "
				. "Longitud minima obligatoria: 600 palabras reales en el contenido final. Si falta longitud, amplia con contexto util y verificable del festival, sin relleno. "
				. "SEO: usa HTML claro, subtitulos H2/H3 descriptivos, parrafos cortos y buena escaneabilidad. "
				. "Incluye marcado Schema.org optimo en JSON-LD, valido y coherente con los datos (sin inventar), integrado en el HTML final. "
				. "La estructura NO debe ser fija ni repetitiva entre festivales; varia orden y subtitulos segun el tipo de cambio. "
				. "No incluyas enlaces de compra de entradas ni menciones a venta de entradas o tiqueteras. "
				. "No uses encabezados tipo \"Resumen operativo\" ni listas con etiquetas (Fechas:, Lugar:, Promotor:); integra la informacion en parrafos naturales. "
				. "No menciones IA, automatizaciones, ni procesos internos. Devuelve HTML completo actualizado.";
		$user = "Festival: {$festival_name}\nEdicion: {$edition}\n\nCambios verificados:\n{$changes_text}\n\nEvidencia:\n{$evidence_text}\n\nContenido actual (HTML):\n{$current_content}";

		$schema = array(
			'name' => 'festival_content_update',
			'strict' => true,
			'schema' => array(
				'type' => 'object',
				'additionalProperties' => false,
				'properties' => array(
					'content' => array( 'type' => 'string' ),
				),
				'required' => array( 'content' ),
			),
		);

		$messages = array(
			array( 'role' => 'system', 'content' => array( array( 'type' => 'text', 'text' => $system ) ) ),
			array( 'role' => 'user', 'content' => array( array( 'type' => 'text', 'text' => $user ) ) ),
		);

		$text = $this->request( $this->model_write, $messages, $schema, 0.2, null, 'rewrite_content' );
		if ( is_wp_error( $text ) ) {
			return $text;
		}

		$decoded = $this->decode_json_from_text( $text );
		if ( ! is_array( $decoded ) || empty( $decoded['content'] ) ) {
			return new WP_Error( 'mfu_ai_json', 'Invalid JSON from AI' );
		}

		return $decoded['content'];
	}

		public function rewrite_festival_content_from_answer( $festival_name, $edition, $current_content, $answer_text ) {
		$edition = trim( (string) $edition );
		$edition_note = $edition ? "Edicion objetivo: {$edition}. No mezcles otras ediciones." : "No se indica edicion; no asumas anio.";

			$system = "Eres editor SEO senior para fichas de festivales. Actualiza el contenido solo con hechos verificables presentes en la respuesta. "
				. "Incluye TODA la informacion relevante del texto (fechas, artistas, ubicacion, horarios, escenarios y cualquier detalle operativo), "
				. "y manten estructura, tono y estilo. {$edition_note} No inventes datos. "
				. "Longitud minima obligatoria: 600 palabras reales en el contenido final. Si falta longitud, amplia con contexto util y verificable del festival, sin relleno. "
				. "OBLIGATORIO: nombra TODOS los artistas verificados en el contenido final. "
				. "No hagas preguntas al lector ni incluyas llamadas a la accion tipo \"dime\" o \"te lo busco\". "
				. "No anadas enlaces de compra de entradas ni precios, ni menciones a venta de entradas o tiqueteras. "
				. "Evita parrafos de aviso tipo \"no esta verificado\" o \"consulta fuentes\". Si no hay datos verificados para una seccion, omite esa seccion o redacta consejos generales y atemporales sin afirmar hechos concretos. "
				. "No uses encabezados tipo \"Resumen operativo\" ni listas con etiquetas (Fechas:, Lugar:, Promotor:); integra la informacion en parrafos naturales. "
				. "SEO: estructura clara en HTML (titulos y subtitulos), primer parrafo con nombre del festival y ciudad, y parrafos cortos. "
				. "Incluye marcado Schema.org optimo en JSON-LD, valido y coherente con los datos (sin inventar), integrado en el HTML final. "
				. "Incluye secciones H2 para Fechas, Cartel y Localizacion si hay datos, y evita contenido vacio. "
				. "La estructura NO debe ser fija ni repetitiva entre festivales; varia orden y subtitulos segun el tipo de cambio. "
				. "No cites fuentes concretas ni menciones medios, notas o fechas de publicacion. No uses Markdown ni cites fuentes en formato [1]. Devuelve HTML completo actualizado.";
		$user = "Festival: {$festival_name}\nEdicion: {$edition}\n\nRespuesta con hechos:\n{$answer_text}\n\nContenido actual (HTML):\n{$current_content}";

		$schema = array(
			'name' => 'festival_content_update_from_answer',
			'strict' => true,
			'schema' => array(
				'type' => 'object',
				'additionalProperties' => false,
				'properties' => array(
					'content' => array( 'type' => 'string' ),
				),
				'required' => array( 'content' ),
			),
		);

		$messages = array(
			array( 'role' => 'system', 'content' => array( array( 'type' => 'text', 'text' => $system ) ) ),
			array( 'role' => 'user', 'content' => array( array( 'type' => 'text', 'text' => $user ) ) ),
		);

		$text = $this->request( $this->model_write, $messages, $schema, 0.2, null, 'rewrite_content_answer' );
		if ( is_wp_error( $text ) ) {
			return $text;
		}

		$decoded = $this->decode_json_from_text( $text );
		if ( ! is_array( $decoded ) || empty( $decoded['content'] ) ) {
			$raw = is_string( $text ) ? trim( $text ) : '';
			if ( $raw !== '' ) {
				return $raw;
			}
			return new WP_Error( 'mfu_ai_json', 'Invalid JSON from AI' );
		}

			return $decoded['content'];
		}

		public function rewrite_content_for_seo( $festival_name, $edition, $current_content ) {
			$edition = trim( (string) $edition );
			$edition_note = $edition ? "Edicion objetivo: {$edition}. No mezcles otras ediciones." : "No se indica edicion; no asumas anio.";

			$system = "Eres editor SEO senior para WordPress. Reescribe el contenido propuesto para mejorar su estructura SEO sin inventar datos. "
				. "Mantener todos los hechos verificables ya presentes y eliminar redundancias. {$edition_note} "
				. "Usa HTML limpio y legible con parrafos cortos y subtitulos H2/H3 utiles para escaneabilidad. "
				. "Incluye una introduccion clara y evita bloques vacios. "
				. "Longitud minima obligatoria: 600 palabras en el contenido final. "
				. "No anadas enlaces de compra de entradas ni menciones a tiqueteras. "
				. "No uses Markdown, no cites fuentes, no menciones IA ni procesos internos. "
				. "No uses encabezados tipo 'Resumen operativo' ni listas con etiquetas fijas (Fechas:, Lugar:, Promotor:). "
				. "Devuelve el HTML final completo.";
			$user = "Festival: {$festival_name}\nEdicion: {$edition}\n\nContenido propuesto (HTML):\n{$current_content}";

			$schema = array(
				'name' => 'festival_content_rewrite_seo',
				'strict' => true,
				'schema' => array(
					'type' => 'object',
					'additionalProperties' => false,
					'properties' => array(
						'content' => array( 'type' => 'string' ),
					),
					'required' => array( 'content' ),
				),
			);

			$messages = array(
				array( 'role' => 'system', 'content' => array( array( 'type' => 'text', 'text' => $system ) ) ),
				array( 'role' => 'user', 'content' => array( array( 'type' => 'text', 'text' => $user ) ) ),
			);

			$text = $this->request( $this->model_write, $messages, $schema, 0.2, null, 'rewrite_content_seo' );
			if ( is_wp_error( $text ) ) {
				return $text;
			}

			$decoded = $this->decode_json_from_text( $text );
			if ( ! is_array( $decoded ) || empty( $decoded['content'] ) ) {
				$raw = is_string( $text ) ? trim( $text ) : '';
				if ( $raw !== '' ) {
					return $raw;
				}
				return new WP_Error( 'mfu_ai_json', 'Invalid JSON from AI' );
			}

			return $decoded['content'];
		}

		public function prefill_localidad_fechas( $festival_name, $edition = '' ) {
			$festival_name = trim( (string) $festival_name );
			$edition = trim( (string) $edition );
			if ( $festival_name === '' ) {
				return new WP_Error( 'mfu_prefill_name', 'Festival name missing' );
			}

			$edition_note = $edition !== '' ? "Edicion objetivo: {$edition}." : "No se indica edicion.";
			$system = "Eres asistente de investigacion editorial. Usa web search para localizar datos oficiales y recientes del festival. "
				. "{$edition_note} Devuelve SOLO localidad y fechas verificables. "
				. "Si no hay datos fiables, devuelve null en esos campos. "
				. "No inventes datos. Devuelve JSON valido.";
			$user = "Festival: {$festival_name}\nEdicion: {$edition}\n\n"
				. "Extrae:\n"
				. "- location: localidad/ubicacion principal (texto breve)\n"
				. "- date_start: fecha inicio en YYYY-MM-DD o null\n"
				. "- date_end: fecha fin en YYYY-MM-DD o null";

			$schema = array(
				'name' => 'festival_prefill_localidad_fechas',
				'strict' => true,
				'schema' => array(
					'type' => 'object',
					'additionalProperties' => false,
					'properties' => array(
						'location' => array( 'type' => array( 'string', 'null' ) ),
						'date_start' => array( 'type' => array( 'string', 'null' ) ),
						'date_end' => array( 'type' => array( 'string', 'null' ) ),
					),
					'required' => array( 'location', 'date_start', 'date_end' ),
				),
			);

			$messages = array(
				array( 'role' => 'system', 'content' => array( array( 'type' => 'text', 'text' => $system ) ) ),
				array( 'role' => 'user', 'content' => array( array( 'type' => 'text', 'text' => $user ) ) ),
			);

			$tools = $this->supports_web_search() ? array( array( 'type' => 'web_search' ) ) : null;
			$text = $this->request( $this->model_extract, $messages, $schema, 0.1, $tools, 'prefill_localidad_fechas' );
			if ( is_wp_error( $text ) ) {
				return $text;
			}

			$decoded = $this->decode_json_from_text( $text );
			if ( ! is_array( $decoded ) ) {
				return new WP_Error( 'mfu_ai_json', 'Invalid JSON from AI' );
			}

			return $decoded;
		}

	public function build_rewrite_prompt_from_answer( $festival_name, $edition, $current_content, $answer_text ) {
		$edition = trim( (string) $edition );
		$edition_note = $edition ? "Edicion objetivo: {$edition}. No mezcles otras ediciones." : "No se indica edicion; no asumas anio.";

		$system = "Eres editor. Actualiza el contenido solo con hechos verificables presentes en la respuesta. "
			. "Incluye TODA la informacion relevante del texto (fechas, artistas, ubicacion, horarios, escenarios y cualquier detalle operativo), "
			. "y manten estructura, tono y estilo. {$edition_note} No inventes datos. "
			. "OBLIGATORIO: nombra TODOS los artistas verificados en el contenido final. "
			. "No hagas preguntas al lector ni incluyas llamadas a la accion tipo \"dime\" o \"te lo busco\". "
			. "No anadas enlaces de compra de entradas ni precios, ni menciones a venta de entradas o tiqueteras. "
			. "Evita parrafos de aviso tipo \"no esta verificado\" o \"consulta fuentes\". Si no hay datos verificados para una seccion, omite esa seccion o redacta consejos generales y atemporales sin afirmar hechos concretos. "
			. "No uses encabezados tipo \"Resumen operativo\" ni listas con etiquetas (Fechas:, Lugar:, Promotor:); integra la informacion en parrafos naturales. "
			. "SEO: estructura clara en HTML (titulos y subtitulos si ya existen), primer parrafo con nombre del festival y ciudad, y parrafos cortos. "
			. "No cites fuentes concretas ni menciones medios, notas o fechas de publicacion. No uses Markdown ni cites fuentes en formato [1]. Devuelve HTML completo actualizado.";
		$user = "Festival: {$festival_name}\nEdicion: {$edition}\n\nRespuesta con hechos:\n{$answer_text}\n\nContenido actual (HTML):\n{$current_content}";

		$schema = array(
			'name' => 'festival_content_update_from_answer',
			'strict' => true,
			'schema' => array(
				'type' => 'object',
				'additionalProperties' => false,
				'properties' => array(
					'content' => array( 'type' => 'string' ),
				),
				'required' => array( 'content' ),
			),
		);

		return array(
			'action' => 'rewrite_content_answer',
			'model' => $this->model_write,
			'temperature' => 0.2,
			'system' => $system,
			'user' => $user,
			'schema' => $schema,
		);
	}

	private function fact_schema( $value_schema = array( 'type' => array( 'string', 'null' ) ) ) {
		return array(
			'type' => 'object',
			'additionalProperties' => false,
			'properties' => array(
				'value' => $value_schema,
				'evidence' => $this->evidence_schema(),
			),
			'required' => array( 'value', 'evidence' ),
		);
	}

	private function evidence_schema() {
		return array(
			'type' => 'array',
			'items' => array(
				'type' => 'object',
				'additionalProperties' => false,
				'properties' => array(
					'url' => array( 'type' => 'string' ),
					'snippet' => array( 'type' => 'string' ),
				),
				'required' => array( 'url', 'snippet' ),
			),
		);
	}

	public function discover_sources( $festival_name ) {
		$system = 'Encuentra fuentes oficiales y fiables sobre el festival. Devuelve solo URLs verificables. Prioriza web oficial y medios culturales reconocidos. Solo espaÃ±ol.';
		$user = "Festival: {$festival_name}\nDevuelve JSON con un array de URLs oficiales y un array de URLs de medios.";

		$schema = array(
			'name' => 'festival_sources',
			'strict' => true,
			'schema' => array(
				'type' => 'object',
				'additionalProperties' => false,
				'properties' => array(
					'official' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					'news' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					'notes' => array( 'type' => 'string' ),
				),
				'required' => array( 'official', 'news', 'notes' ),
			),
		);

		$messages = array(
			array( 'role' => 'system', 'content' => array( array( 'type' => 'text', 'text' => $system ) ) ),
			array( 'role' => 'user', 'content' => array( array( 'type' => 'text', 'text' => $user ) ) ),
		);

		if ( ! $this->supports_web_search() ) {
			return new WP_Error( 'mfu_web_search_disabled', 'web_search disabled' );
		}
		$tools = array( array( 'type' => 'web_search' ) );

		$text = $this->request( $this->model_extract, $messages, $schema, 0.2, $tools, 'discover_sources' );
		if ( is_wp_error( $text ) ) {
			return $text;
		}

		$decoded = $this->decode_json_from_text( $text );
		if ( ! is_array( $decoded ) ) {
			return new WP_Error( 'mfu_ai_json', 'Invalid JSON from AI' );
		}

		return $decoded;
	}

	private function extract_text_from_response( $data ) {
		if ( ! is_array( $data ) ) {
			return null;
		}
		if ( isset( $data['output_text'] ) && is_string( $data['output_text'] ) ) {
			return $data['output_text'];
		}
		if ( isset( $data['output'] ) && is_array( $data['output'] ) ) {
			foreach ( $data['output'] as $item ) {
				if ( isset( $item['content'] ) && is_array( $item['content'] ) ) {
					foreach ( $item['content'] as $content ) {
						if ( isset( $content['type'] ) && $content['type'] === 'output_text' && isset( $content['text'] ) ) {
							return $content['text'];
						}
						if ( isset( $content['type'] ) && $content['type'] === 'text' && isset( $content['text'] ) ) {
							return $content['text'];
						}
					}
				}
			}
		}
		if ( isset( $data['output'][0]['content'][0]['text'] ) ) {
			return $data['output'][0]['content'][0]['text'];
		}
		if ( isset( $data['choices'][0]['message']['content'] ) && is_string( $data['choices'][0]['message']['content'] ) ) {
			return $data['choices'][0]['message']['content'];
		}
		if ( isset( $data['choices'][0]['text'] ) && is_string( $data['choices'][0]['text'] ) ) {
			return $data['choices'][0]['text'];
		}
		return null;
	}

	private function log_usage( $model, $messages, $output_text, $action, $provider = 'openai' ) {
		$settings = get_option( MFU_OPTION_KEY, array() );
		$provider = $provider ? (string) $provider : 'openai';

		$cost_in = isset( $settings['cost_input'] ) ? (float) $settings['cost_input'] : 0.0;
		$cost_out = isset( $settings['cost_output'] ) ? (float) $settings['cost_output'] : 0.0;
		$cost_extract_in = isset( $settings['cost_extract_input'] ) ? (float) $settings['cost_extract_input'] : 0.0;
		$cost_extract_out = isset( $settings['cost_extract_output'] ) ? (float) $settings['cost_extract_output'] : 0.0;
		$cost_write_in = isset( $settings['cost_write_input'] ) ? (float) $settings['cost_write_input'] : 0.0;
		$cost_write_out = isset( $settings['cost_write_output'] ) ? (float) $settings['cost_write_output'] : 0.0;

		if ( $provider === 'perplexity' ) {
			$cost_in = isset( $settings['pplx_cost_input'] ) ? (float) $settings['pplx_cost_input'] : 0.0;
			$cost_out = isset( $settings['pplx_cost_output'] ) ? (float) $settings['pplx_cost_output'] : 0.0;
			$cost_extract_in = isset( $settings['pplx_cost_extract_input'] ) ? (float) $settings['pplx_cost_extract_input'] : 0.0;
			$cost_extract_out = isset( $settings['pplx_cost_extract_output'] ) ? (float) $settings['pplx_cost_extract_output'] : 0.0;
			$cost_write_in = isset( $settings['pplx_cost_write_input'] ) ? (float) $settings['pplx_cost_write_input'] : 0.0;
			$cost_write_out = isset( $settings['pplx_cost_write_output'] ) ? (float) $settings['pplx_cost_write_output'] : 0.0;
		}

		if ( in_array( $action, array( 'extract_facts', 'discover_sources' ), true ) && ( $cost_extract_in > 0 || $cost_extract_out > 0 ) ) {
			$cost_in = $cost_extract_in;
			$cost_out = $cost_extract_out;
		}
		if ( in_array( $action, array( 'write_news', 'rewrite_content' ), true ) && ( $cost_write_in > 0 || $cost_write_out > 0 ) ) {
			$cost_in = $cost_write_in;
			$cost_out = $cost_write_out;
		}

		$input_chars = 0;
		foreach ( $messages as $msg ) {
			if ( isset( $msg['content'] ) && is_array( $msg['content'] ) ) {
				foreach ( $msg['content'] as $part ) {
					if ( isset( $part['text'] ) && is_string( $part['text'] ) ) {
						$input_chars += strlen( $part['text'] );
					}
				}
			} elseif ( isset( $msg['content'] ) && is_string( $msg['content'] ) ) {
				$input_chars += strlen( $msg['content'] );
			}
		}

		$input_tokens = (int) ceil( $input_chars / 4 );
		$output_tokens = (int) ceil( strlen( (string) $output_text ) / 4 );

		$cost = ( $input_tokens * $cost_in + $output_tokens * $cost_out ) / 1000000;

		$usage = get_option( 'mfu_usage_log', array() );
		if ( ! is_array( $usage ) ) {
			$usage = array();
		}

		$day = current_time( 'Y-m-d' );
		if ( empty( $usage['days'][ $day ] ) ) {
			$usage['days'][ $day ] = array( 'requests' => 0, 'input_tokens' => 0, 'output_tokens' => 0, 'cost' => 0.0 );
		}
		$usage['days'][ $day ]['requests'] += 1;
		$usage['days'][ $day ]['input_tokens'] += $input_tokens;
		$usage['days'][ $day ]['output_tokens'] += $output_tokens;
		$usage['days'][ $day ]['cost'] += $cost;

		if ( empty( $usage['actions'][ $action ] ) ) {
			$usage['actions'][ $action ] = array( 'requests' => 0, 'input_tokens' => 0, 'output_tokens' => 0, 'cost' => 0.0 );
		}
		$usage['actions'][ $action ]['requests'] += 1;
		$usage['actions'][ $action ]['input_tokens'] += $input_tokens;
		$usage['actions'][ $action ]['output_tokens'] += $output_tokens;
		$usage['actions'][ $action ]['cost'] += $cost;

		if ( empty( $usage['providers'][ $provider ] ) ) {
			$usage['providers'][ $provider ] = array( 'requests' => 0, 'input_tokens' => 0, 'output_tokens' => 0, 'cost' => 0.0 );
		}
		$usage['providers'][ $provider ]['requests'] += 1;
		$usage['providers'][ $provider ]['input_tokens'] += $input_tokens;
		$usage['providers'][ $provider ]['output_tokens'] += $output_tokens;
		$usage['providers'][ $provider ]['cost'] += $cost;

		if ( empty( $usage['models'][ $model ] ) ) {
			$usage['models'][ $model ] = array( 'requests' => 0, 'input_tokens' => 0, 'output_tokens' => 0, 'cost' => 0.0 );
		}
		$usage['models'][ $model ]['requests'] += 1;
		$usage['models'][ $model ]['input_tokens'] += $input_tokens;
		$usage['models'][ $model ]['output_tokens'] += $output_tokens;
		$usage['models'][ $model ]['cost'] += $cost;

		$usage['last'] = array(
			'model' => $model,
			'action' => $action,
			'provider' => $provider,
			'input_tokens' => $input_tokens,
			'output_tokens' => $output_tokens,
			'cost' => $cost,
			'when' => current_time( 'mysql' ),
		);

		update_option( 'mfu_usage_log', $usage, false );
	}
}

















