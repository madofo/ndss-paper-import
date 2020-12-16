<?php

/** 
 * @package NDSSPaperImportPlugin
 */

/*
Plugin Name: NDSS Paper Import Plugin
Plugin URI: https://github.com/madofo/ndss-paper-import
Description: This plugin will import json data exported from HotCRP to NDSS Paper post types.
Author: Mat Ford
Version: 1.1.0
Author URI: https://github.com/madofo
License: GPLv2 or later
Text Domain: ndss-paper-import
 */

if ( ! defined( 'ABSPATH' ) ) {
    die;
}

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
    echo '<h2>NDSS Paper Metadata Import Plugin</h2>';
    echo '<p>Import JSON-formatted HotCRP output to NDSS Paper posts.';
    echo '<p>Choose File to select a JSON-formatted data source.';
    echo '<p>Set YEAR to format the PDF URL correctly, e.g. "2021" for NDSS 2021';

    ?>
    <?php
    if (isset($_POST['year'])) {
        $format_year = $_POST['year'];
    } else {
        $format_year = 'YYYY';
    }
    handle_post();

    ?>
    <!-- Form to handle the upload - The enctype value here is very important -->
    <form action="options-general.php?page=ndss-button-slug" method="post" enctype="multipart/form-data">
    <input type="file" id="upload_ndss_papers_json" name="upload_ndss_papers_json"></input>
    Year:  <input type="text" name="year" value="<?php echo $format_year; ?>"></input>
    <p>Click on "Upload and Process NDSS paper metadata" to import to NDSS Paper posts.</p>
    <?php wp_nonce_field("ndss_button_clicked"); ?>
    <input type="hidden" value="true" name="ndss_button"></input>
    <?php submit_button("Upload and Process NDSS paper metadata"); ?>
    </form>

    <?php
    echo '</div>';
}

function handle_post() {
    // First check that year is set
    if (isset($_POST['year'])) {
        $format_year = $_POST['year'];
        // Then check if the file appears on the _FILES array AND we've passed the security check
        if(isset($_FILES['upload_ndss_papers_json']) && check_admin_referer('ndss_button_clicked')){
            $json_file = $_FILES['upload_ndss_papers_json'];
 
            // Use the wordpress function to upload
            // upload_ndss_papers_json corresponds to the position in the $_FILES array
            // 0 means the content is not associated with any other posts
            $uploaded=media_handle_upload('upload_ndss_papers_json', 0);
            // Error checking using WP functions
            if(is_wp_error($uploaded)){
                echo "Error uploading file: " . $uploaded->get_error_message();
            }else{
                echo "File upload successful!";
                ndss_button_action($uploaded, $format_year);
            }
        }
    }
}

function ndss_button_action($id, $year) {
  
    echo '<div id="message" class="updated fade">';
    echo '<p>NDSS paper metadata import complete.</p>';
    echo '<p>Now is a good time to go to NDSS Papers, filter by Uncategorized and then bulk edit to Categorize the papers you just uploaded.</p>';
    echo '</div>';

    //Mass import json data into custom fields
    $json_file = get_post($id);
    $json      = file_get_contents(wp_get_attachment_url($id));
	$objs      = json_decode( $json, true );
	$wp_error  = true;
    $post_id   = - 1;

	foreach ( $objs as $obj ) {
        $paper_title = $obj['title'];
        $paper_id = $obj['pid'];
	    $paper_abstract  = $obj['abstract'];
        $authors = $obj['authors'];
        $paper_pdf = 'https://www.ndss-symposium.org/wp-content/uploads/' . $year . '-' . $paper_id . '-paper.pdf';
	    $index = 0;
	    foreach( $authors as $author ) {
            if ( ( isset ($author['first']) && isset ($author['last']) ) ) {
                ${'paper_authors_' . $index . '_author_name'} = $author['first'] . " " . $author['last'];
            }
            if ( isset ($author['affiliation']) ) {
                ${'paper_authors_' . $index . '_author_affiliations'} = $author['affiliation'];
            }
            if ( isset ($author['email']) ) {
                ${'paper_authors_' . $index . '_author_email'} = $author['email'];
            }
		    $index++;
        }
        $post_meta = array(
		    'paper_title' => $paper_title,
            'paper_abstract' => $paper_abstract,
            'paper_id' => $paper_id,
            'paper_pdf' => $paper_pdf,
        );
        $index = 0;
        $post_meta['display_authors'] = "";
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
            '_paper_title'     => 'field_5bed8927f3e63',
            '_paper_abstract'  => 'field_5bed893cf3e64',
            '_paper_authors'   => 'field_5bed8949f3e65',
            '_display_authors' => 'field_5bed8966f3e66',
            '_paper_pdf'       => 'field_5bed8975f3e67',
	    );
	    $index = 0;
	    foreach( $authors as $author ) {
		    $field_meta['_paper_authors_' . $index . '_author_name'] = 'field_5bed8bb4ec481';
		    $field_meta['_paper_authors_' . $index . '_author_affiliations'] = 'field_5bed8bc5ec482';
		    $field_meta['_paper_authors_' . $index . '_author_email'] = 'field_5bed8bdbec483';
		    $index++;
        }
  	    $post_data = array(
		    'post_title'  => $paper_title,
		    'post_status' => 'publish',
		    'post_type'   => 'ndss-paper',
		    'meta_input'  => $post_meta,
	    );

        $page = get_page_by_title( $paper_title, OBJECT, 'ndss-paper' );
        // var_dump($post_meta);

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
            foreach ( $post_meta as $key => $value ) {
                update_post_meta( $post_id, $key, $value );
            }
            foreach ( $field_meta as $key => $value ) {
                update_field( $key, $value, $post_id );
            }
	    }
    }
}
