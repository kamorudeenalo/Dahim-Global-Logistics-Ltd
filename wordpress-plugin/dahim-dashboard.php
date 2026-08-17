<?php
/**
 * Plugin Name: Dahim Dashboard
 * Description: Custom post types, REST API, dashboard authentication, and branded transactional emails for Dahim Global Logistics — powers the separate dashboard app at /dashboard/, independent of the active theme.
 * Version: 1.0.7
 * Author: Dahim Global Logistics
 * Text Domain: dahim-dashboard
 */

if ( ! defined( 'ABSPATH' ) ) exit; // no direct access

/**
 * REST API meta support
 *
 * WordPress requires custom post types that expose registered post meta
 * through the REST API to declare support for the `custom-fields` feature.
 * The Dahim CPTs store their dashboard data in post meta, so without this
 * support the REST response contains an empty `meta` object even though the
 * values are present and visible in wp-admin.
 */
function dahim_enable_rest_meta_support( $args, $post_type ) {
	$dashboard_cpts = array(
		'service',
		'team_member',
		'faq',
		'trade_lane',
		'shipment',
		'department',
		'job',
		'inquiry',
	);

	if ( in_array( $post_type, $dashboard_cpts, true ) ) {
		$supports = isset( $args['supports'] ) ? (array) $args['supports'] : array();
		if ( ! in_array( 'custom-fields', $supports, true ) ) {
			$supports[] = 'custom-fields';
		}
		$args['supports'] = $supports;
	}

	return $args;
}
add_filter( 'register_post_type_args', 'dahim_enable_rest_meta_support', 10, 2 );

/**
 * Site-wide business settings — Contact Info and the outgoing email
 * sender. Deliberately NOT part of the theme: this is business data
 * (a phone number, an email address), not a visual/theme choice, so it's
 * stored as real wp_options via the Customizer's 'option' setting type
 * rather than theme_mods — it'll survive a future theme change instead
 * of resetting the moment someone switches themes. Still lives in the
 * same Appearance → Customize screen editors already know, just with a
 * different mechanism underneath.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function dahim_plugin_customize_register( $wp_customize ) {

	// --- Contact Info ---
	$wp_customize->add_section( 'dahim_contact', array( 'title' => 'Contact Info', 'priority' => 30 ) );

	$contact_fields = array(
		'dahim_phone'     => array( 'label' => 'Phone Number',   'default' => '+234 803 123 4567' ),
		'dahim_phone2'    => array( 'label' => 'Second Phone',   'default' => '+234 802 123 4567' ),
		'dahim_whatsapp'  => array( 'label' => 'WhatsApp Number (digits only, with country code)', 'default' => '2348031234567' ),
		'dahim_email'     => array( 'label' => 'Primary Email',  'default' => 'info@dahimlogistics.com' ),
		'dahim_email_ops' => array( 'label' => 'Ops Email',      'default' => 'ops@dahimlogistics.com' ),
		'dahim_address'   => array( 'label' => 'Office Address', 'default' => "Plot 14, Wharf Road, Apapa,\nLagos State, Nigeria" ),
	);
	foreach ( $contact_fields as $id => $field ) {
		$wp_customize->add_setting( $id, array(
			'type'              => 'option',
			'default'           => $field['default'],
			'sanitize_callback' => 'sanitize_textarea_field',
		) );
		$wp_customize->add_control( $id, array( 'label' => $field['label'], 'section' => 'dahim_contact', 'type' => ( $id === 'dahim_address' ? 'textarea' : 'text' ) ) );
	}

	// --- Outgoing email sender — controls the "From" name/address on every
	// automated email (shipment created/updated/cancelled, contact form
	// notifications). Defaults to a shipments@ address on this domain, but
	// this is exactly the setting to change to info@yourdomain.com instead,
	// or anything else — no code change needed either way. ---
	$default_from_host = preg_replace( '#^www\.#', '', (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	$wp_customize->add_setting( 'dahim_email_from_name', array(
		'type'              => 'option',
		'default'           => get_bloginfo( 'name' ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'dahim_email_from_name', array(
		'label'   => 'Email "From" Name',
		'section' => 'dahim_contact',
	) );
	$wp_customize->add_setting( 'dahim_email_from_address', array(
		'type'              => 'option',
		'default'           => 'shipments@' . $default_from_host,
		'sanitize_callback' => 'sanitize_email',
	) );
	$wp_customize->add_control( 'dahim_email_from_address', array(
		'label'       => 'Email "From" Address',
		'description' => 'Shown as the sender on every automated email. Change to info@yourdomain.com if you\'d rather route everything through one shared inbox.',
		'section'     => 'dahim_contact',
	) );
}
add_action( 'customize_register', 'dahim_plugin_customize_register' );

/* ---------------------------------------------------------
 * 2. CUSTOM POST TYPES
 * ------------------------------------------------------- */

/* Lets the client switch optional content types on/off from wp-admin
 * (Settings → Dahim Features) — e.g. if Jobs aren't being used this
 * quarter, disabling it removes it from the admin menu entirely rather
 * than leaving an empty, confusing section around. Shipments, Inquiries,
 * and Departments are deliberately NOT toggleable here — the rest of the
 * system (the contact form, the tracking page) depends on them existing,
 * so switching them off would break other features rather than just
 * hiding a section. */
function dahim_toggleable_features() {
	return array(
		'service' => 'Services',
		'team'    => 'Team Members',
		'faq'     => 'FAQs',
		'lane'    => 'Trade Lanes',
		'job'     => 'Jobs / Careers',
	);
}
function dahim_feature_enabled( $key ) {
	return get_option( 'dahim_feature_' . $key, '1' ) === '1';
}

function dahim_register_features_settings_page() {
	add_options_page( 'Dahim Features', 'Dahim Features', 'manage_options', 'dahim-features', 'dahim_features_settings_page_html' );
}
add_action( 'admin_menu', 'dahim_register_features_settings_page' );

function dahim_features_settings_page_html() {
	if ( ! current_user_can( 'manage_options' ) ) return;

	if ( isset( $_POST['dahim_features_nonce'] ) && wp_verify_nonce( $_POST['dahim_features_nonce'], 'dahim_save_features' ) ) {
		foreach ( dahim_toggleable_features() as $key => $label ) {
			update_option( 'dahim_feature_' . $key, isset( $_POST[ 'dahim_feature_' . $key ] ) ? '1' : '0' );
		}
		// Post types with public URLs (Jobs) need their rewrite rules
		// regenerated whenever one gets toggled on/off, or the URL
		// structure goes stale until the next unrelated flush.
		flush_rewrite_rules();
		echo '<div class="notice notice-success is-dismissible"><p>Saved.</p></div>';
	}
	?>
	<div class="wrap">
		<h1>Dahim Features</h1>
		<p>Turn optional content types on or off. Turning one off removes it from the admin menu — nothing already saved is deleted, so switching it back on brings everything back exactly as it was.</p>
		<form method="post">
			<?php wp_nonce_field( 'dahim_save_features', 'dahim_features_nonce' ); ?>
			<table class="form-table">
				<?php foreach ( dahim_toggleable_features() as $key => $label ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( $label ); ?></th>
						<td><label><input type="checkbox" name="dahim_feature_<?php echo esc_attr( $key ); ?>" value="1" <?php checked( dahim_feature_enabled( $key ) ); ?>> Enabled</label></td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button( 'Save Changes' ); ?>
		</form>
	</div>
	<?php
}

// --- Services (What We Do) ---
function dahim_register_service_cpt() {
	if ( ! dahim_feature_enabled( 'service' ) ) return;
	register_post_type( 'service', array(
		'labels' => array(
			'name'          => 'Services',
			'singular_name' => 'Service',
			'add_new_item'  => 'Add New Service',
			'edit_item'     => 'Edit Service',
			'menu_name'     => 'Services',
		),
		'public'       => true,
		'has_archive'  => false,
		'menu_icon'    => 'dashicons-truck',
		'supports'     => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
		'rewrite'      => array( 'slug' => 'services' ),
		'show_in_rest' => true,
	) );
}
add_action( 'init', 'dahim_register_service_cpt' );

// --- Team Members ---
function dahim_register_team_cpt() {
	if ( ! dahim_feature_enabled( 'team' ) ) return;
	register_post_type( 'team_member', array(
		'labels' => array(
			'name'          => 'Team Members',
			'singular_name' => 'Team Member',
			'add_new_item'  => 'Add New Team Member',
			'edit_item'     => 'Edit Team Member',
			'menu_name'     => 'Our Team',
		),
		'public'       => true,
		'has_archive'  => false,
		'menu_icon'    => 'dashicons-groups',
		'supports'     => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
		'rewrite'      => array( 'slug' => 'team' ),
		'show_in_rest' => true,
	) );
}
add_action( 'init', 'dahim_register_team_cpt' );

// --- FAQs ---
function dahim_register_faq_cpt() {
	if ( ! dahim_feature_enabled( 'faq' ) ) return;
	register_post_type( 'faq', array(
		'labels' => array(
			'name'          => 'FAQs',
			'singular_name' => 'FAQ',
			'add_new_item'  => 'Add New FAQ',
			'edit_item'     => 'Edit FAQ',
			'menu_name'     => 'FAQs',
		),
		'public'       => true,
		'has_archive'  => false,
		'menu_icon'    => 'dashicons-editor-help',
		'supports'     => array( 'title', 'editor', 'page-attributes' ),
		'rewrite'      => array( 'slug' => 'faq' ),
		'show_in_rest' => true,
	) );
}
add_action( 'init', 'dahim_register_faq_cpt' );

// --- FAQ: which section of the site this question belongs to ---
function dahim_faq_meta_box() {
	add_meta_box( 'dahim_faq_fields', 'FAQ Settings', 'dahim_faq_fields_html', 'faq', 'side', 'default' );
}
add_action( 'add_meta_boxes', 'dahim_faq_meta_box' );

function dahim_faq_groups() {
	return array(
		'general'  => 'General (Homepage)',
		'tracking' => 'Tracking (Track a Shipment page)',
	);
}

function dahim_faq_fields_html( $post ) {
	wp_nonce_field( 'dahim_save_faq_fields', 'dahim_faq_fields_nonce' );
	$group = get_post_meta( $post->ID, '_dahim_faq_group', true ) ?: 'general';
	?>
	<p><label><strong>Shows On</strong><br>
		<select name="dahim_faq_group" style="width:100%;">
			<?php foreach ( dahim_faq_groups() as $val => $label ) : ?>
				<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $group, $val ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
	</label></p>
	<p style="color:#646970;">Use the Order field (below the main editor) to control where this question sits within its section.</p>
	<?php
}

function dahim_save_faq_fields( $post_id ) {
	if ( ! isset( $_POST['dahim_faq_fields_nonce'] ) || ! wp_verify_nonce( $_POST['dahim_faq_fields_nonce'], 'dahim_save_faq_fields' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( isset( $_POST['dahim_faq_group'] ) && array_key_exists( $_POST['dahim_faq_group'], dahim_faq_groups() ) ) {
		update_post_meta( $post_id, '_dahim_faq_group', sanitize_text_field( $_POST['dahim_faq_group'] ) );
	}
}
add_action( 'save_post_faq', 'dahim_save_faq_fields' );

// One-time seed so both FAQ sections have real, manageable content the
// moment this update installs, instead of starting empty or (for the
// Tracking section) staying hardcoded and unmanageable.
function dahim_seed_default_faqs() {
	if ( get_option( 'dahim_faqs_seeded' ) ) return;
	update_option( 'dahim_faqs_seeded', 1 );

	// Never overwrite real content — only seed if this group is genuinely empty.
	$existing = get_posts( array( 'post_type' => 'faq', 'posts_per_page' => 1, 'fields' => 'ids' ) );
	if ( ! empty( $existing ) ) return;

	$general = array(
		array( 'What documents do I need for customs clearance in Nigeria?', "Typical requirements include a Bill of Lading or Airway Bill, commercial invoice, packing list, Form M, and SONCAP or NAFDAC certificates where applicable. Our team reviews your documentation before your cargo arrives so clearance isn't delayed at the port." ),
		array( 'How long does clearing take at Apapa or Tin Can port?', 'Clearing times vary with cargo type and documentation readiness, but with complete paperwork submitted in advance, most shipments clear within a few working days of vessel arrival.' ),
		array( 'Do you handle both import and export shipments?', 'Yes. We manage both inbound and outbound freight, including customs documentation, port coordination, and inland transportation on either side of the shipment.' ),
		array( "Can I track my shipment while it's in transit?", 'Yes. Use the Track a Shipment page with your waybill or container number, or reach our ops desk on WhatsApp for a direct status update.' ),
		array( 'Which locations in Nigeria do you deliver to?', 'We move cargo nationwide from Lagos ports to destinations including Kano, Port Harcourt, Abuja, and other major commercial centres, as well as select cross-border routes into West Africa.' ),
	);
	$tracking = array(
		array( 'What Is a Tracking Number & Where Can I Find It?', "Your tracking number is a unique code we assign to your shipment the moment it's booked, in the format DGL-XXXX-XXXX. It's included in your booking confirmation email and on your shipping documents — search your inbox for \"Dahim\" if you can't locate it." ),
		array( 'When will my tracking information appear?', 'Tracking information is available as soon as your shipment is booked and confirmed by our ops team, usually within the same working day. The status updates as your cargo moves through each stage — booked, cleared/picked up, in transit, out for delivery, and delivered.' ),
		array( 'Why is my tracking number/ID not working?', "Double-check for extra spaces or mistaken characters — our numbers avoid ambiguous letters like O and I to make this easier. If it still doesn't work, your shipment may not be booked into our system yet, or there may be a typo in the confirmation email. Reach out on WhatsApp and we'll look it up directly." ),
		array( 'If I do not have my tracking number, is it still possible to track my shipment?', "Yes. Message our ops desk on WhatsApp with your name, company, and shipment details (origin, destination, or booking date), and we'll locate it for you directly." ),
	);

	foreach ( array( 'general' => $general, 'tracking' => $tracking ) as $group => $items ) {
		foreach ( $items as $i => $item ) {
			$post_id = wp_insert_post( array(
				'post_type'    => 'faq',
				'post_status'  => 'publish',
				'post_title'   => $item[0],
				'post_content' => $item[1],
				'menu_order'   => $i,
			) );
			if ( ! is_wp_error( $post_id ) && $post_id ) {
				update_post_meta( $post_id, '_dahim_faq_group', $group );
			}
		}
	}
}
add_action( 'init', 'dahim_seed_default_faqs', 25 );

// Same idea for Services — seeds the six services the site shipped with
// (including their photos) if none exist yet, so the site never has to
// silently fall back to hardcoded content the client can't manage.
function dahim_seed_default_services() {
	if ( get_option( 'dahim_services_seeded' ) ) return;
	update_option( 'dahim_services_seeded', 1 );

	$existing = get_posts( array( 'post_type' => 'service', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids' ) );
	if ( ! empty( $existing ) ) return;

	$defaults = array(
		array( 'title' => 'Freight Forwarding', 'text' => 'Land, air, and sea cargo coordination, liaising with shipping lines and inland carriers on your behalf.', 'img' => 'freight.webp' ),
		array( 'title' => 'Customs Documentation & Clearance', 'text' => 'Tariff classification, port documentation, and duty processing in full compliance with Nigeria Customs Service.', 'img' => 'dahim-home-about.webp' ),
		array( 'title' => 'Haulage & Inland Transport', 'text' => 'Container haulage from Apapa, Tin Can, and Onne ports to warehouses and distribution points nationwide.', 'img' => 'home-haulage.webp' ),
		array( 'title' => 'Warehousing & Distribution', 'text' => 'Short and long-term storage with cross-docking and nationwide distribution support.', 'img' => 'warehousing.webp' ),
		array( 'title' => 'Procurement & General Supply', 'text' => 'Sourcing and supply of industrial materials and equipment through trusted supplier networks.', 'img' => 'warehousing.webp' ),
		array( 'title' => 'Import & Export Logistics', 'text' => 'Managing shipments from origin to final delivery with accurate coordination and documentation.', 'img' => 'freight.webp' ),
	);

	foreach ( $defaults as $i => $d ) {
		$post_id = wp_insert_post( array(
			'post_type'    => 'service',
			'post_status'  => 'publish',
			'post_title'   => $d['title'],
			'post_content' => $d['text'],
			'post_excerpt' => $d['text'],
			'menu_order'   => $i,
		) );
		if ( is_wp_error( $post_id ) || ! $post_id ) continue;

		$source_path = get_template_directory() . '/assets/images/' . $d['img'];
		if ( ! file_exists( $source_path ) ) continue;

		$upload_dir = wp_upload_dir();
		$filename   = wp_unique_filename( $upload_dir['path'], $d['img'] );
		$dest_path  = $upload_dir['path'] . '/' . $filename;
		if ( ! copy( $source_path, $dest_path ) ) continue;

		$filetype      = wp_check_filetype( $filename, null );
		$attachment_id = wp_insert_attachment( array(
			'post_mime_type' => $filetype['type'],
			'post_title'     => sanitize_file_name( $filename ),
			'post_status'    => 'inherit',
		), $dest_path, $post_id );
		if ( is_wp_error( $attachment_id ) ) continue;

		require_once ABSPATH . 'wp-admin/includes/image.php';
		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $dest_path ) );
		set_post_thumbnail( $post_id, $attachment_id );
	}
}
add_action( 'init', 'dahim_seed_default_services', 25 );

// --- Trade Lanes (Key Routes) ---
function dahim_register_lane_cpt() {
	if ( ! dahim_feature_enabled( 'lane' ) ) return;
	register_post_type( 'trade_lane', array(
		'labels' => array(
			'name'          => 'Trade Lanes',
			'singular_name' => 'Trade Lane',
			'add_new_item'  => 'Add New Trade Lane',
			'edit_item'     => 'Edit Trade Lane',
			'menu_name'     => 'Trade Lanes',
		),
		'public'       => true,
		'has_archive'  => false,
		'menu_icon'    => 'dashicons-location-alt',
		'supports'     => array( 'title', 'page-attributes' ),
		'rewrite'      => array( 'slug' => 'trade-lanes' ),
		'show_in_rest' => true,
		'rest_base'    => 'trade-lanes',
	) );
}
add_action( 'init', 'dahim_register_lane_cpt' );

// --- Shipments (for the Track a Shipment lookup) ---
function dahim_register_shipment_cpt() {
	register_post_type( 'shipment', array(
		'labels' => array(
			'name'          => 'Shipments',
			'singular_name' => 'Shipment',
			'add_new_item'  => 'Add New Shipment',
			'edit_item'     => 'Edit Shipment',
			'menu_name'     => 'Shipments',
		),
		'public'       => false,
		'show_ui'      => true,
		'menu_icon'    => 'dashicons-airplane',
		'supports'     => array( 'title' ),
		'show_in_rest' => true,
		'rest_base'    => 'shipments',
	) );
}
add_action( 'init', 'dahim_register_shipment_cpt' );

// --- Departments — the "reach the right desk" cards on Contact Us, and
// the department dropdown on the contact form / Inquiries. Managed as real
// content so adding a 5th department is an admin action, not a code change. ---
function dahim_register_department_cpt() {
	register_post_type( 'department', array(
		'labels' => array(
			'name'          => 'Departments',
			'singular_name' => 'Department',
			'add_new_item'  => 'Add New Department',
			'edit_item'     => 'Edit Department',
			'menu_name'     => 'Departments',
		),
		'public'       => false,
		'show_ui'      => true,
		'menu_icon'    => 'dashicons-networking',
		'supports'     => array( 'title', 'page-attributes' ),
		'show_in_rest' => true,
		'rest_base'    => 'departments',
	) );
}
add_action( 'init', 'dahim_register_department_cpt' );

// --- Jobs — real postings for the Careers desk, listed on the public
// Careers page and linked into the Careers contact form. ---
function dahim_register_job_cpt() {
	if ( ! dahim_feature_enabled( 'job' ) ) return;
	register_post_type( 'job', array(
		'labels' => array(
			'name'          => 'Jobs',
			'singular_name' => 'Job',
			'add_new_item'  => 'Add New Job',
			'edit_item'     => 'Edit Job',
			'menu_name'     => 'Jobs',
		),
		'public'       => true,
		'has_archive'  => false,
		'menu_icon'    => 'dashicons-id-alt',
		'supports'     => array( 'title', 'editor', 'page-attributes' ),
		'rewrite'      => array( 'slug' => 'careers' ),
		'show_in_rest' => true,
		'rest_base'    => 'jobs',
	) );
}
add_action( 'init', 'dahim_register_job_cpt' );

// Publicly viewable Job pages need their rewrite rules actually generated —
// WordPress only does that when the rule set is flushed (normally triggered
// by visiting Settings → Permalinks and saving). Since this CPT just
// changed from admin-only to public, force that flush once automatically
// so job pages work immediately after this update installs, without
// needing a manual trip to wp-admin.
function dahim_maybe_flush_rewrite_rules() {
	if ( get_option( 'dahim_rewrite_flushed_v2' ) ) return;
	flush_rewrite_rules();
	update_option( 'dahim_rewrite_flushed_v2', 1 );
}
add_action( 'init', 'dahim_maybe_flush_rewrite_rules', 20 );

/* Single source of truth for shipment stage labels — used by the admin
 * list, the meta box, the tracking lookup, and both notification emails. */
function dahim_shipment_stage_labels() {
	return array(
		1 => 'Booked',
		2 => 'Cleared at Port / Picked Up',
		3 => 'In Transit',
		4 => 'Out for Delivery',
		5 => 'Delivered',
	);
}
function dahim_shipment_stage_label( $stage ) {
	$labels = dahim_shipment_stage_labels();
	$stage  = (int) $stage;
	return isset( $labels[ $stage ] ) ? $labels[ $stage ] : 'Booked';
}

// --- Shipments admin list: tracking number, route, status, owner at a glance ---
function dahim_shipment_admin_columns( $columns ) {
	$columns = array(
		'cb'          => $columns['cb'],
		'title'       => 'Shipment',
		'tracking'    => 'Tracking No.',
		'route'       => 'Route',
		'owner'       => 'Owner',
		'stage'       => 'Status',
		'date'        => $columns['date'],
	);
	return $columns;
}
add_filter( 'manage_shipment_posts_columns', 'dahim_shipment_admin_columns' );

function dahim_shipment_admin_column_content( $column, $post_id ) {
	switch ( $column ) {
		case 'tracking':
			$tracking = get_post_meta( $post_id, '_dahim_tracking_number', true );
			echo $tracking ? '<code>' . esc_html( $tracking ) . '</code>' : '—';
			break;
		case 'route':
			$origin = get_post_meta( $post_id, '_dahim_ship_origin', true );
			$dest   = get_post_meta( $post_id, '_dahim_ship_destination', true );
			echo esc_html( $origin ) . ' → ' . esc_html( $dest );
			break;
		case 'owner':
			$name  = get_post_meta( $post_id, '_dahim_ship_owner_name', true );
			$email = get_post_meta( $post_id, '_dahim_ship_owner_email', true );
			echo esc_html( $name );
			if ( $email ) echo '<br><small>' . esc_html( $email ) . '</small>';
			break;
		case 'stage':
			$stage  = (int) get_post_meta( $post_id, '_dahim_ship_stage', true );
			echo esc_html( dahim_shipment_stage_label( $stage ) );
			break;
	}
}
add_action( 'manage_shipment_posts_custom_column', 'dahim_shipment_admin_column_content', 10, 2 );

// --- Services admin list: summary at a glance (no featured-image column) ---
function dahim_service_admin_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( $key === 'title' ) $new['dahim_excerpt'] = 'Summary';
	}
	return $new;
}
add_filter( 'manage_service_posts_columns', 'dahim_service_admin_columns' );
function dahim_service_admin_column_content( $column, $post_id ) {
	if ( $column === 'dahim_excerpt' ) {
		echo esc_html( wp_trim_words( get_the_excerpt( $post_id ), 14 ) );
	}
}
add_action( 'manage_service_posts_custom_column', 'dahim_service_admin_column_content', 10, 2 );

// --- Team Members admin list: photo + role at a glance ---
function dahim_team_admin_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		if ( $key === 'title' ) $new['dahim_thumb'] = '';
		$new[ $key ] = $label;
		if ( $key === 'title' ) { $new['title'] = 'Name'; $new['dahim_role'] = 'Role / Title'; }
	}
	return $new;
}
add_filter( 'manage_team_member_posts_columns', 'dahim_team_admin_columns' );
function dahim_team_admin_column_content( $column, $post_id ) {
	if ( $column === 'dahim_thumb' ) {
		echo has_post_thumbnail( $post_id ) ? get_the_post_thumbnail( $post_id, array( 44, 44 ), array( 'style' => 'border-radius:50%;object-fit:cover;' ) ) : '—';
	} elseif ( $column === 'dahim_role' ) {
		echo esc_html( get_post_meta( $post_id, '_dahim_role', true ) ?: '—' );
	}
}
add_action( 'manage_team_member_posts_custom_column', 'dahim_team_admin_column_content', 10, 2 );

