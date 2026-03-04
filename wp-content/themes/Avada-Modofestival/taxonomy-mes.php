<?php
/**
 * Plantilla para archivo de taxonomía: Mes
 */

// Obtener el término actual
$term = get_queried_object();
$mes_slug = $term->slug;
$mes_nombre = $term->name;

// Descripción nativa de WordPress (arriba)
$descripcion_nativa = term_description($term->term_id, 'mes');

// Campo ACF personalizado (abajo)
$descripcion_larga = get_field('descripcion_larga', $term);

get_header(); 
?>

<style>
/* Reset completo para taxonomías */
body.tax-mes #content,
body.tax-mes #main {
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
body.tax-mes .mf-search-wrapper,
body.tax-mes .mf-busqueda-floater,
body.tax-mes .mf-ticker-wrapper {
    display: none !important;
}

/* Ocultar selector de mes (ya está filtrado) */
body.tax-mes .mf-mes {
    display: none !important;
}

/* Forzar ancho completo del wrapper */
body.tax-mes .mf-agenda-wrapper {
    width: 100% !important;
    max-width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
}

/* Grid responsive */
@media (min-width: 1400px) {
    body.tax-mes .mf-grid {
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 24px !important;
    }
}

@media (min-width: 1024px) and (max-width: 1399px) {
    body.tax-mes .mf-grid {
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 20px !important;
    }
}

@media (min-width: 768px) and (max-width: 1023px) {
    body.tax-mes .mf-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 16px !important;
    }
}

@media (max-width: 767px) {
    body.tax-mes .mf-grid {
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
                    Festivales en <?php echo esc_html($mes_nombre); ?>
                </h1>
                
                <!-- Descripción nativa (corta) -->
                <?php if ($descripcion_nativa): ?>
                    <div class="mf-taxonomy-desc-short">
                        <?php echo wp_kses_post($descripcion_nativa); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Shortcode con listado de festivales -->
            <?php echo do_shortcode('[festival_list_mes mes="' . esc_attr($mes_slug) . '"]'); ?>
            
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

<?php get_footer(); ?>
