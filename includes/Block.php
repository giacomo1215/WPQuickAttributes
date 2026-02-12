<?php
/**
 * Gutenberg Block registration (server-side rendered).
 *
 * Registers a "wpquickattributes/finder" block that uses a
 * server-side render callback to produce the same HTML as the shortcode.
 *
 * @package WPQuickAttributes
 */

defined( 'ABSPATH' ) || exit;

class WPQA_Block {

    /**
     * Hook into WordPress.
     */
    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_block' ) );
    }

    /**
     * Register the block type.
     */
    public static function register_block() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        // Enqueue the tiny editor-side JS that registers the block in the inserter.
        wp_register_script(
            'wpqa-block-editor',
            WPQA_PLUGIN_URL . 'assets/js/block.js',
            array( 'wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-block-editor' ),
            WPQA_VERSION,
            true
        );

        register_block_type( 'wpquickattributes/finder', array(
            'editor_script'   => 'wpqa-block-editor',
            'render_callback' => array( __CLASS__, 'render_callback' ),
            'attributes'      => array(),
        ) );
    }

    /**
     * Server-side render callback.
     *
     * @param array $attributes Block attributes.
     * @return string HTML.
     */
    public static function render_callback( $attributes ) {
        return WPQA_Frontend::render( $attributes );
    }
}