// --- FAQs admin list: answer preview so the question (title) has context ---
function dahim_faq_admin_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		if ( $key === 'title' ) $label = 'Question';
		$new[ $key ] = $label;
		if ( $key === 'title' ) {
			$new['dahim_faq_group'] = 'Shows On';
			$new['dahim_answer']    = 'Answer Preview';
		}
	}
	return $new;
}
add_filter( 'manage_faq_posts_columns', 'dahim_faq_admin_columns' );
function dahim_faq_admin_column_content( $column, $post_id ) {
	if ( $column === 'dahim_faq_group' ) {
		$groups = dahim_faq_groups();
		$group  = get_post_meta( $post_id, '_dahim_faq_group', true ) ?: 'general';
		echo esc_html( isset( $groups[ $group ] ) ? $groups[ $group ] : $groups['general'] );
	} elseif ( $column === 'dahim_answer' ) {
		echo esc_html( wp_trim_words( get_the_content( null, false, $post_id ), 16 ) );
	}
}
add_action( 'manage_faq_posts_custom_column', 'dahim_faq_admin_column_content', 10, 2 );

// --- Filter the FAQs list by which section it belongs to ---
function dahim_faq_group_filter() {
	global $typenow;
	if ( $typenow !== 'faq' ) return;
	$current = isset( $_GET['dahim_faq_group'] ) ? sanitize_text_field( $_GET['dahim_faq_group'] ) : '';
	echo '<select name="dahim_faq_group"><option value="">All Sections</option>';
	foreach ( dahim_faq_groups() as $val => $label ) {
		echo '<option value="' . esc_attr( $val ) . '"' . selected( $current, $val, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select>';
}
add_action( 'restrict_manage_posts', 'dahim_faq_group_filter' );

function dahim_faq_group_filter_query( $query ) {
	global $pagenow, $typenow;
	if ( ! is_admin() || $pagenow !== 'edit.php' || $typenow !== 'faq' || ! $query->is_main_query() ) return;
	if ( ! empty( $_GET['dahim_faq_group'] ) ) {
		$query->set( 'meta_key', '_dahim_faq_group' );
		$query->set( 'meta_value', sanitize_text_field( $_GET['dahim_faq_group'] ) );
	}
}
add_action( 'pre_get_posts', 'dahim_faq_group_filter_query' );

// --- Trade Lanes admin list: route, mode, transit time at a glance ---
function dahim_lane_admin_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		if ( $key === 'title' ) $label = 'Lane';
		$new[ $key ] = $label;
		if ( $key === 'title' ) {
			$new['dahim_route']   = 'Route';
			$new['dahim_mode']    = 'Mode';
			$new['dahim_transit'] = 'Transit Time';
		}
	}
	return $new;
}
add_filter( 'manage_trade_lane_posts_columns', 'dahim_lane_admin_columns' );
function dahim_lane_admin_column_content( $column, $post_id ) {
	switch ( $column ) {
		case 'dahim_route':
			$origin = get_post_meta( $post_id, '_dahim_lane_origin', true );
			$dest   = get_post_meta( $post_id, '_dahim_lane_destination', true );
			echo esc_html( $origin ) . ' → ' . esc_html( $dest );
			break;
		case 'dahim_mode':
			echo esc_html( get_post_meta( $post_id, '_dahim_lane_mode', true ) ?: '—' );
			break;
		case 'dahim_transit':
			echo esc_html( get_post_meta( $post_id, '_dahim_lane_transit', true ) ?: '—' );
			break;
	}
}
add_action( 'manage_trade_lane_posts_custom_column', 'dahim_lane_admin_column_content', 10, 2 );

// --- Departments admin list: description, link type at a glance ---
function dahim_department_admin_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		if ( $key === 'title' ) $label = 'Department';
		$new[ $key ] = $label;
		if ( $key === 'title' ) {
			$new['dahim_dept_desc'] = 'Description';
			$new['dahim_dept_type'] = 'Links To';
		}
	}
	return $new;
}
add_filter( 'manage_department_posts_columns', 'dahim_department_admin_columns' );
function dahim_department_admin_column_content( $column, $post_id ) {
	if ( $column === 'dahim_dept_desc' ) {
		echo esc_html( get_post_meta( $post_id, '_dahim_dept_description', true ) ?: '—' );
	} elseif ( $column === 'dahim_dept_type' ) {
		$external = get_post_meta( $post_id, '_dahim_dept_external_url', true );
		echo $external ? '<code>' . esc_html( $external ) . '</code>' : 'Contact form';
	}
}
add_action( 'manage_department_posts_custom_column', 'dahim_department_admin_column_content', 10, 2 );

// --- Jobs admin list: location, type, deadline, status at a glance ---
function dahim_job_admin_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		if ( $key === 'title' ) $label = 'Role';
		$new[ $key ] = $label;
		if ( $key === 'title' ) {
			$new['dahim_job_location'] = 'Location';
			$new['dahim_job_type']     = 'Type';
			$new['dahim_job_deadline'] = 'Deadline';
			$new['dahim_job_status']   = 'Status';
		}
	}
	return $new;
}
add_filter( 'manage_job_posts_columns', 'dahim_job_admin_columns' );
function dahim_job_admin_column_content( $column, $post_id ) {
	switch ( $column ) {
		case 'dahim_job_location':
			echo esc_html( get_post_meta( $post_id, '_dahim_job_location', true ) ?: '—' );
			break;
		case 'dahim_job_type':
			echo esc_html( get_post_meta( $post_id, '_dahim_job_type', true ) ?: '—' );
			break;
		case 'dahim_job_deadline':
			$deadline = get_post_meta( $post_id, '_dahim_job_deadline', true );
			echo $deadline ? esc_html( date_i18n( 'j M Y', strtotime( $deadline ) ) ) : '—';
			break;
		case 'dahim_job_status':
			$status = get_post_meta( $post_id, '_dahim_job_status', true ) ?: 'open';
			$color  = $status === 'open' ? '#008751' : '#8A8F98';
			echo '<span style="display:inline-block;padding:3px 10px;border-radius:999px;background:' . esc_attr( $color ) . '22;color:' . esc_attr( $color ) . ';font-weight:600;font-size:12px;text-transform:capitalize;">' . esc_html( $status ) . '</span>';
			break;
	}
}
add_action( 'manage_job_posts_custom_column', 'dahim_job_admin_column_content', 10, 2 );

/* ---------------------------------------------------------
 * TEAM MEMBER DATA MIGRATION
 *
 * The public About page contains three fallback team members so the
 * website can still display a team even when the team_member CPT is
 * empty. Those fallback records are presentation-only; they are not
 * WordPress posts, which is why wp-admin can show Team Members (0).
 *
 * This one-time migration creates real Team Member CPT records from
 * the same fallback data and copies their bundled theme images into
 * the Media Library as featured images. It only runs when there are
 * currently no Team Member posts, so it will not duplicate existing
 * records.
 * ------------------------------------------------------- */
function dahim_seed_team_members_from_theme_fallback() {
	if ( get_option( 'dahim_team_fallback_migrated', false ) ) return;
	if ( ! post_type_exists( 'team_member' ) ) return;

	$existing = get_posts( array(
		'post_type'      => 'team_member',
		'post_status'    => 'any',
		'posts_per_page' => 1,
		'fields'         => 'ids',
	) );

	if ( ! empty( $existing ) ) {
		update_option( 'dahim_team_fallback_migrated', 1, false );
		return;
	}

	$fallback_team = array(
		array(
			'img'  => 'dahim-director.webp',
			'role' => 'Managing Director / Founder',
			'name' => 'Ajide Ibrahim Eniobanfe, M.Sc.',
			'bio'  => "Holds a Master's degree in Maritime Administration and Management with over 14 years of experience in logistics and maritime operations.",
		),
		array(
			'img'  => 'abdulsalam-dahim-shipping-manager.webp',
			'role' => 'Shipping Manager',
			'name' => 'Abdulsalam Abdulmajeed Damilola',
			'bio'  => 'Coordinates cargo shipping operations and documentation to ensure smooth movement of goods through ports and international freight channels.',
		),
		array(
			'img'  => 'taiwo-dahim-transport-manager.webp',
			'role' => 'Transport Manager',
			'name' => 'Taiwo Abdulhakeem Akorede',
			'bio'  => 'Oversees haulage and transportation operations, ensuring efficient and timely delivery of cargo to its final destination.',
		),
	);

	$theme_dir = function_exists( 'get_template_directory' ) ? get_template_directory() : '';
	$created_any = false;

	foreach ( $fallback_team as $member ) {
		$post_id = wp_insert_post( array(
			'post_type'    => 'team_member',
			'post_status'  => 'publish',
			'post_title'   => $member['name'],
			'post_content' => $member['bio'],
			'menu_order'   => 0,
		), true );

		if ( is_wp_error( $post_id ) || ! $post_id ) continue;
		update_post_meta( $post_id, '_dahim_role', $member['role'] );
		$created_any = true;

		// Copy the bundled theme image into the Media Library and set it as the featured image.
		$source = $theme_dir ? trailingslashit( $theme_dir ) . 'assets/images/' . $member['img'] : '';
		if ( $source && file_exists( $source ) && is_readable( $source ) ) {
			$uploads = wp_upload_dir();
			if ( ! empty( $uploads['path'] ) && wp_mkdir_p( $uploads['path'] ) ) {
				$filename = wp_unique_filename( $uploads['path'], basename( $source ) );
				$destination = trailingslashit( $uploads['path'] ) . $filename;
				if ( copy( $source, $destination ) ) {
					$filetype = wp_check_filetype( $filename, null );
					$attachment = array(
						'post_mime_type' => $filetype['type'] ?: 'image/webp',
						'post_title'     => sanitize_text_field( pathinfo( $filename, PATHINFO_FILENAME ) ),
						'post_content'   => '',
						'post_status'    => 'inherit',
					);
					$attachment_id = wp_insert_attachment( $attachment, $destination, $post_id );
					if ( ! is_wp_error( $attachment_id ) && $attachment_id ) {
						require_once ABSPATH . 'wp-admin/includes/image.php';
						$metadata = wp_generate_attachment_metadata( $attachment_id, $destination );
						if ( $metadata ) wp_update_attachment_metadata( $attachment_id, $metadata );
						set_post_thumbnail( $post_id, $attachment_id );
					}
				}
			}
		}
	}

	if ( $created_any || empty( $existing ) ) update_option( 'dahim_team_fallback_migrated', 1, false );
}
add_action( 'admin_init', 'dahim_seed_team_members_from_theme_fallback', 20 );

/* ---------------------------------------------------------
 * 3. META BOXES (native, no ACF)
 * ------------------------------------------------------- */

// --- Team Member: Role/Title field ---
function dahim_team_meta_box() {
	add_meta_box( 'dahim_team_role', 'Role / Title', 'dahim_team_role_html', 'team_member', 'side', 'high' );
}
add_action( 'add_meta_boxes', 'dahim_team_meta_box' );

function dahim_team_role_html( $post ) {
	wp_nonce_field( 'dahim_save_team_role', 'dahim_team_role_nonce' );
	$role = get_post_meta( $post->ID, '_dahim_role', true );
	echo '<label for="dahim_role" style="display:block;margin-bottom:6px;font-weight:600;">e.g. Shipping Manager</label>';
	echo '<input type="text" id="dahim_role" name="dahim_role" value="' . esc_attr( $role ) . '" style="width:100%;" />';
}

