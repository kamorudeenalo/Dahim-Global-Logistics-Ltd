/**
 * Dahim — robust sticky header.
 * Uses position:fixed + a scroll listener instead of CSS position:sticky.
 * This sidesteps the most common real-world cause of "half sticky" headers:
 * an ancestor element (added by a caching/optimization plugin, or a theme
 * customization) with overflow or a transform set, which silently breaks
 * position:sticky in every browser.
 */
(function () {
	var header = document.querySelector( '.site-header' );
	if ( ! header ) return;

	var utilityBar = document.querySelector( '.utility-bar' );
	var triggerPoint = utilityBar ? utilityBar.offsetHeight : 0;
	var ticking = false;

	function setFixed( on ) {
		if ( on ) {
			document.body.classList.add( 'header-fixed' );
			document.body.style.paddingTop = header.offsetHeight + 'px';
		} else {
			document.body.classList.remove( 'header-fixed' );
			document.body.style.paddingTop = '';
		}
	}

	function onScroll() {
		if ( ! ticking ) {
			window.requestAnimationFrame( function () {
				setFixed( window.scrollY > triggerPoint );
				ticking = false;
			} );
			ticking = true;
		}
	}

	window.addEventListener( 'scroll', onScroll, { passive: true } );

	// Re-measure on resize (mobile menu height, font-loading reflow, etc.)
	window.addEventListener( 'resize', function () {
		triggerPoint = utilityBar ? utilityBar.offsetHeight : 0;
		if ( document.body.classList.contains( 'header-fixed' ) ) {
			document.body.style.paddingTop = header.offsetHeight + 'px';
		}
	} );

	// Run once on load in case the page opens already scrolled (e.g. anchor link).
	onScroll();
})();
