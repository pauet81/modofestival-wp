<?php
/**
 * MODOFESTIVAL - Sistema de Agenda de Festivales
 * Shortcode [festival_list] + Handlers AJAX
 * 
 * @package Avada-Child
 * @version 2.0
 * @author Modofestival
 */

// Seguridad: evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/* ==========================================================================
   1. ENCOLAR CSS
   ========================================================================== */

/* ==========================================================================
   1. ENCOLAR CSS
   ========================================================================== */

/* ==========================================================================
   1. ENCOLAR CSS
   ========================================================================== */

function modofestival_enqueue_agenda_assets() {
    global $post;
    
    $current_url = $_SERVER['REQUEST_URI'];
    $is_festival_page = false;
    
    // Detectar páginas de taxonomías por URL
    if (strpos($current_url, '/festivales-en/') !== false ||      // Localidades
        strpos($current_url, '/festival/estilo-musical/') !== false ||  // Estilos
        strpos($current_url, '/agenda-festivales/') !== false) {        // Agenda y meses
        $is_festival_page = true;
    }
    
    // Detectar también por taxonomías de WordPress
    // NOTE: taxonomy is "estilo_musical" (not "estilomusical").
    if (is_tax('estilo_musical') || is_tax('mes') || is_tax('localidad')) {
        $is_festival_page = true;
    }
    
    // Si es página singular, verificar shortcodes
    if (is_singular() && $post) {
        if (has_shortcode($post->post_content, 'festival_list') || 
            has_shortcode($post->post_content, 'festival_list_estilo') || 
            has_shortcode($post->post_content, 'festival_list_mes') || 
            has_shortcode($post->post_content, 'festival_list_comunidad') ||
            has_shortcode($post->post_content, 'festivales_destacados')) {
            $is_festival_page = true;
        }
    }
    
    // Cargar CSS
    if ($is_festival_page) {
        wp_enqueue_style(
            'modofestival-agenda',
            get_stylesheet_directory_uri() . '/css/mf-agenda.css',
            array(),
            '2.0.4'
        );
    }
}
add_action('wp_enqueue_scripts', 'modofestival_enqueue_agenda_assets', 999);









/* ==========================================================================
   2. SHORTCODE PRINCIPAL: [festival_list]
   ========================================================================== */

