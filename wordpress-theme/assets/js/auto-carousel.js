/**
 * Dahim — generic auto-scrolling carousel.
 *
 * Works on any element with [data-auto-carousel]:
 * - auto-scrolls continuously via CSS transform (not native scrollLeft —
 *   scrollLeft-based marquees stall on wide viewports where the content
 *   doesn't overflow the container; transform has no such dependency)
 * - pauses on hover
 * - supports click-and-drag (mouse) and touch-drag
 * - optional Prev/Next buttons via [data-carousel-prev] / [data-carousel-next]
 *   placed anywhere inside the same wrapping element
 *
 * Markup contract:
 * <div data-auto-carousel data-speed="0.5">
 *   <div data-carousel-track>...items, duplicated 2x for a seamless loop...</div>
 * </div>
 */
(function () {
	function initCarousel( el ) {
		var track = el.querySelector( '[data-carousel-track]' );
		if ( ! track ) return;

		var position = 0;
		var singleSetWidth = 0;
		var isPaused = false;
		var isDragging = false;
		var dragMoved = false;
		var startX = 0;
		var startPosition = 0;
		var speed = parseFloat( el.getAttribute( 'data-speed' ) ) || 0.5;

		function measure() {
			singleSetWidth = track.scrollWidth / 2;
		}
		measure();
		window.addEventListener( 'resize', measure );
		window.addEventListener( 'load', measure );

		function render() {
			track.style.transform = 'translateX(' + position + 'px)';
		}

		function tick() {
			if ( ! isPaused && ! isDragging && singleSetWidth > 0 ) {
				position -= speed;
				if ( Math.abs( position ) >= singleSetWidth ) position += singleSetWidth;
				render();
			}
			requestAnimationFrame( tick );
		}
		requestAnimationFrame( tick );

		el.addEventListener( 'mouseenter', function () { isPaused = true; } );
		el.addEventListener( 'mouseleave', function () {
			isPaused = false;
			isDragging = false;
			el.classList.remove( 'dragging' );
		} );

		function pointerDown( x ) {
			isDragging = true;
			dragMoved = false;
			el.classList.add( 'dragging' );
			startX = x;
			startPosition = position;
		}
		function pointerMove( x ) {
			if ( ! isDragging ) return;
			var dx = x - startX;
			if ( Math.abs( dx ) > 3 ) dragMoved = true;
			position = startPosition + dx;
			if ( singleSetWidth > 0 ) {
				if ( position > 0 ) position -= singleSetWidth;
				if ( position < -singleSetWidth ) position += singleSetWidth;
			}
			render();
		}
		function pointerUp() {
			isDragging = false;
			el.classList.remove( 'dragging' );
		}

		el.addEventListener( 'mousedown', function ( e ) {
			pointerDown( e.pageX );
			e.preventDefault();
		} );
		window.addEventListener( 'mousemove', function ( e ) { pointerMove( e.pageX ); } );
		window.addEventListener( 'mouseup', pointerUp );

		el.addEventListener( 'touchstart', function ( e ) {
			pointerDown( e.touches[ 0 ].pageX );
		}, { passive: true } );
		el.addEventListener( 'touchmove', function ( e ) {
			pointerMove( e.touches[ 0 ].pageX );
		}, { passive: true } );
		el.addEventListener( 'touchend', pointerUp );

		el.addEventListener( 'click', function ( e ) {
			if ( dragMoved ) e.preventDefault();
		}, true );

		// Optional manual Prev/Next buttons, searched for in the carousel's
		// parent so they can sit visually below/beside it in the markup.
		var scope = el.parentElement || el;
		var prevBtn = scope.querySelector( '[data-carousel-prev]' );
		var nextBtn = scope.querySelector( '[data-carousel-next]' );
		var step = 300;

		function nudge( amount ) {
			isPaused = true;
			position += amount;
			if ( singleSetWidth > 0 ) {
				if ( position > 0 ) position -= singleSetWidth;
				if ( position < -singleSetWidth ) position += singleSetWidth;
			}
			render();
		}
		if ( prevBtn ) prevBtn.addEventListener( 'click', function () { nudge( step ); } );
		if ( nextBtn ) nextBtn.addEventListener( 'click', function () { nudge( -step ); } );
	}

	document.querySelectorAll( '[data-auto-carousel]' ).forEach( initCarousel );
})();
