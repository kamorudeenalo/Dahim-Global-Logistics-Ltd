<?php

/* ---------------------------------------------------------
 * 1. THEME SETUP
 * ------------------------------------------------------- */
function dahim_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption' ) );
	add_theme_support( 'custom-logo', array(
		'height'      => 60,
		'width'       => 220,
		'flex-height' => true,
		'flex-width'  => true,
	) );

	register_nav_menus( array(
		'primary' => __( 'Primary Menu', 'dahim' ),
		'footer'  => __( 'Footer Menu', 'dahim' ),
	) );
}
add_action( 'after_setup_theme', 'dahim_setup' );

// This theme's templates call functions the "Dahim Dashboard" plugin
// provides (Shipments, Inquiries, Departments, Jobs, and everything else
// that used to live directly in this file) — that split is deliberate, so
// the dashboard app and its data survive a future theme change instead of
// disappearing with it. But it does mean this theme now requires that
// plugin to be installed and active; without it, template calls to those
// functions would fatal instead of just showing a broken page. This
// catches that early with a clear admin notice instead.
add_action( 'admin_notices', function () {
	if ( function_exists( 'dahim_register_all_api_meta' ) ) return; // plugin is active
	if ( ! current_user_can( 'activate_plugins' ) ) return;
	echo '<div class="notice notice-error"><p><strong>Dahim theme:</strong> the required "Dahim Dashboard" plugin isn\'t active. Shipments, Inquiries, Departments, Jobs, and the contact form won\'t work correctly until it\'s installed and activated.</p></div>';
} );

/* ---------------------------------------------------------
 * 1B. SEO — meta description, Open Graph, Twitter Card, canonical
 * ------------------------------------------------------- */
function dahim_seo_description() {
	if ( is_singular() ) {
		global $post;
		$excerpt = has_excerpt( $post ) ? get_the_excerpt( $post ) : $post->post_content;
		$desc = wp_strip_all_tags( $excerpt );
		if ( $desc ) return wp_trim_words( $desc, 30 );
	}
	if ( is_category() || is_tag() || is_tax() ) {
		$desc = term_description();
		if ( $desc ) return wp_trim_words( wp_strip_all_tags( $desc ), 30 );
	}
	return get_bloginfo( 'description' ) ?: 'Freight forwarding, customs clearing, and haulage services for businesses moving cargo through Nigeria.';
}

function dahim_seo_image() {
	if ( is_singular() && has_post_thumbnail() ) {
		$thumb = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
		if ( $thumb ) return $thumb[0];
	}
	return get_template_directory_uri() . '/assets/images/dahim-hero.webp';
}

function dahim_seo_tags() {
	$description = esc_attr( dahim_seo_description() );
	$image       = esc_url( dahim_seo_image() );
	$url         = esc_url( is_front_page() ? home_url( '/' ) : ( is_singular() ? get_permalink() : home_url( add_query_arg( array(), $GLOBALS['wp']->request ) ) ) );
	$site_name   = esc_attr( get_bloginfo( 'name' ) );
	$title       = esc_attr( wp_get_document_title() );
	$type        = is_singular( 'post' ) ? 'article' : 'website';

	echo "\n<!-- SEO -->\n";
	echo '<meta name="description" content="' . $description . '">' . "\n";
	echo '<link rel="canonical" href="' . $url . '">' . "\n";
	echo '<meta property="og:type" content="' . esc_attr( $type ) . '">' . "\n";
	echo '<meta property="og:title" content="' . $title . '">' . "\n";
	echo '<meta property="og:description" content="' . $description . '">' . "\n";
	echo '<meta property="og:url" content="' . $url . '">' . "\n";
	echo '<meta property="og:image" content="' . $image . '">' . "\n";
	echo '<meta property="og:site_name" content="' . $site_name . '">' . "\n";
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	echo '<meta name="twitter:title" content="' . $title . '">' . "\n";
	echo '<meta name="twitter:description" content="' . $description . '">' . "\n";
	echo '<meta name="twitter:image" content="' . $image . '">' . "\n";
}
add_action( 'wp_head', 'dahim_seo_tags', 1 );

