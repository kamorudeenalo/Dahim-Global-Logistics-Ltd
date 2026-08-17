/**
 * Dahim — chat widget.
 * Opens a small popup from the round chat button; the first time it's
 * opened, plays a simulated "typing…" then "delivered" sequence for each
 * welcome message before showing the WhatsApp hand-off button.
 */
(function () {
	var fab     = document.getElementById( 'dahim-chat-fab' );
	var popup   = document.getElementById( 'dahim-chat-popup' );
	var closeBtn = document.getElementById( 'dahim-chat-close' );
	var body    = document.getElementById( 'dahim-chat-body' );
	var badge   = document.getElementById( 'dahim-chat-badge' );
	if ( ! fab || ! popup || ! body ) return;

	var messages = [];
	if ( body.getAttribute( 'data-msg-1' ) ) messages.push( body.getAttribute( 'data-msg-1' ) );
	if ( body.getAttribute( 'data-msg-2' ) ) messages.push( body.getAttribute( 'data-msg-2' ) );

	var played = false;

	function showTypingThenMessage( text, onDone ) {
		var typing = document.createElement( 'div' );
		typing.className = 'chat-bubble chat-bubble--typing';
		typing.innerHTML = '<span></span><span></span><span></span>';
		body.appendChild( typing );
		body.scrollTop = body.scrollHeight;

		var delay = 700 + Math.random() * 500;
		setTimeout( function () {
			typing.remove();

			var bubble = document.createElement( 'div' );
			bubble.className = 'chat-bubble';
			bubble.textContent = text;

			var status = document.createElement( 'span' );
			status.className = 'chat-bubble-status';
			status.textContent = 'Delivered';
			bubble.appendChild( status );

			body.appendChild( bubble );
			body.scrollTop = body.scrollHeight;

			if ( onDone ) onDone();
		}, delay );
	}

	function playSequence( index ) {
		if ( index >= messages.length ) {
			played = true;
			return;
		}
		showTypingThenMessage( messages[ index ], function () {
			setTimeout( function () { playSequence( index + 1 ); }, 350 );
		} );
	}

	function openPopup() {
		popup.classList.add( 'is-open' );
		popup.setAttribute( 'aria-hidden', 'false' );
		fab.setAttribute( 'aria-expanded', 'true' );
		if ( badge ) badge.classList.add( 'is-hidden' );
		if ( ! played && ! body.childElementCount ) {
			playSequence( 0 );
		}
	}
	function closePopup() {
		popup.classList.remove( 'is-open' );
		popup.setAttribute( 'aria-hidden', 'true' );
		fab.setAttribute( 'aria-expanded', 'false' );
	}

	fab.addEventListener( 'click', function () {
		if ( popup.classList.contains( 'is-open' ) ) {
			closePopup();
		} else {
			openPopup();
		}
	} );
	if ( closeBtn ) closeBtn.addEventListener( 'click', closePopup );

	document.addEventListener( 'click', function ( e ) {
		if ( ! popup.classList.contains( 'is-open' ) ) return;
		if ( popup.contains( e.target ) || fab.contains( e.target ) ) return;
		closePopup();
	} );
	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' && popup.classList.contains( 'is-open' ) ) closePopup();
	} );
})();