function modofestival_ajax_festival_list_shortcode($atts = array()) {

    wp_enqueue_script('jquery');
    
    $unique_id = 'mf-' . uniqid();
    // Extraer atributos si existen
    $estilo_forzado = !empty($atts['estilo']) ? esc_attr($atts['estilo']) : '';
    $mes_forzado = !empty($atts['mes']) ? esc_attr($atts['mes']) : '';
    $comunidad_forzada = !empty($atts['comunidad']) ? esc_attr($atts['comunidad']) : '';

    // Obtener últimos festivales actualizados para el carrusel
    $ultimos_actualizados = get_posts(array(
        'post_type'      => 'festi',
        'posts_per_page' => 20,
        'orderby'        => 'modified',
        'order'          => 'DESC',
        'post_status'    => 'publish',
    ));
    
    ob_start();
    ?>
    <div class="mf-agenda-wrapper" id="<?php echo esc_attr($unique_id); ?>" <?php
        if ($estilo_forzado) echo 'data-estilo-forzado="' . $estilo_forzado . '" ';
        if ($mes_forzado) echo 'data-mes-forzado="' . $mes_forzado . '" ';
        if ($comunidad_forzada) echo 'data-comunidad-forzada="' . $comunidad_forzada . '" ';
    ?>>

    <!-- SPINNER LOADING FULLSCREEN -->
    <div class="mf-loading-overlay" id="mf-loading-overlay">
        <div class="mf-spinner"></div>
        <div class="mf-loading-text" id="mf-loading-text">Cargando festivales…</div>
    </div>



        
        <?php if (!is_tax('estilo_musical') && !is_tax('mes') && !is_tax('localidad')): ?>
<!-- CARRUSEL DE ÚLTIMOS ACTUALIZADOS (oculto en móvil) -->
        <div class="mf-ticker-wrapper">
            <div class="mf-ticker-label"><span>Últimos actualizados</span></div>
            <div class="mf-ticker">
                <div class="mf-ticker-content">
                    <?php
                    // Duplicar contenido para scroll infinito
                    for ($i = 0; $i < 2; $i++) {
                        foreach ($ultimos_actualizados as $festival) {
                            $edicion = get_field('edicion', $festival->ID);
                            $titulo = get_the_title($festival->ID);
                            if ($edicion) {
                                $titulo .= ' ' . $edicion;
                            }
                            ?>
                            <a href="<?php echo esc_url(get_permalink($festival->ID)); ?>" class="mf-ticker-item">
                                <?php echo esc_html($titulo); ?>
                            </a>
                            <?php
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
<?php endif; ?>

        
        <!-- LIVE SEARCH -->
        <div class="mf-search-wrapper">
            <div class="mf-search-container">
                <svg class="mf-search-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 17A8 8 0 1 0 9 1a8 8 0 0 0 0 16zM19 19l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <input type="text" class="mf-search-input" placeholder="Buscar festival..." autocomplete="off">
                <button class="mf-search-clear" style="display:none;">×</button>
            </div>
            <div class="mf-search-results" style="display:none;">
                <div class="mf-search-results-inner"></div>
            </div>
        </div>
        
        <!-- BOTÓN FLOTANTE MÓVIL: FILTROS -->
        <button class="mf-filtros-toggle" aria-label="Abrir filtros">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 6h14M6 10h8M8 14h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Filtros
            <span class="mf-filtros-badge" style="display:none;">0</span>
        </button>
        
        <!-- BOTÓN FLOTANTE MÓVIL: BÚSQUEDA -->
        <button type="button" class="mf-busqueda-floater" aria-label="Buscar festival">Buscar</button>
        
        <!-- OVERLAY MÓVIL -->
        <div class="mf-filtros-overlay"></div>
        
        <!-- FILTROS -->
        <div class="mf-filtros-wrapper">
            <div class="mf-filtros-header">
                <h3 class="mf-filtros-titulo">Filtrar festivales</h3>
                <button class="mf-filtros-cerrar" aria-label="Cerrar filtros">×</button>
            </div>
            
            <div class="mf-filtros">
                <!-- Comunidad -->
                <select class="mf-filtro mf-comunidad" data-label="Comunidad">
                    <option value="">Todas las comunidades</option>
                    <?php
                    $comunidades = get_terms(array(
                        'taxonomy'   => 'localidad',
                        'parent'     => 0,
                        'hide_empty' => true,
                        'orderby'    => 'name',
                    ));
                    if (!empty($comunidades) && !is_wp_error($comunidades)) {
                        foreach ($comunidades as $term) {
                            echo '<option value="' . esc_attr($term->term_id) . '" data-name="' . esc_attr($term->name) . '">' . esc_html($term->name) . '</option>';
                        }
                    }
                    ?>
                </select>
                
                <!-- Localidad (se rellena por AJAX) -->
                <select class="mf-filtro mf-localidad" data-label="Localidad" style="display:none;">
                    <option value="">Selecciona localidad...</option>
                </select>
                
                <!-- Estilo -->
                <select class="mf-filtro mf-estilo" data-label="Estilo">
                    <option value="">Todos los estilos</option>
                    <?php
                    $estilos = get_terms(array(
                        'taxonomy'   => 'estilo_musical',
                        'hide_empty' => true,
                        'orderby'    => 'name',
                    ));
                    if (!empty($estilos) && !is_wp_error($estilos)) {
                        foreach ($estilos as $term) {
                            echo '<option value="' . esc_attr($term->slug) . '" data-name="' . esc_attr($term->name) . '">' . esc_html($term->name) . '</option>';
                        }
                    }
                    ?>
                </select>
                
                <!-- Mes -->
                <select class="mf-filtro mf-mes" data-label="Mes">
                    <option value="">Todos los meses</option>
                    <?php
                    $meses = get_terms(array(
                        'taxonomy'   => 'mes',
                        'hide_empty' => true,
                        'orderby'    => 'name',
                    ));
                    if (!empty($meses) && !is_wp_error($meses)) {
                        foreach ($meses as $term) {
                            echo '<option value="' . esc_attr($term->slug) . '" data-name="' . esc_attr($term->name) . '">' . esc_html($term->name) . '</option>';
                        }
                    }
                    ?>
                </select>
                
                <!-- Estado -->
                <select class="mf-filtro mf-estado" data-label="Estado">
                    <option value="">Todos los festivales</option>
                    <option value="confechas" data-name="Con fechas confirmadas">Con fechas confirmadas</option>
                    <option value="sinfechas" data-name="Sin fechas confirmadas">Sin fechas confirmadas</option>
                    <option value="cancelados" data-name="Cancelados">Cancelados</option>
                </select>
                
                <button class="mf-btn-limpiar" style="display:none;">Limpiar filtros</button>
            </div>
            
            <div class="mf-filtros-aplicados" style="display:none;"></div>
        </div>
        
        <!-- ANUNCIO AFTER FILTERS -->
        <div class="mf-ad-after-filters">
            <!-- Desktop: 728x90 -->
            <div class="mf-ad-after-desktop">
                <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5154850110659563" crossorigin="anonymous"></script>
                <ins class="adsbygoogle"
                     style="display:inline-block;width:728px;height:90px"
                     data-ad-client="ca-pub-5154850110659563"
                     data-ad-slot="9062788176"></ins>
                <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
            </div>
            
            <!-- Móvil: 320x100 -->
            <div class="mf-ad-after-mobile-top">
                <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5154850110659563" crossorigin="anonymous"></script>
                <ins class="adsbygoogle"
                     style="display:block;width:320px;height:100px"
                     data-ad-client="ca-pub-5154850110659563"
                     data-ad-slot="9035809968"
                     data-ad-format="horizontal"></ins>
                <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
            </div>
        </div>
        
        <!-- LISTADO DE FESTIVALES -->
        <div class="mf-festivales-container">
            <div class="mf-grid"></div>
            <div class="mf-resultados-info"></div>
            <div class="mf-loading" style="display:none;">Cargando festivales...</div>
            <button class="mf-cargar-mas" style="display:none;">Cargar más festivales</button>
        </div>
        
        <!-- ANUNCIO MÓVIL AL FINAL -->
        <div class="mf-ad-after-mobile-bottom">
            <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5154850110659563" crossorigin="anonymous"></script>
            <ins class="adsbygoogle"
                 style="display:block;width:320px;height:100px"
                 data-ad-client="ca-pub-5154850110659563"
                 data-ad-slot="9035809968"
                 data-ad-format="horizontal"></ins>
            <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
        </div>
        
    </div>

    <script type="text/javascript">
    (function() {
    function initModofestival() {
        if (typeof jQuery === 'undefined') {
            setTimeout(initModofestival, 100);
            return;
        }

        var $ = jQuery;
        var container = $('#<?php echo esc_js($unique_id); ?>');
        var currentPage = 1;
        var isLoading = false;
        var isMobile = window.innerWidth <= 768;
        var searchTimeout = null;
// Detectar filtros forzados desde data-attributes
var estiloForzado = container.attr('data-estilo-forzado') || '';
var mesForzado = container.attr('data-mes-forzado') || '';
var comunidadForzada = container.attr('data-comunidad-forzada') || '';

// Ocultar selectores según lo que venga forzado
if (estiloForzado) {
    container.find('.mf-estilo').hide();
}
if (mesForzado) {
    container.find('.mf-mes').hide();
}
if (comunidadForzada) {
    container.find('.mf-comunidad').hide();
}

        // Spinner fullscreen
        function mfShowLoader() {
    container.find('#mf-loading-overlay').addClass('mf-loading-overlay--visible');
}

function mfHideLoader() {
    container.find('#mf-loading-overlay').removeClass('mf-loading-overlay--visible');
}


            
            // LIVE SEARCH
            var searchInput = container.find('.mf-search-input');
            var searchResults = container.find('.mf-search-results');
            var searchResultsInner = container.find('.mf-search-results-inner');
            var searchClear = container.find('.mf-search-clear');
            
            searchInput.on('input', function() {
                var query = $(this).val().trim();
                
                if (query.length === 0) {
                    searchResults.hide();
                    searchClear.hide();
                    return;
                }
                
                searchClear.show();
                if (query.length < 2) return;
                
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    buscarFestivales(query);
                }, 300);
            });
            
            searchClear.on('click', function() {
                searchInput.val('').focus();
                searchResults.hide();
                searchClear.hide();
            });
            
            $(document).on('click', function(e) {
                if (!container.find('.mf-search-wrapper').is(e.target) && 
                    container.find('.mf-search-wrapper').has(e.target).length === 0) {
                    searchResults.hide();
                }
            });
            
            function buscarFestivales(query) {
                searchResultsInner.html('<div class="mf-search-loading">Buscando...</div>');
                searchResults.show();
                
                $.ajax({
                    url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    type: 'POST',
                    data: {
                        action: 'mf_live_search',
                        query: query
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.results.length > 0) {
                            var html = '';
                            $.each(response.results, function(i, item) {
                                html += '<a href="' + item.url + '" class="mf-search-result-item">';
                                if (item.image) {
                                    html += '<div class="mf-search-result-image">';
                                    html += '<img src="' + item.image + '" alt="' + item.title + '" loading="lazy">';
                                    html += '</div>';
                                }
                                html += '<div class="mf-search-result-content">';
                                html += '<div class="mf-search-result-title">' + item.title + '</div>';
                                if (item.meta) {
                                    html += '<div class="mf-search-result-meta">' + item.meta + '</div>';
                                }
                                html += '</div>';
                                html += '</a>';
                            });
                            
                            if (response.total > response.results.length) {
                                html += '<div class="mf-search-result-more">';
                                html += 'Mostrando ' + response.results.length + ' de ' + response.total + ' resultados';
                                html += '</div>';
                            }
                            
                            searchResultsInner.html(html);
                        } else {
                            searchResultsInner.html('<div class="mf-search-no-results">No se encontraron resultados</div>');
                        }
                    },
                    error: function() {
                        searchResultsInner.html('<div class="mf-search-error">Error en la búsqueda</div>');
                    }
                });
            }
            
            // FUNCIONES VARIAS
            function ocultarAnunciosVacios() {
                setTimeout(function() {
                    container.find('.mf-card-ad').each(function() {
                        var $card = $(this);
                        var $ins = $card.find('ins.adsbygoogle');
                        
                        if ($ins.length && $ins.attr('data-ad-status') === 'unfilled') {
                            $card.addClass('mf-ad-empty');
                        }
                        
                        if ($card.find('.mf-ad-infeed').children().length === 0) {
                            $card.addClass('mf-ad-empty');
                        }
                    });
                }, 3000);
            }
            
            // Botón filtros flotante en móvil
            if (isMobile) {
                var filtrosWrapper = container.find('.mf-filtros-wrapper');
                var filtrosToggle = container.find('.mf-filtros-toggle');
                
                $(window).on('scroll', function() {
                    var scrollTop = $(window).scrollTop();
                    var filtrosBottom = filtrosWrapper.offset().top + filtrosWrapper.outerHeight();
                    
                    if (scrollTop > filtrosBottom - 100) {
                        filtrosToggle.addClass('mf-filtros-toggle-visible');
                    } else {
                        filtrosToggle.removeClass('mf-filtros-toggle-visible');
                    }
                });
            }
            
            // Abrir/Cerrar filtros móvil
            container.find('.mf-filtros-toggle').on('click', function() {
                container.find('.mf-filtros-wrapper').addClass('mf-filtros-modal');
                container.find('.mf-filtros-overlay').addClass('mf-filtros-overlay-visible');
                $('body').addClass('mf-no-scroll');
            });
            
            function cerrarFiltros() {
                container.find('.mf-filtros-wrapper').removeClass('mf-filtros-modal');
                container.find('.mf-filtros-overlay').removeClass('mf-filtros-overlay-visible');
                $('body').removeClass('mf-no-scroll');
            }
            
            container.find('.mf-filtros-cerrar, .mf-filtros-overlay').on('click', cerrarFiltros);
            
            // Cargar localidades por comunidad
            function cargarLocalidades(comunidadId) {
                if (!comunidadId) {
                    container.find('.mf-localidad').hide().val('');
                    return;
                }
                
                var comunidadNombre = container.find('.mf-comunidad option:selected').data('name');
                
                $.ajax({
                    url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    type: 'POST',
                    data: {
                        action: 'mf_get_localidades',
                        comunidad_id: comunidadId
                    },
                    success: function(response) {
                        var data = JSON.parse(response);
                        var select = container.find('.mf-localidad');
                        var placeholder = comunidadNombre ? 'Localidades en ' + comunidadNombre : 'Todas las localidades';
                        
                        select.html('<option value="">' + placeholder + '</option>');
                        
                        if (data.localidades && data.localidades.length > 0) {
                            $.each(data.localidades, function(i, loc) {
                                select.append('<option value="' + loc.slug + '" data-name="' + loc.name + '">' + loc.name + ' (' + loc.count + ')</option>');
                            });
                            select.show();
                        } else {
                            select.hide();
                        }
                    }
                });
            }
            
            // Chips de filtros aplicados
            function actualizarChips() {
                var chipsContainer = container.find('.mf-filtros-aplicados');
                var hasFilters = false;
                var filterCount = 0;
                
                chipsContainer.html('');
                
                container.find('.mf-filtro').each(function() {
                    var $select = $(this);
                    var valor = $select.val();
                    
                    if (valor && $select.is(':visible')) {
                        hasFilters = true;
                        filterCount++;
                        
                        var label = $select.data('label');
                        var nombre = $select.find('option:selected').data('name');
                        var filtroTipo = $select.attr('class').split(' ').find(function(c) {
                            return c.startsWith('mf-') && c !== 'mf-filtro';
                        });
                        
                        var chip = '<div class="mf-chip">' +
                                   '<span class="mf-chip-text"><strong>' + label + ':</strong> ' + nombre + '</span>' +
                                   '<button class="mf-chip-remove" data-filtro="' + filtroTipo + '">×</button>' +
                                   '</div>';
                        
                        chipsContainer.append(chip);
                    }
                });
                
                if (hasFilters) {
                    chipsContainer.show();
                    container.find('.mf-btn-limpiar').show();
                    container.find('.mf-filtros-badge').text(filterCount).show();
                } else {
                    chipsContainer.hide();
                    container.find('.mf-btn-limpiar').hide();
                    container.find('.mf-filtros-badge').hide();
                }
            }
            
// Cargar festivales AJAX
function cargarFestivales(append) {
    if (isLoading) return;
    
    append = append || false;
    isLoading = true;

    // Mostrar overlay global con frase
    if (typeof mfShowLoader === 'function') {
        mfShowLoader();
    }
    
    if (!append) {
        currentPage = 1;
        container.find('.mf-grid').html('');
        // Fijar frase del overlay local (por si mfShowLoader no existe)
var frases = [
    'Buscando el próximo festival que te cambiará el verano…',
    'Afinando guitarras y montando escenarios por toda la península…',
    'Revisando carteles y cuadrando fechas para tu escapada perfecta…',
    'Probando sonido en todos los escenarios antes de abrir puertas…',
    'Subiendo el volumen de la agenda de festivales…',
    'Conectando amplis, luces y destinos festivaleros…',
    'Cargando festivales y desenredando cables entre estilos y ciudades…',
    'Poniendo en fila artistas, estilos y ciudades para ti…',
    'Calentando motores para tu próxima maratón de directos…',
    'Localizando festivales donde la resaca merece la pena…'
];
var fraseRandom = frases[Math.floor(Math.random() * frases.length)];
container.find('#mf-loading-text').text(fraseRandom);

    }
    
    container.find('.mf-loading').show();
    container.find('.mf-cargar-mas').hide();
    
    var datos = {
        action: 'mf_filtrar_festivales',
        comunidad: comunidadForzada || container.find('.mf-comunidad').val(),
        localidad: container.find('.mf-localidad').val(),
        estilo: estiloForzado || container.find('.mf-estilo').val(),
        mes: mesForzado || container.find('.mf-mes').val(),
        estado: container.find('.mf-estado').val(),
        page: currentPage
    };
    
    $.ajax({
        url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
        type: 'POST',
        data: datos,
        dataType: 'json',
        timeout: 30000,
        success: function(response) {
            if (append) {
                container.find('.mf-grid').append(response.html);
            } else {
                container.find('.mf-grid').html(response.html);
            }
            
            var showing = currentPage * 24;
            if (showing > response.total) showing = response.total;
            
            if (response.total > 0) {
                container.find('.mf-resultados-info').html('Mostrando ' + showing + ' de ' + response.total + ' festivales');
            } else {
                container.find('.mf-resultados-info').html('');
            }
            
            if (response.hasmore) {
                container.find('.mf-cargar-mas').show();
            } else {
                container.find('.mf-cargar-mas').hide();
            }
            
            container.find('.mf-loading').hide();
            isLoading = false;
            ocultarAnunciosVacios();
            
            if (isMobile && container.find('.mf-filtros-wrapper').hasClass('mf-filtros-modal')) {
                cerrarFiltros();
            }

            // Ocultar overlay global
    if (typeof mfHideLoader === 'function') {
        mfHideLoader();
    }
    
    // Scroll hacia los filtros cuando termina la carga (solo si no es append)
    if (!append) {
        var filtrosOffset = container.find('.mf-filtros-wrapper').offset();
        if (filtrosOffset) {
            $('html, body').animate({
                scrollTop: filtrosOffset.top - 20
            }, 400);
        }
    }
},
        error: function(xhr, status, error) {
            console.error('Error:', error);
            container.find('.mf-grid').html('<div class="mf-no-results"><p>Error al cargar festivales</p></div>');
            container.find('.mf-loading').hide();
            isLoading = false;

            // Ocultar overlay también si hay error
            if (typeof mfHideLoader === 'function') {
                mfHideLoader();
            }
        }
    });
}

            
            // Eventos filtros
            container.find('.mf-comunidad').on('change', function() {
                var comunidadId = $(this).val();
                container.find('.mf-localidad').val('').hide();
                cargarLocalidades(comunidadId);
                currentPage = 1;
                actualizarChips();
                cargarFestivales(false);
            });
            
            container.find('.mf-estilo, .mf-mes, .mf-estado, .mf-localidad').on('change', function() {
                currentPage = 1;
                actualizarChips();
                cargarFestivales(false);
            });
            
            container.on('click', '.mf-chip-remove', function() {
                var filtro = $(this).data('filtro');
                container.find('.' + filtro).val('');
                
                if (filtro === 'mf-comunidad') {
                    container.find('.mf-localidad').hide().val('');
                }
                
                actualizarChips();
                currentPage = 1;
                cargarFestivales(false);
            });
            
            container.find('.mf-btn-limpiar').on('click', function() {
                container.find('.mf-filtro').val('');
                container.find('.mf-localidad').hide();
                actualizarChips();
                currentPage = 1;
                cargarFestivales(false);
            });
            
            container.find('.mf-cargar-mas').on('click', function() {
                currentPage++;
                cargarFestivales(true);
            });
            
            // Botón lupa flotante
            container.find('.mf-busqueda-floater').on('click', function(e) {
                e.preventDefault();
                var wrapper = container.find('.mf-search-wrapper');
                if (wrapper.length) {
                    $('html,body').animate({
                        scrollTop: wrapper.offset().top - 16
                    }, 500);
                    wrapper.find('.mf-search-input').focus();
                }
            });
            
            // Carga inicial
            cargarFestivales(false);
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initModofestival);
        } else {
            initModofestival();
        }
    })();
    </script>
    <?php
    
    return ob_get_clean();
}
add_shortcode('festival_list', 'modofestival_ajax_festival_list_shortcode');

