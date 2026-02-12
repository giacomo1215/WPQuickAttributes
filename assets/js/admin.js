/**
 * WPQuickAttributes — Admin column toggle.
 *
 * Shows / hides column editor rows based on the "Desktop Columns" dropdown.
 *
 * @package WPQuickAttributes
 */
(function ( $ ) {
    'use strict';

    $( function () {
        var $select = $( '#wpqa_num_columns' );
        var $rows   = $( '.wpqa-column-row' );

        function toggleColumns() {
            var num = parseInt( $select.val(), 10 ) || 3;

            $rows.each( function () {
                var colIndex = parseInt( $( this ).data( 'column' ), 10 );
                if ( colIndex <= num ) {
                    $( this ).show();
                } else {
                    $( this ).hide();
                }
            });
        }

        $select.on( 'change', toggleColumns );

        // Initial state is already set server-side, but run once to be safe.
        toggleColumns();

        /* ── Tab switching ──────────────────────────────────────────── */
        $( '.wpqa-nav-tabs .nav-tab' ).on( 'click', function ( e ) {
            e.preventDefault();
            var target = $( this ).attr( 'href' );

            $( '.wpqa-nav-tabs .nav-tab' ).removeClass( 'nav-tab-active' );
            $( this ).addClass( 'nav-tab-active' );

            $( '.wpqa-tab-content' ).hide().removeClass( 'wpqa-tab-active' );
            $( target ).show().addClass( 'wpqa-tab-active' );
        });

        /* ── Colour pickers ─────────────────────────────────────────── */
        $( '.wpqa-color-picker' ).wpColorPicker();
    });

})( jQuery );
