<?php
/**
 * Plugin Name: Modofestival – Safe Fix Schema Pro deps (admin only)
 * Description: Satisface dependencias que WP Schema Pro declara pero no registra. Solo en /wp-admin y solo si faltan. Se auto-desactiva si deja de ser necesario.
 * Author: Pau / Modofestival
 */

if ( ! defined('ABSPATH') ) exit;

add_action('admin_enqueue_scripts', function () {

    // 1) Solo en admin y si el script de Schema Pro existe
    if ( ! is_admin() ) return;

    $schema_handle = 'schema-admin-script';

    if ( ! wp_script_is($schema_handle, 'registered') && ! wp_script_is($schema_handle, 'enqueued') ) {
        return;
    }

    $needs_timepicker = ! wp_script_is('jquery-ui-timepicker', 'registered');
    $needs_select2    = ! wp_script_is('bsf-target-rule-select2', 'registered');

    if ( ! $needs_timepicker && ! $needs_select2 ) {
        set_transient('modofestival_schema_fix_not_needed', time(), DAY_IN_SECONDS * 14);
        return;
    }

    delete_transient('modofestival_schema_fix_not_needed');

    wp_enqueue_script('jquery-ui-datepicker');

    if ( $needs_timepicker ) {
        $acf_timepicker_candidates = array('acf-timepicker', 'acf-input-timepicker', 'jquery-ui-timepicker-addon');
        $has_alias = false;
        foreach ($acf_timepicker_candidates as $h) {
            if ( wp_script_is($h, 'registered') || wp_script_is($h, 'enqueued') ) {
                wp_register_script('jquery-ui-timepicker', false, array($h), null, true);
                $has_alias = true;
                break;
            }
        }

        if ( ! $has_alias ) {
            $timepicker_js  = apply_filters('modofestival_schema_fix_timepicker_js',
                'https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.js'
            );
            $timepicker_css = apply_filters('modofestival_schema_fix_timepicker_css',
                'https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.css'
            );

            wp_register_script(
                'jquery-ui-timepicker',
                $timepicker_js,
                array('jquery', 'jquery-ui-datepicker'),
                '1.6.3',
                true
            );

            wp_register_style(
                'jquery-ui-timepicker-css',
                $timepicker_css,
                array(),
                '1.6.3'
            );
        }
    }

    if ( $needs_select2 ) {
        $has_core_select2 = wp_script_is('select2', 'registered') || wp_script_is('select2', 'enqueued');

        if ( $has_core_select2 ) {
            wp_register_script('bsf-target-rule-select2', false, array('select2'), null, true);
        } else {
            $select2_js  = apply_filters('modofestival_schema_fix_select2_js',
                'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js'
            );
            $select2_css = apply_filters('modofestival_schema_fix_select2_css',
                'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css'
            );

            wp_register_script(
                'bsf-target-rule-select2',
                $select2_js,
                array('jquery'),
                '4.0.13',
                true
            );

            wp_register_style(
                'bsf-target-rule-select2',
                $select2_css,
                array(),
                '4.0.13'
            );
        }
    }

    if ( wp_script_is($schema_handle, 'enqueued') ) {
        if ( wp_style_is('jquery-ui-timepicker-css', 'registered') ) {
            wp_enqueue_style('jquery-ui-timepicker-css');
        }
        if ( wp_style_is('bsf-target-rule-select2', 'registered') ) {
            wp_enqueue_style('bsf-target-rule-select2');
        }
    }

}, 20);

add_action('admin_init', function () {
    if ( get_transient('modofestival_schema_fix_not_needed') ) {
        return;
    }
});
