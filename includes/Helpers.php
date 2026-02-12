<?php
/**
 * Shared helper utilities.
 *
 * Provides term-fetching (with transient caching), URL building,
 * option retrieval, and transient flushing.
 *
 * @package WPQuickAttributes
 */

defined( 'ABSPATH' ) || exit;

class WPQA_Helpers {

    /**
     * Prefix used for all transient keys.
     */
    const TRANSIENT_PREFIX = 'wpqa_terms_';

    /* ── Options ─────────────────────────────────────────────────────── */

    /**
     * Retrieve all plugin settings with defaults merged in.
     *
     * @return array
     */
    public static function get_settings() {
        $defaults = array(
            'columns' => array(
                1 => array( 'taxonomy' => '', 'heading' => '' ),
                2 => array( 'taxonomy' => '', 'heading' => '' ),
                3 => array( 'taxonomy' => '', 'heading' => '' ),
                4 => array( 'taxonomy' => '', 'heading' => '' ),
                5 => array( 'taxonomy' => '', 'heading' => '' ),
                6 => array( 'taxonomy' => '', 'heading' => '' ),
            ),
            'base_url_type'   => 'shop',
            'base_category'   => 0,
            'show_counts'     => 0,
            'order_by'        => 'name',
            'hide_empty'      => 1,
            'container_title' => '',
            'term_overrides'  => array(),
            'num_columns'     => 3,
        );

        $saved = get_option( WPQA_OPTION_KEY, array() );
        return wp_parse_args( $saved, $defaults );
    }

    /* ── WooCommerce attribute taxonomies ────────────────────────────── */

    /**
     * Get an associative array of registered product attribute taxonomies.
     *
     * @return array  [ 'pa_color' => 'Color', … ]
     */
    public static function get_attribute_taxonomies() {
        if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
            return array();
        }

        $result = array();
        foreach ( wc_get_attribute_taxonomies() as $attr ) {
            $taxonomy = wc_attribute_taxonomy_name( $attr->attribute_name );
            $result[ $taxonomy ] = $attr->attribute_label;
        }
        return $result;
    }

    /* ── Term fetching (cached) ─────────────────────────────────────── */

    /**
     * Retrieve terms for a taxonomy, with transient caching.
     *
     * Cache is keyed by taxonomy + language + settings hash so that
     * changes in ordering or hide-empty flag are respected.
     *
     * @param string $taxonomy  Taxonomy slug (pa_*).
     * @param array  $settings  Plugin settings array.
     * @return \WP_Term[]
     */
    public static function get_terms_cached( $taxonomy, $settings = array() ) {
        if ( empty( $taxonomy ) || ! taxonomy_exists( $taxonomy ) ) {
            return array();
        }

        if ( empty( $settings ) ) {
            $settings = self::get_settings();
        }

        $lang      = WPQA_Polylang::current_language();
        $cache_key = self::TRANSIENT_PREFIX . md5( $taxonomy . $lang . wp_json_encode( $settings ) );

        $cached = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        $orderby    = in_array( $settings['order_by'], array( 'name', 'menu_order', 'count' ), true )
                        ? $settings['order_by']
                        : 'name';
        $hide_empty = ! empty( $settings['hide_empty'] );

        // menu_order maps to WP term meta ordering when supported.
        $args = array(
            'taxonomy'   => $taxonomy,
            'orderby'    => ( 'menu_order' === $orderby ) ? 'meta_value_num' : $orderby,
            'order'      => ( 'count' === $orderby ) ? 'DESC' : 'ASC',
            'hide_empty' => $hide_empty,
        );

        if ( 'menu_order' === $orderby ) {
            $args['meta_key'] = 'order'; // WooCommerce stores attribute term order here.
        }

        $terms = get_terms( $args );

        if ( is_wp_error( $terms ) ) {
            $terms = array();
        }

        set_transient( $cache_key, $terms, HOUR_IN_SECONDS );

        return $terms;
    }

    /* ── URL building ────────────────────────────────────────────────── */

    /**
     * Build the catalog filter URL for a given attribute term.
     *
     * Uses WooCommerce layered-nav query vars:
     *   filter_{attribute_name}={term_slug}
     *
     * @param string   $taxonomy Taxonomy slug (pa_*).
     * @param \WP_Term $term     Term object.
     * @param array    $settings Plugin settings.
     * @return string  Escaped URL.
     */
    public static function build_filter_url( $taxonomy, $term, $settings = array() ) {
        if ( empty( $settings ) ) {
            $settings = self::get_settings();
        }

        // Base URL.
        if ( 'category' === $settings['base_url_type'] && ! empty( $settings['base_category'] ) ) {
            $base = WPQA_Polylang::category_url( (int) $settings['base_category'] );
        } else {
            $base = WPQA_Polylang::shop_url();
        }

        // Attribute name without pa_ prefix.
        $attr_name = wc_attribute_taxonomy_slug( $taxonomy );

        $url = add_query_arg(
            array( 'filter_' . $attr_name => $term->slug ),
            $base
        );

        return esc_url( $url );
    }

    /* ── Term display label ─────────────────────────────────────────── */

    /**
     * Return the display label for a term, respecting overrides and Polylang.
     *
     * Override lookup order:
     *   1. Per-language override for the current language (if Polylang active).
     *   2. Global override (key "_global").
     *   3. Polylang translated term name.
     *   4. Raw term name.
     *
     * @param \WP_Term $term     Term object.
     * @param array    $settings Plugin settings.
     * @return string
     */
    public static function term_label( $term, $settings = array() ) {
        if ( empty( $settings ) ) {
            $settings = self::get_settings();
        }

        $overrides = isset( $settings['term_overrides'] ) ? $settings['term_overrides'] : array();
        $lang      = WPQA_Polylang::current_language();
        $tid       = (string) $term->term_id;

        // 1. Per-language override.
        if ( ! empty( $overrides[ $lang ][ $tid ] ) ) {
            return $overrides[ $lang ][ $tid ];
        }

        // 2. Global override (when Polylang is absent).
        if ( ! empty( $overrides['_global'][ $tid ] ) ) {
            return $overrides['_global'][ $tid ];
        }

        // 3. Polylang translated name.
        return WPQA_Polylang::translated_term_name( $term );
    }

    /* ── Transient flushing ─────────────────────────────────────────── */

    /**
     * Delete every WPQA transient.
     *
     * Uses a direct DB query because WordPress doesn't provide
     * a wildcard transient delete function.
     */
    public static function flush_all_transients() {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_' . self::TRANSIENT_PREFIX . '%',
                '_transient_timeout_' . self::TRANSIENT_PREFIX . '%'
            )
        );
    }
}
