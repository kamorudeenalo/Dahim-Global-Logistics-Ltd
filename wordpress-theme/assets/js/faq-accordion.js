/**
 * Dahim — FAQ accordion.
 * Modern browsers already do this natively via <details name="dahim-faq">,
 * but this reinforces the same behavior everywhere (including older
 * browsers that don't yet support exclusive details groups) so opening
 * one FAQ always closes any other one that's open.
 */
(function () {
	var items = document.querySelectorAll( '.faq-item' );
	if ( ! items.length ) return;

	items.forEach( function ( item ) {
		item.addEventListener( 'toggle', function () {
			if ( item.open ) {
				items.forEach( function ( other ) {
					if ( other !== item ) other.open = false;
				} );
			}
		} );
	} );
})();
