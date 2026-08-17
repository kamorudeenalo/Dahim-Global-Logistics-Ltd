/**
 * Dahim — cookie / privacy notice.
 * Shows once per visitor (tracked via localStorage) and dismisses on
 * "Accept". No cookies are actually set by this script itself — it only
 * gates whether analytics (if a GA Measurement ID is configured) has
 * already been acknowledged by the visitor.
 */
(function () {
	var KEY = 'dahim_cookie_notice_dismissed';
	var banner = document.getElementById( 'dahim-cookie-notice' );
	if ( ! banner ) return;

	var alreadyDismissed = false;
	try {
		alreadyDismissed = window.localStorage.getItem( KEY ) === '1';
	} catch ( e ) {
		// Private browsing / storage disabled — just show the banner every visit.
	}
	if ( alreadyDismissed ) return;

	banner.classList.add( 'is-visible' );

	var acceptBtn = document.getElementById( 'dahim-cookie-accept' );
	if ( acceptBtn ) {
		acceptBtn.addEventListener( 'click', function () {
			banner.classList.remove( 'is-visible' );
			try {
				window.localStorage.setItem( KEY, '1' );
			} catch ( e ) {}
			if ( window.dahimInitAnalytics ) window.dahimInitAnalytics();
		} );
	}
})();
