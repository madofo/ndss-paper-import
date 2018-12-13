<?php

/** 
 * @package NDSSPaperImportPlugin
 */

/*
Plugin Name: NDSS Paper Import Plugin
Plugin URI: github repo goes here
Description: This plugin will import json data exported from HotCRP to NDSS Paper post types.
Author: Mat Ford
Version: 1.0.0
Author URI: https://
License: GPLv2 or later
Text Domain: ndss-paper-import
 */

if ( ! defined( 'ABSPATH' ) ) {
    die;
}

// class NDSSPaperImportPlugin
// {
//     function activate() {
//         flush_rewrite_rules();
//     }

//     function deactivate() {
//         flush_rewrite_rules();
//     }

// }

// if ( class_exists( 'NDSSPaperImportPlugin' ) ) {
//     $ndssPaperImportPlugin = new NDSSPaperImportPlugin();
// }

// // activation
// register_activation_hook( __FILE__, array( $ndssPaperImportPlugin, 'activate' ) );

// // deactivation
// register_deactivation_hook( __FILE__, array( $ndssPaperImportPlugin, 'deactivate' ) );

add_action('admin_menu', 'ndss_button_menu');

function ndss_button_menu() {
    add_menu_page('NDSS Button Page', 'NDSS', 'manage_options', 'ndss-button-slug', 'ndss_button_admin_page');
}

function ndss_button_admin_page() {

    // This function creates the output for the admin page.
    // It also checks the value of the $_POST variable to see whether
    // there has been a form submission. 

    // The check_admin_referer is a WordPress function that does some security
    // checking and is recommended good practice.

    // General check for user permissions.
    if (!current_user_can('manage_options'))  {
        wp_die( __('You do not have sufficient pilchards to access this page.')    );
    }

    // Start building the page

    echo '<div class="wrap">';

    echo '<h2>NDSS Paper Import Plugin</h2>';

    echo '<p>This will import json-formatted HotCRP output to NDSS Paper posts.</p>';

    // Check whether the button has been pressed AND also check the nonce
    if (isset($_POST['ndss_button']) && check_admin_referer('ndss_button_clicked')) {
    // the button has been pressed AND we've passed the security check
        ndss_button_action();
    }

    echo '<form action="options-general.php?page=ndss-button-slug" method="post">';

    // this is a WordPress security feature - see: https://codex.wordpress.org/WordPress_Nonces
    wp_nonce_field('ndss_button_clicked');
    echo '<input type="hidden" value="true" name="ndss_button" />';
    submit_button('Process NDSS papers');
    echo '</form>';

    echo '</div>';

}

function ndss_button_action() {
  
    echo '<div id="message" class="updated fade">';
    echo '<p>NDSS data import complete.</p>';
    echo '</div>';

    $path = 'test-button-log.txt';

    $handle = fopen($path,"w");

    if ($handle == false) {
        echo '<p>Could not write the log file to the temporary directory: ' . $path . '</p>';
    }
    else {
        echo '<p>Log written to: ' . $path . '</p>';

        fwrite ($handle , "Call Function button clicked on: " . date("D j M Y H:i:s", time())); 
        fclose ($handle);
    }

    //Mass import json data into custom fields
    // $json_feed = 'http://localhost/wordpress/wp-content/anrw17-data.json';
    $json_feed = 'ndss19-data.json';
	$json      = file_get_contents(__DIR__ . $json_feed );
	$objs      = json_decode( $json, true );
	$wp_error  = true;
	$post_id   = - 1;

	foreach ( $objs as $obj ) {
	    $title = $obj['title'];
        $paper_title = $obj['title'];
        $paper_id = $obj['pid'];
	    $paper_abstract  = $obj['abstract'];
	    $authors = $obj['authors'];
	    $index = 0;
	    foreach( $authors as $author ) {
		    ${'paper_authors_' . $index . '_author_name'} = $author['first'] . " " . $author['last'];
		    ${'paper_authors_' . $index . '_author_affiliations'} = $author['affiliation'];
		    ${'paper_authors_' . $index . '_author_email'} = $author['email'];
		    $index++;
        }
        $post_meta = array(
		    'paper_title' => $paper_title,
            'paper_abstract' => $paper_abstract,
            'paper_id' => $paper_id,
        );
	    $index = 0;
	    foreach( $authors as $author ) {
		    $post_meta['paper_authors_' . $index . '_author_name'] = ${'paper_authors_' . $index . '_author_name'};
		    $post_meta['paper_authors_' . $index . '_author_affiliations'] = ${'paper_authors_' . $index . '_author_affiliations'};
		    $post_meta['paper_authors_' . $index . '_author_email'] = ${'paper_authors_' . $index . '_author_email'};
            if ( next($authors)==true ) {
                $post_meta['display_authors'] .= ${'paper_authors_' . $index . '_author_name'} . " (" . ${'paper_authors_' . $index . '_author_affiliations'} . "), ";
            } else {
                $post_meta['display_authors'] .= ${'paper_authors_' . $index . '_author_name'} . " (" . ${'paper_authors_' . $index . '_author_affiliations'} . ")";
            }
            $index++;
        }
        $post_meta['paper_authors'] = (string)$index;
	    $field_meta = array(
            '_paper_title'    => 'field_5bbcbcd8f654e',
            '_paper_abstract' => 'field_5bbcbd2cf654f',
            '_paper_authors'  => 'field_5bbcbd5bf6550',
            '_display_authors' => 'field_5bbcec6e47a54',
	    );
	    $index = 0;
	    foreach( $authors as $author ) {
		    $field_meta['_paper_authors_' . $index . '_author_name'] = 'field_5bbcbd7cf6551';
		    $field_meta['_paper_authors_' . $index . '_author_affiliations'] = 'field_5bbcbd87f6552';
		    $field_meta['_paper_authors_' . $index . '_author_email'] = 'field_5bbcbd9af6553';
		    $index++;
        }
  	    $post_data = array(
		    'post_title'  => $title,
		    'post_status' => 'publish',
		    'post_type'   => 'ndss-paper',
		    'meta_input'  => $post_meta,
	    );
        
        $page = get_page_by_title( $title, OBJECT, 'ndss-paper' );
        // var_dump($page);

	    if ( empty( $page ) ) {
		    $post_id = wp_insert_post( $post_data, $wp_error );
		    foreach ( $post_meta as $key => $value ) {
			    update_post_meta( $post_id, $key, $value );
		    }
		    foreach ( $field_meta as $key => $value ) {
			    update_field( $key, $value, $post_id );
		    }
        }
        else {
            $post_id = $page->ID;
            $post_data['ID'] = $post_id;
            wp_update_post( $post_data );
            // var_dump ( $post_meta );
            foreach ( $post_meta as $key => $value ) {
                update_post_meta( $post_id, $key, $value );
            }
            foreach ( $field_meta as $key => $value ) {
                update_field( $key, $value, $post_id );
            }
	    }
	}
}