// --- Organization / LocalBusiness structured data (schema.org) ---
function dahim_structured_data() {
	if ( ! is_front_page() ) return;

	$phone   = get_option( 'dahim_phone', '+234 803 123 4567' );
	$email   = get_option( 'dahim_email', 'info@dahimlogistics.com' );
	$address = get_option( 'dahim_address', "Plot 14, Wharf Road, Apapa,\nLagos State, Nigeria" );
	$lines   = array_filter( array_map( 'trim', explode( "\n", $address ) ) );

	$data = array(
		'@context'  => 'https://schema.org',
		'@type'     => 'LocalBusiness',
		'name'      => get_bloginfo( 'name' ),
		'url'       => home_url( '/' ),
		'logo'      => get_template_directory_uri() . '/assets/images/dahim-logo.webp',
		'image'     => get_template_directory_uri() . '/assets/images/dahim-hero.webp',
		'telephone' => $phone,
		'email'     => $email,
		'address'   => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => isset( $lines[0] ) ? $lines[0] : '',
			'addressLocality' => isset( $lines[1] ) ? $lines[1] : 'Lagos',
			'addressCountry'  => 'NG',
		),
		'areaServed'     => 'NG',
		'priceRange'     => '$$',
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $data ) . '</script>' . "\n";
}
add_action( 'wp_head', 'dahim_structured_data', 2 );

// --- Analytics (Google Analytics 4) — plumbing only, off until a Measurement ID is set ---
function dahim_analytics_tag() {
	$ga_id = get_theme_mod( 'dahim_ga_id', '' );
	if ( ! $ga_id ) return;
	?>
	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		window.dahimInitAnalytics = function () {
			if ( window.dahimAnalyticsLoaded ) return;
			window.dahimAnalyticsLoaded = true;
			var s = document.createElement('script');
			s.async = true;
			s.src = 'https://www.googletagmanager.com/gtag/js?id=<?php echo esc_js( $ga_id ); ?>';
			document.head.appendChild(s);
			gtag('js', new Date());
			gtag('config', '<?php echo esc_js( $ga_id ); ?>');
		};
		try {
			if ( window.localStorage.getItem('dahim_cookie_notice_dismissed') === '1' ) {
				window.dahimInitAnalytics();
			}
		} catch (e) {}
	</script>
	<?php
}
add_action( 'wp_head', 'dahim_analytics_tag' );

function dahim_asset_version( $relative_path ) {
	$full_path = get_template_directory() . $relative_path;
	return file_exists( $full_path ) ? filemtime( $full_path ) : '1.0';
}

