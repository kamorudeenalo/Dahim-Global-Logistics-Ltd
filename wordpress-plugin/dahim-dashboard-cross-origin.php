<?php
/**
 * Plugin Name: Dahim Dashboard — Cross-Origin Bridge
 * Description: Allows the Dahim Dashboard subdomain to use the WordPress REST API with cookie authentication while keeping the origin allow-list explicit.
 * Version: 1.0.0
 * Author: Dahim Global Logistics
 * Text Domain: dahim-dashboard-cross-origin
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The dashboard is intentionally hosted on a separate subdomain from the
 * WordPress site. Browser requests therefore need CORS headers, but we must
 * never use a wildcard origin because authentication uses cookies.
 */
function dahim_dashboard_allowed_origins() {
	return array(
		'https://dashboard-staging.technophilesdigital.com',
		'https://dashboard.technophilesdigital.com',
	);
}

function dahim_dashboard_request_origin() {
	$origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? trim( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ) : '';
	return untrailingslashit( esc_url_raw( $origin ) );
}

function dahim_dashboard_is_allowed_origin( $origin = null ) {
	$origin = null === $origin ? dahim_dashboard_request_origin() : untrailingslashit( (string) $origin );
	return $origin && in_array( $origin, dahim_dashboard_allowed_origins(), true );
}

/**
 * Replace the old same-origin permission callback used by the dashboard
 * plugin's unauthenticated auth POST endpoints. The endpoint callbacks still
 * perform their own validation; this only changes the trusted browser origin.
 */
function dahim_dashboard_cross_origin_auth_permissions( $endpoints ) {
	$auth_routes = array(
		'/dahim/v1/auth/login',
		'/dahim/v1/auth/register',
		'/dahim/v1/auth/forgot-password',
		'/dahim/v1/auth/reset-password',
	);

	foreach ( $auth_routes as $route ) {
		if ( empty( $endpoints[ $route ] ) ) continue;

		foreach ( $endpoints[ $route ] as $index => $endpoint ) {
			$methods = isset( $endpoint['methods'] ) ? $endpoint['methods'] : array();
			if ( is_string( $methods ) ) {
				$methods = array_map( 'trim', explode( ',', $methods ) );
			}
			if ( ! isset( $methods['POST'] ) && ! in_array( 'POST', $methods, true ) ) continue;

			$endpoints[ $route ][ $index ]['permission_callback'] = 'dahim_dashboard_cross_origin_auth_permission';
		}
	}

	return $endpoints;
}
add_filter( 'rest_endpoints', 'dahim_dashboard_cross_origin_auth_permissions', 20 );

function dahim_dashboard_cross_origin_auth_permission( WP_REST_Request $request ) {
	if ( dahim_dashboard_is_allowed_origin() ) return true;

	return new WP_Error(
		'dahim_invalid_origin',
		'This request must come from the Dahim Dashboard.',
		array( 'status' => 403 )
	);
}

/**
 * Replace WordPress's permissive default REST CORS response with a strict
 * allow-list. This is required for credentialed browser requests.
 */
function dahim_dashboard_send_cors_headers( $served ) {
	$origin = dahim_dashboard_request_origin();

	if ( ! dahim_dashboard_is_allowed_origin( $origin ) ) return $served;

	header( 'Access-Control-Allow-Origin: ' . $origin );
	header( 'Access-Control-Allow-Credentials: true' );
	header( 'Access-Control-Allow-Methods: OPTIONS, GET, POST, PUT, PATCH, DELETE' );
	header( 'Access-Control-Allow-Headers: Authorization, X-WP-Nonce, Content-Disposition, Content-MD5, Content-Type' );
	header( 'Vary: Origin', false );

	return $served;
}

function dahim_dashboard_register_cors_filter() {
	remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
	add_filter( 'rest_pre_serve_request', 'dahim_dashboard_send_cors_headers', 10 );
}
add_action( 'rest_api_init', 'dahim_dashboard_register_cors_filter', 15 );
