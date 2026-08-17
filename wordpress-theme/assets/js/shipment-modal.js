/**
 * Dahim — shipment details modal.
 *
 * Handles the "View Full Shipment Details" pop-up on the tracking page:
 * - opens on button click, and automatically once a shipment is found
 * - closes on the close button, overlay click, or Escape
 * - Print button uses the browser's native print dialog, scoped to just
 *   the shipment document (via a body class + print stylesheet), so
 *   "Save as PDF" from that dialog also works as a PDF export
 * - Download PDF button builds a real PDF client-side with jsPDF, reading
 *   the already-rendered detail fields straight out of the DOM
 */
(function () {
	var modal = document.getElementById( 'dahim-shipment-modal' );
	if ( ! modal ) return;

	var openBtn   = document.getElementById( 'dahim-open-shipment-modal' );
	var closeBtn  = document.getElementById( 'dahim-close-shipment-modal' );
	var printBtn  = document.getElementById( 'dahim-print-shipment' );
	var downloadBtn = document.getElementById( 'dahim-download-shipment' );
	var doc       = document.getElementById( 'dahim-shipment-doc' );

	function openModal() {
		modal.classList.add( 'is-open' );
		modal.setAttribute( 'aria-hidden', 'false' );
		document.body.classList.add( 'dahim-modal-open' );
	}
	function closeModal() {
		modal.classList.remove( 'is-open' );
		modal.setAttribute( 'aria-hidden', 'true' );
		document.body.classList.remove( 'dahim-modal-open' );
	}

	if ( openBtn ) openBtn.addEventListener( 'click', openModal );
	if ( closeBtn ) closeBtn.addEventListener( 'click', closeModal );

	modal.addEventListener( 'click', function ( e ) {
		if ( e.target === modal ) closeModal();
	} );
	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' && modal.classList.contains( 'is-open' ) ) closeModal();
	} );

	// Pop the full details up automatically the moment a shipment is found.
	if ( modal.getAttribute( 'data-autopen' ) === 'true' ) {
		openModal();
	}

	// --- Print (also usable as "Save as PDF" via the browser's print dialog) ---
	if ( printBtn ) {
		printBtn.addEventListener( 'click', function () {
			window.print();
		} );
	}

	// --- Download PDF, built directly from the rendered fields ---
	function fieldText( key ) {
		var el = doc.querySelector( '[data-field="' + key + '"]' );
		return el ? el.textContent.trim() : '';
	}

	function buildPdf() {
		if ( ! window.jspdf || ! window.jspdf.jsPDF ) {
			window.print();
			return;
		}
		var jsPDF = window.jspdf.jsPDF;
		var pdf = new jsPDF( { unit: 'pt', format: 'a4' } );
		var pageWidth = pdf.internal.pageSize.getWidth();
		var margin = 48;
		var y = margin;

		function heading( text, size ) {
			pdf.setFont( 'helvetica', 'bold' );
			pdf.setFontSize( size || 12 );
			pdf.setTextColor( 30, 42, 68 );
			pdf.text( text, margin, y );
			y += ( size || 12 ) * 0.9;
		}
		function row( label, value ) {
			if ( ! value ) value = '—';
			pdf.setFont( 'helvetica', 'bold' );
			pdf.setFontSize( 10 );
			pdf.setTextColor( 76, 90, 120 );
			pdf.text( label + ':', margin, y );
			pdf.setFont( 'helvetica', 'normal' );
			pdf.setTextColor( 30, 34, 41 );
			var lines = pdf.splitTextToSize( value, pageWidth - margin * 2 - 150 );
			pdf.text( lines, margin + 150, y );
			y += Math.max( 16, lines.length * 13 );
		}
		function spacer( amount ) { y += amount || 10; }
		function ensureRoom( needed ) {
			if ( y + needed > pdf.internal.pageSize.getHeight() - margin ) {
				pdf.addPage();
				y = margin;
			}
		}

		pdf.setFont( 'helvetica', 'bold' );
		pdf.setFontSize( 18 );
		pdf.setTextColor( 30, 42, 68 );
		pdf.text( 'Dahim Global Logistics', margin, y );
		y += 22;

		pdf.setFontSize( 13 );
		pdf.setTextColor( 199, 155, 60 );
		pdf.text( 'Tracking No. ' + fieldText( 'tracking_number' ), margin, y );
		y += 14;

		pdf.setFont( 'helvetica', 'normal' );
		pdf.setFontSize( 10 );
		pdf.setTextColor( 76, 90, 120 );
		pdf.text( 'Status: ' + fieldText( 'stage_label' ), margin, y );
		spacer( 24 );

		heading( 'Shipment Owner' );
		spacer( 6 );
		row( 'Name', fieldText( 'owner_name' ) );
		row( 'Email', fieldText( 'owner_email' ) );
		row( 'Phone', fieldText( 'owner_phone' ) );
		spacer( 14 );

		heading( 'Consignee' );
		spacer( 6 );
		row( 'Name', fieldText( 'consignee_name' ) );
		row( 'Phone', fieldText( 'consignee_phone' ) );
		spacer( 14 );

		ensureRoom( 100 );
		heading( 'Route' );
		spacer( 6 );
		row( 'Origin', fieldText( 'origin' ) );
		row( 'Destination', fieldText( 'destination' ) );
		row( 'Current Location', fieldText( 'current_location' ) );
		spacer( 14 );

		ensureRoom( 120 );
		heading( 'Service' );
		spacer( 6 );
		row( 'Service Type', fieldText( 'service_type' ) );
		row( 'Carrier', fieldText( 'carrier' ) );
		row( 'Date Booked', fieldText( 'date_booked' ) );
		row( 'Estimated Delivery', fieldText( 'estimated_delivery' ) );
		spacer( 14 );

		ensureRoom( 140 );
		heading( 'Cargo Details' );
		spacer( 6 );
		row( 'Description', fieldText( 'package_description' ) );
		row( 'Weight', fieldText( 'weight' ) );
		row( 'Pieces', fieldText( 'pieces' ) );
		row( 'Dimensions', fieldText( 'dimensions' ) );
		row( 'Declared Value', fieldText( 'declared_value' ) );
		row( 'Special Instructions', fieldText( 'special_instructions' ) );

		spacer( 20 );
		pdf.setFont( 'helvetica', 'normal' );
		pdf.setFontSize( 9 );
		pdf.setTextColor( 140, 140, 140 );
		pdf.text( 'Generated ' + new Date().toLocaleString(), margin, y );

		var filename = 'dahim-shipment-' + ( fieldText( 'tracking_number' ) || 'details' ) + '.pdf';
		pdf.save( filename );
	}

	if ( downloadBtn ) downloadBtn.addEventListener( 'click', buildPdf );
})();