/* ==========================================================================
   3. AJAX: Live Search
   ========================================================================== */

function mf_live_search_handler() {
    $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
    
    if (empty($query) || strlen($query) < 2) {
        echo wp_json_encode(array('success' => false, 'results' => array()));
        wp_die();
    }
    
    $args = array(
        'post_type'      => 'festi',
        'post_status'    => 'publish',
        'posts_per_page' => 8,
        's'              => $query,
    );
    
    // Buscar también en taxonomías
    $tax_query = array('relation' => 'OR');
    
    $estilos = get_terms(array(
        'taxonomy'   => 'estilo_musical',
        'name__like' => $query,
        'hide_empty' => false,
    ));
    if (!empty($estilos) && !is_wp_error($estilos)) {
        $tax_query[] = array(
            'taxonomy' => 'estilo_musical',
            'field'    => 'term_id',
            'terms'    => wp_list_pluck($estilos, 'term_id'),
        );
    }
    
    $localidades = get_terms(array(
        'taxonomy'   => 'localidad',
        'name__like' => $query,
        'hide_empty' => false,
    ));
    if (!empty($localidades) && !is_wp_error($localidades)) {
        $tax_query[] = array(
            'taxonomy' => 'localidad',
            'field'    => 'term_id',
            'terms'    => wp_list_pluck($localidades, 'term_id'),
        );
    }
    
    if (count($tax_query) > 1) {
        $args['tax_query'] = $tax_query;
    }
    
    $search_query = new WP_Query($args);
    $results = array();
    
    if ($search_query->have_posts()) {
        while ($search_query->have_posts()) {
            $search_query->the_post();
            
            $fecha_inicio = get_field('fecha_inicio');
            $edicion = get_field('edicion');
            $localidad_terms = wp_get_post_terms(get_the_ID(), 'localidad');
            $localidad_str = '';
            
            if (!empty($localidad_terms) && !is_wp_error($localidad_terms)) {
                $localidades_nombres = array();
                foreach ($localidad_terms as $loc) {
                    if ($loc->parent != 0) {
                        $localidades_nombres[] = $loc->name;
                    }
                }
                if (!empty($localidades_nombres)) {
                    $localidad_str = implode(', ', $localidades_nombres);
                }
            }
            
            $meta = '';
            if ($fecha_inicio) {
                $meta .= '📅 Fecha: ' . $fecha_inicio;
            }
            if ($localidad_str) {
                if ($meta) $meta .= ' • ';
                $meta .= '📍 Lugar: ' . $localidad_str;
            }
            
            $titulo = get_the_title();
            if ($edicion) {
                $titulo .= ' ' . $edicion;
            }
            
            $image = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
            
            $results[] = array(
                'title' => $titulo,
                'url'   => get_permalink(),
                'image' => $image,
                'meta'  => $meta,
            );
        }
        wp_reset_postdata();
    }
    
    echo wp_json_encode(array(
        'success' => true,
        'results' => $results,
        'total'   => $search_query->found_posts,
    ));
    
    wp_die();
}
add_action('wp_ajax_mf_live_search', 'mf_live_search_handler');
add_action('wp_ajax_nopriv_mf_live_search', 'mf_live_search_handler');

