/**
 * Live-updates the site's colors inside the Customizer preview pane
 * as soon as a color picker changes — before "Publish" is even clicked.
 */
( function ( $ ) {
	function setVar( name, value ) {
		document.documentElement.style.setProperty( name, value );
	}

	wp.customize( 'dahim_color_ink', function ( value ) {
		value.bind( function ( newval ) { setVar( '--ink', newval ); } );
	} );
	wp.customize( 'dahim_color_amber', function ( value ) {
		value.bind( function ( newval ) { setVar( '--amber', newval ); } );
	} );
	wp.customize( 'dahim_color_green', function ( value ) {
		value.bind( function ( newval ) { setVar( '--green', newval ); } );
	} );
	wp.customize( 'dahim_color_paper', function ( value ) {
		value.bind( function ( newval ) { setVar( '--paper', newval ); } );
	} );
} )( jQuery );
