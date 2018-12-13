<?php

/**
 * Trigger this file on plugin uninstall
 * 
 * @package NDSSPaperImportPlugin
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

// Clear database stored data

$papers = get_posts( array( 'post_type' => 'ndss-paper', 'numberposts' => -1 ) );

foreach( $papers as $paper ) {
    wp_delete_post( $paper->ID, false );
}