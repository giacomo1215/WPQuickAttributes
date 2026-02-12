<?php
/**
 * Shortcode handler.
 *
 * Registers [wpquickattributes] and delegates rendering to WPQA_Frontend.
 *
 * @package WPQuickAttributes
 */

defined( 'ABSPATH' ) || exit;

class WPQA_Shortcode {

    /**
     * Hook into WordPress.
     */
    public static function init() {
        add_shortcode( 'wpquickattributes', array( __CLASS__, 'handle' ) );
    }

    /**
     * Shortcode callback.
     *
     * @param array|string $atts Shortcode attributes.
     * @return string Rendered HTML.
     */
    public static function handle( $atts = array() ) {
        $atts = shortcode_atts( array(), $atts, 'wpquickattributes' );
        return WPQA_Frontend::render( $atts );
    }
}
