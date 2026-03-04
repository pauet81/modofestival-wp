<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if ( !function_exists( 'chld_thm_cfg_locale_css' ) ):
    function chld_thm_cfg_locale_css( $uri ){
        if ( empty( $uri ) && is_rtl() && file_exists( get_template_directory() . '/rtl.css' ) )
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;
add_filter( 'locale_stylesheet_uri', 'chld_thm_cfg_locale_css' );

if ( !function_exists( 'chld_thm_cfg_parent_css' ) ):
    function chld_thm_cfg_parent_css() {
        wp_enqueue_style( 'chld_thm_cfg_parent', trailingslashit( get_template_directory_uri() ) . 'style.css', array(  ) );
    }
endif;
add_action( 'wp_enqueue_scripts', 'chld_thm_cfg_parent_css', 10 );
         
if ( !function_exists( 'child_theme_configurator_css' ) ):
    function child_theme_configurator_css() {
        wp_enqueue_style( 'chld_thm_cfg_child', trailingslashit( get_stylesheet_directory_uri() ) . 'style.css', array( 'chld_thm_cfg_parent' ) );
    }
endif;
add_action( 'wp_enqueue_scripts', 'child_theme_configurator_css', 10 );

// END ENQUEUE PARENT ACTION
// 
function replace_tags_with_tags(){
?>
<script>
  (function($) {       
      function replaceElementTag(targetSelector, newTagString) {
        $(targetSelector).each(function(){
          var newElem = $(newTagString, {html: $(this).html()});
          $.each(this.attributes, function() {
            newElem.attr(this.name, this.value);
          });
          $(this).replaceWith(newElem);
        });
      }
    
      replaceElementTag('.fusion-events-single-title-content h2', '<h1></h1>');   // each replacement separated by semi-colon
    
  }(jQuery)); 
</script>
<?php
}
add_action('wp_footer', 'replace_tags_with_tags');
// Cargar sistema de Agenda de Festivales
require_once get_stylesheet_directory() . '/includes/modofestival-agenda.php';

