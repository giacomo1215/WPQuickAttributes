/**
 * WPQuickAttributes — Gutenberg block registration (editor side).
 *
 * This is a minimal block that uses ServerSideRender to display the
 * same output as the [wpquickattributes] shortcode. All configuration
 * is done via the plugin settings page, so no InspectorControls are needed.
 *
 * @package WPQuickAttributes
 */
( function ( blocks, element, serverSideRender ) {
    var el  = element.createElement;
    var SSR = serverSideRender.default || serverSideRender;

    blocks.registerBlockType( 'wpquickattributes/finder', {
        title: 'WPQuickAttributes',
        description: 'Display WooCommerce attribute terms as filterable links in columns.',
        category: 'widgets',
        icon: 'filter',
        supports: {
            html: false,
            multiple: false,
        },
        edit: function () {
            return el(
                'div',
                { className: 'wpqa-block-editor-wrapper' },
                el( SSR, {
                    block: 'wpquickattributes/finder',
                    attributes: {},
                } )
            );
        },
        save: function () {
            // Rendered server-side; return null.
            return null;
        },
    } );
} )(
    window.wp.blocks,
    window.wp.element,
    window.wp.serverSideRender
);
