<?php
/**
 * Plugin Name: Modofestival – Lean Front (scripts seguros)
 * Description: Reduce JS en el frontend con desactivaciones seguras y condicionales. No afecta al admin.
 * Author: Pau / Modofestival
 */

if ( ! defined('ABSPATH') ) exit;

/**
 * 1) Desactiva emojis (JS + DNS prefetch)
 */
add_action('init', function (){
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    add_filter('emoji_svg_url', '__return_false');
});

/**
 * 2) Desactiva o limita Heartbeat en el front para ahorrar CPU/peticiones
 */
add_action('init', function() {
    if ( ! is_admin() ) {
        wp_deregister_script('heartbeat');
    }
});
add_filter('heartbeat_settings', function($settings) {
    $settings['interval'] = 60; // segundos (por si se ejecuta en alguna vista)
    return $settings;
});

/**
 * 3) Quita Dashicons para visitantes (ahorra un request y algo de JS)
 */
add_action('wp_enqueue_scripts', function (){
    if ( ! is_user_logged_in() ) {
        wp_deregister_style('dashicons');
    }
}, 100);

/**
 * 4) Elimina wp-embed (evita script para embeber posts de WP dentro de WP)
 */
add_action('wp_footer', function (){
    wp_deregister_script('wp-embed');
}, 1);

/**
 * 5) comment-reply solo donde hace falta (single con comentarios enhebrados)
 */
add_action('wp_enqueue_scripts', function () {
    if ( is_singular() && comments_open() && get_option('thread_comments') ) {
        wp_enqueue_script('comment-reply');
    } else {
        wp_dequeue_script('comment-reply');
    }
}, 11);

/**
 * 6) Desactiva scripts de plugins sociales/ratings fuera de single (ajusta handles si cambian)
 *    Detectados en tu instalación: 'ssb-front-js' (Simple Social Buttons), 'kk-star-ratings'
 */
add_action('wp_enqueue_scripts', function (){
    if ( ! is_single() ) {
        foreach (['ssb-front-js', 'kk-star-ratings'] as $h) {
            if ( wp_script_is($h, 'enqueued') ) {
                wp_dequeue_script($h);
            }
            if ( wp_style_is($h, 'enqueued') ) {
                wp_dequeue_style($h);
            }
        }
    }
}, 100);

/**
 * 7) Desactiva Gutenberg block library en front si tu theme no la usa (CSS principalmente)
 *    Mantengo solo la parte JS si apareciera. De momento, quitamos el estilo para ahorrar peso.
 */
add_action('wp_enqueue_scripts', function (){
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('global-styles');
}, 100);
