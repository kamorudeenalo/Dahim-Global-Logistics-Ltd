/**
 * Dahim — back to top button.
 * Fades in once the visitor has scrolled down a bit, and smooth-scrolls
 * back to the top of the page when clicked.
 */
(function () {
	var btn = document.getElementById( 'dahim-back-to-top' );
	if ( ! btn ) return;

	var SHOW_AFTER = 480; // px scrolled before the button appears
	var visible = false;

	function updateVisibility() {
		var shouldShow = window.scrollY > SHOW_AFTER;
		if ( shouldShow === visible ) return;
		visible = shouldShow;
		btn.classList.toggle( 'is-visible', visible );
	}

	window.addEventListener( 'scroll', updateVisibility, { passive: true } );
	updateVisibility();

	btn.addEventListener( 'click', function () {
		window.scrollTo( { top: 0, behavior: 'smooth' } );
	} );
})();
