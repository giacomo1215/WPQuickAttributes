<?php
/**
 * Front-end rendering.
 *
 * Builds the HTML output for the attribute columns,
 * enqueues the stylesheet, and exposes a static render method
 * used by both the shortcode and the Gutenberg block.
 *
 * @package WPQuickAttributes
 */

defined( 'ABSPATH' ) || exit;

class WPQA_Frontend {

    /**
     * Hook into WordPress.
     */
    public static function init() {
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
    }

    /* ── Asset registration ─────────────────────────────────────────── */

    /**
     * Register (but don't enqueue yet) the front-end stylesheet.
     * It will be enqueued only when the shortcode/block is actually rendered.
     */
    public static function register_assets() {
        wp_register_style(
            'wpqa-frontend',
            WPQA_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            WPQA_VERSION
        );
    }

    /* ── Main render method ─────────────────────────────────────────── */

    /**
     * Return the complete HTML for the attribute quick-finder.
     *
     * @param array $atts Shortcode / block attributes (currently unused; settings come from the DB).
     * @return string HTML markup.
     */
    public static function render( $atts = array() ) {
        // Ensure the stylesheet is enqueued.
        wp_enqueue_style( 'wpqa-frontend' );

        // Inject custom styles from the Style Editor.
        $custom_css = WPQA_Helpers::generate_custom_css();
        if ( ! empty( $custom_css ) ) {
            wp_add_inline_style( 'wpqa-frontend', $custom_css );
        }

        $settings    = WPQA_Helpers::get_settings();
        $num_columns = max( 1, (int) $settings['num_columns'] );

        ob_start();
        ?>
        <div class="wpqa-container" style="--wpqa-cols: <?php echo esc_attr( $num_columns ); ?>;">
            <?php if ( ! empty( $settings['container_title'] ) ) : ?>
                <h2 class="wpqa-container__title">
                    <?php echo esc_html( $settings['container_title'] ); ?>
                </h2>
            <?php endif; ?>

            <div class="wpqa-columns">
                <?php
                foreach ( $settings['columns'] as $col_index => $col ) :
                    $taxonomy = $col['taxonomy'];
                    if ( empty( $taxonomy ) ) {
                        continue;
                    }

                    // Determine heading.
                    $heading = $col['heading'];
                    if ( '' === $heading ) {
                        $tax_obj = get_taxonomy( $taxonomy );
                        $heading = $tax_obj ? $tax_obj->labels->singular_name : $taxonomy;
                    }

                    $terms = WPQA_Helpers::get_terms_cached( $taxonomy, $settings );
                ?>
                <div class="wpqa-column">
                    <h3 class="wpqa-column__heading"><?php echo esc_html( $heading ); ?></h3>

                    <?php if ( ! empty( $terms ) ) : ?>
                        <ul class="wpqa-column__list">
                            <?php foreach ( $terms as $term ) :
                                $label = WPQA_Helpers::term_label( $term, $settings );
                                $url   = WPQA_Helpers::build_filter_url( $taxonomy, $term, $settings );
                                $count = $term->count;
                            ?>
                            <li class="wpqa-column__item">
                                <a href="<?php echo esc_url( $url ); ?>" class="wpqa-column__link">
                                    <?php echo esc_html( $label ); ?>
                                    <?php if ( ! empty( $settings['show_counts'] ) ) : ?>
                                        <span class="wpqa-column__count">(<?php echo esc_html( $count ); ?>)</span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="wpqa-column__empty">
                            <?php esc_html_e( 'No terms found.', 'wpquickattributes' ); ?>
                        </p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