function dahim_assets() {
	wp_enqueue_style( 'dahim-fonts', 'https://fonts.googleapis.com/css2?family=Archivo+Expanded:wght@700;800&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap', array(), null );
	wp_enqueue_style( 'dahim-style', get_stylesheet_uri(), array(), dahim_asset_version( '/style.css' ) );
	wp_enqueue_script( 'dahim-sticky-header', get_template_directory_uri() . '/assets/js/sticky-header.js', array(), dahim_asset_version( '/assets/js/sticky-header.js' ), true );
	wp_enqueue_script( 'dahim-mobile-nav', get_template_directory_uri() . '/assets/js/mobile-nav.js', array(), dahim_asset_version( '/assets/js/mobile-nav.js' ), true );
	wp_enqueue_script( 'dahim-auto-carousel', get_template_directory_uri() . '/assets/js/auto-carousel.js', array(), dahim_asset_version( '/assets/js/auto-carousel.js' ), true );
	wp_enqueue_script( 'dahim-faq-accordion', get_template_directory_uri() . '/assets/js/faq-accordion.js', array(), dahim_asset_version( '/assets/js/faq-accordion.js' ), true );
	wp_enqueue_script( 'dahim-chat-widget', get_template_directory_uri() . '/assets/js/chat-widget.js', array(), dahim_asset_version( '/assets/js/chat-widget.js' ), true );
	wp_enqueue_script( 'dahim-back-to-top', get_template_directory_uri() . '/assets/js/back-to-top.js', array(), dahim_asset_version( '/assets/js/back-to-top.js' ), true );
	wp_enqueue_script( 'dahim-cookie-notice', get_template_directory_uri() . '/assets/js/cookie-notice.js', array(), dahim_asset_version( '/assets/js/cookie-notice.js' ), true );

	if ( is_page_template( 'page-track.php' ) ) {
		wp_enqueue_script( 'dahim-jspdf', 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js', array(), '2.5.1', true );
		wp_enqueue_script( 'dahim-shipment-modal', get_template_directory_uri() . '/assets/js/shipment-modal.js', array( 'dahim-jspdf' ), dahim_asset_version( '/assets/js/shipment-modal.js' ), true );
	}

	if ( is_page_template( 'page-contact.php' ) ) {
		wp_enqueue_script( 'dahim-contact-form', get_template_directory_uri() . '/assets/js/contact-form.js', array(), dahim_asset_version( '/assets/js/contact-form.js' ), true );
	}
}
add_action( 'wp_enqueue_scripts', 'dahim_assets' );

/* ---------------------------------------------------------
 * 4. CUSTOMIZER — visual/theme settings editors can change
 *    without touching code (Appearance → Customize). Contact
 *    Info and the email sender settings live in the Dahim
 *    Dashboard plugin instead (Settings → Dahim Settings) —
 *    those are business data, not a theme-specific look, so
 *    they're stored as options rather than theme_mods and
 *    survive a future theme change.
 * ------------------------------------------------------- */
function dahim_customize_register( $wp_customize ) {

	// --- Brand Colors (the whole site's palette, editable with zero code) ---
	$wp_customize->add_section( 'dahim_colors', array( 'title' => 'Brand Colors', 'priority' => 10 ) );

	$color_fields = array(
		'dahim_color_ink'    => array( 'label' => 'Navy (headers, footer, dark backgrounds)', 'default' => '#1E2A44' ),
		'dahim_color_amber'  => array( 'label' => 'Gold (buttons, accents, highlights)',        'default' => '#C79B3C' ),
		'dahim_color_green'  => array( 'label' => 'WhatsApp Green',                             'default' => '#008751' ),
		'dahim_color_paper'  => array( 'label' => 'Page Background',                            'default' => '#FFFFFF' ),
	);
	foreach ( $color_fields as $id => $field ) {
		$wp_customize->add_setting( $id, array( 'default' => $field['default'], 'sanitize_callback' => 'sanitize_hex_color', 'transport' => 'postMessage' ) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $id, array(
			'label'   => $field['label'],
			'section' => 'dahim_colors',
		) ) );
	}

	// --- Footer Logo (light-colored version for the navy footer) ---
	$wp_customize->add_section( 'dahim_footer_logo', array( 'title' => 'Footer Logo', 'priority' => 15 ) );
	$wp_customize->add_setting( 'dahim_footer_logo', array( 'default' => get_template_directory_uri() . '/assets/images/dahim-footer-logo.webp' ) );
	$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'dahim_footer_logo', array(
		'label'       => 'Footer Logo',
		'description' => 'Use a light/white version of the logo — the footer background is dark navy.',
		'section'     => 'dahim_footer_logo',
	) ) );

	// --- SEO & Analytics ---
	$wp_customize->add_section( 'dahim_seo', array( 'title' => 'SEO & Analytics', 'priority' => 31 ) );
	$wp_customize->add_setting( 'dahim_ga_id', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
	$wp_customize->add_control( 'dahim_ga_id', array(
		'label'       => 'Google Analytics Measurement ID',
		'description' => 'e.g. G-XXXXXXXXXX. Leave blank to disable analytics tracking.',
		'section'     => 'dahim_seo',
	) );

	// --- Homepage Stats (4 stat blocks) ---
	$wp_customize->add_section( 'dahim_stats', array( 'title' => 'Homepage Stats', 'priority' => 35 ) );
	$stat_defaults = array(
		1 => array( 'num' => '14+',  'label' => 'Years of Industry Experience' ),
		2 => array( 'num' => '500+', 'label' => 'Successful Cargo Operations' ),
		3 => array( 'num' => '120+', 'label' => 'Happy Clients Across Nigeria' ),
		4 => array( 'num' => '14+',  'label' => 'Reliable Logistics Network' ),
	);
	foreach ( $stat_defaults as $i => $d ) {
		$wp_customize->add_setting( "dahim_stat_{$i}_num", array( 'default' => $d['num'], 'sanitize_callback' => 'sanitize_text_field' ) );
		$wp_customize->add_control( "dahim_stat_{$i}_num", array( 'label' => "Stat {$i} Number", 'section' => 'dahim_stats' ) );
		$wp_customize->add_setting( "dahim_stat_{$i}_label", array( 'default' => $d['label'], 'sanitize_callback' => 'sanitize_text_field' ) );
		$wp_customize->add_control( "dahim_stat_{$i}_label", array( 'label' => "Stat {$i} Label", 'section' => 'dahim_stats' ) );
	}

	// --- Homepage Hero ---
	$wp_customize->add_section( 'dahim_hero', array( 'title' => 'Homepage Hero', 'priority' => 20 ) );
	$wp_customize->add_setting( 'dahim_hero_heading', array( 'default' => "Your cargo,\ncleared and\nmoving on time.", 'sanitize_callback' => 'sanitize_textarea_field' ) );
	$wp_customize->add_control( 'dahim_hero_heading', array( 'label' => 'Headline (one line per <br>, last line highlighted)', 'section' => 'dahim_hero', 'type' => 'textarea' ) );
	$wp_customize->add_setting( 'dahim_hero_lead', array( 'default' => "Dahim Global Logistics Ltd handles freight forwarding, port haulage, and customs clearance for businesses importing and exporting through Nigeria — with one team managing your shipment from the port to your door.", 'sanitize_callback' => 'sanitize_textarea_field' ) );
	$wp_customize->add_control( 'dahim_hero_lead', array( 'label' => 'Supporting Paragraph', 'section' => 'dahim_hero', 'type' => 'textarea' ) );
	$wp_customize->add_setting( 'dahim_hero_image', array( 'default' => get_template_directory_uri() . '/assets/images/dahim-hero.webp' ) );
	$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'dahim_hero_image', array( 'label' => 'Hero Image', 'section' => 'dahim_hero' ) ) );

	// --- About Page: Mission, Vision, Company Overview, Experience ---
	$wp_customize->add_section( 'dahim_about', array( 'title' => 'About Page Content', 'priority' => 40 ) );
	$about_fields = array(
		'dahim_mission'  => array( 'label' => 'Mission Statement', 'default' => 'Our mission is to deliver reliable, transparent, and efficient logistics solutions while maintaining the highest standards of professionalism, maritime compliance, and operational excellence in every shipment we handle.' ),
		'dahim_vision'   => array( 'label' => 'Vision Statement', 'default' => 'To become a leading logistics and maritime solutions provider recognized for reliability, innovation, and excellence in delivering seamless global shipping and supply chain solutions.' ),
		'dahim_overview' => array( 'label' => 'Company Overview (Paragraph 1)', 'default' => 'DAHIM Global Logistics Limited (DGL) is a trusted logistics and freight management company dedicated to delivering reliable, efficient, and cost-effective supply chain solutions across Nigeria and West Africa.' ),
		'dahim_experience' => array( 'label' => 'Our Experience', 'default' => 'DAHIM Global Logistics Limited was founded by Ajide Ibrahim Eniobanfe, a seasoned logistics professional with over 14 years of hands-on experience in clearing and forwarding operations.' ),
	);
	foreach ( $about_fields as $id => $field ) {
		$wp_customize->add_setting( $id, array( 'default' => $field['default'], 'sanitize_callback' => 'sanitize_textarea_field' ) );
		$wp_customize->add_control( $id, array( 'label' => $field['label'], 'section' => 'dahim_about', 'type' => 'textarea' ) );
	}
}
add_action( 'customize_register', 'dahim_customize_register' );

