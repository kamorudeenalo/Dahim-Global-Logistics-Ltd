<?php
/**
 * Plugin Name: Dahim Insights SEO Fields
 * Description: Registers the SEO metadata used by the custom Dahim Insights editor and exposes it through the WordPress REST API.
 * Version: 1.0.0
 * Author: Dahim Global Logistics
 * Text Domain: dahim-insights-seo
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', function () {
    $fields = array(
        'dahim_focus_keyword' => array(
            'type' => 'string',
            'description' => 'Focus keyword for the Dahim Insights SEO panel.',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ),
        'dahim_seo_title' => array(
            'type' => 'string',
            'description' => 'Custom SEO title for a Dahim Insight.',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ),
        'dahim_meta_description' => array(
            'type' => 'string',
            'description' => 'Custom meta description for a Dahim Insight.',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => '',
        ),
    );

    foreach ( $fields as $key => $args ) {
        register_post_meta( 'post', $key, array_merge(
            $args,
            array(
                'single' => true,
                'show_in_rest' => true,
                'auth_callback' => function () {
                    return current_user_can( 'edit_posts' );
                },
            )
        ) );
    }
} );
