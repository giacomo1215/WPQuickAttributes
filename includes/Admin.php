<?php
/**
 * Admin settings page.
 *
 * Registers a sub-menu under WooCommerce, renders the settings form,
 * and handles sanitization + saving.
 *
 * @package WPQuickAttributes
 */

defined( 'ABSPATH' ) || exit;

class WPQA_Admin {

    /**
     * Hook into WordPress.
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_menu_page' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
    }

    /* ── Menu page ──────────────────────────────────────────────────── */

    /**
     * Add a sub-menu page under WooCommerce.
     */
    public static function add_menu_page() {
        add_submenu_page(
            'woocommerce',
            __( 'WPQuickAttributes Settings', 'wpquickattributes' ),
            __( 'WPQuickAttributes', 'wpquickattributes' ),
            'manage_woocommerce',
            'wpquickattributes',
            array( __CLASS__, 'render_page' )
        );
    }

    /* ── Register setting ───────────────────────────────────────────── */

    /**
     * Register the single option group.
     */
    public static function register_settings() {
        register_setting(
            'wpqa_settings_group',
            WPQA_OPTION_KEY,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
            )
        );
    }

    /* ── Asset enqueuing ────────────────────────────────────────────── */

    /**
     * Enqueue admin CSS only on our settings page.
     *
     * @param string $hook_suffix Current admin page hook.
     */
    public static function enqueue_assets( $hook_suffix ) {
        if ( 'woocommerce_page_wpquickattributes' !== $hook_suffix ) {
            return;
        }

        wp_enqueue_style(
            'wpqa-admin',
            WPQA_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WPQA_VERSION
        );
    }

    /* ── Sanitization ───────────────────────────────────────────────── */

    /**
     * Sanitize all incoming settings.
     *
     * @param array $input Raw $_POST data for the option.
     * @return array Sanitized values.
     */
    public static function sanitize_settings( $input ) {
        $clean = array();

        // Columns.
        $attr_taxonomies = array_keys( WPQA_Helpers::get_attribute_taxonomies() );
        $columns         = isset( $input['columns'] ) ? (array) $input['columns'] : array();
        $clean['columns'] = array();

        for ( $i = 1; $i <= 3; $i++ ) {
            $col = isset( $columns[ $i ] ) ? (array) $columns[ $i ] : array();
            $clean['columns'][ $i ] = array(
                'taxonomy' => isset( $col['taxonomy'] ) && in_array( $col['taxonomy'], $attr_taxonomies, true )
                                ? sanitize_text_field( $col['taxonomy'] )
                                : '',
                'heading'  => isset( $col['heading'] ) ? sanitize_text_field( $col['heading'] ) : '',
            );
        }

        // Base URL type.
        $clean['base_url_type'] = ( isset( $input['base_url_type'] ) && 'category' === $input['base_url_type'] )
                                    ? 'category'
                                    : 'shop';

        // Base category.
        $clean['base_category'] = isset( $input['base_category'] ) ? absint( $input['base_category'] ) : 0;

        // Toggles.
        $clean['show_counts'] = ! empty( $input['show_counts'] ) ? 1 : 0;
        $clean['hide_empty']  = ! empty( $input['hide_empty'] ) ? 1 : 0;

        // Order by.
        $clean['order_by'] = isset( $input['order_by'] ) && in_array( $input['order_by'], array( 'name', 'menu_order', 'count' ), true )
                                ? $input['order_by']
                                : 'name';

        // Container title.
        $clean['container_title'] = isset( $input['container_title'] ) ? sanitize_text_field( $input['container_title'] ) : '';

        // Number of desktop columns.
        $clean['num_columns'] = isset( $input['num_columns'] ) ? absint( $input['num_columns'] ) : 3;
        if ( $clean['num_columns'] < 1 || $clean['num_columns'] > 6 ) {
            $clean['num_columns'] = 3;
        }

        // ── Term overrides ─────────────────────────────────────────────
        $overrides_raw = isset( $input['term_overrides'] ) ? (array) $input['term_overrides'] : array();
        $clean['term_overrides'] = array();

        foreach ( $overrides_raw as $lang_key => $terms ) {
            $lang_key = sanitize_key( $lang_key );
            if ( ! is_array( $terms ) ) {
                continue;
            }
            foreach ( $terms as $tid => $label ) {
                $tid   = absint( $tid );
                $label = sanitize_text_field( $label );
                if ( $tid && '' !== $label ) {
                    $clean['term_overrides'][ $lang_key ][ $tid ] = $label;
                }
            }
        }

        // Flush term caches on save.
        WPQA_Helpers::flush_all_transients();

        return $clean;
    }

    /* ── Render page ────────────────────────────────────────────────── */

    /**
     * Output the settings page HTML.
     */
    public static function render_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'wpquickattributes' ) );
        }

        $settings   = WPQA_Helpers::get_settings();
        $taxonomies = WPQA_Helpers::get_attribute_taxonomies();
        $languages  = WPQA_Polylang::get_languages();
        $pll_active = WPQA_Polylang::is_active();

        // Product category terms for base URL selector.
        $product_cats = get_terms( array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
        ) );
        if ( is_wp_error( $product_cats ) ) {
            $product_cats = array();
        }

        ?>
        <div class="wrap wpqa-admin-wrap">
            <h1><?php esc_html_e( 'WPQuickAttributes Settings', 'wpquickattributes' ); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'wpqa_settings_group' );
                ?>

                <!-- ── General ──────────────────────────────────────────── -->
                <h2><?php esc_html_e( 'General', 'wpquickattributes' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wpqa_container_title"><?php esc_html_e( 'Container Title', 'wpquickattributes' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="wpqa_container_title" class="regular-text"
                                   name="<?php echo esc_attr( WPQA_OPTION_KEY ); ?>[container_title]"
                                   value="<?php echo esc_attr( $settings['container_title'] ); ?>"
                                   placeholder="<?php esc_attr_e( 'e.g. FIND THE RIGHT GEAR', 'wpquickattributes' ); ?>">
                            <p class="description"><?php esc_html_e( 'Optional heading displayed above the columns.', 'wpquickattributes' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wpqa_num_columns"><?php esc_html_e( 'Desktop Columns', 'wpquickattributes' ); ?></label>
                        </th>
                        <td>
                            <select id="wpqa_num_columns" name="<?php echo esc_attr( WPQA_OPTION_KEY ); ?>[num_columns]">
                                <?php for ( $i = 1; $i <= 6; $i++ ) : ?>
                                    <option value="<?php echo esc_attr( $i ); ?>" <?php selected( $settings['num_columns'], $i ); ?>>
                                        <?php echo esc_html( $i ); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </td>
                    </tr>
                </table>

                <!-- ── Columns ─────────────────────────────────────────── -->
                <h2><?php esc_html_e( 'Attribute Columns', 'wpquickattributes' ); ?></h2>
                <table class="form-table">
                    <?php for ( $col = 1; $col <= 3; $col++ ) :
                        $col_data = isset( $settings['columns'][ $col ] ) ? $settings['columns'][ $col ] : array( 'taxonomy' => '', 'heading' => '' );
                    ?>
                    <tr>
                        <th scope="row">
                            <?php
                            /* translators: %d: column number */
                            printf( esc_html__( 'Column %d', 'wpquickattributes' ), $col );
                            ?>
                        </th>
                        <td>
                            <label><?php esc_html_e( 'Attribute:', 'wpquickattributes' ); ?>
                                <select name="<?php echo esc_attr( WPQA_OPTION_KEY ); ?>[columns][<?php echo esc_attr( $col ); ?>][taxonomy]">
                                    <option value=""><?php esc_html_e( '— Select —', 'wpquickattributes' ); ?></option>
                                    <?php foreach ( $taxonomies as $tax_slug => $tax_label ) : ?>
                                        <option value="<?php echo esc_attr( $tax_slug ); ?>"
                                            <?php selected( $col_data['taxonomy'], $tax_slug ); ?>>
                                            <?php echo esc_html( $tax_label . ' (' . $tax_slug . ')' ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <br><br>
                            <label><?php esc_html_e( 'Heading:', 'wpquickattributes' ); ?>
                                <input type="text" class="regular-text"
                                       name="<?php echo esc_attr( WPQA_OPTION_KEY ); ?>[columns][<?php echo esc_attr( $col ); ?>][heading]"
                                       value="<?php echo esc_attr( $col_data['heading'] ); ?>"
                                       placeholder="<?php esc_attr_e( 'Leave empty to use attribute label', 'wpquickattributes' ); ?>">
                            </label>
                        </td>
                    </tr>
                    <?php endfor; ?>
                </table>

                <!-- ── Base URL ────────────────────────────────────────── -->
                <h2><?php esc_html_e( 'Link Target', 'wpquickattributes' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Base Catalog URL', 'wpquickattributes' ); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="radio" name="<?php echo esc_attr( WPQA_OPTION_KEY ); ?>[base_url_type]"
                                           value="shop" <?php checked( $settings['base_url_type'], 'shop' ); ?>>
                                    <?php esc_html_e( 'Shop page (default)', 'wpquickattributes' ); ?>
                                </label><br>
                                <label>
                                    <input type="radio" name="<?php echo esc_attr( WPQA_OPTION_KEY ); ?>[base_url_type]"
                                           value="category" <?php checked( $settings['base_url_type'], 'category' ); ?>>
                                    <?php esc_html_e( 'Product category page:', 'wpquickattributes' ); ?>
                                </label>
                                <select name="<?php echo esc_attr( WPQA_OPTION_KEY ); ?>[base_category]">
                                    <option value="0"><?php esc_html_e( '— Select category —', 'wpquickattributes' ); ?></option>
                                    <?php foreach ( $product_cats as $cat ) : ?>
                                        <option value="<?php echo esc_attr( $cat->term_id ); ?>"
                                            <?php selected( $settings['base_category'], $cat->term_id ); ?>>
                                            <?php echo esc_html( $cat->name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </fieldset>
                        </td>
                    </tr>
                </table>

                <!-- ── Display toggles ─────────────────────────────────── -->
                <h2><?php esc_html_e( 'Display Options', 'wpquickattributes' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Show Term Counts', 'wpquickattributes' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr( WPQA_OPTION_KEY ); ?>[show_counts]"
                                       value="1" <?php checked( $settings['show_counts'], 1 ); ?>>
                                <?php esc_html_e( 'Display the number of products next to each term', 'wpquickattributes' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Hide Empty Terms', 'wpquickattributes' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr( WPQA_OPTION_KEY ); ?>[hide_empty]"
                                       value="1" <?php checked( $settings['hide_empty'], 1 ); ?>>
                                <?php esc_html_e( 'Hide terms that have no products assigned', 'wpquickattributes' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wpqa_order_by"><?php esc_html_e( 'Order Terms By', 'wpquickattributes' ); ?></label>
                        </th>
                        <td>
                            <select id="wpqa_order_by" name="<?php echo esc_attr( WPQA_OPTION_KEY ); ?>[order_by]">
                                <option value="name" <?php selected( $settings['order_by'], 'name' ); ?>>
                                    <?php esc_html_e( 'Name', 'wpquickattributes' ); ?>
                                </option>
                                <option value="menu_order" <?php selected( $settings['order_by'], 'menu_order' ); ?>>
                                    <?php esc_html_e( 'Menu Order', 'wpquickattributes' ); ?>
                                </option>
                                <option value="count" <?php selected( $settings['order_by'], 'count' ); ?>>
                                    <?php esc_html_e( 'Count', 'wpquickattributes' ); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                </table>

                <!-- ── Term label overrides ────────────────────────────── -->
                <h2><?php esc_html_e( 'Term Label Overrides', 'wpquickattributes' ); ?></h2>
                <p class="description">
                    <?php esc_html_e( 'Override the displayed label for individual terms. Leave blank to use the default (translated) term name.', 'wpquickattributes' ); ?>
                </p>

                <?php
                // Collect all terms from the selected taxonomies.
                $all_terms = array();
                foreach ( $settings['columns'] as $col_data ) {
                    $tax = $col_data['taxonomy'];
                    if ( $tax && taxonomy_exists( $tax ) ) {
                        $terms = get_terms( array(
                            'taxonomy'   => $tax,
                            'hide_empty' => false,
                        ) );
                        if ( ! is_wp_error( $terms ) ) {
                            foreach ( $terms as $t ) {
                                $all_terms[ $t->term_id ] = $t;
                            }
                        }
                    }
                }

                if ( empty( $all_terms ) ) :
                    echo '<p>' . esc_html__( 'Select attribute taxonomies above and save first, then term override fields will appear here.', 'wpquickattributes' ) . '</p>';
                else :
                    // Determine language keys for override fields.
                    $override_keys = $pll_active ? $languages : array( '_global' );
                    foreach ( $override_keys as $lang_key ) :
                        $lang_label = ( '_global' === $lang_key )
                            ? __( 'All Languages', 'wpquickattributes' )
                            : strtoupper( $lang_key );
                ?>
                <h3><?php echo esc_html( $lang_label ); ?></h3>
                <table class="form-table wpqa-overrides-table">
                    <?php foreach ( $all_terms as $t ) :
                        $existing = isset( $settings['term_overrides'][ $lang_key ][ $t->term_id ] )
                                    ? $settings['term_overrides'][ $lang_key ][ $t->term_id ]
                                    : '';
                    ?>
                    <tr>
                        <th scope="row">
                            <?php echo esc_html( $t->name . ' (' . $t->taxonomy . ')' ); ?>
                        </th>
                        <td>
                            <input type="text" class="regular-text"
                                   name="<?php echo esc_attr( WPQA_OPTION_KEY ); ?>[term_overrides][<?php echo esc_attr( $lang_key ); ?>][<?php echo esc_attr( $t->term_id ); ?>]"
                                   value="<?php echo esc_attr( $existing ); ?>"
                                   placeholder="<?php echo esc_attr( $t->name ); ?>">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php
                    endforeach;
                endif;
                ?>

                <?php submit_button(); ?>
            </form>

            <!-- ── Shortcode / Block usage hint ────────────────────────── -->
            <hr>
            <h2><?php esc_html_e( 'Usage', 'wpquickattributes' ); ?></h2>
            <p>
                <?php esc_html_e( 'Shortcode:', 'wpquickattributes' ); ?>
                <code>[wpquickattributes]</code>
            </p>
            <p>
                <?php esc_html_e( 'Gutenberg Block:', 'wpquickattributes' ); ?>
                <?php esc_html_e( 'Search for "WPQuickAttributes" in the block inserter.', 'wpquickattributes' ); ?>
            </p>
        </div>
        <?php
    }
}
