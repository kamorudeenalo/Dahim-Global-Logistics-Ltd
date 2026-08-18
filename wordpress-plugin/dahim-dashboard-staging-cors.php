<?php
/**
 * Plugin Name: Dahim Dashboard — Staging CORS
 * Description: Allows the dedicated staging Dashboard subdomain to use the Dahim WordPress REST API with credentialed WordPress cookie authentication. Staging-only configuration.
 * Version: 1.0.0
 * Author: Dahim Global Logistics
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Exact allowlist. Do not use '*' because credentialed requests cannot use
 * wildcard origins and doing so would be unsafe for an authenticated API.
 */
function dahim_staging_dashboard_origin() {
	return 'https://dashboard-staging.technophilesdigital.com';
}

function dahim_staging_dashboard_is_allowed_origin() {
	$origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? trim( (string) $_SERVER['HTTP_ORIGIN'] ) : '';
	return $origin !== '' && hash_equals( dahim_staging_dashboard_origin(), $origin );
}

/**
 * Add CORS headers to REST responses. Credentials are explicitly enabled so
 * the browser can send the WordPress authentication cookie to staging.
 */
function dahim_staging_dashboard_cors_headers( $served, $result, $request, $server ) {
	if ( ! dahim_staging_dashboard_is_allowed_origin() ) return $served;

	$origin = dahim_staging_dashboard_origin();
	header( 'Access-Control-Allow-Origin: ' . $origin );
	header( 'Access-Control-Allow-Credentials: true' );
	header( 'Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS' );
	header( 'Access-Control-Allow-Headers: Content-Type, X-WP-Nonce' );
	header( 'Access-Control-Expose-Headers: X-WP-Total, X-WP-TotalPages, Link' );
	header( 'Vary: Origin', false );

	return $served;
}
add_filter( 'rest_pre_serve_request', 'dahim_staging_dashboard_cors_headers', 10, 4 );

/**
 * Handle browser preflight requests before WordPress tries to authenticate
 * the request. No WordPress credentials are required for OPTIONS.
 */
function dahim_staging_dashboard_preflight( $result, $server, $request ) {
	if ( 'OPTIONS' !== strtoupper( $request->get_method() ) || ! dahim_staging_dashboard_is_allowed_origin() ) {
		return $result;
	}

	return new WP_REST_Response( null, 200 );
}
add_filter( 'rest_pre_dispatch', 'dahim_staging_dashboard_preflight', 10, 3 );

/**
 * Send the same CORS headers for a preflight response. WordPress's REST
 * response filter is normally sufficient, but this early header hook makes
 * the behaviour explicit for OPTIONS responses on different hosting stacks.
 */
function dahim_staging_dashboard_preflight_headers() {
	if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) return;
	if ( 'OPTIONS' !== strtoupper( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) return;
	if ( ! dahim_staging_dashboard_is_allowed_origin() ) return;

	$origin = dahim_staging_dashboard_origin();
	header( 'Access-Control-Allow-Origin: ' . $origin );
	header( 'Access-Control-Allow-Credentials: true' );
	header( 'Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS' );
	header( 'Access-Control-Allow-Headers: Content-Type, X-WP-Nonce' );
	header( 'Access-Control-Max-Age: 600' );
	header( 'Vary: Origin', false );
}
add_action( 'rest_api_init', 'dahim_staging_dashboard_preflight_headers', 1 );
