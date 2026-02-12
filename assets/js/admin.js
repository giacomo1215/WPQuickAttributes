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
        var stylePickersInit = false;

        $( '.wpqa-nav-tabs .nav-tab' ).on( 'click', function ( e ) {
            e.preventDefault();
            var target = $( this ).attr( 'href' );

            $( '.wpqa-nav-tabs .nav-tab' ).removeClass( 'nav-tab-active' );
            $( this ).addClass( 'nav-tab-active' );

            $( '.wpqa-tab-content' ).removeClass( 'wpqa-tab-active' );
            $( target ).addClass( 'wpqa-tab-active' );

            // Lazy-init colour pickers the first time the Style Editor is shown.
            if ( '#wpqa-tab-style-editor' === target && ! stylePickersInit ) {
                $( '#wpqa-tab-style-editor .wpqa-color-picker' ).wpColorPicker();
                stylePickersInit = true;
            }
        });
    });

})( jQuery );
