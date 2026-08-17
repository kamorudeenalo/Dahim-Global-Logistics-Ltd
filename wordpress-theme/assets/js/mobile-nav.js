/**
 * Dahim — mobile menu toggle.
 * Opens/closes the nav panel, closes it when a link is tapped or Escape is
 * pressed, and keeps the toggle button's aria-expanded state in sync.
 */
(function () {
	var toggle = document.querySelector( '.menu-toggle' );
	var nav = document.getElementById( 'dahim-main-nav' );
	if ( ! toggle || ! nav ) return;

	function closeMenu() {
		document.body.classList.remove( 'nav-open' );
		toggle.setAttribute( 'aria-expanded', 'false' );
	}

	function toggleMenu() {
		var isOpen = document.body.classList.toggle( 'nav-open' );
		toggle.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
	}

	toggle.addEventListener( 'click', toggleMenu );

	// Close the panel once a link inside it is used to navigate.
	nav.querySelectorAll( 'a' ).forEach( function ( link ) {
		link.addEventListener( 'click', closeMenu );
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' ) closeMenu();
	} );

	// If the window is resized back up to desktop width while the menu is
	// open, reset state so it doesn't stay "open" once mobile styles no
	// longer apply.
	window.addEventListener( 'resize', function () {
		if ( window.innerWidth > 900 ) closeMenu();
	} );

	// --- Insights page search toggle (full bar on desktop, icon-only on mobile) ---
	var searchToggle = document.getElementById( 'dahim-insight-search-toggle' );
	var searchBox = document.getElementById( 'dahim-insight-search' );
	if ( searchToggle && searchBox ) {
		searchToggle.addEventListener( 'click', function () {
			var isOpen = searchBox.classList.toggle( 'is-open' );
			searchToggle.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
			if ( isOpen ) {
				var field = searchBox.querySelector( 'input[type="search"]' );
				if ( field ) field.focus();
			}
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' ) {
				searchBox.classList.remove( 'is-open' );
				searchToggle.setAttribute( 'aria-expanded', 'false' );
			}
		} );
	}
})();