/* ==========================================================================
   4. AJAX: Obtener localidades hijas
   ========================================================================== */

function mf_get_localidades_handler() {
    $comunidad_id = isset($_POST['comunidad_id']) ? intval($_POST['comunidad_id']) : 0;
    
    if (!$comunidad_id) {
        wp_send_json(array('localidades' => array()));
    }

    // Cache per comunidad to avoid rebuilding term list + counts on every request.
    $transient_key = 'mf_localidades_' . $comunidad_id;
    $cached = get_transient($transient_key);
    if (is_array($cached)) {
        wp_send_json(array('localidades' => $cached));
    }
    
    $localidades = get_terms(array(
        'taxonomy'   => 'localidad',
        'parent'     => $comunidad_id,
        'hide_empty' => false,
        'orderby'    => 'name',
    ));
    
    $result = array();
    
    if (!empty($localidades) && !is_wp_error($localidades)) {
        // Fetch counts for all child localidades in one query (avoid N+1 WP_Query loop).
        global $wpdb;
        $tt = $wpdb->term_taxonomy;
        $tr = $wpdb->term_relationships;
        $posts = $wpdb->posts;

        $counts = array();
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT tt.term_id, COUNT(DISTINCT p.ID) AS cnt
                FROM {$tt} tt
                LEFT JOIN {$tr} tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
                LEFT JOIN {$posts} p
                  ON p.ID = tr.object_id
                 AND p.post_type = %s
                 AND p.post_status = 'publish'
                WHERE tt.taxonomy = %s
                  AND tt.parent = %d
                GROUP BY tt.term_id
                ",
                'festi',
                'localidad',
                $comunidad_id
            )
        );
        if (!empty($rows)) {
            foreach ($rows as $r) {
                $counts[(int) $r->term_id] = (int) $r->cnt;
            }
        }

        foreach ($localidades as $localidad) {
            $term_id = (int) $localidad->term_id;
            $result[] = array(
                'term_id' => $term_id,
                'slug'    => $localidad->slug,
                'name'    => $localidad->name,
                'count'   => $counts[$term_id] ?? 0,
            );
        }
    }
    
    // Cache for 6 hours; invalidate manually when taxonomy changes.
    set_transient($transient_key, $result, 6 * HOUR_IN_SECONDS);

    wp_send_json(array('localidades' => $result));
}
add_action('wp_ajax_mf_get_localidades', 'mf_get_localidades_handler');
add_action('wp_ajax_nopriv_mf_get_localidades', 'mf_get_localidades_handler');

/* ==========================================================================
   5. AJAX: Filtrar festivales (con anuncios in-feed)
   ========================================================================== */