function dahim_save_team_role( $post_id ) {
	if ( ! isset( $_POST['dahim_team_role_nonce'] ) || ! wp_verify_nonce( $_POST['dahim_team_role_nonce'], 'dahim_save_team_role' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( isset( $_POST['dahim_role'] ) ) {
		update_post_meta( $post_id, '_dahim_role', sanitize_text_field( $_POST['dahim_role'] ) );
	}
}
add_action( 'save_post_team_member', 'dahim_save_team_role' );

// --- Trade Lane: Origin / Destination / Mode / Transit Time ---
function dahim_lane_meta_box() {
	add_meta_box( 'dahim_lane_fields', 'Route Details', 'dahim_lane_fields_html', 'trade_lane', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'dahim_lane_meta_box' );

function dahim_lane_fields_html( $post ) {
	wp_nonce_field( 'dahim_save_lane_fields', 'dahim_lane_fields_nonce' );
	$origin      = get_post_meta( $post->ID, '_dahim_lane_origin', true );
	$destination = get_post_meta( $post->ID, '_dahim_lane_destination', true );
	$mode        = get_post_meta( $post->ID, '_dahim_lane_mode', true );
	$transit     = get_post_meta( $post->ID, '_dahim_lane_transit', true );
	?>
	<p><label><strong>Origin</strong><br><input type="text" name="dahim_lane_origin" value="<?php echo esc_attr( $origin ); ?>" style="width:100%;" placeholder="e.g. China" /></label></p>
	<p><label><strong>Destination</strong><br><input type="text" name="dahim_lane_destination" value="<?php echo esc_attr( $destination ); ?>" style="width:100%;" placeholder="e.g. Apapa Port, Lagos" /></label></p>
	<p><label><strong>Mode</strong><br><input type="text" name="dahim_lane_mode" value="<?php echo esc_attr( $mode ); ?>" style="width:100%;" placeholder="e.g. Ocean FCL/LCL" /></label></p>
	<p><label><strong>Transit Time</strong><br><input type="text" name="dahim_lane_transit" value="<?php echo esc_attr( $transit ); ?>" style="width:100%;" placeholder="e.g. 28–35 days" /></label></p>
	<?php
}

function dahim_save_lane_fields( $post_id ) {
	if ( ! isset( $_POST['dahim_lane_fields_nonce'] ) || ! wp_verify_nonce( $_POST['dahim_lane_fields_nonce'], 'dahim_save_lane_fields' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	foreach ( array( 'dahim_lane_origin', 'dahim_lane_destination', 'dahim_lane_mode', 'dahim_lane_transit' ) as $field ) {
		if ( isset( $_POST[ $field ] ) ) {
			update_post_meta( $post_id, '_' . $field, sanitize_text_field( $_POST[ $field ] ) );
		}
	}
}
add_action( 'save_post_trade_lane', 'dahim_save_lane_fields' );

// --- Shared icon library — used by Department cards. Add a key/SVG pair
// here and it becomes available in the icon picker automatically. ---
function dahim_icon_library() {
	return array(
		'briefcase' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
		'handshake' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
		'target'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>',
		'chat'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>',
		'search'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
		'truck'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 21c1.5-1.2 3-1.2 4.5 0s3 1.2 4.5 0 3-1.2 4.5 0 3 1.2 4.5 0"/><path d="M4 18l1-9h14l1 9"/><path d="M12 9V4h4l3 5"/></svg>',
		'globe'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
		'shield'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
	);
}

// --- Department: description, icon, CTA text, optional external link ---
function dahim_department_meta_box() {
	add_meta_box( 'dahim_department_fields', 'Department Details', 'dahim_department_fields_html', 'department', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'dahim_department_meta_box' );

function dahim_department_fields_html( $post ) {
	wp_nonce_field( 'dahim_save_department_fields', 'dahim_department_fields_nonce' );
	$get = function( $key ) use ( $post ) { return get_post_meta( $post->ID, '_dahim_dept_' . $key, true ); };
	$icon = $get( 'icon' ) ?: 'chat';
	?>
	<p><label><strong>Description</strong><br>
		<textarea name="dahim_dept_description" rows="2" style="width:100%;" placeholder="One line describing what this desk handles"><?php echo esc_textarea( $get( 'description' ) ); ?></textarea>
	</label></p>
	<p><label><strong>Icon</strong><br>
		<select name="dahim_dept_icon">
			<?php foreach ( array_keys( dahim_icon_library() ) as $key ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $icon, $key ); ?>><?php echo esc_html( ucfirst( $key ) ); ?></option>
			<?php endforeach; ?>
		</select>
	</label></p>
	<p><label><strong>Card Link Text</strong><br>
		<input type="text" name="dahim_dept_link_text" value="<?php echo esc_attr( $get( 'link_text' ) ?: 'Get in touch →' ); ?>" style="width:100%;" />
	</label></p>
	<p><label><strong>External Link (optional)</strong><br>
		<input type="text" name="dahim_dept_external_url" value="<?php echo esc_attr( $get( 'external_url' ) ); ?>" style="width:100%;" placeholder="e.g. /track/ — leave blank to route to the contact form instead" />
		<span style="color:#646970;">If set, this desk's card links straight here (e.g. Track a Shipment) instead of opening the contact form, and it won't appear as a choice in the Inquiries department dropdown.</span>
	</label></p>

	<hr style="margin:20px 0;">
	<p style="font-weight:600;">Contact form behavior when this department is selected</p>
	<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
		<p><label><strong>Form Eyebrow</strong><br>
			<input type="text" name="dahim_dept_eyebrow" value="<?php echo esc_attr( $get( 'eyebrow' ) ?: 'Get In Touch' ); ?>" style="width:100%;" />
		</label></p>
		<p><label><strong>Form Heading</strong><br>
			<input type="text" name="dahim_dept_heading" value="<?php echo esc_attr( $get( 'heading' ) ?: 'How can we help?' ); ?>" style="width:100%;" />
		</label></p>
		<p><label><strong>Message Field Label</strong><br>
			<input type="text" name="dahim_dept_message_label" value="<?php echo esc_attr( $get( 'message_label' ) ?: 'Your Message' ); ?>" style="width:100%;" />
		</label></p>
		<p><label><strong>Message Field Placeholder</strong><br>
			<input type="text" name="dahim_dept_message_placeholder" value="<?php echo esc_attr( $get( 'message_placeholder' ) ?: 'How can we help?' ); ?>" style="width:100%;" />
		</label></p>
		<p><label><strong>Submit Button Text</strong><br>
			<input type="text" name="dahim_dept_submit_label" value="<?php echo esc_attr( $get( 'submit_label' ) ?: 'Send Message' ); ?>" style="width:100%;" />
		</label></p>
	</div>
	<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:8px;">
		<p><label>
			<input type="checkbox" name="dahim_dept_show_company" value="1" <?php checked( $get( 'show_company' ) !== '0' ); ?> />
			Show the Company field
		</label></p>
		<p><label>
			<input type="checkbox" name="dahim_dept_show_service" value="1" <?php checked( $get( 'show_service' ) === '1' ); ?> />
			Show the Service Needed dropdown (for shipment/quote enquiries)
		</label></p>
		<p><label>
			<input type="checkbox" name="dahim_dept_show_role_cv" value="1" <?php checked( $get( 'show_role_cv' ) === '1' ); ?> />
			Show Role Applying For + CV/Portfolio Link fields (for job applications)
		</label></p>
	</div>

	<hr style="margin:20px 0;">
	<p style="font-weight:600;">Notifications</p>
	<p><label><strong>Staff Notification Email</strong><br>
		<input type="email" name="dahim_dept_notify_email" value="<?php echo esc_attr( $get( 'notify_email' ) ); ?>" style="width:100%;" placeholder="e.g. careers@dahimlogistics.com — leave blank to use the site-wide Primary Email" />
		<span style="color:#646970;">Where your team is notified when someone submits this department's form. Leave blank to fall back to Contact Info → Primary Email.</span>
	</label></p>

	<hr style="margin:20px 0;">
	<p style="font-weight:600;">Confirmation email sent to the customer</p>
	<p style="color:#646970;margin-top:-8px;">Sent automatically the moment someone submits this department's form. Customize the wording below — <code>{name}</code> is replaced with the customer's name.</p>
	<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
		<p><label><strong>Email Subject</strong><br>
			<input type="text" name="dahim_dept_confirm_subject" value="<?php echo esc_attr( $get( 'confirm_subject' ) ?: "We've received your message" ); ?>" style="width:100%;" />
		</label></p>
		<p><label><strong>Email Heading</strong><br>
			<input type="text" name="dahim_dept_confirm_heading" value="<?php echo esc_attr( $get( 'confirm_heading' ) ?: 'Message Received' ); ?>" style="width:100%;" />
		</label></p>
	</div>
	<p><label><strong>Email Message</strong><br>
		<textarea name="dahim_dept_confirm_message" rows="4" style="width:100%;"><?php echo esc_textarea( $get( 'confirm_message' ) ?: "Hi {name}, thanks for reaching out to us. We've received your message and someone from our team will get back to you within one business day." ); ?></textarea>
	</label></p>
	<?php
}

function dahim_save_department_fields( $post_id ) {
	if ( ! isset( $_POST['dahim_department_fields_nonce'] ) || ! wp_verify_nonce( $_POST['dahim_department_fields_nonce'], 'dahim_save_department_fields' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( isset( $_POST['dahim_dept_description'] ) ) update_post_meta( $post_id, '_dahim_dept_description', sanitize_textarea_field( $_POST['dahim_dept_description'] ) );
	if ( isset( $_POST['dahim_dept_icon'] ) ) update_post_meta( $post_id, '_dahim_dept_icon', sanitize_text_field( $_POST['dahim_dept_icon'] ) );
	if ( isset( $_POST['dahim_dept_link_text'] ) ) update_post_meta( $post_id, '_dahim_dept_link_text', sanitize_text_field( $_POST['dahim_dept_link_text'] ) );
	if ( isset( $_POST['dahim_dept_external_url'] ) ) update_post_meta( $post_id, '_dahim_dept_external_url', sanitize_text_field( $_POST['dahim_dept_external_url'] ) );
	if ( isset( $_POST['dahim_dept_eyebrow'] ) ) update_post_meta( $post_id, '_dahim_dept_eyebrow', sanitize_text_field( $_POST['dahim_dept_eyebrow'] ) );
	if ( isset( $_POST['dahim_dept_heading'] ) ) update_post_meta( $post_id, '_dahim_dept_heading', sanitize_text_field( $_POST['dahim_dept_heading'] ) );
	if ( isset( $_POST['dahim_dept_message_label'] ) ) update_post_meta( $post_id, '_dahim_dept_message_label', sanitize_text_field( $_POST['dahim_dept_message_label'] ) );
	if ( isset( $_POST['dahim_dept_message_placeholder'] ) ) update_post_meta( $post_id, '_dahim_dept_message_placeholder', sanitize_text_field( $_POST['dahim_dept_message_placeholder'] ) );
	if ( isset( $_POST['dahim_dept_submit_label'] ) ) update_post_meta( $post_id, '_dahim_dept_submit_label', sanitize_text_field( $_POST['dahim_dept_submit_label'] ) );
	if ( isset( $_POST['dahim_dept_notify_email'] ) ) update_post_meta( $post_id, '_dahim_dept_notify_email', sanitize_email( $_POST['dahim_dept_notify_email'] ) );
	if ( isset( $_POST['dahim_dept_confirm_subject'] ) ) update_post_meta( $post_id, '_dahim_dept_confirm_subject', sanitize_text_field( $_POST['dahim_dept_confirm_subject'] ) );
	if ( isset( $_POST['dahim_dept_confirm_heading'] ) ) update_post_meta( $post_id, '_dahim_dept_confirm_heading', sanitize_text_field( $_POST['dahim_dept_confirm_heading'] ) );
	if ( isset( $_POST['dahim_dept_confirm_message'] ) ) update_post_meta( $post_id, '_dahim_dept_confirm_message', sanitize_textarea_field( $_POST['dahim_dept_confirm_message'] ) );
	// Checkboxes don't submit at all when unchecked, so their absence must
	// explicitly write '0' — otherwise unchecking one would never save.
	update_post_meta( $post_id, '_dahim_dept_show_company', isset( $_POST['dahim_dept_show_company'] ) ? '1' : '0' );
	update_post_meta( $post_id, '_dahim_dept_show_service', isset( $_POST['dahim_dept_show_service'] ) ? '1' : '0' );
	update_post_meta( $post_id, '_dahim_dept_show_role_cv', isset( $_POST['dahim_dept_show_role_cv'] ) ? '1' : '0' );
}
add_action( 'save_post_department', 'dahim_save_department_fields' );

// --- Job: location, employment type, deadline, open/closed ---
function dahim_job_meta_box() {
	add_meta_box( 'dahim_job_fields', 'Job Details', 'dahim_job_fields_html', 'job', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'dahim_job_meta_box' );

function dahim_job_fields_html( $post ) {
	wp_nonce_field( 'dahim_save_job_fields', 'dahim_job_fields_nonce' );
	$get = function( $key ) use ( $post ) { return get_post_meta( $post->ID, '_dahim_job_' . $key, true ); };
	$type   = $get( 'type' ) ?: 'Full-time';
	$status = $get( 'status' ) ?: 'open';
	?>
	<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
		<p><label><strong>Location</strong><br>
			<input type="text" name="dahim_job_location" value="<?php echo esc_attr( $get( 'location' ) ); ?>" style="width:100%;" placeholder="e.g. Apapa, Lagos" />
		</label></p>
		<p><label><strong>Employment Type</strong><br>
			<select name="dahim_job_type" style="width:100%;">
				<?php foreach ( array( 'Full-time', 'Part-time', 'Contract', 'Internship' ) as $opt ) : ?>
					<option value="<?php echo esc_attr( $opt ); ?>" <?php selected( $type, $opt ); ?>><?php echo esc_html( $opt ); ?></option>
				<?php endforeach; ?>
			</select>
		</label></p>
		<p><label><strong>Application Deadline</strong><br>
			<input type="date" name="dahim_job_deadline" value="<?php echo esc_attr( $get( 'deadline' ) ); ?>" style="width:100%;" />
		</label></p>
		<p><label><strong>Status</strong><br>
			<select name="dahim_job_status" style="width:100%;">
				<option value="open" <?php selected( $status, 'open' ); ?>>Open — visible on the Careers page</option>
				<option value="closed" <?php selected( $status, 'closed' ); ?>>Closed — hidden from the Careers page</option>
			</select>
		</label></p>
	</div>
	<p style="color:#646970;">Write the full role description, responsibilities, and requirements in the main content editor below.</p>
	<?php
}

function dahim_save_job_fields( $post_id ) {
	if ( ! isset( $_POST['dahim_job_fields_nonce'] ) || ! wp_verify_nonce( $_POST['dahim_job_fields_nonce'], 'dahim_save_job_fields' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	foreach ( array( 'dahim_job_location', 'dahim_job_type', 'dahim_job_deadline', 'dahim_job_status' ) as $field ) {
		if ( isset( $_POST[ $field ] ) ) {
			update_post_meta( $post_id, '_' . $field, sanitize_text_field( $_POST[ $field ] ) );
		}
	}
}
add_action( 'save_post_job', 'dahim_save_job_fields' );

// --- Shipment: full logistics record ---
function dahim_shipment_meta_box() {
	add_meta_box( 'dahim_shipment_fields', 'Shipment Details', 'dahim_shipment_fields_html', 'shipment', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'dahim_shipment_meta_box' );

function dahim_shipment_field_group( $legend, $rows ) {
	echo '<fieldset style="border:1px solid #dcdcde;border-radius:4px;padding:14px 16px;margin-bottom:16px;">';
	echo '<legend style="font-weight:600;padding:0 6px;">' . esc_html( $legend ) . '</legend>';
	echo $rows;
	echo '</fieldset>';
}

function dahim_shipment_fields_html( $post ) {
	wp_nonce_field( 'dahim_save_shipment_fields', 'dahim_shipment_fields_nonce' );

	$get = function( $key ) use ( $post ) { return get_post_meta( $post->ID, '_dahim_ship_' . $key, true ); };
	$tracking = get_post_meta( $post->ID, '_dahim_tracking_number', true );
	$stage    = $get( 'stage' );
	if ( $stage === '' ) $stage = '1';

	echo '<style>.dahim-field{margin-bottom:12px;}.dahim-field label{display:block;font-weight:600;margin-bottom:4px;}.dahim-field input,.dahim-field select,.dahim-field textarea{width:100%;}.dahim-field-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;}</style>';

	// Tracking number — auto-generated, read-only after creation.
	echo '<div class="dahim-field"><label>Tracking Number</label>';
	if ( $tracking ) {
		echo '<input type="text" value="' . esc_attr( $tracking ) . '" readonly style="background:#f0f0f1;font-family:monospace;font-weight:700;letter-spacing:0.05em;" />';
		echo '<p style="color:#646970;margin-top:4px;">Auto-generated on first save. Cannot be edited.</p>';
	} else {
		echo '<input type="text" value="Will be generated automatically when you publish this shipment." readonly style="background:#f0f0f1;color:#646970;font-style:italic;" />';
	}
	echo '</div>';

	// Shipper / shipment owner
	dahim_shipment_field_group( 'Shipment Owner (Shipper)', '
		<div class="dahim-field-row">
			<div class="dahim-field"><label>Full Name *</label><input type="text" name="dahim_ship_owner_name" value="' . esc_attr( $get('owner_name') ) . '" /></div>
			<div class="dahim-field"><label>Phone Number *</label><input type="text" name="dahim_ship_owner_phone" value="' . esc_attr( $get('owner_phone') ) . '" placeholder="e.g. +234 803 123 4567" /></div>
		</div>
		<div class="dahim-field"><label>Email Address *</label><input type="email" name="dahim_ship_owner_email" value="' . esc_attr( $get('owner_email') ) . '" placeholder="Shipment update email will be sent here" /></div>
	' );

	// Consignee / receiver
	dahim_shipment_field_group( 'Consignee (Receiver)', '
		<div class="dahim-field-row">
			<div class="dahim-field"><label>Full Name</label><input type="text" name="dahim_ship_consignee_name" value="' . esc_attr( $get('consignee_name') ) . '" /></div>
			<div class="dahim-field"><label>Phone Number</label><input type="text" name="dahim_ship_consignee_phone" value="' . esc_attr( $get('consignee_phone') ) . '" /></div>
		</div>
	' );

	// Route
	dahim_shipment_field_group( 'Route', '
		<div class="dahim-field-row">
			<div class="dahim-field"><label>Origin *</label><input type="text" name="dahim_ship_origin" value="' . esc_attr( $get('origin') ) . '" /></div>
			<div class="dahim-field"><label>Destination *</label><input type="text" name="dahim_ship_destination" value="' . esc_attr( $get('destination') ) . '" /></div>
		</div>
		<div class="dahim-field"><label>Current Location</label><input type="text" name="dahim_ship_current_location" value="' . esc_attr( $get('current_location') ) . '" placeholder="e.g. Apapa Port, Lagos — used on the tracking page" /></div>
	' );

	// Cargo details
	dahim_shipment_field_group( 'Cargo Details', '
		<div class="dahim-field"><label>Package Description</label><textarea name="dahim_ship_package_description" rows="2">' . esc_textarea( $get('package_description') ) . '</textarea></div>
		<div class="dahim-field-row">
			<div class="dahim-field"><label>Weight (kg)</label><input type="text" name="dahim_ship_weight" value="' . esc_attr( $get('weight') ) . '" /></div>
			<div class="dahim-field"><label>Pieces / Quantity</label><input type="text" name="dahim_ship_pieces" value="' . esc_attr( $get('pieces') ) . '" /></div>
		</div>
		<div class="dahim-field-row">
			<div class="dahim-field"><label>Dimensions</label><input type="text" name="dahim_ship_dimensions" value="' . esc_attr( $get('dimensions') ) . '" placeholder="e.g. 120 x 80 x 100 cm" /></div>
			<div class="dahim-field"><label>Declared Value</label><input type="text" name="dahim_ship_declared_value" value="' . esc_attr( $get('declared_value') ) . '" placeholder="e.g. ₦450,000" /></div>
		</div>
	' );

	// Service & carrier
	$service_type = $get('service_type');
	$service_options = array( 'Sea Freight (FCL/LCL)', 'Air Freight', 'Road Haulage', 'Warehousing & Distribution', 'Express Courier' );
	$service_html = '<div class="dahim-field-row">';
	$service_html .= '<div class="dahim-field"><label>Service Type</label><select name="dahim_ship_service_type">';
	foreach ( $service_options as $opt ) {
		$service_html .= '<option value="' . esc_attr( $opt ) . '"' . selected( $service_type, $opt, false ) . '>' . esc_html( $opt ) . '</option>';
	}
	$service_html .= '</select></div>';
	$service_html .= '<div class="dahim-field"><label>Carrier</label><input type="text" name="dahim_ship_carrier" value="' . esc_attr( $get('carrier') ) . '" placeholder="e.g. vessel, airline, or trucking company" /></div>';
	$service_html .= '</div>';
	dahim_shipment_field_group( 'Service', $service_html );

	// Dates & status
	$stage_options = array();
	foreach ( dahim_shipment_stage_labels() as $val => $label ) {
		$stage_options[ (string) $val ] = $val . ' — ' . $label;
	}
	$stage_html = '<div class="dahim-field-row">';
	$stage_html .= '<div class="dahim-field"><label>Date Booked</label><input type="date" name="dahim_ship_date_booked" value="' . esc_attr( $get('date_booked') ?: gmdate('Y-m-d') ) . '" /></div>';
	$stage_html .= '<div class="dahim-field"><label>Estimated Delivery</label><input type="date" name="dahim_ship_estimated_delivery" value="' . esc_attr( $get('estimated_delivery') ) . '" /></div>';
	$stage_html .= '</div>';
	$stage_html .= '<div class="dahim-field"><label>Current Stage</label><select name="dahim_ship_stage">';
	foreach ( $stage_options as $val => $label ) {
		$stage_html .= '<option value="' . esc_attr( $val ) . '"' . selected( $stage, $val, false ) . '>' . esc_html( $label ) . '</option>';
	}
	$stage_html .= '</select></div>';
	$stage_html .= '<div class="dahim-field"><label>Special Instructions / Notes</label><textarea name="dahim_ship_special_instructions" rows="2">' . esc_textarea( $get('special_instructions') ) . '</textarea></div>';
	dahim_shipment_field_group( 'Status & Dates', $stage_html );
}

function dahim_save_shipment_fields( $post_id ) {
	if ( ! isset( $_POST['dahim_shipment_fields_nonce'] ) || ! wp_verify_nonce( $_POST['dahim_shipment_fields_nonce'], 'dahim_save_shipment_fields' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	$text_fields = array(
		'owner_name', 'owner_phone', 'consignee_name', 'consignee_phone',
		'origin', 'destination', 'current_location',
		'weight', 'pieces', 'dimensions', 'declared_value',
		'service_type', 'carrier', 'stage', 'date_booked', 'estimated_delivery',
	);
	foreach ( $text_fields as $field ) {
		if ( isset( $_POST[ 'dahim_ship_' . $field ] ) ) {
			update_post_meta( $post_id, '_dahim_ship_' . $field, sanitize_text_field( $_POST[ 'dahim_ship_' . $field ] ) );
		}
	}
	if ( isset( $_POST['dahim_ship_owner_email'] ) ) {
		update_post_meta( $post_id, '_dahim_ship_owner_email', sanitize_email( $_POST['dahim_ship_owner_email'] ) );
	}
	foreach ( array( 'package_description', 'special_instructions' ) as $field ) {
		if ( isset( $_POST[ 'dahim_ship_' . $field ] ) ) {
			update_post_meta( $post_id, '_dahim_ship_' . $field, sanitize_textarea_field( $_POST[ 'dahim_ship_' . $field ] ) );
		}
	}
}
add_action( 'save_post_shipment', 'dahim_save_shipment_fields' );

/* Tracking-number generation and customer notification — deliberately NOT
 * gated behind the classic-form nonce above, so this also fires correctly
 * when a shipment is created or updated through the REST API (the
 * dashboard app), not only through the wp-admin meta box form. */
function dahim_shipment_side_effects( $post_id, $post, $update ) {
	if ( $post->post_type !== 'shipment' ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;

	$stage_before = get_post_meta( $post_id, '_dahim_ship_stage_before_save', true );

	// Auto-generate the tracking number once, on first save — never overwritten afterwards.
	$existing_tracking = get_post_meta( $post_id, '_dahim_tracking_number', true );
	if ( empty( $existing_tracking ) ) {
		update_post_meta( $post_id, '_dahim_tracking_number', dahim_generate_tracking_number() );
	}

	$owner_email     = get_post_meta( $post_id, '_dahim_ship_owner_email', true );
	$already_sent    = get_post_meta( $post_id, '_dahim_ship_email_sent', true );
	$admin_notified  = get_post_meta( $post_id, '_dahim_ship_admin_notified', true );
	$stage_after     = get_post_meta( $post_id, '_dahim_ship_stage', true );

	if ( ! $already_sent ) {
		if ( is_email( $owner_email ) ) {
			$sent = dahim_send_shipment_email( $post_id );
			if ( $sent ) {
				update_post_meta( $post_id, '_dahim_ship_email_sent', current_time( 'mysql' ) );
			}
		}
	} elseif ( $stage_after !== $stage_before && $stage_before !== '' && is_email( $owner_email ) ) {
		dahim_send_shipment_status_update_email( $post_id, $stage_before, $stage_after );
	}

	// Notify staff of every new shipment, regardless of whether a customer
	// email was provided — this is about ops visibility, not the customer.
	// Gated on owner_name being present (not just "hasn't fired yet") for
	// the same reason the customer email above checks owner_email: this
	// function can run once before REST-submitted meta has actually been
	// saved yet (see the note below) — without this guard, that empty-data
	// pass would send a blank notification and mark it "sent," silently
	// preventing the real one from ever going out once the real data lands.
	$owner_name = get_post_meta( $post_id, '_dahim_ship_owner_name', true );
	if ( ! $admin_notified && $owner_name ) {
		dahim_send_shipment_admin_notification( $post_id );
		update_post_meta( $post_id, '_dahim_ship_admin_notified', current_time( 'mysql' ) );
	}

	update_post_meta( $post_id, '_dahim_ship_stage_before_save', $stage_after );
}
// Runs after WordPress has already saved post meta for the classic
// wp-admin save path and CSV import. REST-driven saves (the dashboard app)
// persist meta AFTER save_post fires, so this hook firing there too is
// harmless — it just no-ops (the owner email isn't saved yet) — and
// rest_after_insert_shipment below does the real work at the correct time.
add_action( 'save_post_shipment', 'dahim_shipment_side_effects', 20, 3 );

// rest_after_insert_shipment passes ($post, $request, $creating) — adapt
// to the same ($post_id, $post, $update) signature used above.
add_action( 'rest_after_insert_shipment', function ( $post, $request, $creating ) {
	dahim_shipment_side_effects( $post->ID, $post, ! $creating );
}, 20, 3 );

/* Generates a random, non-sequential tracking number that can't be
 * guessed by incrementing/decrementing a known one — format DGL-XXXX-XXXX
 * using an alphabet with ambiguous characters (0/O/1/I/L) removed, so it's
 * also easy for a customer to read back over the phone. Checked against
 * existing shipments to guarantee uniqueness. */
function dahim_generate_tracking_number() {
	$charset = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
	$max     = strlen( $charset ) - 1;
	$tries   = 0;

	do {
		$code = 'DGL-';
		for ( $i = 0; $i < 4; $i++ ) $code .= $charset[ random_int( 0, $max ) ];
		$code .= '-';
		for ( $i = 0; $i < 4; $i++ ) $code .= $charset[ random_int( 0, $max ) ];

		$clash = get_posts( array(
			'post_type'      => 'shipment',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_dahim_tracking_number',
			'meta_value'     => $code,
		) );
		$tries++;
	} while ( ! empty( $clash ) && $tries < 20 );

	return $code;
}

/* Finds the Page using the "Track a Shipment" template, so emails and
 * links always point at wherever the client has actually placed it. */
function dahim_get_tracking_page_url() {
	$pages = get_posts( array(
		'post_type'      => 'page',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_key'       => '_wp_page_template',
		'meta_value'     => 'page-track.php',
	) );
	return $pages ? get_permalink( $pages[0] ) : home_url( '/' );
}

// --- Outgoing mail sender — applies to every wp_mail() call site-wide,
// not just shipment emails, so the contact form's admin notification and
// anything else WordPress sends is branded consistently too. ---
function dahim_mail_from_address( $original_email ) {
	$configured = get_option( 'dahim_email_from_address', '' );
	return ( $configured && is_email( $configured ) ) ? $configured : $original_email;
}
add_filter( 'wp_mail_from', 'dahim_mail_from_address' );

function dahim_mail_from_name( $original_name ) {
	$configured = get_option( 'dahim_email_from_name', '' );
	return $configured ? $configured : get_bloginfo( 'name' );
}
add_filter( 'wp_mail_from_name', 'dahim_mail_from_name' );

/* ---------------------------------------------------------
 * BRANDED HTML EMAILS — a shared template matching the site's own
 * navy/amber identity (same mark as the header), used by every
 * customer-facing shipment email. Built with inline styles and a
 * table-based layout throughout, since that's what actually renders
 * reliably across email clients (Gmail, Outlook, Apple Mail) — modern
 * CSS like flexbox/grid is not safe to rely on in email. The brand mark
 * is rendered as styled text rather than a logo image on purpose: image
 * blocking is on by default in many email clients (recipients see a
 * broken-image icon until they click "show images"), so a text mark
 * that's the exact same navy/amber pairing as the website looks correct
 * from the very first render — no dependency on images loading at all. */
function dahim_email_wrap( $preheader, $body_html, $heading = '' ) {
	$site_name = get_bloginfo( 'name' );
	$site_url  = home_url( '/' );
	$host      = preg_replace( '#^https?://#', '', untrailingslashit( $site_url ) );

	ob_start();
	?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc_html( $site_name ); ?></title>
</head>
<body style="margin:0;padding:0;background-color:#F0EDE4;">
<?php
// Email clients (Gmail especially) show ~100-140 characters in the inbox
// preview. If our hidden preheader text is shorter than that, the client
// keeps reading past it into the visible body to fill the gap — which is
// exactly what makes the subject/preview look like it's duplicated with
// the email's own opening line. Padding with invisible characters (never
// rendered, but still "readable" by the client's preview scanner) closes
// that gap so nothing after it gets pulled in.
$preheader_padded = esc_html( $preheader ) . str_repeat( '&nbsp;&zwnj;', 80 );
?>
<div style="display:none;max-height:0;overflow:hidden;font-size:1px;line-height:1px;color:#F0EDE4;mso-hide:all;"><?php echo $preheader_padded; ?></div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F0EDE4;padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#FFFFFF;max-width:600px;width:100%;border-radius:10px;overflow:hidden;">
<tr>
<td style="background-color:#1E2A44;padding:26px 32px;">
<img src="<?php echo esc_url( plugins_url( 'assets/images/dahim-logo-email.png', __FILE__ ) ); ?>" width="175" height="50" alt="<?php echo esc_attr( $site_name ); ?>" style="display:block;border:0;color:#FFFFFF;font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:bold;">
</td>
</tr>
<tr><td style="background-color:#C79B3C;height:4px;line-height:4px;font-size:1px;">&nbsp;</td></tr>
<tr>
<td style="padding:32px;color:#1E2229;font-size:15px;line-height:1.7;font-family:Arial,Helvetica,sans-serif;">
<?php if ( $heading ) : ?>
<h1 style="margin:0 0 18px;font-size:20px;color:#1E2A44;font-family:Arial,Helvetica,sans-serif;"><?php echo esc_html( $heading ); ?></h1>
<?php endif; ?>
<?php echo $body_html; // phpcs:ignore -- built exclusively from our own esc_html()-escaped helpers below ?>
</td>
</tr>
<tr>
<td style="background-color:#1E2A44;padding:22px 32px;text-align:center;">
<div style="color:#F0EDE4;font-size:12px;font-family:Arial,Helvetica,sans-serif;line-height:1.6;">
<?php echo esc_html( $site_name ); ?><br>
<a href="<?php echo esc_url( $site_url ); ?>" style="color:#C79B3C;text-decoration:none;"><?php echo esc_html( $host ); ?></a>
</div>
</td>
</tr>
</table>
</td></tr>
</table>
</body>
</html>
	<?php
	return ob_get_clean();
}

// A centered amber CTA button, styled to match the site's own buttons.
function dahim_email_button( $url, $label ) {
	return '<div style="text-align:center;margin:26px 0;"><a href="' . esc_url( $url ) . '" style="display:inline-block;background-color:#C79B3C;color:#1E2A44;font-weight:bold;font-family:Arial,Helvetica,sans-serif;font-size:14px;text-decoration:none;padding:14px 28px;border-radius:8px;">' . esc_html( $label ) . '</a></div>';
}

// A highlighted box for the tracking number — the one detail a customer
// most needs to be able to spot at a glance.
function dahim_email_tracking_box( $tracking_number ) {
	return '<div style="background-color:#F0EDE4;border-radius:8px;padding:18px 20px;margin:20px 0;text-align:center;">'
		. '<div style="font-family:Arial,Helvetica,sans-serif;font-size:11px;letter-spacing:1px;color:#4C5A78;text-transform:uppercase;margin-bottom:6px;">Tracking Number</div>'
		. '<div style="font-family:Consolas,Monaco,monospace;font-size:20px;font-weight:bold;color:#1E2A44;letter-spacing:1px;">' . esc_html( $tracking_number ) . '</div>'
		. '</div>';
}

// One label/value line within an email body (route, status, current location, etc).
function dahim_email_detail_line( $label, $value ) {
	if ( ! $value ) return '';
	return '<p style="margin:0 0 8px;font-size:14px;"><strong style="color:#1E2A44;">' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '</p>';
}

/* Notifies staff the moment a new inquiry comes in — branded like every
 * other system email, with a "What They Sent" recap and a direct link
 * into wp-admin to act on it, instead of a bare plain-text dump. */
function dahim_send_inquiry_admin_notification( $to, $department, $inquiry_id, $data ) {
	if ( ! is_email( $to ) ) return false;

	$subject = "New {$department} Inquiry from " . ( $data['name'] ?: 'Website' );

	$body  = '<p style="margin:0 0 16px;">A new inquiry came in through the ' . esc_html( $department ) . ' desk.</p>';

	$recap  = dahim_email_detail_line( 'Name', $data['name'] );
	$recap .= dahim_email_detail_line( 'Company', $data['company'] );
	$recap .= dahim_email_detail_line( 'Phone', $data['phone'] );
	$recap .= dahim_email_detail_line( 'Email', $data['email'] );
	$recap .= dahim_email_detail_line( 'Service', $data['service'] );
	$recap .= dahim_email_detail_line( 'Role Applying For', $data['role'] );
	$recap .= dahim_email_detail_line( 'CV / Portfolio', $data['cv_link'] );
	$body  .= '<div style="background-color:#F0EDE4;border-radius:8px;padding:18px 20px;margin:20px 0;">' . $recap . '</div>';

	if ( $data['message'] ) {
		$body .= '<div style="font-family:Arial,Helvetica,sans-serif;font-size:11px;letter-spacing:1px;color:#4C5A78;text-transform:uppercase;margin-bottom:8px;">Message</div>';
		$body .= '<p style="margin:0 0 20px;white-space:pre-wrap;">' . esc_html( $data['message'] ) . '</p>';
	}

	if ( $inquiry_id ) {
		$body .= dahim_email_button( home_url( '/dashboard/inquiries/' . $inquiry_id ), 'View in Dashboard' );
	}

	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	if ( is_email( $data['email'] ) ) $headers[] = 'Reply-To: ' . $data['email'];

	$preheader = $data['message'] ? wp_trim_words( $data['message'], 18 ) : 'View the full details in wp-admin.';

	return wp_mail( $to, $subject, dahim_email_wrap( $preheader, $body, "New {$department} Inquiry" ), $headers );
}

/* Sends the customer a branded confirmation the moment they submit the
 * contact form — content ($dept_data['confirm_subject'/'confirm_heading'
 * /'confirm_message']) is fully editable per department from wp-admin
 * under Departments, not hardcoded here. */
function dahim_send_inquiry_confirmation_email( $to, $name, $dept_data, $extra ) {
	$display_name = $name ?: 'there';
	$subject      = str_replace( '{name}', $display_name, $dept_data['confirm_subject'] );
	$message      = str_replace( '{name}', $display_name, $dept_data['confirm_message'] );

	$body  = wpautop( esc_html( $message ) );
	$body  = str_replace( array( '<p>', '</p>' ), array( '<p style="margin:0 0 16px;">', '</p>' ), $body );

	$recap  = dahim_email_detail_line( 'Company', $extra['company'] );
	$recap .= dahim_email_detail_line( 'Phone', $extra['phone'] );
	$recap .= dahim_email_detail_line( 'Service', $extra['service'] );
	$recap .= dahim_email_detail_line( 'Role Applying For', $extra['role'] );
	$recap .= dahim_email_detail_line( 'CV / Portfolio', $extra['cv_link'] );
	$recap .= dahim_email_detail_line( 'Message', $extra['message'] );

	if ( $recap ) {
		$body .= '<div style="background-color:#F0EDE4;border-radius:8px;padding:18px 20px;margin:20px 0;">'
			. '<div style="font-family:Arial,Helvetica,sans-serif;font-size:11px;letter-spacing:1px;color:#4C5A78;text-transform:uppercase;margin-bottom:10px;">What You Sent Us</div>'
			. $recap
			. '</div>';
	}

	$headers = array( 'Content-Type: text/html; charset=UTF-8' );

	// The preheader is the snippet inbox lists show next to the subject —
	// it must say something the subject doesn't, or it just looks like the
	// subject got printed twice. Using the actual message text here instead.
	$preheader = wp_trim_words( $message, 18 );

	return wp_mail( $to, $subject, dahim_email_wrap( $preheader, $body, $dept_data['confirm_heading'] ), $headers );
}

/* Notifies staff (not the customer) the moment a new shipment is created —
 * whether it came from wp-admin, the dashboard app, or a CSV import. Fires
 * regardless of whether the customer has an email on file, since this is
 * about ops visibility into what's been booked, not customer comms. */
function dahim_send_shipment_admin_notification( $post_id ) {
	$to = get_option( 'dahim_email', get_option( 'admin_email' ) );
	if ( ! is_email( $to ) ) return false;

	$owner       = get_post_meta( $post_id, '_dahim_ship_owner_name', true );
	$owner_email = get_post_meta( $post_id, '_dahim_ship_owner_email', true );
	$owner_phone = get_post_meta( $post_id, '_dahim_ship_owner_phone', true );
	$tracking    = get_post_meta( $post_id, '_dahim_tracking_number', true );
	$origin      = get_post_meta( $post_id, '_dahim_ship_origin', true );
	$destination = get_post_meta( $post_id, '_dahim_ship_destination', true );
	$service     = get_post_meta( $post_id, '_dahim_ship_service_type', true );
	$carrier     = get_post_meta( $post_id, '_dahim_ship_carrier', true );

	$subject = "New Shipment Created — {$tracking}";

	$body  = '<p style="margin:0 0 16px;">A new shipment has been booked.</p>';
	$body .= dahim_email_tracking_box( $tracking );
	$body .= '<div style="background-color:#F0EDE4;border-radius:8px;padding:18px 20px;margin:20px 0;">';
	$body .= dahim_email_detail_line( 'Owner', $owner );
	$body .= dahim_email_detail_line( 'Email', $owner_email );
	$body .= dahim_email_detail_line( 'Phone', $owner_phone );
	$body .= dahim_email_detail_line( 'Route', trim( $origin . ' → ' . $destination, ' →' ) );
	$body .= dahim_email_detail_line( 'Service Type', $service );
	$body .= dahim_email_detail_line( 'Carrier', $carrier );
	$body .= '</div>';
	$body .= dahim_email_button( home_url( '/dashboard/shipments' ), 'View in Dashboard' );

	$headers = array( 'Content-Type: text/html; charset=UTF-8' );

	$preheader = trim( $origin . ' → ' . $destination, ' →' ) ?: 'A new shipment has just been booked.';

	return wp_mail( $to, $subject, dahim_email_wrap( $preheader, $body, 'New Shipment' ), $headers );
}

function dahim_send_shipment_email( $post_id ) {
	$to = get_post_meta( $post_id, '_dahim_ship_owner_email', true );
	if ( ! is_email( $to ) ) return false;

	$name        = get_post_meta( $post_id, '_dahim_ship_owner_name', true );
	$tracking    = get_post_meta( $post_id, '_dahim_tracking_number', true );
	$origin      = get_post_meta( $post_id, '_dahim_ship_origin', true );
	$destination = get_post_meta( $post_id, '_dahim_ship_destination', true );
	$track_url   = add_query_arg( 'tracking', rawurlencode( $tracking ), dahim_get_tracking_page_url() );
	$site_name   = get_bloginfo( 'name' );

	$subject = "Your shipment has been created — Tracking No. {$tracking}";

	$body  = '<p style="margin:0 0 16px;">Hi ' . esc_html( $name ?: 'there' ) . ',</p>';
	$body .= '<p style="margin:0 0 16px;">Your shipment with ' . esc_html( $site_name ) . ' has been created and is now being processed.</p>';
	$body .= dahim_email_tracking_box( $tracking );
	if ( $origin || $destination ) {
		$body .= dahim_email_detail_line( 'Route', trim( $origin . ' → ' . $destination, ' →' ) );
	}
	$body .= dahim_email_button( $track_url, 'Track Your Shipment' );
	$body .= '<p style="margin:20px 0 0;font-size:13.5px;color:#4C5A78;">We\'ll keep this tracking number updated as your cargo moves. Keep it handy for any enquiries.</p>';

	$headers = array( 'Content-Type: text/html; charset=UTF-8' );

	$preheader = $origin && $destination
		? "Route: {$origin} → {$destination}. We'll keep you posted as it moves."
		: "It's now being processed. We'll keep you posted as it moves.";

	return wp_mail( $to, $subject, dahim_email_wrap( $preheader, $body, 'Shipment Created' ), $headers );
}

/* Emails the shipment owner whenever the status moves from one stage to
 * another (e.g. In Transit → Delivered) after the shipment already exists. */
function dahim_send_shipment_status_update_email( $post_id, $stage_before, $stage_after ) {
	$to = get_post_meta( $post_id, '_dahim_ship_owner_email', true );
	if ( ! is_email( $to ) ) return false;

	$name        = get_post_meta( $post_id, '_dahim_ship_owner_name', true );
	$tracking    = get_post_meta( $post_id, '_dahim_tracking_number', true );
	$origin      = get_post_meta( $post_id, '_dahim_ship_origin', true );
	$destination = get_post_meta( $post_id, '_dahim_ship_destination', true );
	$location    = get_post_meta( $post_id, '_dahim_ship_current_location', true );
	$track_url   = add_query_arg( 'tracking', rawurlencode( $tracking ), dahim_get_tracking_page_url() );
	$site_name   = get_bloginfo( 'name' );

	$old_label = dahim_shipment_stage_label( $stage_before );
	$new_label = dahim_shipment_stage_label( $stage_after );

	$subject = "Shipment update — {$new_label} — Tracking No. {$tracking}";

	$body  = '<p style="margin:0 0 16px;">Hi ' . esc_html( $name ?: 'there' ) . ',</p>';
	$body .= '<p style="margin:0 0 16px;">There\'s an update on your shipment with ' . esc_html( $site_name ) . '.</p>';
	$body .= dahim_email_tracking_box( $tracking );
	$body .= '<p style="margin:16px 0;text-align:center;font-size:15px;"><span style="color:#4C5A78;">' . esc_html( $old_label ) . '</span> <span style="color:#C79B3C;">→</span> <strong style="color:#1E2A44;">' . esc_html( $new_label ) . '</strong></p>';
	if ( $origin || $destination ) {
		$body .= dahim_email_detail_line( 'Route', trim( $origin . ' → ' . $destination, ' →' ) );
	}
	if ( $location ) {
		$body .= dahim_email_detail_line( 'Current Location', $location );
	}
	$body .= dahim_email_button( $track_url, 'Track Your Shipment' );

	$headers = array( 'Content-Type: text/html; charset=UTF-8' );

	$preheader = "Moved from {$old_label} to {$new_label}" . ( $location ? " — now at {$location}." : '.' );

	return wp_mail( $to, $subject, dahim_email_wrap( $preheader, $body, 'Shipment Update' ), $headers );
}

/* Emails the shipment owner if their shipment is deleted (trashed or
 * permanently removed), letting them know it's been cancelled. Guarded by
 * a meta flag so trashing then later permanently deleting doesn't double-send. */
function dahim_send_shipment_cancelled_email( $post_id ) {
	if ( get_post_meta( $post_id, '_dahim_ship_cancel_sent', true ) ) return false;

	$to = get_post_meta( $post_id, '_dahim_ship_owner_email', true );
	if ( ! is_email( $to ) ) return false;

	$name        = get_post_meta( $post_id, '_dahim_ship_owner_name', true );
	$tracking    = get_post_meta( $post_id, '_dahim_tracking_number', true );
	$origin      = get_post_meta( $post_id, '_dahim_ship_origin', true );
	$destination = get_post_meta( $post_id, '_dahim_ship_destination', true );
	$site_name   = get_bloginfo( 'name' );

	$subject = "Your shipment has been cancelled — Tracking No. {$tracking}";

	$body  = '<p style="margin:0 0 16px;">Hi ' . esc_html( $name ?: 'there' ) . ',</p>';
	$body .= '<p style="margin:0 0 16px;">Your shipment with ' . esc_html( $site_name ) . ' has been cancelled.</p>';
	$body .= dahim_email_tracking_box( $tracking );
	if ( $origin || $destination ) {
		$body .= dahim_email_detail_line( 'Route', trim( $origin . ' → ' . $destination, ' →' ) );
	}
	$body .= '<p style="margin:20px 0 0;font-size:13.5px;color:#4C5A78;">If you weren\'t expecting this, or believe this is a mistake, please reach out to us directly and we\'ll look into it right away.</p>';

	$headers = array( 'Content-Type: text/html; charset=UTF-8' );

	$sent = wp_mail( $to, $subject, dahim_email_wrap( "If this wasn't expected, please get in touch and we'll look into it right away.", $body, 'Shipment Cancelled' ), $headers );
	if ( $sent ) {
		update_post_meta( $post_id, '_dahim_ship_cancel_sent', current_time( 'mysql' ) );
	}
	return $sent;
}

/* Fires when a shipment is moved to Trash (the normal "Delete" action from
 * the admin list) and when one is permanently deleted (e.g. emptying Trash,
 * or a direct delete) — whichever happens first sends the notice; the
 * "already sent" guard above stops a second email if both occur. */
function dahim_handle_shipment_removed( $post_id ) {
	if ( get_post_type( $post_id ) !== 'shipment' ) return;
	dahim_send_shipment_cancelled_email( $post_id );
}
add_action( 'wp_trash_post', 'dahim_handle_shipment_removed' );
add_action( 'before_delete_post', 'dahim_handle_shipment_removed' );

/* ---------------------------------------------------------
 * 5. INQUIRIES — every contact-form submission is saved here
 *    (not just emailed), tagged by department, so each desk's
 *    inquiries can be reviewed and managed separately in wp-admin.
 * ------------------------------------------------------- */
/* Returns every Department post as a plain-data array, in admin-set order.
 * This is the single source of truth for the desk cards on Contact Us and
 * the department dropdown/behavior on the contact form. */
function dahim_get_all_departments() {
	static $cache = null;
	if ( $cache !== null ) return $cache;

	$posts = get_posts( array(
		'post_type'      => 'department',
		'posts_per_page' => -1,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	) );

	$icons = dahim_icon_library();
	$out = array();
	foreach ( $posts as $p ) {
		$get = function( $key ) use ( $p ) { return get_post_meta( $p->ID, '_dahim_dept_' . $key, true ); };
		$icon_key = $get( 'icon' ) ?: 'chat';
		$out[] = array(
			'id'                  => $p->ID,
			'title'               => $p->post_title,
			'description'         => $get( 'description' ),
			'icon_svg'            => isset( $icons[ $icon_key ] ) ? $icons[ $icon_key ] : $icons['chat'],
			'link_text'           => $get( 'link_text' ) ?: 'Get in touch →',
			'external_url'        => $get( 'external_url' ),
			'eyebrow'             => $get( 'eyebrow' ) ?: 'Get In Touch',
			'heading'             => $get( 'heading' ) ?: 'How can we help?',
			'message_label'       => $get( 'message_label' ) ?: 'Your Message',
			'message_placeholder' => $get( 'message_placeholder' ) ?: 'How can we help?',
			'submit_label'        => $get( 'submit_label' ) ?: 'Send Message',
			'show_company'        => $get( 'show_company' ) !== '0',
			'show_service'        => $get( 'show_service' ) === '1',
			'show_role_cv'        => $get( 'show_role_cv' ) === '1',
			'notify_email'        => $get( 'notify_email' ),
			'confirm_subject'     => $get( 'confirm_subject' ) ?: "We've received your message",
			'confirm_heading'     => $get( 'confirm_heading' ) ?: 'Message Received',
			'confirm_message'     => $get( 'confirm_message' ) ?: "Hi {name}, thanks for reaching out to us. We've received your message and someone from our team will get back to you within one business day.",
		);
	}

	// First run on a fresh install (no Departments created yet) — seed the
	// four departments the site shipped with, so nothing breaks before an
	// admin has visited Departments in wp-admin even once.
	if ( empty( $out ) && ! get_option( 'dahim_departments_seeded' ) ) {
		dahim_seed_default_departments();
		update_option( 'dahim_departments_seeded', 1 );
		$cache = null;
		return dahim_get_all_departments();
	}

	$cache = $out;
	return $out;
}

function dahim_seed_default_departments() {
	$defaults = array(
		array(
			'title' => 'Sales & Shipping Quotes', 'icon' => 'briefcase',
			'description' => 'New shipments, rate requests, and route planning.',
			'link_text' => 'Request a quote →',
			'eyebrow' => 'Request a Quote', 'heading' => 'Tell us about your shipment',
			'message_label' => 'Cargo Details',
			'message_placeholder' => 'Tell us about your shipment — origin, destination, cargo type, and estimated volume',
			'submit_label' => 'Send Request', 'show_company' => '1', 'show_service' => '1', 'show_role_cv' => '0',
			'notify_email' => 'sales@dahimlogistics.com',
			'confirm_subject' => 'We\'ve received your quote request',
			'confirm_heading' => 'Quote Request Received',
			'confirm_message' => 'Hi {name}, thanks for reaching out about your shipment. Our sales desk has your cargo details and will get back to you with a quote within one business day.',
		),
		array(
			'title' => 'Existing Shipments', 'icon' => 'search',
			'description' => 'Status updates, delays, or delivery questions.',
			'link_text' => 'Track a shipment →', 'external_url' => '/track/',
		),
		array(
			'title' => 'Partnerships & Vendors', 'icon' => 'handshake',
			'description' => 'Carrier onboarding, agency, and vendor enquiries.',
			'link_text' => 'Get in touch →',
			'eyebrow' => 'Partner With Us', 'heading' => 'Tell us about the opportunity',
			'message_label' => 'Tell Us More',
			'message_placeholder' => 'Tell us about your company and the partnership or vendor opportunity',
			'submit_label' => 'Send Request', 'show_company' => '1', 'show_service' => '0', 'show_role_cv' => '0',
			'notify_email' => 'partnerships@dahimlogistics.com',
			'confirm_subject' => 'We\'ve received your partnership enquiry',
			'confirm_heading' => 'Enquiry Received',
			'confirm_message' => 'Hi {name}, thanks for your interest in partnering with us. Our team reviews every partnership and vendor enquiry personally and will be in touch within one business day.',
		),
		array(
			'title' => 'Careers', 'icon' => 'target',
			'description' => 'Open roles across ops, customs, and dispatch.',
			'link_text' => 'Get in touch →',
			'eyebrow' => 'Join The Team', 'heading' => 'Apply for a role',
			'message_label' => 'Tell Us More',
			'message_placeholder' => "Anything else you'd like us to know?",
			'submit_label' => 'Send Application', 'show_company' => '0', 'show_service' => '0', 'show_role_cv' => '1',
			'notify_email' => 'careers@dahimlogistics.com',
			'confirm_subject' => 'We\'ve received your application',
			'confirm_heading' => 'Application Received',
			'confirm_message' => 'Hi {name}, thank you for applying! Our team reviews every application and will reach out if your background is a fit for the role.',
		),
		array(
			'title' => 'General Enquiry', 'icon' => 'chat',
			'description' => "Anything else — we'll route it to the right person.",
			'link_text' => 'Get in touch →',
			'eyebrow' => 'Get In Touch', 'heading' => 'How can we help?',
			'message_label' => 'Your Message',
			'message_placeholder' => 'How can we help?',
			'submit_label' => 'Send Message', 'show_company' => '1', 'show_service' => '0', 'show_role_cv' => '0',
			'notify_email' => '',
			'confirm_subject' => 'We\'ve received your message',
			'confirm_heading' => 'Message Received',
			'confirm_message' => "Hi {name}, thanks for reaching out to us. We've received your message and someone from our team will get back to you within one business day.",
		),
	);
	foreach ( $defaults as $i => $d ) {
		$post_id = wp_insert_post( array( 'post_type' => 'department', 'post_status' => 'publish', 'post_title' => $d['title'], 'menu_order' => $i ) );
		if ( is_wp_error( $post_id ) || ! $post_id ) continue;
		foreach ( $d as $key => $val ) {
			if ( $key === 'title' ) continue;
			update_post_meta( $post_id, '_dahim_dept_' . $key, $val );
		}
	}
}

/* Real (non-external-link) departments only — these are the ones that make
 * sense in the Inquiries dropdown, since "Existing Shipments" etc. route
 * straight off-site instead of creating an inquiry. */
function dahim_inquiry_departments() {
	$out = array();
	foreach ( dahim_get_all_departments() as $dept ) {
		if ( empty( $dept['external_url'] ) ) $out[] = $dept['title'];
	}
	return $out;
}

function dahim_inquiry_statuses() {
	return array(
		'new'         => 'New',
		'contacted'   => 'Contacted',
		'in_progress' => 'In Progress',
		'closed'      => 'Closed',
		'abandoned'   => 'Abandoned',
	);
}

// Color used for each status's pill, shared between the admin list dropdown
// and anywhere else a status badge is shown.
function dahim_inquiry_status_color( $status ) {
	$colors = array(
		'new'         => '#C79B3C',
		'contacted'   => '#4C5A78',
		'in_progress' => '#2E6F9E',
		'closed'      => '#008751',
		'abandoned'   => '#8A8F98',
	);
	return isset( $colors[ $status ] ) ? $colors[ $status ] : '#C79B3C';
}

function dahim_register_inquiry_cpt() {
	register_post_type( 'inquiry', array(
		'labels' => array(
			'name'          => 'Inquiries',
			'singular_name' => 'Inquiry',
			'add_new_item'  => 'Add New Inquiry',
			'edit_item'     => 'View Inquiry',
			'menu_name'     => 'Inquiries',
		),
		'public'      => false,
		'show_ui'     => true,
		'menu_icon'   => 'dashicons-email-alt2',
		'supports'    => array( 'title' ),
		'has_archive' => false,
		'show_in_rest' => true,
		'rest_base'    => 'inquiries',
	) );
}
add_action( 'init', 'dahim_register_inquiry_cpt' );

// --- Admin list: Department, Email, Phone, Status, Date ---
function dahim_inquiry_admin_columns( $columns ) {
	return array(
		'cb'         => $columns['cb'],
		'title'      => 'Inquiry',
		'department' => 'Department',
		'email'      => 'Email',
		'phone'      => 'Phone',
		'status'     => 'Status',
		'date'       => $columns['date'],
	);
}
add_filter( 'manage_inquiry_posts_columns', 'dahim_inquiry_admin_columns' );

function dahim_inquiry_admin_column_content( $column, $post_id ) {
	switch ( $column ) {
		case 'department':
			echo esc_html( get_post_meta( $post_id, '_dahim_inquiry_department', true ) ?: 'General Enquiry' );
			break;
		case 'email':
			$email = get_post_meta( $post_id, '_dahim_inquiry_email', true );
			echo $email ? '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>' : '—';
			break;
		case 'phone':
			echo esc_html( get_post_meta( $post_id, '_dahim_inquiry_phone', true ) ?: '—' );
			break;
		case 'status':
			$statuses = dahim_inquiry_statuses();
			$status   = get_post_meta( $post_id, '_dahim_inquiry_status', true ) ?: 'new';
			$color    = dahim_inquiry_status_color( $status );
			echo '<select class="dahim-status-select" data-post-id="' . esc_attr( $post_id ) . '" data-status="' . esc_attr( $status ) . '" style="color:' . esc_attr( $color ) . ';border-color:' . esc_attr( $color ) . ';">';
			foreach ( $statuses as $val => $label ) {
				echo '<option value="' . esc_attr( $val ) . '"' . selected( $status, $val, false ) . '>' . esc_html( $label ) . '</option>';
			}
			echo '</select>';
			break;
	}
}
add_action( 'manage_inquiry_posts_custom_column', 'dahim_inquiry_admin_column_content', 10, 2 );

// --- Change an inquiry's status directly from the list, no need to open it ---
function dahim_admin_inquiry_status_assets( $hook ) {
	global $typenow;
	if ( $hook !== 'edit.php' || $typenow !== 'inquiry' ) return;
	$rel_path = 'assets/js/admin-inquiry-status.js';
	wp_enqueue_script(
		'dahim-admin-inquiry-status',
		plugins_url( $rel_path, __FILE__ ),
		array(),
		file_exists( plugin_dir_path( __FILE__ ) . $rel_path ) ? filemtime( plugin_dir_path( __FILE__ ) . $rel_path ) : '1.0',
		true
	);
	wp_localize_script( 'dahim-admin-inquiry-status', 'dahimInquiryAdmin', array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'dahim_update_inquiry_status' ),
		'colors'  => array_combine( array_keys( dahim_inquiry_statuses() ), array_map( 'dahim_inquiry_status_color', array_keys( dahim_inquiry_statuses() ) ) ),
	) );
}
add_action( 'admin_enqueue_scripts', 'dahim_admin_inquiry_status_assets' );

function dahim_admin_inquiry_status_styles() {
	global $pagenow, $typenow;
	if ( $pagenow !== 'edit.php' || $typenow !== 'inquiry' ) return;
	echo '<style>
		.dahim-status-select{border-radius:999px;padding:4px 26px 4px 10px;font-weight:600;font-size:12px;border:1.5px solid;background:#fff;cursor:pointer;}
		.dahim-status-select.is-error{border-color:#B3261E!important;color:#B3261E!important;}
		.dahim-status-select:disabled{opacity:0.5;cursor:wait;}
	</style>';
}
add_action( 'admin_head', 'dahim_admin_inquiry_status_styles' );

function dahim_ajax_update_inquiry_status() {
	check_ajax_referer( 'dahim_update_inquiry_status', 'nonce' );

	$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
	$status  = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';

	if ( ! $post_id || get_post_type( $post_id ) !== 'inquiry' ) {
		wp_send_json_error( 'Invalid inquiry.' );
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		wp_send_json_error( 'You are not allowed to update this inquiry.' );
	}
	if ( ! array_key_exists( $status, dahim_inquiry_statuses() ) ) {
		wp_send_json_error( 'Invalid status.' );
	}

	update_post_meta( $post_id, '_dahim_inquiry_status', $status );
	wp_send_json_success( array( 'status' => $status, 'color' => dahim_inquiry_status_color( $status ) ) );
}
add_action( 'wp_ajax_dahim_update_inquiry_status', 'dahim_ajax_update_inquiry_status' );

// --- Filter the Inquiries list by department ---
function dahim_inquiry_department_filter() {
	global $typenow;
	if ( $typenow !== 'inquiry' ) return;

	$locked_dept = dahim_current_user_locked_department();
	if ( $locked_dept ) {
		echo '<span class="button button-disabled" style="margin-left:6px;">Showing: ' . esc_html( $locked_dept ) . ' only</span>';
		return;
	}

	$current = isset( $_GET['dahim_department'] ) ? sanitize_text_field( $_GET['dahim_department'] ) : '';
	echo '<select name="dahim_department"><option value="">All Departments</option>';
	foreach ( dahim_inquiry_departments() as $dept ) {
		echo '<option value="' . esc_attr( $dept ) . '"' . selected( $current, $dept, false ) . '>' . esc_html( $dept ) . '</option>';
	}
	echo '</select>';
}
add_action( 'restrict_manage_posts', 'dahim_inquiry_department_filter' );

function dahim_inquiry_department_filter_query( $query ) {
	global $pagenow, $typenow;
	if ( ! is_admin() || $pagenow !== 'edit.php' || $typenow !== 'inquiry' || ! $query->is_main_query() ) return;
	if ( ! empty( $_GET['dahim_department'] ) ) {
		$query->set( 'meta_key', '_dahim_inquiry_department' );
		$query->set( 'meta_value', sanitize_text_field( $_GET['dahim_department'] ) );
	}
}
add_action( 'pre_get_posts', 'dahim_inquiry_department_filter_query' );

/* ---------------------------------------------------------
 * DEPARTMENT-SCOPED ACCESS — lets a user's account be limited to
 * seeing only one department's Inquiries (e.g. a Careers-only
 * account can't see Sales leads). Administrators always see all.
 * ------------------------------------------------------- */
function dahim_current_user_locked_department() {
	if ( current_user_can( 'manage_options' ) ) return '';
	$dept = get_user_meta( get_current_user_id(), 'dahim_inquiry_department_access', true );
	return ( $dept && $dept !== 'all' ) ? $dept : '';
}

function dahim_add_department_profile_field( $user ) {
	if ( ! current_user_can( 'edit_posts' ) ) return;
	$current = get_user_meta( $user->ID, 'dahim_inquiry_department_access', true ) ?: 'all';
	wp_nonce_field( 'dahim_save_department_access', 'dahim_department_access_nonce' );
	?>
	<h2>Dahim Inquiry Access</h2>
	<table class="form-table">
		<tr>
			<th><label for="dahim_inquiry_department_access">Visible Department</label></th>
			<td>
				<select name="dahim_inquiry_department_access" id="dahim_inquiry_department_access">
					<option value="all" <?php selected( $current, 'all' ); ?>>All Departments</option>
					<?php foreach ( dahim_inquiry_departments() as $dept ) : ?>
						<option value="<?php echo esc_attr( $dept ); ?>" <?php selected( $current, $dept ); ?>><?php echo esc_html( $dept ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description">Limits which department's Inquiries this user can see in wp-admin. Administrators always see every department regardless of this setting.</p>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'dahim_add_department_profile_field' );
add_action( 'edit_user_profile', 'dahim_add_department_profile_field' );

function dahim_save_department_profile_field( $user_id ) {
	if ( ! isset( $_POST['dahim_department_access_nonce'] ) || ! wp_verify_nonce( $_POST['dahim_department_access_nonce'], 'dahim_save_department_access' ) ) return;
	if ( ! current_user_can( 'edit_user', $user_id ) ) return;
	if ( isset( $_POST['dahim_inquiry_department_access'] ) ) {
		update_user_meta( $user_id, 'dahim_inquiry_department_access', sanitize_text_field( $_POST['dahim_inquiry_department_access'] ) );
	}
}
add_action( 'personal_options_update', 'dahim_save_department_profile_field' );
add_action( 'edit_user_profile_update', 'dahim_save_department_profile_field' );

// Runs after dahim_inquiry_department_filter_query (priority 20 > 10) so a
// locked user can't bypass their restriction by editing the ?dahim_department= URL.
function dahim_restrict_inquiries_by_department( $query ) {
	global $pagenow, $typenow;
	if ( ! is_admin() || $pagenow !== 'edit.php' || $typenow !== 'inquiry' || ! $query->is_main_query() ) return;
	$locked_dept = dahim_current_user_locked_department();
	if ( ! $locked_dept ) return;
	$query->set( 'meta_key', '_dahim_inquiry_department' );
	$query->set( 'meta_value', $locked_dept );
}
add_action( 'pre_get_posts', 'dahim_restrict_inquiries_by_department', 20 );

// Block opening another department's Inquiry directly by URL/post ID —
// the list restriction above only hides it from view, this enforces it.
function dahim_block_cross_department_edit() {
	global $pagenow;
	if ( $pagenow !== 'post.php' || empty( $_GET['post'] ) ) return;
	$post_id = (int) $_GET['post'];
	if ( get_post_type( $post_id ) !== 'inquiry' ) return;
	$locked_dept = dahim_current_user_locked_department();
	if ( ! $locked_dept ) return;
	$post_dept = get_post_meta( $post_id, '_dahim_inquiry_department', true );
	if ( $post_dept !== $locked_dept ) {
		wp_die( 'You do not have access to inquiries outside your assigned department.', 'Access Restricted', array( 'response' => 403 ) );
	}
}
add_action( 'load-post.php', 'dahim_block_cross_department_edit' );

// --- Meta box: full submission details + an editable status ---
function dahim_inquiry_meta_box() {
	add_meta_box( 'dahim_inquiry_fields', 'Inquiry Details', 'dahim_inquiry_fields_html', 'inquiry', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'dahim_inquiry_meta_box' );

function dahim_inquiry_fields_html( $post ) {
	wp_nonce_field( 'dahim_save_inquiry_fields', 'dahim_inquiry_fields_nonce' );
	$get = function( $key ) use ( $post ) { return get_post_meta( $post->ID, '_dahim_inquiry_' . $key, true ); };
	$status = $get( 'status' ) ?: 'new';

	echo '<style>.dahim-field{margin-bottom:12px;}.dahim-field label{display:block;font-weight:600;margin-bottom:4px;}.dahim-field input,.dahim-field select,.dahim-field textarea{width:100%;}.dahim-field-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;}.dahim-readonly{background:#f0f0f1;}</style>';

	echo '<div class="dahim-field-row">';
	echo '<div class="dahim-field"><label>Name</label><input type="text" value="' . esc_attr( $get('name') ) . '" readonly class="dahim-readonly" /></div>';
	echo '<div class="dahim-field"><label>Company</label><input type="text" value="' . esc_attr( $get('company') ) . '" readonly class="dahim-readonly" /></div>';
	echo '</div>';
	echo '<div class="dahim-field-row">';
	echo '<div class="dahim-field"><label>Email</label><input type="text" value="' . esc_attr( $get('email') ) . '" readonly class="dahim-readonly" /></div>';
	echo '<div class="dahim-field"><label>Phone</label><input type="text" value="' . esc_attr( $get('phone') ) . '" readonly class="dahim-readonly" /></div>';
	echo '</div>';
	echo '<div class="dahim-field-row">';
	echo '<div class="dahim-field"><label>Department</label><input type="text" value="' . esc_attr( $get('department') ) . '" readonly class="dahim-readonly" /></div>';
	echo '<div class="dahim-field"><label>Service Needed</label><input type="text" value="' . esc_attr( $get('service') ) . '" readonly class="dahim-readonly" /></div>';
	echo '</div>';
	if ( $get( 'role' ) || $get( 'cv_link' ) ) {
		echo '<div class="dahim-field-row">';
		echo '<div class="dahim-field"><label>Role Applying For</label><input type="text" value="' . esc_attr( $get('role') ) . '" readonly class="dahim-readonly" /></div>';
		echo '<div class="dahim-field"><label>CV / Portfolio Link</label><input type="text" value="' . esc_attr( $get('cv_link') ) . '" readonly class="dahim-readonly" /></div>';
		echo '</div>';
	}
	echo '<div class="dahim-field"><label>Message</label><textarea rows="4" readonly class="dahim-readonly">' . esc_textarea( $get('message') ) . '</textarea></div>';

	echo '<div class="dahim-field" style="margin-top:20px;border-top:1px solid #dcdcde;padding-top:16px;"><label>Status</label><select name="dahim_inquiry_status">';
	foreach ( dahim_inquiry_statuses() as $val => $label ) {
		echo '<option value="' . esc_attr( $val ) . '"' . selected( $status, $val, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select><p style="color:#646970;margin-top:4px;">Update this as you follow up — everything above is read-only, saved from the original submission.</p></div>';
}

function dahim_save_inquiry_fields( $post_id ) {
	if ( ! isset( $_POST['dahim_inquiry_fields_nonce'] ) || ! wp_verify_nonce( $_POST['dahim_inquiry_fields_nonce'], 'dahim_save_inquiry_fields' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;
	if ( isset( $_POST['dahim_inquiry_status'] ) ) {
		$status = sanitize_text_field( $_POST['dahim_inquiry_status'] );
		if ( array_key_exists( $status, dahim_inquiry_statuses() ) ) {
			update_post_meta( $post_id, '_dahim_inquiry_status', $status );
		}
	}
}
add_action( 'save_post_inquiry', 'dahim_save_inquiry_fields' );

/* ---------------------------------------------------------
 * 6. CONTACT FORM HANDLER — saves an Inquiry AND emails the team
 *    (no plugin — native wp_mail)
 * ------------------------------------------------------- */
function dahim_handle_contact_form() {
	if ( ! isset( $_POST['dahim_contact_nonce'] ) || ! wp_verify_nonce( $_POST['dahim_contact_nonce'], 'dahim_contact_submit' ) ) {
		wp_die( 'Security check failed.' );
	}

	$redirect_base = wp_get_referer() ? wp_get_referer() : home_url( '/contact/' );
	$redirect_base = remove_query_arg( 'dahim_contact', $redirect_base );

	// --- Spam checks: a filled honeypot, or a submission faster than any
	// human could actually fill the form, both silently redirect to the
	// normal "success" screen (never tip a bot off that it was caught). ---
	$honeypot = isset( $_POST['dahim_hp'] ) ? trim( $_POST['dahim_hp'] ) : '';
	$rendered_at = isset( $_POST['dahim_form_ts'] ) ? (int) $_POST['dahim_form_ts'] : 0;
	$elapsed = time() - $rendered_at;
	if ( $honeypot !== '' || $rendered_at <= 0 || $elapsed < 3 ) {
		wp_safe_redirect( add_query_arg( 'dahim_contact', 'success', $redirect_base ) );
		exit;
	}

	$name       = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
	$company    = isset( $_POST['company'] ) ? sanitize_text_field( $_POST['company'] ) : '';
	$phone      = isset( $_POST['phone'] ) ? sanitize_text_field( $_POST['phone'] ) : '';
	$email      = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
	$service    = isset( $_POST['service'] ) ? sanitize_text_field( $_POST['service'] ) : '';
	$role       = isset( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : '';
	$cv_link    = isset( $_POST['cv_link'] ) ? esc_url_raw( $_POST['cv_link'] ) : '';
	$message    = isset( $_POST['message'] ) ? sanitize_textarea_field( $_POST['message'] ) : '';
	$department = isset( $_POST['department'] ) ? sanitize_text_field( $_POST['department'] ) : '';
	$token      = isset( $_POST['dahim_submission_token'] ) ? sanitize_text_field( $_POST['dahim_submission_token'] ) : '';
	if ( ! in_array( $department, dahim_inquiry_departments(), true ) ) {
		$department = 'General Enquiry';
	}

	// --- Duplicate-submission guard: a refresh or double-click resubmitting
	// the exact same form load must not create a second Inquiry. ---
	$already_exists = false;
	if ( $token !== '' ) {
		$existing = get_posts( array(
			'post_type'      => 'inquiry',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_dahim_inquiry_token',
			'meta_value'     => $token,
		) );
		$already_exists = ! empty( $existing );
	}

	$sent = true;
	if ( ! $already_exists ) {
		// Save it as an Inquiry so it's manageable in wp-admin, not just emailed.
		$inquiry_id = wp_insert_post( array(
			'post_type'   => 'inquiry',
			'post_status' => 'publish',
			'post_title'  => ( $name ? $name : 'Website Visitor' ) . ' — ' . $department,
		) );
		if ( ! is_wp_error( $inquiry_id ) && $inquiry_id ) {
			update_post_meta( $inquiry_id, '_dahim_inquiry_name', $name );
			update_post_meta( $inquiry_id, '_dahim_inquiry_company', $company );
			update_post_meta( $inquiry_id, '_dahim_inquiry_phone', $phone );
			update_post_meta( $inquiry_id, '_dahim_inquiry_email', $email );
			update_post_meta( $inquiry_id, '_dahim_inquiry_service', $service );
			update_post_meta( $inquiry_id, '_dahim_inquiry_role', $role );
			update_post_meta( $inquiry_id, '_dahim_inquiry_cv_link', $cv_link );
			update_post_meta( $inquiry_id, '_dahim_inquiry_message', $message );
			update_post_meta( $inquiry_id, '_dahim_inquiry_department', $department );
			update_post_meta( $inquiry_id, '_dahim_inquiry_status', 'new' );
			if ( $token !== '' ) update_post_meta( $inquiry_id, '_dahim_inquiry_token', $token );
		}

		// Look up this department's configured data — staff notification
		// address and the tailored customer confirmation content.
		$dept_data = null;
		foreach ( dahim_get_all_departments() as $d ) {
			if ( $d['title'] === $department ) { $dept_data = $d; break; }
		}

		$to = ( $dept_data && $dept_data['notify_email'] && is_email( $dept_data['notify_email'] ) )
			? $dept_data['notify_email']
			: get_option( 'dahim_email', get_option( 'admin_email' ) );

		$valid_inquiry_id = ( ! is_wp_error( $inquiry_id ) && $inquiry_id ) ? $inquiry_id : 0;
		$sent = dahim_send_inquiry_admin_notification( $to, $department, $valid_inquiry_id, array(
			'name' => $name, 'company' => $company, 'phone' => $phone, 'email' => $email,
			'service' => $service, 'role' => $role, 'cv_link' => $cv_link, 'message' => $message,
		) );

		// Confirm to the customer that their message was received — tailored
		// per department, with subject/heading/message fully editable from
		// wp-admin under Departments, not hardcoded here.
		if ( is_email( $email ) && $dept_data ) {
			dahim_send_inquiry_confirmation_email( $email, $name, $dept_data, array(
				'company' => $company, 'phone' => $phone, 'service' => $service,
				'role' => $role, 'cv_link' => $cv_link, 'message' => $message,
			) );
		}
	}

	$redirect = add_query_arg( 'dahim_contact', $sent ? 'success' : 'error', $redirect_base );
	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'admin_post_dahim_contact_submit', 'dahim_handle_contact_form' );
add_action( 'admin_post_nopriv_dahim_contact_submit', 'dahim_handle_contact_form' );

/* ---------------------------------------------------------
 * 7. SHIPMENT TRACKING LOOKUP
 * ------------------------------------------------------- */
function dahim_find_shipment( $tracking_number ) {
	if ( empty( $tracking_number ) ) return null;
	$query = new WP_Query( array(
		'post_type'      => 'shipment',
		'posts_per_page' => 1,
		'meta_query'     => array(
			array(
				'key'     => '_dahim_tracking_number',
				'value'   => sanitize_text_field( $tracking_number ),
				'compare' => '=',
			),
		),
	) );
	if ( $query->have_posts() ) {
		$query->the_post();
		$id = get_the_ID();
		$m  = function( $key ) use ( $id ) { return get_post_meta( $id, '_dahim_ship_' . $key, true ); };

		$stage = (int) $m( 'stage' );
		if ( $stage < 1 ) $stage = 1;

		$result = array(
			'tracking_number'      => get_post_meta( $id, '_dahim_tracking_number', true ),
			'owner_name'           => $m( 'owner_name' ),
			'owner_email'          => $m( 'owner_email' ),
			'owner_phone'          => $m( 'owner_phone' ),
			'consignee_name'       => $m( 'consignee_name' ),
			'consignee_phone'      => $m( 'consignee_phone' ),
			'origin'               => $m( 'origin' ),
			'destination'          => $m( 'destination' ),
			'current_location'     => $m( 'current_location' ),
			'package_description'  => $m( 'package_description' ),
			'weight'               => $m( 'weight' ),
			'pieces'               => $m( 'pieces' ),
			'dimensions'           => $m( 'dimensions' ),
			'declared_value'       => $m( 'declared_value' ),
			'service_type'         => $m( 'service_type' ),
			'carrier'              => $m( 'carrier' ),
			'date_booked'          => $m( 'date_booked' ),
			'estimated_delivery'   => $m( 'estimated_delivery' ),
			'special_instructions' => $m( 'special_instructions' ),
			'stage'                => $stage,
			'stage_label'          => dahim_shipment_stage_label( $stage ),
		);
		wp_reset_postdata();
		return $result;
	}
	wp_reset_postdata();
	return false; // not found
}

/* Renders one label/value row inside the shipment details modal — shared
 * between the modal markup and, via the data-field attribute, read by the
 * PDF/print JS on the front end. */
function dahim_detail_row( $label, $field_key, $value ) {
	echo '<div class="ship-detail-row"><span class="ship-detail-label">' . esc_html( $label ) . '</span><span class="ship-detail-value" data-field="' . esc_attr( $field_key ) . '">' . ( $value !== '' ? esc_html( $value ) : '—' ) . '</span></div>';
}

/* ---------------------------------------------------------
 * 11. DUPLICATOR — one-click "Duplicate" for anything in the
 *     admin: Posts, Pages, Shipments, FAQs, Services, Team
 *     Members, Trade Lanes. Adds a "Duplicate" link next to
 *     Edit/Trash in every list table; clones content, all
 *     custom fields, the featured image, and taxonomy terms
 *     into a new Draft so it can be reviewed before publishing.
 * ------------------------------------------------------- */

// Post types this applies to. 'attachment' is deliberately excluded —
// duplicating media doesn't make sense the same way.
function dahim_duplicator_post_types() {
	return array( 'post', 'page', 'service', 'team_member', 'faq', 'trade_lane', 'shipment', 'department', 'job' );
}

function dahim_add_duplicate_link( $actions, $post ) {
	if ( ! in_array( $post->post_type, dahim_duplicator_post_types(), true ) ) return $actions;
	if ( ! current_user_can( 'edit_post', $post->ID ) ) return $actions;

	$url = wp_nonce_url(
		add_query_arg( array( 'action' => 'dahim_duplicate_post', 'post' => $post->ID ), admin_url( 'admin.php' ) ),
		'dahim_duplicate_post_' . $post->ID
	);
	$actions['dahim_duplicate'] = '<a href="' . esc_url( $url ) . '" aria-label="Duplicate this item">Duplicate</a>';
	return $actions;
}
add_filter( 'post_row_actions', 'dahim_add_duplicate_link', 10, 2 ); // non-hierarchical types (post, service, faq, team_member, trade_lane, shipment)
add_filter( 'page_row_actions', 'dahim_add_duplicate_link', 10, 2 ); // hierarchical types (page)

// Also available as a single button on the edit screen itself, not just the list table.
function dahim_add_duplicate_button( $post ) {
	if ( ! in_array( $post->post_type, dahim_duplicator_post_types(), true ) ) return;
	if ( 'auto-draft' === $post->post_status ) return; // nothing saved yet to duplicate
	if ( ! current_user_can( 'edit_post', $post->ID ) ) return;

	$url = wp_nonce_url(
		add_query_arg( array( 'action' => 'dahim_duplicate_post', 'post' => $post->ID ), admin_url( 'admin.php' ) ),
		'dahim_duplicate_post_' . $post->ID
	);
	echo '<div class="misc-pub-section"><a href="' . esc_url( $url ) . '" class="button button-secondary" style="width:100%;text-align:center;">Duplicate This</a></div>';
}
add_action( 'post_submitbox_misc_actions', 'dahim_add_duplicate_button' );

function dahim_handle_duplicate_post() {
	if ( ! isset( $_GET['post'] ) ) wp_die( 'No item specified to duplicate.' );
	$post_id = (int) $_GET['post'];

	check_admin_referer( 'dahim_duplicate_post_' . $post_id );

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( 'You are not allowed to duplicate this item.' );
	}

	$original = get_post( $post_id );
	if ( ! $original || ! in_array( $original->post_type, dahim_duplicator_post_types(), true ) ) {
		wp_die( 'Original item not found.' );
	}

	$new_id = wp_insert_post( array(
		'post_title'     => $original->post_title . ' (Copy)',
		'post_content'   => $original->post_content,
		'post_excerpt'   => $original->post_excerpt,
		'post_status'    => 'draft',
		'post_type'      => $original->post_type,
		'post_author'    => get_current_user_id(),
		'menu_order'     => $original->menu_order,
		'comment_status' => $original->comment_status,
		'ping_status'    => $original->ping_status,
		'post_parent'    => $original->post_parent,
	), true );

	if ( is_wp_error( $new_id ) ) {
		wp_die( 'Could not duplicate this item: ' . esc_html( $new_id->get_error_message() ) );
	}

	// Copy every taxonomy term this post type has (categories, tags, or any custom taxonomy).
	foreach ( get_object_taxonomies( $original->post_type ) as $taxonomy ) {
		$terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
		if ( ! is_wp_error( $terms ) ) {
			wp_set_object_terms( $new_id, $terms, $taxonomy );
		}
	}

	// Copy every custom field — this carries over the featured image, page
	// template, and every _dahim_* field (shipment details, service icons,
	// FAQ order, trade lane route info, etc.) automatically.
	$skip_meta = array( '_edit_lock', '_edit_last' );
	foreach ( get_post_meta( $post_id ) as $key => $values ) {
		if ( in_array( $key, $skip_meta, true ) ) continue;
		foreach ( $values as $value ) {
			add_post_meta( $new_id, $key, maybe_unserialize( $value ) );
		}
	}

	// Shipments are the one type that must NOT come out looking identical to
	// the original: give the duplicate its own fresh, unique tracking number
	// and reset the notification flags so it behaves like a brand-new record.
	if ( 'shipment' === $original->post_type ) {
		delete_post_meta( $new_id, '_dahim_tracking_number' );
		delete_post_meta( $new_id, '_dahim_ship_email_sent' );
		delete_post_meta( $new_id, '_dahim_ship_cancel_sent' );
		update_post_meta( $new_id, '_dahim_tracking_number', dahim_generate_tracking_number() );
	}

	wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . $new_id . '&dahim_duplicated=1' ) );
	exit;
}
add_action( 'admin_action_dahim_duplicate_post', 'dahim_handle_duplicate_post' );

// Friendly confirmation banner on the new draft's edit screen.
function dahim_duplicate_admin_notice() {
	if ( empty( $_GET['dahim_duplicated'] ) ) return;
	echo '<div class="notice notice-success is-dismissible"><p>This is a duplicate, saved as a Draft. Review it and Publish when you\'re ready.</p></div>';
}
add_action( 'admin_notices', 'dahim_duplicate_admin_notice' );

/* ---------------------------------------------------------
 * 12. ADMIN DASHBOARD IMPROVEMENTS — Inquiries workflow
 * ------------------------------------------------------- */

// Counts inquiries currently marked "New" — shared by the menu badge and
// the dashboard widget so both always agree.
function dahim_count_new_inquiries() {
	$q = new WP_Query( array(
		'post_type'      => 'inquiry',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_key'       => '_dahim_inquiry_status',
		'meta_value'     => 'new',
	) );
	return (int) $q->found_posts;
}

// --- A. "New" count badge on the Inquiries menu item, like Comments has ---
function dahim_inquiry_menu_badge() {
	global $menu;
	if ( ! is_array( $menu ) ) return;
	$new_count = dahim_count_new_inquiries();
	if ( $new_count < 1 ) return;
	foreach ( $menu as $key => $item ) {
		if ( isset( $item[2] ) && $item[2] === 'edit.php?post_type=inquiry' ) {
			$menu[ $key ][0] .= ' <span class="awaiting-mod count-' . (int) $new_count . '"><span class="pending-count">' . (int) $new_count . '</span></span>';
		}
	}
}
add_action( 'admin_menu', 'dahim_inquiry_menu_badge', 999 );

// --- B. Bulk actions: mark selected Inquiries New / Contacted / Closed at once ---
function dahim_inquiry_bulk_actions( $bulk_actions ) {
	$bulk_actions['dahim_mark_new']         = 'Mark as New';
	$bulk_actions['dahim_mark_contacted']   = 'Mark as Contacted';
	$bulk_actions['dahim_mark_in_progress'] = 'Mark as In Progress';
	$bulk_actions['dahim_mark_closed']      = 'Mark as Closed';
	$bulk_actions['dahim_mark_abandoned']   = 'Mark as Abandoned';
	return $bulk_actions;
}
add_filter( 'bulk_actions-edit-inquiry', 'dahim_inquiry_bulk_actions' );

function dahim_inquiry_handle_bulk_actions( $redirect_to, $doaction, $post_ids ) {
	$map = array(
		'dahim_mark_new'         => 'new',
		'dahim_mark_contacted'   => 'contacted',
		'dahim_mark_in_progress' => 'in_progress',
		'dahim_mark_closed'      => 'closed',
		'dahim_mark_abandoned'   => 'abandoned',
	);
	if ( ! isset( $map[ $doaction ] ) ) return $redirect_to;

	$updated = 0;
	foreach ( $post_ids as $post_id ) {
		if ( current_user_can( 'edit_post', $post_id ) ) {
			update_post_meta( $post_id, '_dahim_inquiry_status', $map[ $doaction ] );
			$updated++;
		}
	}
	return add_query_arg( 'dahim_bulk_status_updated', $updated, $redirect_to );
}
add_filter( 'handle_bulk_actions-edit-inquiry', 'dahim_inquiry_handle_bulk_actions', 10, 3 );

function dahim_inquiry_bulk_action_notice() {
	if ( empty( $_REQUEST['dahim_bulk_status_updated'] ) ) return;
	$count = (int) $_REQUEST['dahim_bulk_status_updated'];
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $count ) . ' inquir' . ( $count === 1 ? 'y' : 'ies' ) . ' updated.</p></div>';
}
add_action( 'admin_notices', 'dahim_inquiry_bulk_action_notice' );

// --- C. Make the Inquiries search box actually search the submission data
// (name, email, phone, company, message) — not just the post title ---
function dahim_inquiry_extend_search( $search_sql, $wp_query ) {
	global $wpdb, $pagenow;
	if ( ! is_admin() || $pagenow !== 'edit.php' ) return $search_sql;
	if ( $wp_query->get( 'post_type' ) !== 'inquiry' || empty( $wp_query->query_vars['s'] ) ) return $search_sql;

	$like = '%' . $wpdb->esc_like( $wp_query->query_vars['s'] ) . '%';
	return $wpdb->prepare(
		" AND ( {$wpdb->posts}.post_title LIKE %s
			OR EXISTS (
				SELECT 1 FROM {$wpdb->postmeta} dahim_pm
				WHERE dahim_pm.post_id = {$wpdb->posts}.ID
				AND dahim_pm.meta_key IN ('_dahim_inquiry_name','_dahim_inquiry_email','_dahim_inquiry_phone','_dahim_inquiry_company','_dahim_inquiry_message','_dahim_inquiry_role')
				AND dahim_pm.meta_value LIKE %s
			)
		) ",
		$like, $like
	);
}
add_filter( 'posts_search', 'dahim_inquiry_extend_search', 10, 2 );

// --- D. Dashboard "At a Glance" widget ---
function dahim_register_dashboard_widget() {
	if ( ! current_user_can( 'edit_posts' ) ) return;
	wp_add_dashboard_widget( 'dahim_glance', 'Dahim — At a Glance', 'dahim_dashboard_widget_html' );
}
add_action( 'wp_dashboard_setup', 'dahim_register_dashboard_widget' );

function dahim_dashboard_widget_html() {
	$new_inquiries = dahim_count_new_inquiries();

	$week_inquiries_q = new WP_Query( array(
		'post_type'      => 'inquiry',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'date_query'     => array( array( 'after' => '7 days ago' ) ),
	) );
	$week_inquiries = (int) $week_inquiries_q->found_posts;

	$in_transit_q = new WP_Query( array(
		'post_type'      => 'shipment',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_query'     => array(
			array( 'key' => '_dahim_ship_stage', 'value' => array( '2', '3', '4' ), 'compare' => 'IN' ),
		),
	) );
	$in_transit = (int) $in_transit_q->found_posts;

	$delivered_month_q = new WP_Query( array(
		'post_type'      => 'shipment',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'date_query'     => array( array( 'after' => '30 days ago' ) ),
		'meta_query'     => array(
			array( 'key' => '_dahim_ship_stage', 'value' => '5', 'compare' => '=' ),
		),
	) );
	$delivered_month = (int) $delivered_month_q->found_posts;

	$rows = array(
		array( 'label' => 'New inquiries', 'value' => $new_inquiries, 'url' => admin_url( 'edit.php?post_type=inquiry' ) ),
		array( 'label' => 'Inquiries this week', 'value' => $week_inquiries, 'url' => admin_url( 'edit.php?post_type=inquiry' ) ),
		array( 'label' => 'Shipments in transit', 'value' => $in_transit, 'url' => admin_url( 'edit.php?post_type=shipment' ) ),
		array( 'label' => 'Delivered (30 days)', 'value' => $delivered_month, 'url' => admin_url( 'edit.php?post_type=shipment' ) ),
	);

	echo '<ul style="margin:0;">';
	foreach ( $rows as $row ) {
		echo '<li style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f0f0f1;">';
		echo '<a href="' . esc_url( $row['url'] ) . '" style="font-size:13.5px;">' . esc_html( $row['label'] ) . '</a>';
		echo '<strong style="font-size:15px;">' . esc_html( $row['value'] ) . '</strong>';
		echo '</li>';
	}
	echo '</ul>';
}

// --- E. Export Inquiries and Shipments as CSV, one click from their admin list ---
function dahim_export_buttons() {
	global $typenow;
	if ( $typenow === 'inquiry' ) {
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=dahim_export_inquiries_csv' ), 'dahim_export_inquiries' );
		echo '<a href="' . esc_url( $url ) . '" class="button" style="margin-left:6px;">Export CSV</a>';
	} elseif ( $typenow === 'shipment' ) {
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=dahim_export_shipments_csv' ), 'dahim_export_shipments' );
		echo '<a href="' . esc_url( $url ) . '" class="button" style="margin-left:6px;">Export CSV</a>';
	}
	if ( in_array( $typenow, array( 'inquiry', 'shipment', 'trade_lane' ), true ) ) {
		$import_url = admin_url( 'admin.php?page=dahim-import-csv&type=' . $typenow );
		echo '<a href="' . esc_url( $import_url ) . '" class="button" style="margin-left:6px;">Import CSV</a>';
	}
}
add_action( 'restrict_manage_posts', 'dahim_export_buttons' );

function dahim_handle_export_inquiries_csv() {
	if ( ! current_user_can( 'edit_others_posts' ) ) wp_die( 'You are not allowed to export this.' );
	check_admin_referer( 'dahim_export_inquiries' );

	$locked_dept = dahim_current_user_locked_department();
	$args = array( 'post_type' => 'inquiry', 'posts_per_page' => -1, 'post_status' => 'publish', 'orderby' => 'date', 'order' => 'DESC' );
	if ( $locked_dept ) {
		$args['meta_key']   = '_dahim_inquiry_department';
		$args['meta_value'] = $locked_dept;
	}
	$posts = get_posts( $args );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=dahim-inquiries-' . gmdate( 'Y-m-d' ) . '.csv' );
	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( 'Date', 'Name', 'Company', 'Email', 'Phone', 'Department', 'Service', 'Role Applied For', 'CV / Portfolio Link', 'Status', 'Message' ) );
	foreach ( $posts as $p ) {
		fputcsv( $out, array(
			get_the_date( 'Y-m-d H:i', $p ),
			get_post_meta( $p->ID, '_dahim_inquiry_name', true ),
			get_post_meta( $p->ID, '_dahim_inquiry_company', true ),
			get_post_meta( $p->ID, '_dahim_inquiry_email', true ),
			get_post_meta( $p->ID, '_dahim_inquiry_phone', true ),
			get_post_meta( $p->ID, '_dahim_inquiry_department', true ),
			get_post_meta( $p->ID, '_dahim_inquiry_service', true ),
			get_post_meta( $p->ID, '_dahim_inquiry_role', true ),
			get_post_meta( $p->ID, '_dahim_inquiry_cv_link', true ),
			get_post_meta( $p->ID, '_dahim_inquiry_status', true ),
			get_post_meta( $p->ID, '_dahim_inquiry_message', true ),
		) );
	}
	fclose( $out );
	exit;
}
add_action( 'admin_post_dahim_export_inquiries_csv', 'dahim_handle_export_inquiries_csv' );

function dahim_handle_export_shipments_csv() {
	if ( ! current_user_can( 'edit_others_posts' ) ) wp_die( 'You are not allowed to export this.' );
	check_admin_referer( 'dahim_export_shipments' );

	$posts = get_posts( array( 'post_type' => 'shipment', 'posts_per_page' => -1, 'post_status' => 'publish', 'orderby' => 'date', 'order' => 'DESC' ) );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=dahim-shipments-' . gmdate( 'Y-m-d' ) . '.csv' );
	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( 'Date', 'Tracking No.', 'Owner', 'Email', 'Phone', 'Origin', 'Destination', 'Status', 'Service Type', 'Carrier' ) );
	foreach ( $posts as $p ) {
		$stage = (int) get_post_meta( $p->ID, '_dahim_ship_stage', true );
		fputcsv( $out, array(
			get_the_date( 'Y-m-d H:i', $p ),
			get_post_meta( $p->ID, '_dahim_tracking_number', true ),
			get_post_meta( $p->ID, '_dahim_ship_owner_name', true ),
			get_post_meta( $p->ID, '_dahim_ship_owner_email', true ),
			get_post_meta( $p->ID, '_dahim_ship_owner_phone', true ),
			get_post_meta( $p->ID, '_dahim_ship_origin', true ),
			get_post_meta( $p->ID, '_dahim_ship_destination', true ),
			dahim_shipment_stage_label( $stage ),
			get_post_meta( $p->ID, '_dahim_ship_service_type', true ),
			get_post_meta( $p->ID, '_dahim_ship_carrier', true ),
		) );
	}
	fclose( $out );
	exit;
}
add_action( 'admin_post_dahim_export_shipments_csv', 'dahim_handle_export_shipments_csv' );

/* ---------------------------------------------------------
 * 13. CSV IMPORT — bring in existing client Inquiries, Shipments,
 *     and Trade Lanes in bulk, matching the columns each Export
 *     CSV produces so round-tripping export → edit → import works.
 * ------------------------------------------------------- */
function dahim_import_page_types() {
	return array(
		'inquiry'    => 'Inquiries',
		'shipment'   => 'Shipments',
		'trade_lane' => 'Trade Lanes',
	);
}

function dahim_import_columns( $type ) {
	switch ( $type ) {
		case 'shipment':   return array( 'Tracking No.', 'Owner', 'Email', 'Phone', 'Origin', 'Destination', 'Status', 'Service Type', 'Carrier' );
		case 'inquiry':    return array( 'Name', 'Company', 'Email', 'Phone', 'Department', 'Service', 'Role Applied For', 'CV / Portfolio Link', 'Status', 'Message' );
		case 'trade_lane': return array( 'Title', 'Origin', 'Destination', 'Mode', 'Transit Time' );
	}
	return array();
}

// A hidden (not shown in the sidebar) admin page — reached only via the
// "Import CSV" button on each content type's list screen.
function dahim_register_import_page() {
	add_submenu_page( null, 'Import CSV', 'Import CSV', 'edit_others_posts', 'dahim-import-csv', 'dahim_import_csv_page_html' );
}
add_action( 'admin_menu', 'dahim_register_import_page' );

function dahim_import_csv_page_html() {
	$types = dahim_import_page_types();
	$type  = isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : '';
	if ( ! isset( $types[ $type ] ) ) {
		wp_die( 'Choose a valid content type to import.' );
	}
	?>
	<div class="wrap">
		<h1>Import <?php echo esc_html( $types[ $type ] ); ?> from CSV</h1>

		<?php if ( isset( $_GET['imported'] ) ) : ?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php echo (int) $_GET['imported']; ?> record(s) imported.
					<?php if ( ! empty( $_GET['skipped'] ) ) : ?>
						<?php echo (int) $_GET['skipped']; ?> row(s) skipped (missing a required column).
					<?php endif; ?>
				</p>
			</div>
		<?php endif; ?>

		<p>Upload a CSV file with a header row. Each row becomes a new <?php echo esc_html( strtolower( rtrim( $types[ $type ], 's' ) ) ); ?> record — this does not update or de-duplicate existing ones.</p>
		<p><strong>Expected columns:</strong> <code><?php echo esc_html( implode( ', ', dahim_import_columns( $type ) ) ); ?></code></p>

		<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="dahim_import_csv">
			<input type="hidden" name="dahim_import_type" value="<?php echo esc_attr( $type ); ?>">
			<?php wp_nonce_field( 'dahim_import_csv_' . $type, 'dahim_import_nonce' ); ?>
			<p><input type="file" name="dahim_csv_file" accept=".csv,text/csv" required></p>
			<p><button type="submit" class="button button-primary">Upload &amp; Import</button></p>
		</form>
	</div>
	<?php
}

function dahim_handle_csv_import() {
	if ( ! current_user_can( 'edit_others_posts' ) ) wp_die( 'You are not allowed to import content.' );

	$type = isset( $_POST['dahim_import_type'] ) ? sanitize_text_field( $_POST['dahim_import_type'] ) : '';
	if ( ! array_key_exists( $type, dahim_import_page_types() ) ) wp_die( 'Invalid import type.' );

	check_admin_referer( 'dahim_import_csv_' . $type, 'dahim_import_nonce' );

	if ( empty( $_FILES['dahim_csv_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['dahim_csv_file']['tmp_name'] ) ) {
		wp_die( 'No file was uploaded.' );
	}

	$handle = fopen( $_FILES['dahim_csv_file']['tmp_name'], 'r' );
	if ( ! $handle ) wp_die( 'Could not read the uploaded file.' );

	$header = fgetcsv( $handle );
	if ( ! $header ) wp_die( 'The file appears to be empty.' );
	$header = array_map( 'trim', $header );

	$imported = 0;
	$skipped  = 0;
	while ( ( $row = fgetcsv( $handle ) ) !== false ) {
		$data = array();
		foreach ( $header as $i => $col ) {
			$data[ $col ] = isset( $row[ $i ] ) ? trim( $row[ $i ] ) : '';
		}
		if ( dahim_import_row( $type, $data ) ) {
			$imported++;
		} else {
			$skipped++;
		}
	}
	fclose( $handle );

	$redirect = add_query_arg( array(
		'page'     => 'dahim-import-csv',
		'type'     => $type,
		'imported' => $imported,
		'skipped'  => $skipped,
	), admin_url( 'admin.php' ) );
	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'admin_post_dahim_import_csv', 'dahim_handle_csv_import' );

// Creates one record from one parsed CSV row. Returns true on success,
// false if the row was skipped (missing a required field).
function dahim_import_row( $type, $data ) {
	if ( $type === 'shipment' ) {
		$owner = isset( $data['Owner'] ) ? sanitize_text_field( $data['Owner'] ) : '';
		if ( ! $owner ) return false;

		$post_id = wp_insert_post( array( 'post_type' => 'shipment', 'post_status' => 'publish', 'post_title' => $owner ) );
		if ( is_wp_error( $post_id ) || ! $post_id ) return false;

		$tracking = isset( $data['Tracking No.'] ) ? sanitize_text_field( $data['Tracking No.'] ) : '';
		update_post_meta( $post_id, '_dahim_tracking_number', $tracking ? $tracking : dahim_generate_tracking_number() );
		update_post_meta( $post_id, '_dahim_ship_owner_name', $owner );
		update_post_meta( $post_id, '_dahim_ship_owner_email', sanitize_email( $data['Email'] ?? '' ) );
		update_post_meta( $post_id, '_dahim_ship_owner_phone', sanitize_text_field( $data['Phone'] ?? '' ) );
		update_post_meta( $post_id, '_dahim_ship_origin', sanitize_text_field( $data['Origin'] ?? '' ) );
		update_post_meta( $post_id, '_dahim_ship_destination', sanitize_text_field( $data['Destination'] ?? '' ) );
		update_post_meta( $post_id, '_dahim_ship_service_type', sanitize_text_field( $data['Service Type'] ?? '' ) );
		update_post_meta( $post_id, '_dahim_ship_carrier', sanitize_text_field( $data['Carrier'] ?? '' ) );

		$stage_label = isset( $data['Status'] ) ? $data['Status'] : '';
		$stage = 1;
		foreach ( dahim_shipment_stage_labels() as $num => $label ) {
			if ( strcasecmp( $label, $stage_label ) === 0 ) { $stage = $num; break; }
		}
		update_post_meta( $post_id, '_dahim_ship_stage', (string) $stage );
		// Treat imported (historical) shipments as already notified, so a
		// later edit doesn't suddenly email the customer a "created" notice.
		update_post_meta( $post_id, '_dahim_ship_email_sent', current_time( 'mysql' ) );
		return true;
	}

	if ( $type === 'inquiry' ) {
		$name = isset( $data['Name'] ) ? sanitize_text_field( $data['Name'] ) : '';
		if ( ! $name ) return false;

		$department = isset( $data['Department'] ) && in_array( $data['Department'], dahim_inquiry_departments(), true ) ? $data['Department'] : 'General Enquiry';
		$post_id = wp_insert_post( array( 'post_type' => 'inquiry', 'post_status' => 'publish', 'post_title' => $name . ' — ' . $department ) );
		if ( is_wp_error( $post_id ) || ! $post_id ) return false;

		update_post_meta( $post_id, '_dahim_inquiry_name', $name );
		update_post_meta( $post_id, '_dahim_inquiry_company', sanitize_text_field( $data['Company'] ?? '' ) );
		update_post_meta( $post_id, '_dahim_inquiry_email', sanitize_email( $data['Email'] ?? '' ) );
		update_post_meta( $post_id, '_dahim_inquiry_phone', sanitize_text_field( $data['Phone'] ?? '' ) );
		update_post_meta( $post_id, '_dahim_inquiry_department', $department );
		update_post_meta( $post_id, '_dahim_inquiry_service', sanitize_text_field( $data['Service'] ?? '' ) );
		update_post_meta( $post_id, '_dahim_inquiry_role', sanitize_text_field( $data['Role Applied For'] ?? '' ) );
		update_post_meta( $post_id, '_dahim_inquiry_cv_link', esc_url_raw( $data['CV / Portfolio Link'] ?? '' ) );
		update_post_meta( $post_id, '_dahim_inquiry_message', sanitize_textarea_field( $data['Message'] ?? '' ) );

		$status_key = strtolower( str_replace( ' ', '_', isset( $data['Status'] ) ? $data['Status'] : 'new' ) );
		update_post_meta( $post_id, '_dahim_inquiry_status', array_key_exists( $status_key, dahim_inquiry_statuses() ) ? $status_key : 'new' );
		// Imported inquiries were already handled elsewhere — mark them
		// already-notified so no automated "we got your message" logic fires.
		update_post_meta( $post_id, '_dahim_inquiry_imported', current_time( 'mysql' ) );
		return true;
	}

	if ( $type === 'trade_lane' ) {
		$origin      = isset( $data['Origin'] ) ? sanitize_text_field( $data['Origin'] ) : '';
		$destination = isset( $data['Destination'] ) ? sanitize_text_field( $data['Destination'] ) : '';
		$title       = isset( $data['Title'] ) && $data['Title'] ? sanitize_text_field( $data['Title'] ) : trim( $origin . ' → ' . $destination, " \t\n\r\0\x0B→" );
		if ( ! $title ) return false;

		$post_id = wp_insert_post( array( 'post_type' => 'trade_lane', 'post_status' => 'publish', 'post_title' => $title ) );
		if ( is_wp_error( $post_id ) || ! $post_id ) return false;

		update_post_meta( $post_id, '_dahim_lane_origin', $origin );
		update_post_meta( $post_id, '_dahim_lane_destination', $destination );
		update_post_meta( $post_id, '_dahim_lane_mode', sanitize_text_field( $data['Mode'] ?? '' ) );
		update_post_meta( $post_id, '_dahim_lane_transit', sanitize_text_field( $data['Transit Time'] ?? '' ) );
		return true;
	}

	return false;
}

/* ---------------------------------------------------------
 * DASHBOARD API — exposes Shipments, Inquiries, Trade Lanes,
 * Departments, and Jobs through WordPress's own REST API (rather
 * than a hand-rolled one) so the separate dashboard app can manage
 * this content without ever touching wp-admin. Posts (Insights) are
 * already REST-enabled by WordPress core — nothing extra needed there.
 *
 * AUTHENTICATION: standard WordPress cookie + nonce — the exact same
 * mechanism wp-admin's own JavaScript uses, not a custom scheme. An
 * earlier version of this used a hand-built token system specifically
 * to work around this host (LiteSpeed) stripping the Authorization
 * HTTP header, which broke WordPress's normal Application Passwords
 * flow. That workaround succeeded at its one job, but caused a much
 * bigger problem: because it deliberately never set a WordPress login
 * cookie, no caching layer on this host — including LiteSpeed Cache,
 * which is active here — had any way to recognize these requests as
 * personalized, and would cache and replay one person's authenticated
 * response to everyone hitting the same URL, or serve a stale response
 * from before a session even existed. That was the real, root cause
 * behind the "works right after login, breaks later" pattern.
 *
 * Cookie + nonce auth avoids that entirely: it's the exact signal
 * every caching plugin already knows to check for, and it doesn't
 * touch the Authorization header at all (nonces travel as an ordinary
 * custom header, or a request parameter — neither is subject to the
 * legacy special-casing that specifically affects Authorization).
 * ------------------------------------------------------- */

// Registers one meta field as visible/writable through the REST API.
// Every field here is underscore-prefixed ("protected" in WP's eyes), so
// without this it would stay invisible to the REST API even with
// show_in_rest on the post type itself.
function dahim_register_api_meta( $post_type, $meta_key, $type = 'string' ) {
	register_post_meta( $post_type, $meta_key, array(
		'type'          => $type,
		'single'        => true,
		'show_in_rest'  => true,
		'auth_callback' => function () {
			return current_user_can( 'edit_posts' );
		},
	) );
}

function dahim_register_all_api_meta() {
	foreach ( array(
		'_dahim_tracking_number', '_dahim_ship_owner_name', '_dahim_ship_owner_email', '_dahim_ship_owner_phone',
		'_dahim_ship_consignee_name', '_dahim_ship_consignee_phone', '_dahim_ship_origin', '_dahim_ship_destination',
		'_dahim_ship_current_location', '_dahim_ship_package_description', '_dahim_ship_weight', '_dahim_ship_pieces',
		'_dahim_ship_dimensions', '_dahim_ship_declared_value', '_dahim_ship_service_type', '_dahim_ship_carrier',
		'_dahim_ship_date_booked', '_dahim_ship_estimated_delivery', '_dahim_ship_special_instructions', '_dahim_ship_stage',
	) as $key ) dahim_register_api_meta( 'shipment', $key );

	foreach ( array(
		'_dahim_inquiry_name', '_dahim_inquiry_company', '_dahim_inquiry_phone', '_dahim_inquiry_email',
		'_dahim_inquiry_service', '_dahim_inquiry_role', '_dahim_inquiry_cv_link', '_dahim_inquiry_message',
		'_dahim_inquiry_department', '_dahim_inquiry_status',
	) as $key ) dahim_register_api_meta( 'inquiry', $key );

	foreach ( array( '_dahim_lane_origin', '_dahim_lane_destination', '_dahim_lane_mode', '_dahim_lane_transit' ) as $key ) {
		dahim_register_api_meta( 'trade_lane', $key );
	}

	foreach ( array(
		'_dahim_dept_description', '_dahim_dept_icon', '_dahim_dept_link_text', '_dahim_dept_external_url',
		'_dahim_dept_eyebrow', '_dahim_dept_heading', '_dahim_dept_message_label', '_dahim_dept_message_placeholder',
		'_dahim_dept_submit_label', '_dahim_dept_show_company', '_dahim_dept_show_service', '_dahim_dept_show_role_cv',
		'_dahim_dept_notify_email', '_dahim_dept_confirm_subject', '_dahim_dept_confirm_heading', '_dahim_dept_confirm_message',
	) as $key ) dahim_register_api_meta( 'department', $key );

	foreach ( array( '_dahim_job_location', '_dahim_job_type', '_dahim_job_deadline', '_dahim_job_status' ) as $key ) {
		dahim_register_api_meta( 'job', $key );
	}

	// Team Member role/title is stored in post meta and must be exposed to the dashboard REST API.
	dahim_register_api_meta( 'team_member', '_dahim_role' );
}
add_action( 'init', 'dahim_register_all_api_meta', 20 ); // after CPTs register at the default priority

// The dashboard app lives on this same domain (a subfolder, not a
// subdomain), so it's same-origin and needs no CORS allowance at all.
// This just closes the door on cross-origin API access entirely, since
// nothing legitimate needs it — WordPress's own default REST CORS
// handling (which otherwise reflects any origin back) is removed rather
// than reconfigured.
add_action( 'rest_api_init', function () {
	remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
}, 15 );

// Defense in depth, kept even after moving to cookie auth (which caching
// plugins should now recognize on their own): explicitly forces every
// /wp-json/ response to bypass any caching layer regardless.
add_action( 'rest_api_init', function () {
	if ( function_exists( 'do_action' ) ) {
		do_action( 'litespeed_control_set_nocache', 'Dahim dashboard API — per-request personalized data' );
	}
}, 1 );
add_filter( 'rest_pre_serve_request', function ( $served ) {
	if ( ! headers_sent() ) {
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Pragma: no-cache' );
	}
	return $served;
}, 1 );

// --- Let this dashboard's session bootstrap endpoint through WordPress's
// cookie-nonce gate before core clears the valid logged-in user. ---
// The static dashboard must ask whether a cookie session exists before it
// can possibly know the nonce for that session. Returning true at priority
// 99 tells WordPress that this one request is already authenticated, so its
// default nonce check at priority 100 does not reset the current user.
add_filter( 'rest_authentication_errors', function ( $result ) {
	if ( ! empty( $result ) ) return $result;
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
	if ( strpos( $uri, 'dahim/v1/auth/me' ) !== false && is_user_logged_in() ) {
		return true;
	}
	return $result;
}, 99 );

// --- Let this dashboard's remaining bootstrap endpoints through WordPress's
// cookie-nonce gate, since each already does its own correct auth check. ---
// WordPress requires a valid X-WP-Nonce on every REST request once a login
// cookie is present (rest_cookie_check_errors(), built into core) — normal
// wp-admin pages satisfy this by having PHP embed a fresh nonce directly
// into the page's HTML at render time. This dashboard is a static-built
// single-page app with no PHP-rendered page to embed one into, so its very
// first request on load — asking "is there already a valid session from an
// earlier visit?" — has no nonce to send yet by definition; that's the
// question being asked. Without this, WordPress's own gate would reject
// that very check outright for anyone with an existing valid cookie,
// before dahim_api_me() ever got a chance to answer it correctly itself.
// Login gets the same treatment because it re-authenticates fully from
// scratch. Registration, forgot-password,
// and reset-password are included too — each only makes sense for someone
// who may not have a valid session at all, so none of them should ever
// depend on one already existing.
add_filter( 'rest_authentication_errors', function ( $result ) {
	if ( ! is_wp_error( $result ) || $result->get_error_code() !== 'rest_cookie_invalid_nonce' ) {
		return $result;
	}
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
	foreach ( array(
		'dahim/v1/auth/login',
		'dahim/v1/auth/register', 'dahim/v1/auth/forgot-password', 'dahim/v1/auth/reset-password',
	) as $bootstrap_route ) {
		if ( strpos( $uri, $bootstrap_route ) !== false ) {
			return null; // let the request through; the endpoint's own logic handles auth correctly
		}
	}
	return $result;
}, 101 ); // after WordPress's own check (rest_cookie_check_errors runs at priority 100)

function dahim_register_dashboard_auth_routes() {
	register_rest_route( 'dahim/v1', '/auth/login', array(
		'methods'             => 'POST',
		'callback'            => 'dahim_api_login',
		'permission_callback' => 'dahim_dashboard_same_origin_post_permission',
	) );
	register_rest_route( 'dahim/v1', '/auth/logout', array(
		'methods'             => 'POST',
		'callback'            => 'dahim_api_logout',
		'permission_callback' => function () { return is_user_logged_in(); },
	) );
	register_rest_route( 'dahim/v1', '/auth/me', array(
		'methods'             => 'GET',
		'callback'            => 'dahim_api_me',
		'permission_callback' => '__return_true', // does its own is_user_logged_in() check
	) );
	register_rest_route( 'dahim/v1', '/auth/register', array(
		'methods'             => 'POST',
		'callback'            => 'dahim_api_register_account',
		'permission_callback' => 'dahim_dashboard_same_origin_post_permission',
	) );
	register_rest_route( 'dahim/v1', '/auth/forgot-password', array(
		'methods'             => 'POST',
		'callback'            => 'dahim_api_forgot_password',
		'permission_callback' => 'dahim_dashboard_same_origin_post_permission',
	) );
	register_rest_route( 'dahim/v1', '/auth/reset-password', array(
		'methods'             => 'POST',
		'callback'            => 'dahim_api_reset_password',
		'permission_callback' => 'dahim_dashboard_same_origin_post_permission',
	) );
}
add_action( 'rest_api_init', 'dahim_register_dashboard_auth_routes' );

// The dashboard is a same-origin application. Its unauthenticated POST
// endpoints intentionally cannot use a WordPress nonce, so require the
// browser-supplied Origin header instead. This prevents a third-party form
// from creating accounts, sending reset mail, or logging a visitor into an
// attacker-controlled account.
function dahim_dashboard_same_origin_post_permission( WP_REST_Request $request ) {
	$origin = untrailingslashit( $request->get_header( 'origin' ) );
	$site   = wp_parse_url( home_url( '/' ) );
	if ( empty( $site['scheme'] ) || empty( $site['host'] ) || ! $origin ) {
		return new WP_Error( 'dahim_invalid_origin', 'This request must come from the dashboard.', array( 'status' => 403 ) );
	}
	$expected = $site['scheme'] . '://' . $site['host'] . ( isset( $site['port'] ) ? ':' . $site['port'] : '' );
	if ( ! hash_equals( $expected, $origin ) ) {
		return new WP_Error( 'dahim_invalid_origin', 'This request must come from the dashboard.', array( 'status' => 403 ) );
	}
	return true;
}

// --- Sign in with a real WordPress login (wp_signon — the same function
// wp-login.php itself calls), setting a normal WordPress auth cookie.
// Returns a REST nonce alongside the user's basic info; the dashboard
// sends that nonce on every request afterward via the X-WP-Nonce header,
// exactly like wp-admin's own scripts do. ---
function dahim_api_login( WP_REST_Request $request ) {
	$username = sanitize_user( (string) $request->get_param( 'username' ) );
	$password = (string) $request->get_param( 'password' );
	if ( ! $username || ! $password ) {
		return new WP_Error( 'dahim_missing_credentials', 'Username and password are required.', array( 'status' => 400 ) );
	}

	$user = wp_signon( array(
		'user_login'    => $username,
		'user_password' => $password,
		'remember'      => true,
	), is_ssl() );

	if ( is_wp_error( $user ) ) {
		return new WP_Error( 'dahim_invalid_credentials', 'Invalid username or password.', array( 'status' => 401 ) );
	}

	if ( dahim_dashboard_account_status( $user->ID ) === 'pending' ) {
		wp_logout(); // don't leave a working session behind for an account that isn't approved yet
		return new WP_Error( 'dahim_pending_approval', 'Your account is still awaiting admin approval.', array( 'status' => 403 ) );
	}

	wp_set_current_user( $user->ID );

	return rest_ensure_response( array(
		'nonce' => wp_create_nonce( 'wp_rest' ),
		'user'  => array(
			'id'       => $user->ID,
			'name'     => $user->display_name,
			'username' => $user->user_login,
		),
	) );
}

// Clears the real WordPress session — the same effect as using the
// "Log Out" link anywhere else in WordPress.
function dahim_api_logout() {
	wp_logout();
	return rest_ensure_response( array( 'loggedOut' => true ) );
}

// Called once when the dashboard app first loads (and nowhere else) to
// find out whether the browser's existing WordPress cookie is still
// valid, and to get a fresh nonce for this session — nonces are shorter-
// lived than the login cookie itself, so re-fetching one on every fresh
// app load keeps a long-open browser tab from running into a stale one.
function dahim_api_me() {
	if ( ! is_user_logged_in() ) {
		return new WP_Error( 'dahim_not_logged_in', 'Not signed in.', array( 'status' => 401 ) );
	}
	$user = wp_get_current_user();
	if ( dahim_dashboard_account_status( $user->ID ) === 'pending' ) {
		return new WP_Error( 'dahim_pending_approval', 'Your account is still awaiting admin approval.', array( 'status' => 403 ) );
	}
	return rest_ensure_response( array(
		'nonce' => wp_create_nonce( 'wp_rest' ),
		'user'  => array(
			'id'       => $user->ID,
			'name'     => $user->display_name,
			'username' => $user->user_login,
		),
	) );
}

/* Self-registration — creates the account as a Subscriber (WordPress's
 * lowest, no-access role), so a new signup can authenticate but can't
 * actually read or write any Shipments/Inquiries/etc. until an
 * Administrator manually promotes their role in wp-admin → Users. That
 * promotion IS the "approval" — there's no separate pending/approved flag
 * to manage, the account is simply harmless until someone deliberately
 * elevates it. */
function dahim_api_register_account( WP_REST_Request $request ) {
	$username = sanitize_user( (string) $request->get_param( 'username' ) );
	$email    = sanitize_email( (string) $request->get_param( 'email' ) );
	$password = (string) $request->get_param( 'password' );

	if ( ! $username || ! $email || ! $password ) {
		return new WP_Error( 'dahim_missing_fields', 'Username, email, and password are all required.', array( 'status' => 400 ) );
	}
	if ( ! is_email( $email ) ) {
		return new WP_Error( 'dahim_invalid_email', 'Enter a valid email address.', array( 'status' => 400 ) );
	}
	if ( strlen( $password ) < 8 ) {
		return new WP_Error( 'dahim_weak_password', 'Password must be at least 8 characters.', array( 'status' => 400 ) );
	}
	if ( username_exists( $username ) ) {
		return new WP_Error( 'dahim_username_taken', 'That username is already taken.', array( 'status' => 409 ) );
	}
	if ( email_exists( $email ) ) {
		return new WP_Error( 'dahim_email_taken', 'That email is already registered.', array( 'status' => 409 ) );
	}

	$user_id = wp_insert_user( array(
		'user_login' => $username,
		'user_email' => $email,
		'user_pass'  => $password,
		'role'       => 'subscriber',
	) );
	if ( is_wp_error( $user_id ) ) {
		return new WP_Error( 'dahim_registration_failed', $user_id->get_error_message(), array( 'status' => 500 ) );
	}

	update_user_meta( $user_id, '_dahim_dashboard_status', 'pending' );
	dahim_send_dashboard_account_requested_email( $email, $username );
	dahim_notify_admin_of_new_signup( $user_id, $username, $email );

	return rest_ensure_response( array( 'registered' => true ) );
}

/* "Forgot password" resets the person's real WordPress password (not a
 * separate shadow password), using WordPress core's own secure reset-key
 * mechanism under the hood — this endpoint just wraps it with our branded
 * email and lets the whole flow live inside the dashboard app instead of
 * sending people out to wp-login.php. */
function dahim_api_forgot_password( WP_REST_Request $request ) {
	$identifier = sanitize_text_field( (string) $request->get_param( 'username' ) );
	if ( ! $identifier ) {
		return new WP_Error( 'dahim_missing_identifier', 'Enter your username or email.', array( 'status' => 400 ) );
	}

	$user = is_email( $identifier ) ? get_user_by( 'email', $identifier ) : get_user_by( 'login', $identifier );

	// Always respond success either way — never reveal whether an account exists.
	if ( $user ) {
		$key = get_password_reset_key( $user );
		if ( ! is_wp_error( $key ) ) {
			dahim_send_password_reset_email( $user, $key );
		}
	}

	return rest_ensure_response( array( 'requested' => true ) );
}

function dahim_api_reset_password( WP_REST_Request $request ) {
	$username = sanitize_text_field( (string) $request->get_param( 'username' ) );
	$key      = (string) $request->get_param( 'key' );
	$password = (string) $request->get_param( 'password' );

	if ( strlen( $password ) < 8 ) {
		return new WP_Error( 'dahim_weak_password', 'Password must be at least 8 characters.', array( 'status' => 400 ) );
	}

	$user = check_password_reset_key( $key, $username );
	if ( is_wp_error( $user ) ) {
		return new WP_Error( 'dahim_invalid_token', 'This reset link is invalid or has expired. Request a new one.', array( 'status' => 400 ) );
	}

	// NOTE: wp-login.php's own reset_password() wrapper isn't available here
	// (that file isn't loaded during a REST request) — wp_set_password() is
	// the real underlying primitive it calls internally, and is always
	// available. wp_password_change_notification() is called explicitly
	// right after, matching what that wrapper does, so the (now branded,
	// see the wp_password_change_notification_email filter below) "your
	// password was changed" notice still goes out exactly once.
	wp_set_password( $password, $user->ID );
	wp_password_change_notification( $user );

	return rest_ensure_response( array( 'reset' => true ) );
}

function dahim_notify_admin_of_new_signup( $user_id, $username, $email ) {
	$to = get_option( 'dahim_email', get_option( 'admin_email' ) );
	if ( ! is_email( $to ) ) return;

	$subject     = "New dashboard account request — {$username}";
	$approve_url = admin_url( 'user-edit.php?user_id=' . $user_id );

	$body  = '<p style="margin:0 0 16px;">Someone just created a dashboard account and is waiting on your approval.</p>';
	$body .= dahim_email_detail_line( 'Username', $username );
	$body .= dahim_email_detail_line( 'Email', $email );
	$body .= '<p style="margin:16px 0;font-size:13.5px;color:#4C5A78;">They can\'t sign in yet. To approve them, open their profile and change their role from Subscriber to Editor (or Administrator) — that role change is what grants access.</p>';
	$body .= dahim_email_button( $approve_url, 'Review This Account' );

	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	wp_mail( $to, $subject, dahim_email_wrap( "New signup: {$username} ({$email})", $body, 'New Dashboard Account Request' ), $headers );
}

/* Confirms to the person who just registered that their request is in —
 * and, since we can't yet say whether they're logged in, sets the right
 * expectation instead of leaving them wondering why sign-in doesn't work. */
function dahim_send_dashboard_account_requested_email( $to, $username ) {
	if ( ! is_email( $to ) ) return;

	$subject = 'Your dashboard access request has been received';
	$body  = '<p style="margin:0 0 16px;">Thanks for signing up. Your account has been created, but it needs to be approved by an administrator before you can sign in.</p>';
	$body .= dahim_email_detail_line( 'Username', $username );
	$body .= '<p style="margin:16px 0 0;font-size:13.5px;color:#4C5A78;">We\'ll email you the moment you\'re approved — usually within one business day.</p>';

	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	wp_mail( $to, $subject, dahim_email_wrap( 'Your account is pending approval.', $body, 'Access Request Received' ), $headers );
}

/* Sent the moment an admin approves a pending account (by assigning it a
 * real role) — lets them know they can sign in now instead of having to
 * guess and try again. */
function dahim_send_dashboard_account_approved_email( $user ) {
	if ( ! is_email( $user->user_email ) ) return;

	$subject   = 'Your dashboard account is ready';
	$login_url = home_url( '/dashboard/' );

	$body  = '<p style="margin:0 0 16px;">Good news — your dashboard account has been approved. You can sign in now.</p>';
	$body .= dahim_email_detail_line( 'Username', $user->user_login );
	$body .= dahim_email_button( $login_url, 'Sign In' );

	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	wp_mail( $user->user_email, $subject, dahim_email_wrap( "You're approved — sign in whenever you're ready.", $body, 'Account Approved' ), $headers );
}

/* The actual password-reset link email — key/username are WordPress
 * core's own secure reset tokens, just wrapped in our branded template
 * and pointed at the dashboard app instead of wp-login.php. */
function dahim_send_password_reset_email( $user, $key ) {
	if ( ! is_email( $user->user_email ) ) return;

	$reset_url = add_query_arg( array(
		'username' => rawurlencode( $user->user_login ),
		'key'      => rawurlencode( $key ),
	), home_url( '/dashboard/reset-password' ) );

	$subject = 'Reset your dashboard password';
	$body  = '<p style="margin:0 0 16px;">We received a request to reset the password for your account (' . esc_html( $user->user_login ) . ').</p>';
	$body .= dahim_email_button( $reset_url, 'Reset Password' );
	$body .= '<p style="margin:20px 0 0;font-size:13.5px;color:#4C5A78;">This link expires in 24 hours. If you didn\'t request this, you can safely ignore this email — your password won\'t be changed.</p>';

	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	wp_mail( $user->user_email, $subject, dahim_email_wrap( 'This link expires in 24 hours.', $body, 'Reset Your Password' ), $headers );
}

/* Brands WordPress's own built-in "password changed" notification (fired
 * by wp_password_change_notification() — called both by our reset flow
 * above and by WordPress's native wp-login.php one) instead of a separate
 * custom function, so this is covered no matter which path triggered it. */
add_filter( 'wp_password_change_notification_email', function ( $email, $user, $blogname ) {
	$body  = '<p style="margin:0 0 16px;">The password for your account (' . esc_html( $user->user_login ) . ') was just changed.</p>';
	$body .= '<p style="margin:0;font-size:13.5px;color:#4C5A78;">If this wasn\'t you, please contact us immediately.</p>';

	$email['subject'] = 'Your password was changed';
	$email['message'] = dahim_email_wrap( "If this wasn't you, contact us right away.", $body, 'Password Changed' );
	$email['headers'] = array( 'Content-Type: text/html; charset=UTF-8' );
	return $email;
}, 10, 3 );

/* Brands WordPress's own native password-reset-request email too (the one
 * wp-login.php's "Lost your password?" sends) — using the classic filter
 * pair WordPress has exposed for this since long before the newer combined
 * array filter existed, so it's covered on any WP version. This is
 * separate from dahim_send_password_reset_email() above, which is what
 * fires for OUR OWN dashboard forgot-password flow specifically; this one
 * catches anyone using the built-in wp-login.php flow directly instead. */
function dahim_native_reset_email_flag( $set = null ) {
	static $flag = false;
	if ( $set !== null ) $flag = $set;
	return $flag;
}
add_filter( 'retrieve_password_title', function ( $title ) {
	dahim_native_reset_email_flag( true );
	return 'Reset your password';
} );
add_filter( 'retrieve_password_message', function ( $message, $key, $user_login, $user_data ) {
	$reset_url = add_query_arg( array(
		'username' => rawurlencode( $user_login ),
		'key'      => rawurlencode( $key ),
	), home_url( '/dashboard/reset-password' ) );

	$body  = '<p style="margin:0 0 16px;">We received a request to reset the password for your account (' . esc_html( $user_login ) . ').</p>';
	$body .= dahim_email_button( $reset_url, 'Reset Password' );
	$body .= '<p style="margin:20px 0 0;font-size:13.5px;color:#4C5A78;">If you didn\'t request this, you can safely ignore this email — your password won\'t be changed.</p>';

	return dahim_email_wrap( "If you didn't request this, you can ignore it.", $body, 'Reset Your Password' );
}, 10, 4 );
// The two filters above run inside the same request as the eventual
// wp_mail() call for this specific email — this flags it as HTML only for
// that one send, without changing the content type of any other email.
add_filter( 'wp_mail', function ( $args ) {
	if ( dahim_native_reset_email_flag() ) {
		$args['headers'] = array( 'Content-Type: text/html; charset=UTF-8' );
		dahim_native_reset_email_flag( false );
	}
	return $args;
} );

// '' = not a self-registered account (always allowed), 'pending' = awaiting
// admin approval, 'approved' = self-registered and cleared to sign in.
function dahim_dashboard_account_status( $user_id ) {
	return get_user_meta( $user_id, '_dahim_dashboard_status', true ) ?: '';
}

// "Promotion is the approval": the moment an admin gives a self-registered
// account any real role (Editor, Administrator, etc. — anything but the
// harmless default Subscriber), treat that as approval automatically,
// rather than requiring a separate "Approve" click on top of the role
// change an admin was going to make anyway.
add_action( 'set_user_role', function ( $user_id, $role, $old_roles ) {
	if ( $role === 'subscriber' ) return;
	if ( dahim_dashboard_account_status( $user_id ) !== 'pending' ) return;
	update_user_meta( $user_id, '_dahim_dashboard_status', 'approved' );
	$user = get_userdata( $user_id );
	if ( $user ) dahim_send_dashboard_account_approved_email( $user );
}, 10, 3 );

// Quick-glance "Pending / Approved" indicator on the Users list — approving
// is just changing their role above, this is only a status readout.
add_filter( 'manage_users_columns', function ( $columns ) {
	$columns['dahim_dashboard'] = 'Dashboard Access';
	return $columns;
} );
add_filter( 'manage_users_custom_column', function ( $value, $column, $user_id ) {
	if ( $column !== 'dahim_dashboard' ) return $value;
	$status = dahim_dashboard_account_status( $user_id );
	if ( $status === 'pending' ) return '<span style="color:#C79B3C;font-weight:600;">Pending</span>';
	if ( $status === 'approved' ) return '<span style="color:#008751;font-weight:600;">Approved</span>';
	return '—';
}, 10, 3 );

function dahim_register_contact_settings_routes() {
	register_rest_route( 'dahim/v1', '/contact-settings', array(
		array(
			'methods'             => 'GET',
			'callback'            => 'dahim_api_get_contact_settings',
			'permission_callback' => function () { return current_user_can( 'manage_options' ); },
		),
		array(
			'methods'             => 'POST',
			'callback'            => 'dahim_api_update_contact_settings',
			'permission_callback' => function () { return current_user_can( 'manage_options' ); },
		),
	) );
}
add_action( 'rest_api_init', 'dahim_register_contact_settings_routes' );

function dahim_api_get_contact_settings() {
	return rest_ensure_response( array(
		'phone'     => get_option( 'dahim_phone', '' ),
		'phone2'    => get_option( 'dahim_phone2', '' ),
		'email'     => get_option( 'dahim_email', '' ),
		'email_ops' => get_option( 'dahim_email_ops', '' ),
		'address'   => get_option( 'dahim_address', '' ),
		'whatsapp'  => get_option( 'dahim_whatsapp', '' ),
	) );
}

function dahim_api_update_contact_settings( WP_REST_Request $request ) {
	$fields = array( 'phone', 'phone2', 'email', 'email_ops', 'address', 'whatsapp' );
	foreach ( $fields as $field ) {
		if ( $request->has_param( $field ) ) {
			$value = $request->get_param( $field );
			$value = in_array( $field, array( 'email', 'email_ops' ), true ) ? sanitize_email( $value ) : sanitize_textarea_field( $value );
			update_option( 'dahim_' . $field, $value );
		}
	}
	return dahim_api_get_contact_settings();
}

// Rewrite rules (Jobs has a public URL structure) need regenerating once
// on activation, same reason the old in-theme version needed its one-time
// flush — this makes that automatic instead of requiring a manual trip to
// Settings → Permalinks after installing.
register_activation_hook( __FILE__, function () {
	// CPTs registered on 'init' haven't run yet during the activation hook
	// itself, so trigger that first or the new rewrite rules wouldn't
	// exist yet to flush.
	do_action( 'init' );
	flush_rewrite_rules();
} );
register_deactivation_hook( __FILE__, function () {
	flush_rewrite_rules();
} );
