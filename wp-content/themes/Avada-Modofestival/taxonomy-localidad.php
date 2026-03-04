<?php
/**
 * Plantilla para archivo de taxonomía: Localidad (Comunidades)
 */

// Obtener el término actual
$term = get_queried_object();
$localidad_id = $term->term_id;
$localidad_slug = $term->slug;
$localidad_nombre = $term->name;

// Descripción nativa de WordPress (arriba)
$descripcion_nativa = term_description($term->term_id, 'localidad');

// Campo ACF personalizado (abajo)
$descripcion_larga = get_field('descripcion_larga', $term);

get_header(); 
?>

<style>
/* Reset completo para taxonomías */
body.tax-localidad #content,
body.tax-localidad #main {
    width: 100% !important;
    max-width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
}

/* Contenedor principal */
.mf-taxonomy-container {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
    padding: 40px 20px;
    box-sizing: border-box;
}

/* Cabecera de taxonomía */
.mf-taxonomy-header {
    margin-bottom: 40px;
}

.mf-taxonomy-title {
    margin: 0 0 20px 0;
    font-size: 32px;
    font-weight: 700;
    color: #2c3e50;
    line-height: 1.2;
}

.mf-taxonomy-desc-short {
    font-size: 16px;
    color: #555;
    line-height: 1.6;
}

/* Descripción larga (después del listado) */
.mf-taxonomy-footer {
    margin-top: 60px;
    padding-top: 40px;
    border-top: 1px solid #e0e0e0;
}

.mf-taxonomy-desc-long {
    font-size: 16px;
    color: #2c3e50;
    line-height: 1.6;
    width: 100%;
}

.mf-taxonomy-desc-long h2,
.mf-taxonomy-desc-long h3 {
    margin-top: 24px;
    margin-bottom: 12px;
    color: #2c3e50;
}

.mf-taxonomy-desc-long p {
    margin-bottom: 16px;
}

.mf-taxonomy-desc-long ul,
.mf-taxonomy-desc-long ol {
    margin-bottom: 16px;
    padding-left: 24px;
}

/* Ocultar buscador */
body.tax-localidad .mf-search-wrapper,
body.tax-localidad .mf-busqueda-floater,
body.tax-localidad .mf-ticker-wrapper {
    display: none !important;
}

/* Ocultar el selector de comunidad */
body.tax-localidad .mf-comunidad {
    display: none !important;
}

/* Mostrar el selector de localidades */
body.tax-localidad .mf-localidad {
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

/* Forzar ancho completo del wrapper */
body.tax-localidad .mf-agenda-wrapper {
    width: 100% !important;
    max-width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
}

/* Grid responsive */
@media (min-width: 1400px) {
    body.tax-localidad .mf-grid {
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 24px !important;
    }
}

@media (min-width: 1024px) and (max-width: 1399px) {
    body.tax-localidad .mf-grid {
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 20px !important;
    }
}

@media (min-width: 768px) and (max-width: 1023px) {
    body.tax-localidad .mf-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 16px !important;
    }
}

@media (max-width: 767px) {
    body.tax-localidad .mf-grid {
        grid-template-columns: 1fr !important;
        gap: 16px !important;
    }
    
    .mf-taxonomy-container {
        padding: 20px 10px;
    }
    
    .mf-taxonomy-title {
        font-size: 24px;
    }
    
    .mf-taxonomy-footer {
        margin-top: 40px;
        padding-top: 30px;
    }
}
</style>

<div id="content">
    <main id="main" class="site-main">
        
        <div class="mf-taxonomy-container">
            
            <!-- Cabecera con descripción corta -->
            <div class="mf-taxonomy-header">
                <h1 class="mf-taxonomy-title">
                    Festivales en <?php echo esc_html($localidad_nombre); ?>
                </h1>
                
                <!-- Descripción nativa (corta) -->
                <?php if ($descripcion_nativa): ?>
                    <div class="mf-taxonomy-desc-short">
                        <?php echo wp_kses_post($descripcion_nativa); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Shortcode con listado de festivales -->
            <?php echo do_shortcode('[festival_list_comunidad comunidad="' . esc_attr($localidad_id) . '"]'); ?>
            
            <!-- Descripción larga (al final) -->
            <?php if ($descripcion_larga): ?>
                <div class="mf-taxonomy-footer">
                    <div class="mf-taxonomy-desc-long">
                        <?php echo wp_kses_post($descripcion_larga); ?>
                    </div>
                </div>
            <?php endif; ?>
            
        </div>
        
    </main>
</div>

<script>
(function() {
    'use strict';
    
    var comunidadId = <?php echo intval($localidad_id); ?>;
    
    function esperarJQuery(callback) {
        if (typeof jQuery !== 'undefined') {
            callback(jQuery);
        } else {
            setTimeout(function() {
                esperarJQuery(callback);
            }, 100);
        }
    }
    
    function inicializarLocalidades($) {
        var $comunidad = $('.mf-comunidad');
        var $localidad = $('.mf-localidad');
        
        if (!$comunidad.length || !$localidad.length) {
            setTimeout(function() {
                inicializarLocalidades($);
            }, 200);
            return;
        }
        
        console.log('Iniciando carga de localidades para comunidad:', comunidadId);
        
        // Preseleccionar la comunidad
        $comunidad.val(comunidadId);
        
        // Cargar localidades vía AJAX
        $.ajax({
            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
            type: 'POST',
            data: {
                action: 'mf_get_localidades',
                comunidad_id: comunidadId
            },
            success: function(response) {
                console.log('Respuesta AJAX recibida:', response);
                
                try {
                    var data = JSON.parse(response);
                    
                    if (data.localidades && data.localidades.length > 0) {
                        var comunidadNombre = $comunidad.find('option:selected').data('name');
                        var placeholder = comunidadNombre ? 'Localidades en ' + comunidadNombre : 'Todas las localidades';
                        
                        $localidad.html('<option value="">' + placeholder + '</option>');
                        
                        $.each(data.localidades, function(i, loc) {
                            $localidad.append('<option value="' + loc.slug + '" data-name="' + loc.name + '">' + loc.name + ' (' + loc.count + ')</option>');
                        });
                        
                        $localidad.show();
                        console.log('✓ Localidades cargadas:', data.localidades.length);
                    } else {
                        console.log('No hay localidades para esta comunidad');
                        $localidad.hide();
                    }
                } catch(e) {
                    console.error('Error parseando respuesta:', e);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX:', error);
            }
        });
    }
    
    // Esperar a que jQuery esté disponible
    esperarJQuery(function($) {
        console.log('jQuery listo, esperando DOM...');
        
        $(document).ready(function() {
            console.log('DOM listo, esperando shortcode...');
            setTimeout(function() {
                inicializarLocalidades($);
            }, 800);
        });
    });
})();
</script>

<?php get_footer(); ?>