/* Small helper: turn a raw WhatsApp number into a wa.me link. The number
 * itself is plugin-owned data (Settings → Dahim Settings), read via
 * get_option() so it survives a future theme change. */
function dahim_whatsapp_link( $text_after = '' ) {
	$number = get_option( 'dahim_whatsapp', '2348031234567' );
	return 'https://wa.me/' . preg_replace( '/[^0-9]/', '', $number ) . $text_after;
}

/* Uses the Privacy Policy page set under Settings → Privacy if one exists;
 * otherwise falls back to the conventional /privacy-policy/ slug so the
 * footer link always points somewhere sensible even before that's configured. */
function dahim_privacy_url() {
	$url = get_privacy_policy_url();
	return $url ? $url : home_url( '/privacy-policy/' );
}

/* Same idea for Terms of Service — WordPress has no core equivalent to
 * Settings → Privacy for this, so it looks for a page at the conventional
 * /terms-of-service/ slug. */
function dahim_terms_url() {
	$page = get_page_by_path( 'terms-of-service' );
	return $page ? get_permalink( $page ) : home_url( '/terms-of-service/' );
}

/* Returns a stat's saved value, or its correct default if the Customizer
 * "Homepage Stats" section has never been opened/saved yet. Registering a
 * Customizer setting with a 'default' only pre-fills the Customize screen —
 * it does NOT make get_theme_mod() return that value elsewhere unless the
 * setting has actually been saved to the database at least once. Every
 * call site must pass its own fallback, which is what this centralizes. */