function mf_filtrar_festivales_handler() {
    if (!isset($_POST['action']) || $_POST['action'] != 'mf_filtrar_festivales') {
        wp_die('Petición inválida');
    }
    
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $per_page = 24;

    // Cache filtered results for a short period to reduce repeated expensive AJAX calls.
    // This is especially effective with persistent object cache (Redis) on the server.
    $cache_key = 'mf_filtrar_' . md5(wp_json_encode(array(
        'page'      => $page,
        'per_page'  => $per_page,
        'localidad' => isset($_POST['localidad']) ? sanitize_text_field(wp_unslash($_POST['localidad'])) : '',
        'comunidad' => isset($_POST['comunidad']) ? sanitize_text_field(wp_unslash($_POST['comunidad'])) : '',
        'estilo'    => isset($_POST['estilo']) ? sanitize_text_field(wp_unslash($_POST['estilo'])) : '',
        'mes'       => isset($_POST['mes']) ? sanitize_text_field(wp_unslash($_POST['mes'])) : '',
        'estado'    => isset($_POST['estado']) ? sanitize_text_field(wp_unslash($_POST['estado'])) : '',
    )));
    $cached = get_transient($cache_key);
    if (is_array($cached) && isset($cached['html'])) {
        wp_send_json($cached);
    }
    
    $args = array(
        'post_type'      => 'festi',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        // We only need IDs for sorting/pagination. We'll fetch the page posts later in one query.
        'fields'                 => 'ids',
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        'ignore_sticky_posts'    => true,
        'tax_query'      => array('relation' => 'AND'),
        'meta_query'     => array('relation' => 'AND'),
    );
    
    // Filtro por localidad
    if (!empty($_POST['localidad'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'localidad',
            'field'    => 'slug',
            'terms'    => sanitize_text_field(wp_unslash($_POST['localidad'])),
        );
    } elseif (!empty($_POST['comunidad'])) {
        $comunidad_id = intval($_POST['comunidad']);
        $term_ids = array($comunidad_id);
        
        $hijas = get_terms(array(
            'taxonomy'   => 'localidad',
            'parent'     => $comunidad_id,
            'fields'     => 'ids',
            'hide_empty' => false,
        ));
        
        if (!empty($hijas) && !is_wp_error($hijas)) {
            $term_ids = array_merge($term_ids, $hijas);
        }
        
        $args['tax_query'][] = array(
            'taxonomy' => 'localidad',
            'field'    => 'term_id',
            'terms'    => $term_ids,
        );
    }
    
    // Filtro por estilo
    if (!empty($_POST['estilo'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'estilo_musical',
            'field'    => 'slug',
            'terms'    => sanitize_text_field(wp_unslash($_POST['estilo'])),
        );
    }
    
    // Filtro por mes
    if (!empty($_POST['mes'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'mes',
            'field'    => 'slug',
            'terms'    => sanitize_text_field(wp_unslash($_POST['mes'])),
        );
    }
    
    // Filtro por estado
    if (!empty($_POST['estado'])) {
        $estado = sanitize_text_field(wp_unslash($_POST['estado']));
        
        if ($estado === 'confechas') {
            $args['meta_query'] = array(
                'relation' => 'AND',
                array(
                    'key'     => 'sin_fechas_confirmadas',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => 'cancelado',
                    'compare' => 'NOT EXISTS',
                ),
            );
        } elseif ($estado === 'sinfechas') {
            $args['meta_query'][] = array(
                'key'     => 'sin_fechas_confirmadas',
                'value'   => '1',
                'compare' => '=',
            );
        } elseif ($estado === 'cancelados') {
            $args['meta_query'][] = array(
                'key'     => 'cancelado',
                'value'   => '1',
                'compare' => '=',
            );
        }
    }
    
    $query = new WP_Query($args);
    $all_ids = is_array($query->posts) ? $query->posts : array();

    // Reordenar: mes actual primero, luego futuros, pasados y sin fecha.
    // IMPORTANT: avoid calling get_field() for every post during sorting (N+1). We bulk-load meta we need.
    $ordered_ids = array();
    if (!empty($all_ids)) {
        global $wpdb;
        $pm = $wpdb->postmeta;
        $id_chunks = array_chunk($all_ids, 1000);

        $fecha_inicio_by_id = array();
        $sin_fechas_by_id = array();

        foreach ($id_chunks as $chunk) {
            $in = implode(',', array_fill(0, count($chunk), '%d'));
            $sql = "
                SELECT post_id, meta_key, meta_value
                FROM {$pm}
                WHERE post_id IN ($in)
                  AND meta_key IN ('fecha_inicio', 'sin_fechas_confirmadas')
            ";
            $rows = $wpdb->get_results($wpdb->prepare($sql, $chunk));
            if (!empty($rows)) {
                foreach ($rows as $r) {
                    $pid = (int) $r->post_id;
                    if ($r->meta_key === 'fecha_inicio') {
                        $fecha_inicio_by_id[$pid] = (string) $r->meta_value;
                    } elseif ($r->meta_key === 'sin_fechas_confirmadas') {
                        $sin_fechas_by_id[$pid] = (string) $r->meta_value;
                    }
                }
            }
        }

        $mes_actual = (int) date('n');
        $year_actual = (int) date('Y');
        $timestamp_hoy = time();

        $ids_mes_actual = array();
        $ids_futuros = array();
        $ids_pasados = array();
        $ids_sin_fecha = array();
        $ts_by_id = array();

        foreach ($all_ids as $pid) {
            $pid = (int) $pid;
            $fecha_inicio = $fecha_inicio_by_id[$pid] ?? '';
            $sin_fechas = !empty($sin_fechas_by_id[$pid]) && $sin_fechas_by_id[$pid] !== '0';

            if ($sin_fechas || empty($fecha_inicio)) {
                $ids_sin_fecha[] = $pid;
                continue;
            }

            $parts = explode('/', $fecha_inicio);
            if (count($parts) !== 3) {
                $ids_sin_fecha[] = $pid;
                continue;
            }

            $dia = (int) $parts[0];
            $mes_festival = (int) $parts[1];
            $year_festival = (int) $parts[2];
            $ts = mktime(0, 0, 0, $mes_festival, $dia, $year_festival);
            $ts_by_id[$pid] = $ts;

            if ($mes_festival === $mes_actual && $year_festival === $year_actual) {
                $ids_mes_actual[] = $pid;
            } elseif ($ts >= $timestamp_hoy) {
                $ids_futuros[] = $pid;
            } else {
                $ids_pasados[] = $pid;
            }
        }

        $ordenar_por_ts = function($a, $b) use ($ts_by_id) {
            $ta = $ts_by_id[$a] ?? PHP_INT_MAX;
            $tb = $ts_by_id[$b] ?? PHP_INT_MAX;
            return $ta <=> $tb;
        };

        usort($ids_mes_actual, $ordenar_por_ts);
        usort($ids_futuros, $ordenar_por_ts);
        usort($ids_pasados, $ordenar_por_ts);

        $ordered_ids = array_merge($ids_mes_actual, $ids_futuros, $ids_pasados, $ids_sin_fecha);
    }

    $total_posts = count($ordered_ids);
    $total_pages = (int) ceil($total_posts / $per_page);
    $offset = ($page - 1) * $per_page;
    $ids_pagina = array_slice($ordered_ids, $offset, $per_page);

    // Fetch only the posts for this page (preserve order).
    $posts_pagina = array();
    if (!empty($ids_pagina)) {
        $posts_pagina = get_posts(array(
            'post_type'      => 'festi',
            'post_status'    => 'publish',
            'posts_per_page' => count($ids_pagina),
            'post__in'       => $ids_pagina,
            'orderby'        => 'post__in',
        ));
    }
    
    $response = array(
        'html'         => '',
        'total'        => $total_posts,
        'current_page' => $page,
        'total_pages'  => $total_pages,
        'hasmore'      => $page < $total_pages,
    );
    
    ob_start();
    
    if (!empty($posts_pagina)) {
        $counter = 0;
        foreach ($posts_pagina as $post) {
            setup_postdata($post);
            $counter++;
            
            // ANUNCIO IN-FEED CADA 10 FESTIVALES
            if ($counter % 10 == 0) {
                ?>
                <div class="mf-card-ad" data-ad-card>
                    <div class="mf-ad-infeed">
                        <!-- Desktop: Anuncio fluid -->
                        <div class="mf-ad-infeed-desktop">
                            <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5154850110659563" crossorigin="anonymous"></script>
                            <ins class="adsbygoogle"
                                 style="display:block"
                                 data-ad-format="fluid"
                                 data-ad-layout-key="-6t+ed+2i-1n-4w"
                                 data-ad-client="ca-pub-5154850110659563"
                                 data-ad-slot="4600798983"></ins>
                            <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
                        </div>
                        
                        <!-- Móvil: 320x100 -->
                        <div class="mf-ad-infeed-mobile">
                            <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5154850110659563" crossorigin="anonymous"></script>
                            <ins class="adsbygoogle"
                                 style="display:block;width:320px;height:100px"
                                 data-ad-client="ca-pub-5154850110659563"
                                 data-ad-slot="9035809968"
                                 data-ad-format="horizontal"></ins>
                            <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
                        </div>
                    </div>
                </div>
                <?php
            }
            
      $fecha_inicio    = get_field('fecha_inicio', $post->ID);
$fecha_fin       = get_field('fecha_fin', $post->ID);
$cancelado       = get_field('cancelado', $post->ID);
$sin_fechas      = get_field('sin_fechas_confirmadas', $post->ID);
$enlace_entradas = get_field('enlace_entradas', $post->ID);
$edicion         = get_field('edicion', $post->ID);

            
            $localidad_terms = wp_get_post_terms($post->ID, 'localidad');
            $localidad_ciudad = '';
            $localidad_comunidad = '';
            
            if (!empty($localidad_terms) && !is_wp_error($localidad_terms)) {
                foreach ($localidad_terms as $loc_term) {
                    if ($loc_term->parent == 0) {
                        $localidad_comunidad = $loc_term->name;
                    } else {
                        $localidad_ciudad = $loc_term->name;
                    }
                }
            }
            
            $estilo_terms = wp_get_post_terms($post->ID, 'estilo_musical', array('fields' => 'names'));
            
            $loading = ($counter <= 4) ? 'eager' : 'lazy';
            $fetchpriority = ($counter <= 4) ? 'high' : 'auto';
            ?>
            
            <article class="mf-card" itemscope itemtype="https://schema.org/MusicEvent">
                
                <?php
                // CALCULAR DÍAS QUE FALTAN Y ESTADO
                $dias_faltan = null;
                $estado_festival = null;
                
                if ($fecha_inicio && !$sin_fechas && !$cancelado) {
                    $partes = explode('/', $fecha_inicio);
                    if (count($partes) === 3) {
                        $dia = (int)$partes[0];
                        $mes = (int)$partes[1];
                        $ano = (int)$partes[2];
                        
                        $fecha_inicio_timestamp = mktime(0, 0, 0, $mes, $dia, $ano);
                        $fecha_hoy = time();
                        
                        $fecha_fin_timestamp = $fecha_inicio_timestamp;
                        if ($fecha_fin) {
                            $partes_fin = explode('/', $fecha_fin);
                            if (count($partes_fin) === 3) {
                                $fecha_fin_timestamp = mktime(23, 59, 59, (int)$partes_fin[1], (int)$partes_fin[0], (int)$partes_fin[2]);
                            }
                        }
                        
                        if ($fecha_hoy >= $fecha_inicio_timestamp && $fecha_hoy <= $fecha_fin_timestamp) {
                            $estado_festival = 'en_curso';
                        } elseif ($fecha_hoy > $fecha_fin_timestamp) {
                            $estado_festival = 'celebrado';
                        } else {
                            $estado_festival = 'futuro';
                            $diferencia = $fecha_inicio_timestamp - $fecha_hoy;
                            $dias_faltan = floor($diferencia / (60 * 60 * 24));
                        }
                    }
                }
                ?>
                
                <?php if ($estado_festival) : ?>
                    <div class="mf-card-countdown">
                        <?php if ($estado_festival === 'en_curso') : ?>
                            <span class="mf-countdown-badge mf-countdown-en-curso">¡Ya empezó!</span>
                        
                        <?php elseif ($estado_festival === 'celebrado') : ?>
                            <span class="mf-countdown-badge mf-countdown-celebrado">Ya celebrado</span>
                        
                        <?php elseif ($estado_festival === 'futuro' && $dias_faltan !== null) : ?>
                            <?php if ($dias_faltan === 0) : ?>
                                <span class="mf-countdown-badge mf-countdown-hoy">¡Hoy!</span>
                            <?php elseif ($dias_faltan === 1) : ?>
                                <span class="mf-countdown-badge mf-countdown-pronto">Falta 1 día</span>
                            <?php elseif ($dias_faltan <= 7) : ?>
                                <span class="mf-countdown-badge mf-countdown-urgente">Faltan <?php echo $dias_faltan; ?> días</span>
                            <?php elseif ($dias_faltan <= 30) : ?>
                                <span class="mf-countdown-badge mf-countdown-pronto">Faltan <?php echo $dias_faltan; ?> días</span>
                            <?php else : ?>
                                <span class="mf-countdown-badge">Faltan <?php echo $dias_faltan; ?> días</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (has_post_thumbnail($post->ID)) : ?>
                    <a href="<?php echo esc_url(get_permalink($post->ID)); ?>" class="mf-card__image">
                        <?php
                        $alt_text = get_the_title($post->ID);
                        if ($edicion) $alt_text .= ' ' . $edicion;
                        if ($localidad_ciudad) $alt_text .= ' - ' . $localidad_ciudad;
                        
                        echo get_the_post_thumbnail(
                            $post->ID,
                            'medium',
                            array(
                                'loading'       => $loading,
                                'fetchpriority' => $fetchpriority,
                                'itemprop'      => 'image',
                                'alt'           => esc_attr($alt_text),
                            )
                        );
                        ?>
                        
                        <?php if ($edicion) : ?>
                            <span class="mf-badge mf-badge--edicion"><?php echo esc_html($edicion); ?></span>
                        <?php endif; ?>
                        
                        <?php if ($cancelado) : ?>
                            <span class="mf-badge mf-badge--cancelado">Cancelado</span>
                        <?php elseif ($sin_fechas) : ?>
                            <span class="mf-badge mf-badge--sin-fechas">Sin fechas confirmadas</span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
                
                <div class="mf-card__content">
                    <h3 class="mf-card__title" itemprop="name">
                        <a href="<?php echo esc_url(get_permalink($post->ID)); ?>">
                            <?php
                            $titulo = get_the_title($post->ID);
                            if ($edicion) $titulo .= ' ' . $edicion;
                            echo esc_html($titulo);
                            ?>
                        </a>
                    </h3>
                    
                    <div class="mf-card__meta">
                        <?php if ($fecha_inicio && $fecha_fin && !$sin_fechas) : ?>
                            <span class="mf-meta-item" itemprop="startDate">
                                <?php echo esc_html($fecha_inicio . ' - ' . $fecha_fin); ?>
                            </span>
                        <?php elseif ($fecha_inicio && !$sin_fechas) : ?>
                            <span class="mf-meta-item" itemprop="startDate">
                                <?php echo esc_html($fecha_inicio); ?>
                            </span>
                        <?php elseif ($sin_fechas) : ?>
                            <span class="mf-meta-item">Fechas por confirmar</span>
                        <?php endif; ?>
                        
                        <?php if ($localidad_ciudad || $localidad_comunidad) : ?>
                            <span class="mf-meta-item" itemprop="location">
                                <?php
                                $local_text = array_filter(array($localidad_ciudad, $localidad_comunidad));
                                echo esc_html(implode(', ', $local_text));
                                ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if (!empty($estilo_terms) && !is_wp_error($estilo_terms)) : ?>
                            <span class="mf-meta-item mf-meta__estilo">
                                <?php echo esc_html(implode(', ', $estilo_terms)); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
             <div class="mf-card__actions">
    <?php if ( ! empty( $enlace_entradas ) && empty( $cancelado ) ) : ?>
        <a href="<?php echo esc_url( $enlace_entradas ); ?>"
           class="mf-btn-entradas"
           target="_blank"
           rel="noopener"
           itemprop="url">
            Entradas
        </a>
    <?php endif; ?>
</div>



                
            </article>
            
            <?php
        }
        wp_reset_postdata();
    } else {
        ?>
        <div class="mf-no-results">
            <p>No se han encontrado festivales con los filtros seleccionados.</p>
        </div>
        <?php
    }
    
    $response['html'] = ob_get_clean();
    
    // Cache for 5 minutes.
    set_transient($cache_key, $response, 5 * MINUTE_IN_SECONDS);

    wp_send_json($response);
}
add_action('wp_ajax_mf_filtrar_festivales', 'mf_filtrar_festivales_handler');
add_action('wp_ajax_nopriv_mf_filtrar_festivales', 'mf_filtrar_festivales_handler');
/* ==========================================================================
   REGISTRO DE SHORTCODES
   ========================================================================== */

// Shortcode principal - ahora soporta todos los atributos directamente
add_shortcode('festival_list', 'modofestival_ajax_festival_list_shortcode');

// Shortcodes especializados con detección automática de taxonomía
function modofestival_estilo_shortcode($atts) {
    $atts = shortcode_atts(array('estilo' => ''), $atts);
    
    // Si no se especifica estilo, intentar detectar de la taxonomía actual
    if (empty($atts['estilo']) && is_tax('estilo_musical')) {
        $term = get_queried_object();
        $atts['estilo'] = $term->slug;
    }
    
    return modofestival_ajax_festival_list_shortcode($atts);
}
add_shortcode('festival_list_estilo', 'modofestival_estilo_shortcode');

function modofestival_mes_shortcode($atts) {
    $atts = shortcode_atts(array('mes' => ''), $atts);
    
    // Si no se especifica mes, intentar detectar de la taxonomía actual
    if (empty($atts['mes']) && is_tax('mes')) {
        $term = get_queried_object();
        $atts['mes'] = $term->slug;
    }
    
    return modofestival_ajax_festival_list_shortcode($atts);
}
add_shortcode('festival_list_mes', 'modofestival_mes_shortcode');

function modofestival_comunidad_shortcode($atts) {
    $atts = shortcode_atts(array('comunidad' => ''), $atts);
    
    // Si no se especifica comunidad, intentar detectar de la taxonomía actual
    if (empty($atts['comunidad']) && is_tax('localidad')) {
        $term = get_queried_object();
        $atts['comunidad'] = $term->slug;
    }
    
    return modofestival_ajax_festival_list_shortcode($atts);
}
add_shortcode('festival_list_comunidad', 'modofestival_comunidad_shortcode');
/* ==========================================================================
   SHORTCODE HOMEPAGE: [festivales_destacados]
   ========================================================================== */

function modofestival_festivales_destacados_shortcode($atts = array()) {
    wp_enqueue_script('jquery');
    
    // Obtener últimos festivales actualizados para el carrusel
    $ultimos_actualizados = get_posts(array(
        'post_type' => 'festi',
        'posts_per_page' => 20,
        'orderby' => 'modified',
        'order' => 'DESC',
        'post_status' => 'publish',
    ));
    
    $args = array(
        'post_type' => 'festi',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    );
    
    $query = new WP_Query($args);
    
    // Arrays para organizar festivales
    $posts_futuros_con_fecha = array();
    $posts_sin_fecha = array();
    
    if ($query->have_posts()) {
        foreach ($query->posts as $post) {
            $cancelado = get_field('cancelado', $post->ID);
            
            // EXCLUIR festivales cancelados
            if ($cancelado) {
                continue;
            }
            
            $fecha_inicio = get_field('fecha_inicio', $post->ID);
            $fecha_fin = get_field('fecha_fin', $post->ID);
            $sin_fechas = get_field('sin_fechas_confirmadas', $post->ID);
            
            if ($sin_fechas || empty($fecha_inicio)) {
                $posts_sin_fecha[] = $post;
            } else {
                $parts = explode('/', $fecha_inicio);
                
                if (count($parts) == 3) {
                    $dia = (int)$parts[0];
                    $mes_festival = (int)$parts[1];
                    $year_festival = (int)$parts[2];
                    
                    if ($fecha_fin) {
                        $parts_fin = explode('/', $fecha_fin);
                        if (count($parts_fin) == 3) {
                            $timestamp_festival = mktime(23, 59, 59, (int)$parts_fin[1], (int)$parts_fin[0], (int)$parts_fin[2]);
                        } else {
                            $timestamp_festival = mktime(23, 59, 59, $mes_festival, $dia, $year_festival);
                        }
                    } else {
                        $timestamp_festival = mktime(23, 59, 59, $mes_festival, $dia, $year_festival);
                    }
                    
                    $timestamp_hoy = time();
                    
                    if ($timestamp_festival >= $timestamp_hoy) {
                        $posts_futuros_con_fecha[] = $post;
                    }
                }
            }
        }
    }
    
    $ordenar_por_fecha = function($a, $b) {
        $fecha_a = get_field('fecha_inicio', $a->ID);
        $fecha_b = get_field('fecha_inicio', $b->ID);
        
        if (empty($fecha_a)) return 1;
        if (empty($fecha_b)) return -1;
        
        $parts_a = explode('/', $fecha_a);
        $parts_b = explode('/', $fecha_b);
        
        if (count($parts_a) == 3 && count($parts_b) == 3) {
            $timestamp_a = mktime(0, 0, 0, $parts_a[1], $parts_a[0], $parts_a[2]);
            $timestamp_b = mktime(0, 0, 0, $parts_b[1], $parts_b[0], $parts_b[2]);
            return $timestamp_a - $timestamp_b;
        }
        return 0;
    };
    
    usort($posts_futuros_con_fecha, $ordenar_por_fecha);
    
    $posts_todos = array_merge($posts_futuros_con_fecha, $posts_sin_fecha);
    
    // Detectar si es móvil
    $is_mobile = wp_is_mobile();
    
    // Limitar a 4 en móvil, 16 en escritorio
    $limit = $is_mobile ? 4 : 16;
    $posts_finales = array_slice($posts_todos, 0, $limit);
    
    $unique_id = 'mf-' . uniqid();
    
    ob_start();
    ?>
    
    <div class="mf-agenda-wrapper" id="<?php echo esc_attr($unique_id); ?>">
        
        <!-- CARRUSEL DE ÚLTIMOS ACTUALIZADOS -->
        <div class="mf-ticker-wrapper">
            <div class="mf-ticker-label"><span>Últimos actualizados</span></div>
            <div class="mf-ticker">
                <div class="mf-ticker-content">
                    <?php
                    // Duplicar contenido para scroll infinito
                    for ($i = 0; $i < 2; $i++) {
                        foreach ($ultimos_actualizados as $festival) {
                            $edicion = get_field('edicion', $festival->ID);
                            $titulo = get_the_title($festival->ID);
                            if ($edicion) {
                                $titulo .= ' ' . $edicion;
                            }
                            ?>
                            <a href="<?php echo esc_url(get_permalink($festival->ID)); ?>" class="mf-ticker-item">
                                <?php echo esc_html($titulo); ?>
                            </a>
                            <?php
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- LIVE SEARCH -->
        <div class="mf-search-wrapper">
            <div class="mf-search-container">
                <svg class="mf-search-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 17A8 8 0 1 0 9 1a8 8 0 0 0 0 16zM19 19l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <input type="text" class="mf-search-input" placeholder="Buscar festival..." autocomplete="off" />
                <button class="mf-search-clear" style="display:none;">×</button>
            </div>
            <div class="mf-search-results" style="display:none;">
                <div class="mf-search-results-inner"></div>
            </div>
        </div>
        
        <!-- LISTADO DE FESTIVALES -->
        <div class="mf-festivales-container">
            <div class="mf-grid">
            <?php
            if (!empty($posts_finales)) {
                $counter = 0;
                foreach ($posts_finales as $post) {
                    setup_postdata($post);
                    $counter++;
                    
                    $fecha_inicio = get_field('fecha_inicio', $post->ID);
                    $fecha_fin = get_field('fecha_fin', $post->ID);
                    $cancelado = get_field('cancelado', $post->ID);
                    $sin_fechas = get_field('sin_fechas_confirmadas', $post->ID);
                    $enlace_entradas = get_field('enlace_entradas', $post->ID);
                    $edicion = get_field('edicion', $post->ID);
                    
                    $localidad_terms = wp_get_post_terms($post->ID, 'localidad');
                    $localidad_ciudad = '';
                    $localidad_comunidad = '';
                    
                    if (!empty($localidad_terms) && !is_wp_error($localidad_terms)) {
                        foreach ($localidad_terms as $loc_term) {
                            if ($loc_term->parent == 0) {
                                $localidad_comunidad = $loc_term->name;
                            } else {
                                $localidad_ciudad = $loc_term->name;
                            }
                        }
                    }
                    
                    $estilo_terms = wp_get_post_terms($post->ID, 'estilomusical', array('fields' => 'names'));
                    
                    $loading = $counter <= 4 ? 'eager' : 'lazy';
                    $fetchpriority = $counter <= 4 ? 'high' : 'auto';
                    
                    // CALCULAR DÍAS QUE FALTAN Y ESTADO
                    $dias_faltan = null;
                    $estado_festival = null;
                    
                    if ($fecha_inicio && !$sin_fechas && !$cancelado) {
                        $partes = explode('/', $fecha_inicio);
                        if (count($partes) == 3) {
                            $dia = (int)$partes[0];
                            $mes = (int)$partes[1];
                            $ano = (int)$partes[2];
                            
                            $fecha_inicio_timestamp = mktime(0, 0, 0, $mes, $dia, $ano);
                            $fecha_hoy = time();
                            $fecha_fin_timestamp = $fecha_inicio_timestamp;
                            
                            if ($fecha_fin) {
                                $partes_fin = explode('/', $fecha_fin);
                                if (count($partes_fin) == 3) {
                                    $fecha_fin_timestamp = mktime(23, 59, 59, (int)$partes_fin[1], (int)$partes_fin[0], (int)$partes_fin[2]);
                                }
                            }
                            
                            if ($fecha_hoy >= $fecha_inicio_timestamp && $fecha_hoy <= $fecha_fin_timestamp) {
                                $estado_festival = 'encurso';
                            } elseif ($fecha_hoy > $fecha_fin_timestamp) {
                                $estado_festival = 'celebrado';
                            } else {
                                $estado_festival = 'futuro';
                                $diferencia = $fecha_inicio_timestamp - $fecha_hoy;
                                $dias_faltan = floor($diferencia / (60 * 60 * 24));
                            }
                        }
                    }
                    ?>
                    
                    <article class="mf-card" itemscope itemtype="https://schema.org/MusicEvent">
                        
                        <?php if ($estado_festival): ?>
                        <div class="mf-card-countdown">
                            <?php if ($estado_festival == 'encurso'): ?>
                                <span class="mf-countdown-badge mf-countdown-en-curso">¡Ya empezó!</span>
                            <?php elseif ($estado_festival == 'celebrado'): ?>
                                <span class="mf-countdown-badge mf-countdown-celebrado">Ya celebrado</span>
                            <?php elseif ($estado_festival == 'futuro' && $dias_faltan !== null): ?>
                                <?php if ($dias_faltan == 0): ?>
                                    <span class="mf-countdown-badge mf-countdown-hoy">¡Hoy!</span>
                                <?php elseif ($dias_faltan == 1): ?>
                                    <span class="mf-countdown-badge mf-countdown-pronto">Falta 1 día</span>
                                <?php elseif ($dias_faltan <= 7): ?>
                                    <span class="mf-countdown-badge mf-countdown-urgente">Faltan <?php echo $dias_faltan; ?> días</span>
                                <?php elseif ($dias_faltan <= 30): ?>
                                    <span class="mf-countdown-badge mf-countdown-pronto">Faltan <?php echo $dias_faltan; ?> días</span>
                                <?php else: ?>
                                    <span class="mf-countdown-badge">Faltan <?php echo $dias_faltan; ?> días</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (has_post_thumbnail($post->ID)): ?>
                        <a href="<?php echo esc_url(get_permalink($post->ID)); ?>" class="mf-card__image">
                            <?php
                            $alt_text = get_the_title($post->ID);
                            if ($edicion) $alt_text .= ' ' . $edicion;
                            if ($localidad_ciudad) $alt_text .= ' - ' . $localidad_ciudad;
                            
                            echo get_the_post_thumbnail(
                                $post->ID,
                                'medium',
                                array(
                                    'loading' => $loading,
                                    'fetchpriority' => $fetchpriority,
                                    'itemprop' => 'image',
                                    'alt' => esc_attr($alt_text)
                                )
                            );
                            ?>
                            
                            <?php if ($edicion): ?>
                                <span class="mf-badge mf-badge--edicion"><?php echo esc_html($edicion); ?></span>
                            <?php endif; ?>
                            
                            <?php if ($cancelado): ?>
                                <span class="mf-badge mf-badge--cancelado">Cancelado</span>
                            <?php elseif ($sin_fechas): ?>
                                <span class="mf-badge mf-badge--sin-fechas">Sin fechas confirmadas</span>
                            <?php endif; ?>
                        </a>
                        <?php endif; ?>
                        
                        <div class="mf-card__content">
                            <h3 class="mf-card__title" itemprop="name">
                                <a href="<?php echo esc_url(get_permalink($post->ID)); ?>">
                                    <?php
                                    $titulo = get_the_title($post->ID);
                                    if ($edicion) $titulo .= ' ' . $edicion;
                                    echo esc_html($titulo);
                                    ?>
                                </a>
                            </h3>
                            
                            <div class="mf-card__meta">
                                <?php if ($fecha_inicio && $fecha_fin && !$sin_fechas): ?>
                                    <span class="mf-meta-item" itemprop="startDate"><?php echo esc_html($fecha_inicio . ' - ' . $fecha_fin); ?></span>
                                <?php elseif ($fecha_inicio && !$sin_fechas): ?>
                                    <span class="mf-meta-item" itemprop="startDate"><?php echo esc_html($fecha_inicio); ?></span>
                                <?php elseif ($sin_fechas): ?>
                                    <span class="mf-meta-item">Fechas por confirmar</span>
                                <?php endif; ?>
                                
                                <?php if ($localidad_ciudad || $localidad_comunidad): ?>
                                    <span class="mf-meta-item" itemprop="location">
                                        <?php
                                        $loc_text = array_filter(array($localidad_ciudad, $localidad_comunidad));
                                        echo esc_html(implode(', ', $loc_text));
                                        ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($estilo_terms) && !is_wp_error($estilo_terms)): ?>
                                    <span class="mf-meta-item mf-meta__estilo"><?php echo esc_html(implode(', ', $estilo_terms)); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mf-card__actions">
                            <?php if (!empty($enlace_entradas) && empty($cancelado)): ?>
                                <a href="<?php echo esc_url($enlace_entradas); ?>" class="mf-btn-entradas" target="_blank" rel="noopener" itemprop="url">Entradas</a>
                            <?php endif; ?>
                        </div>
                        
                    </article>
                    
                    <?php
                }
                wp_reset_postdata();
            }
            ?>
            </div>
        </div>
        
        <!-- Botón Ver agenda completa -->
        <button class="mf-cargar-mas" style="display: block; margin: 40px auto 0;" onclick="window.location.href='<?php echo esc_url( home_url( '/agenda-festivales/' ) ); ?>'">
            Ver agenda completa
        </button>
        
    </div>
    
    <script type="text/javascript">
    (function() {
        function initModofestival() {
            if (typeof jQuery === 'undefined') {
                setTimeout(initModofestival, 100);
                return;
            }
            
            var $ = jQuery;
            var container = $('#<?php echo esc_js($unique_id); ?>');
            
            // LIVE SEARCH
            var searchInput = container.find('.mf-search-input');
            var searchResults = container.find('.mf-search-results');
            var searchResultsInner = container.find('.mf-search-results-inner');
            var searchClear = container.find('.mf-search-clear');
            var searchTimeout = null;
            
            searchInput.on('input', function() {
                var query = $(this).val().trim();
                
                if (query.length === 0) {
                    searchResults.hide();
                    searchClear.hide();
                    return;
                }
                
                searchClear.show();
                
                if (query.length < 2) {
                    return;
                }
                
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    buscarFestivales(query);
                }, 300);
            });
            
            searchClear.on('click', function() {
                searchInput.val('').focus();
                searchResults.hide();
                searchClear.hide();
            });
            
            $(document).on('click', function(e) {
                if (!container.find('.mf-search-wrapper').is(e.target) && container.find('.mf-search-wrapper').has(e.target).length === 0) {
                    searchResults.hide();
                }
            });
            
            function buscarFestivales(query) {
                searchResultsInner.html('<div class="mf-search-loading">Buscando...</div>');
                searchResults.show();
                
                $.ajax({
                    url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    type: 'POST',
                    data: {
                        action: 'mf_live_search',
                        query: query
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.results.length > 0) {
                            var html = '';
                            $.each(response.results, function(i, item) {
                                html += '<a href="' + item.url + '" class="mf-search-result-item">';
                                if (item.image) {
                                    html += '<div class="mf-search-result-image">';
                                    html += '<img src="' + item.image + '" alt="' + item.title + '" loading="lazy">';
                                    html += '</div>';
                                }
                                html += '<div class="mf-search-result-content">';
                                html += '<div class="mf-search-result-title">' + item.title + '</div>';
                                if (item.meta) {
                                    html += '<div class="mf-search-result-meta">' + item.meta + '</div>';
                                }
                                html += '</div>';
                                html += '</a>';
                            });
                            
                            if (response.total > response.results.length) {
                                html += '<div class="mf-search-result-more">';
                                html += 'Mostrando ' + response.results.length + ' de ' + response.total + ' resultados';
                                html += '</div>';
                            }
                            
                            searchResultsInner.html(html);
                        } else {
                            searchResultsInner.html('<div class="mf-search-no-results">No se encontraron resultados</div>');
                        }
                    },
                    error: function() {
                        searchResultsInner.html('<div class="mf-search-error">Error en la búsqueda</div>');
                    }
                });
            }
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initModofestival);
        } else {
            initModofestival();
        }
    })();
    </script>
    
    <?php
    return ob_get_clean();
}
add_shortcode('festivales_destacados', 'modofestival_festivales_destacados_shortcode');
