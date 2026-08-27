/**
 * Membership tiers table — regional price switching.
 *
 * The table is rendered in full by PHP; this only swaps the visible price string and
 * the pricing note when the country selector changes. Nothing is re-created, so the
 * table's semantics, focus position and scroll offset all survive a change.
 */
( function () {
    'use strict';

    function initTable( root ) {
        if ( root.dataset.mauticTiersReady === 'true' ) {
            return;
        }

        var select = root.querySelector( '[data-mautic-tiers-select]' );
        var note = root.querySelector( '[data-mautic-tiers-note]' );
        var prices = root.querySelectorAll( '[data-mautic-tiers-price]' );

        if ( ! select || ! prices.length ) {
            return;
        }

        function update() {
            var index = parseInt( select.value, 10 );

            if ( isNaN( index ) || index < 0 || index > 2 ) {
                index = 0;
            }

            Array.prototype.forEach.call( prices, function ( el ) {
                var next = el.getAttribute( 'data-price-' + index );

                if ( next ) {
                    el.textContent = next;
                }
            } );

            if ( note ) {
                var nextNote = note.getAttribute( 'data-note-' + index );

                if ( nextNote ) {
                    note.textContent = nextNote;
                }
            }
        }

        select.addEventListener( 'change', update );
        root.dataset.mauticTiersReady = 'true';

        // The browser may restore a previous selection on a back/forward navigation.
        update();
    }

    function initAll( context ) {
        var scope = context || document;

        Array.prototype.forEach.call(
            scope.querySelectorAll( '[data-mautic-tiers]' ),
            initTable
        );
    }

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', function () {
            initAll();
        } );
    } else {
        initAll();
    }

    // Re-run inside the Elementor editor, where widgets are injected after load.
    window.addEventListener( 'elementor/frontend/init', function () {
        if ( ! window.elementorFrontend || ! window.elementorFrontend.hooks ) {
            return;
        }

        window.elementorFrontend.hooks.addAction(
            'frontend/element_ready/mautic_membership_tiers.default',
            function ( $scope ) {
                initAll( $scope && $scope[ 0 ] ? $scope[ 0 ] : undefined );
            }
        );
    } );
}() );