function dahim_stat( $index, $field ) {
	$defaults = array(
		1 => array( 'num' => '14+',  'label' => 'Years of Industry Experience' ),
		2 => array( 'num' => '500+', 'label' => 'Successful Cargo Operations' ),
		3 => array( 'num' => '120+', 'label' => 'Happy Clients Across Nigeria' ),
		4 => array( 'num' => '14+',  'label' => 'Reliable Logistics Network' ),
	);
	$default = isset( $defaults[ $index ][ $field ] ) ? $defaults[ $index ][ $field ] : '';
	return get_theme_mod( "dahim_stat_{$index}_{$field}", $default );
}

/* Output the Brand Colors from the Customizer as CSS variables.
 * This is what makes the color pickers actually repaint the site —
 * no code edits needed, ever, to change the palette. */
function dahim_dynamic_colors_css() {
	$ink   = get_theme_mod( 'dahim_color_ink', '#1E2A44' );
	$amber = get_theme_mod( 'dahim_color_amber', '#C79B3C' );
	$green = get_theme_mod( 'dahim_color_green', '#008751' );
	$paper = get_theme_mod( 'dahim_color_paper', '#FFFFFF' );
	?>
	<style id="dahim-dynamic-colors">
		:root{
			--ink: <?php echo esc_html( $ink ); ?>;
			--amber: <?php echo esc_html( $amber ); ?>;
			--green: <?php echo esc_html( $green ); ?>;
			--paper: <?php echo esc_html( $paper ); ?>;
		}
	</style>
	<?php
}
add_action( 'wp_head', 'dahim_dynamic_colors_css', 20 );

/* Live preview in the Customizer (updates colors instantly as you drag the picker,
 * before you even click Publish) */
function dahim_customize_preview_js() {
	wp_enqueue_script( 'dahim-customizer-preview', get_template_directory_uri() . '/assets/js/customizer-preview.js', array( 'customize-preview' ), dahim_asset_version( '/assets/js/customizer-preview.js' ), true );
}
add_action( 'customize_preview_init', 'dahim_customize_preview_js' );

/* ---------------------------------------------------------
 * 8. FALLBACK MENU (in case no menu is assigned yet)
 * ------------------------------------------------------- */
function dahim_fallback_menu() {
	echo '<ul>';
	echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">Home</a></li>';
	echo '<li><a href="' . esc_url( home_url( '/about/' ) ) . '">About Dahim</a></li>';
	echo '<li class="has-dropdown"><a href="' . esc_url( home_url( '/services/' ) ) . '">Our Services</a>
			<div class="dropdown">
				<a href="' . esc_url( home_url( '/services/#what-we-do' ) ) . '">What We Do</a>
				<a href="' . esc_url( home_url( '/services/#industries' ) ) . '">Industries We Service</a>
			</div></li>';
	echo '<li><a href="' . esc_url( home_url( '/contact/' ) ) . '">Contact Us</a></li>';
	echo '<li><a href="' . esc_url( home_url( '/track/' ) ) . '">Track a Shipment</a></li>';
	echo '<li><a href="' . esc_url( get_permalink( get_option( 'page_for_posts' ) ) ) . '">Insights</a></li>';
	echo '</ul>';
}

/* ---------------------------------------------------------
 * 9. MISC
 * ------------------------------------------------------- */
// Register a small admin menu icon set is unnecessary — dashicons cover it.

