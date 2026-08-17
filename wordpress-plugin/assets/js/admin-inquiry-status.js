/**
 * Dahim — inline inquiry status updates.
 * Lets an admin change an inquiry's status directly from the Inquiries
 * list table (a dropdown in the Status column) without opening the
 * inquiry, via a small AJAX call.
 */
(function () {
	if ( typeof dahimInquiryAdmin === 'undefined' ) return;

	document.addEventListener( 'change', function ( e ) {
		if ( ! e.target.classList || ! e.target.classList.contains( 'dahim-status-select' ) ) return;

		var select = e.target;
		var postId = select.getAttribute( 'data-post-id' );
		var status = select.value;
		var previousStatus = select.getAttribute( 'data-status' );

		select.disabled = true;
		select.classList.remove( 'is-error' );

		var body = new URLSearchParams();
		body.append( 'action', 'dahim_update_inquiry_status' );
		body.append( 'post_id', postId );
		body.append( 'status', status );
		body.append( 'nonce', dahimInquiryAdmin.nonce );

		fetch( dahimInquiryAdmin.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString()
		} )
			.then( function ( r ) { return r.json(); } )
			.then( function ( res ) {
				select.disabled = false;
				if ( res && res.success ) {
					select.setAttribute( 'data-status', status );
					var color = ( dahimInquiryAdmin.colors && dahimInquiryAdmin.colors[ status ] ) || '#C79B3C';
					select.style.color = color;
					select.style.borderColor = color;
				} else {
					select.value = previousStatus;
					select.classList.add( 'is-error' );
				}
			} )
			.catch( function () {
				select.disabled = false;
				select.value = previousStatus;
				select.classList.add( 'is-error' );
			} );
	} );
})();
