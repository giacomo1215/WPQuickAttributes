<?php
/**
 * Plugin Name: WPQuickAttributes
 * Plugin URI:  https://github.com/giacomo1215/wpquickattributes
 * Description: WooCommerce attribute quick-finder — display product attribute terms as filterable links in columns/cards.
 * Version:     1.0.2
 * Author:      Giacomo Giorgi
 * Author URI:  https://github.com/giacomo1215
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wpquickattributes
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 *
 * @package WPQuickAttributes
 */

defined( 'ABSPATH' ) || exit;

/* ─── Constants ───────────────────────────────────────────────────────── */
define( 'WPQA_VERSION', '1.0.0' );
define( 'WPQA_PLUGIN_FILE', __FILE__ );
define( 'WPQA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPQA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPQA_OPTION_KEY', 'wpqa_settings' );

/* ─── Autoload includes ──────────────────────────────────────────────── */
require_once WPQA_PLUGIN_DIR . 'includes/Polylang.php';
require_once WPQA_PLUGIN_DIR . 'includes/Helpers.php';
require_once WPQA_PLUGIN_DIR . 'includes/Admin.php';
require_once WPQA_PLUGIN_DIR . 'includes/Frontend.php';
require_once WPQA_PLUGIN_DIR . 'includes/Shortcode.php';
require_once WPQA_PLUGIN_DIR . 'includes/Block.php';

/* ─── Activation hook — set defaults ─────────────────────────────────── */
register_activation_hook( __FILE__, 'wpqa_activate' );

/**
 * On activation: seed default options if they don't already exist.
 */
function wpqa_activate() {
    if ( false === get_option( WPQA_OPTION_KEY ) ) {
        $defaults = array(
            'columns' => array(
                1 => array( 'taxonomy' => '', 'heading' => '' ),
                2 => array( 'taxonomy' => '', 'heading' => '' ),
                3 => array( 'taxonomy' => '', 'heading' => '' ),
            ),
            'base_url_type'   => 'shop',       // 'shop' | 'category'
            'base_category'   => 0,             // term_id of product_cat
            'show_counts'     => 0,
            'order_by'        => 'name',        // name | menu_order | count
            'hide_empty'      => 1,
            'container_title' => '',
            'term_overrides'  => array(),        // lang_slug => { term_id => label }
            'num_columns'     => 3,
        );
        update_option( WPQA_OPTION_KEY, $defaults );
    }
}

/* ─── Load text domain ───────────────────────────────────────────────── */
add_action( 'init', 'wpqa_load_textdomain' );

function wpqa_load_textdomain() {
    load_plugin_textdomain( 'wpquickattributes', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

/* ─── Boot plugin components ─────────────────────────────────────────── */
add_action( 'plugins_loaded', 'wpqa_init' );

function wpqa_init() {
    // Bail early if WooCommerce is not active.
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>';
            esc_html_e( 'WPQuickAttributes requires WooCommerce to be installed and active.', 'wpquickattributes' );
            echo '</p></div>';
        } );
        return;
    }

    WPQA_Admin::init();
    WPQA_Frontend::init();
    WPQA_Shortcode::init();
    WPQA_Block::init();
}

/* ─── Cache invalidation: flush transients when attribute terms change ─ */
add_action( 'created_term', 'wpqa_flush_transients', 10, 3 );
add_action( 'edited_term',  'wpqa_flush_transients', 10, 3 );
add_action( 'delete_term',  'wpqa_flush_transients', 10, 3 );

/**
 * Delete all WPQA transients when a pa_* term is created/edited/deleted.
 *
 * @param int    $term_id  Term ID.
 * @param int    $tt_id    Term-taxonomy ID.
 * @param string $taxonomy Taxonomy slug.
 */
function wpqa_flush_transients( $term_id, $tt_id, $taxonomy ) {
    if ( 0 === strpos( $taxonomy, 'pa_' ) ) {
        WPQA_Helpers::flush_all_transients();
    }
}