/* ---------------------------------------------------------
 * 10. BLOG / INSIGHTS HELPERS
 * ------------------------------------------------------- */
function dahim_reading_time( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$content = get_post_field( 'post_content', $post_id );
	$word_count = str_word_count( wp_strip_all_tags( $content ) );
	$minutes = max( 1, ceil( $word_count / 200 ) );
	return $minutes . ' min read';
}

function dahim_primary_category( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$cats = get_the_category( $post_id );
	return ! empty( $cats ) ? $cats[0]->name : 'Insights';
}

/* Renders a simple "Home > ... > Current Page" breadcrumb trail.
 * Also outputs matching BreadcrumbList structured data for SEO. */
function dahim_breadcrumbs() {
	if ( is_front_page() ) return;

	$trail = array( array( 'label' => 'Home', 'url' => home_url( '/' ) ) );

	if ( is_singular( 'post' ) ) {
		$page_for_posts = get_option( 'page_for_posts' );
		if ( $page_for_posts ) $trail[] = array( 'label' => 'Insights', 'url' => get_permalink( $page_for_posts ) );
		$cats = get_the_category();
		if ( ! empty( $cats ) ) $trail[] = array( 'label' => $cats[0]->name, 'url' => get_category_link( $cats[0]->term_id ) );
		$trail[] = array( 'label' => get_the_title(), 'url' => '' );
	} elseif ( is_category() ) {
		$page_for_posts = get_option( 'page_for_posts' );
		if ( $page_for_posts ) $trail[] = array( 'label' => 'Insights', 'url' => get_permalink( $page_for_posts ) );
		$trail[] = array( 'label' => single_cat_title( '', false ), 'url' => '' );
	} elseif ( is_singular( 'service' ) ) {
		$trail[] = array( 'label' => 'Our Services', 'url' => home_url( '/services/' ) );
		$trail[] = array( 'label' => get_the_title(), 'url' => '' );
	} elseif ( is_singular( 'job' ) ) {
		$careers_page = get_page_by_path( 'careers' );
		if ( $careers_page ) $trail[] = array( 'label' => 'Careers', 'url' => get_permalink( $careers_page ) );
		$trail[] = array( 'label' => get_the_title(), 'url' => '' );
	} elseif ( is_search() ) {
		$trail[] = array( 'label' => 'Search Results', 'url' => '' );
	} elseif ( is_404() ) {
		return;
	} elseif ( is_page() ) {
		$trail[] = array( 'label' => get_the_title(), 'url' => '' );
	}

	if ( count( $trail ) < 2 ) return;

	echo '<nav class="breadcrumbs" aria-label="Breadcrumb"><div class="wrap"><ol>';
	$schema_items = array();
	foreach ( $trail as $i => $item ) {
		$is_last = ( $i === count( $trail ) - 1 );
		echo '<li>';
		if ( $item['url'] && ! $is_last ) {
			echo '<a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a>';
		} else {
			echo '<span aria-current="page">' . esc_html( $item['label'] ) . '</span>';
		}
		if ( ! $is_last ) echo '<span class="sep">/</span>';
		echo '</li>';

		$schema_items[] = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => $item['label'],
			'item'     => $item['url'] ? $item['url'] : ( is_singular() ? get_permalink() : home_url( add_query_arg( array(), $GLOBALS['wp']->request ) ) ),
		);
	}
	echo '</ol></div></nav>';

	echo '<script type="application/ld+json">' . wp_json_encode( array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $schema_items,
	) ) . '</script>';
}

/* Make the excerpt length shorter for service cards where used. */
function dahim_excerpt_length( $length ) {
	return 30;
}
add_filter( 'excerpt_length', 'dahim_excerpt_length' );

/* Show 12 Insights per page on desktop, but only 6 on mobile (detected
 * server-side via wp_is_mobile(), so the smaller page loads faster and
 * the card grid doesn't feel endless on a phone). Only touches the
 * main blog query — the site-wide Settings → Reading value is untouched. */
function dahim_insights_per_page( $query ) {
	if ( ! is_admin() && $query->is_main_query() && $query->is_home() ) {
		$query->set( 'posts_per_page', wp_is_mobile() ? 6 : 12 );
	}
}
add_action( 'pre_get_posts', 'dahim_insights_per_page' );
