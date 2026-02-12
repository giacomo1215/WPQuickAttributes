<?php
/**
 * Polylang compatibility wrapper.
 *
 * Every call guards against Polylang not being active —
 * if functions are missing the helpers return sensible fallbacks.
 *
 * @package WPQuickAttributes
 */

defined( 'ABSPATH' ) || exit;

class WPQA_Polylang {

    /* ── Detection ──────────────────────────────────────────────────── */

    /**
     * Check whether Polylang is active and its API functions available.
     *
     * @return bool
     */
    public static function is_active() {
        return function_exists( 'pll_current_language' );
    }

    /* ── Current language ───────────────────────────────────────────── */

    /**
     * Return the current language slug (e.g. "en", "it").
     * Falls back to the WP locale prefix when Polylang is absent.
     *
     * @return string
     */
    public static function current_language() {
        if ( self::is_active() ) {
            $lang = pll_current_language( 'slug' );
            if ( $lang ) {
                return $lang;
            }
        }
        return substr( get_locale(), 0, 2 );
    }

    /* ── Languages list ─────────────────────────────────────────────── */

    /**
     * Return an array of language slugs registered in Polylang.
     *
     * @return string[]
     */
    public static function get_languages() {
        if ( self::is_active() && function_exists( 'pll_languages_list' ) ) {
            return pll_languages_list( array( 'fields' => 'slug' ) );
        }
        return array( self::current_language() );
    }

    /* ── Translated term name ───────────────────────────────────────── */

    /**
     * Get the translated name for a term.
     * If Polylang is active it fetches the translation in the current
     * language; otherwise it returns the original name.
     *
     * @param \WP_Term $term Term object.
     * @return string
     */
    public static function translated_term_name( $term ) {
        if ( self::is_active() && function_exists( 'pll_get_term' ) ) {
            $translated_id = pll_get_term( $term->term_id, self::current_language() );
            if ( $translated_id && $translated_id !== $term->term_id ) {
                $translated = get_term( $translated_id );
                if ( $translated && ! is_wp_error( $translated ) ) {
                    return $translated->name;
                }
            }
        }
        return $term->name;
    }

    /* ── Translated shop / category base URL ────────────────────────── */

    /**
     * Return the base shop URL, using Polylang-translated page if available.
     *
     * @return string
     */
    public static function shop_url() {
        $shop_page_id = wc_get_page_id( 'shop' );

        if ( self::is_active() && function_exists( 'pll_get_post' ) && $shop_page_id > 0 ) {
            $translated_id = pll_get_post( $shop_page_id, self::current_language() );
            if ( $translated_id ) {
                return get_permalink( $translated_id );
            }
        }

        if ( $shop_page_id > 0 ) {
            return get_permalink( $shop_page_id );
        }

        // Ultimate fallback.
        return home_url( '/shop/' );
    }

    /**
     * Return the URL for a product_cat term, Polylang-aware.
     *
     * @param int $cat_id product_cat term_id.
     * @return string
     */
    public static function category_url( $cat_id ) {
        if ( self::is_active() && function_exists( 'pll_get_term' ) ) {
            $translated_id = pll_get_term( $cat_id, self::current_language() );
            if ( $translated_id ) {
                $link = get_term_link( (int) $translated_id, 'product_cat' );
                if ( ! is_wp_error( $link ) ) {
                    return $link;
                }
            }
        }

        $link = get_term_link( (int) $cat_id, 'product_cat' );
        return is_wp_error( $link ) ? self::shop_url() : $link;
    }

    /* ── Home URL ───────────────────────────────────────────────────── */

    /**
     * Return the home URL for the current language.
     *
     * @return string
     */
    public static function home_url() {
        if ( self::is_active() && function_exists( 'pll_home_url' ) ) {
            return pll_home_url();
        }
        return home_url( '/' );
    }
}
