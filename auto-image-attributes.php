<?php
/*
Plugin Name: Auto Image Attributes
Description: Automatically Add Image Title, Image Caption, Description And Alt Text From Image Filename. Since this plugin includes a bulk updater this can update both existing images in the Media Library and new images.
Author: Mathieu Pellegrin
Version: 1.0
Text Domain: auto_image_attributes
Domain Path: /languages
License: GPL v2 - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/


/*------------------------------------------*/
/* Plugin Setup Functions                   */
/*------------------------------------------*/

// Exit If Accessed Directly
if ( ! defined( 'ABSPATH' ) ) exit;


// Add Admin Menu Pages
// Refer: https://developer.wordpress.org/plugins/administration-menus/
function autoimageattributes_add_menu_links() {
	add_options_page( __('Auto Image Attributes','auto_image_attributes'), __('Image Attributes','auto_image_attributes'), 'manage_options', 'image-attributes-from-filename','autoimageattributes_admin_interface_render'  );
}
add_action( 'admin_menu', 'autoimageattributes_add_menu_links' );


// Print Direct Link To Plugin Settings In Plugins List In Admin
function autoimageattributes_settings_link( $links ) {
	return array_merge(
		array(
			'settings' => '<a href="' . admin_url( 'options-general.php?page=image-attributes-from-filename' ) . '">' . __( 'Settings', 'auto_image_attributes' ) . '</a>'
		),
		$links
	);
}
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'autoimageattributes_settings_link' );

// Load Text Domain
function autoimageattributes_load_plugin_textdomain() {
	load_plugin_textdomain( 'auto_image_attributes', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'autoimageattributes_load_plugin_textdomain' );


// Do Stuff On Plugin Activation
function autoimageattributes_activate_plugin() {
	add_option( 'autoimageattributes_bulk_updater_counter', '0' );				// Setting numer of images processed as zero
}
register_activation_hook( __FILE__, 'autoimageattributes_activate_plugin' );


// Register Settings
function autoimageattributes_register_settings() {

	// Register Setting
	register_setting(
		'autoimageattributes_settings_group', 	// Group Name
		'autoimageattributes_settings' 		// Setting Name = HTML form <input> name on settings form
	);

	// Register A New Section
	add_settings_section(
		'autoimageattributes_auto_image_attributes_settings',						// ID
		__('Auto Image Attributes For New Uploads', 'auto_image_attributes'),	// Title
		'autoimageattributes_auto_image_attributes_callback',						// Callback Function
		'image-attributes-from-filename'							// Page slug
	);

	// General Settings
	add_settings_field(
		'autoimageattributes_general_settings',								// ID
		__('General Settings', 'auto_image_attributes'),					// Title
		'autoimageattributes_auto_image_attributes_settings_field_callback',	// Callback function
		'image-attributes-from-filename',						// Page slug
		'autoimageattributes_auto_image_attributes_settings'					// Settings Section ID
	);

	// Filter Settings
	add_settings_field(
		'autoimageattributes_filter_settings',									// ID
		__('Filter Settings', 'auto_image_attributes'),					// Title
		'autoimageattributes_auto_image_attributes_filter_settings_callback',	// Callback function
		'image-attributes-from-filename',						// Page slug
		'autoimageattributes_auto_image_attributes_settings'					// Settings Section ID
	);

}
add_action( 'admin_init', 'autoimageattributes_register_settings' );


// Do Stuff On Plugin Uninstall
function autoimageattributes_uninstall_plugin() {
	delete_option( 'autoimageattributes_settings' );
	delete_option( 'autoimageattributes_bulk_updater_counter' );
	delete_option( 'autoimageattributes_image_attributes_from_filename_settings' );	// Used in Ver 1.0 of the plugin. Simpler days.
}
register_uninstall_hook(__FILE__, 'autoimageattributes_uninstall_plugin' );



/*--------------------------------------*/
/* Admin Options Page                   */
/*--------------------------------------*/

function autoimageattributes_auto_image_attributes_callback() {
	echo '<p>' . __('Automatically add Image attributes such as Image Title, Image Caption, Description And Alt Text from Image Filename.', 'auto_image_attributes') . '</p>';
}

// General Settings Field Callback
function autoimageattributes_auto_image_attributes_settings_field_callback() {

	// Default Values For Settings
	$defaults = array(
		'image_title' => '1',
		'image_caption' => '1',
		'image_description' => '1',
		'image_alttext' => '1',
		'hyphens' => '1',
		'under_score' => '1'
	);

	// Get Settings
	$settings = get_option('autoimageattributes_settings', $defaults);

	// General Settings. Name of form element should be same as the setting name in register_setting(). ?>

	<!-- Auto Add Image Title  -->
	<input type="checkbox" name="autoimageattributes_settings[image_title]" id="autoimageattributes_settings[image_title]" value="1"
	<?php if ( isset( $settings['image_title'] ) ) { checked( '1', $settings['image_title'] ); } ?>>
	<label for="autoimageattributes_settings[image_title]"><?php _e('Set Image Title from filename', 'auto_image_attributes') ?></label>
	<br>

	<!-- Auto Add Image Caption  -->
	<input type="checkbox" name="autoimageattributes_settings[image_caption]" id="autoimageattributes_settings[image_caption]" value="1"
	<?php if ( isset( $settings['image_caption'] ) ) { checked( '1', $settings['image_caption'] ); } ?>>
	<label for="autoimageattributes_settings[image_caption]"><?php _e('Set Image Caption from filename', 'auto_image_attributes') ?></label>
	<br>

	<!-- Auto Add Image Description  -->
	<input type="checkbox" name="autoimageattributes_settings[image_description]" id="autoimageattributes_settings[image_description]" value="1"
	<?php if ( isset( $settings['image_description'] ) ) { checked( '1', $settings['image_description'] ); } ?>>
	<label for="autoimageattributes_settings[image_description]"><?php _e('Set Image Description from filename', 'auto_image_attributes') ?></label>
	<br>

	<!-- Auto Add Alt Text -->
	<input type="checkbox" name="autoimageattributes_settings[image_alttext]" id="autoimageattributes_settings[image_alttext]" value="1"
	<?php if ( isset( $settings['image_alttext'] ) ) { checked( '1', $settings['image_alttext'] ); } ?>>
	<label for="autoimageattributes_settings[image_alttext]"><?php _e('Set Image Alt Text from filename', 'auto_image_attributes') ?></label>
	<br>

	<?php
}

// Filter Settings Field Callback
function autoimageattributes_auto_image_attributes_filter_settings_callback() {

	// Default Values For Settings
	$defaults = array(
		'hyphens' => '1',
		'under_score' => '1',
	);

	// Get Settings
	$settings = get_option('autoimageattributes_settings', $defaults); ?>

	<!-- Filter Hyphens -->
	<input type="checkbox" name="autoimageattributes_settings[hyphens]" id="autoimageattributes_settings[hyphens]" value="1"
		<?php if ( isset( $settings['hyphens'] ) ) { checked( '1', $settings['hyphens'] ); } ?>>
		<label for="autoimageattributes_settings[hyphens]"><?php _e('Remove hyphens ( - ) from filename', 'auto_image_attributes') ?></label>
		<br>

	<!-- Filter Underscore  -->
	<input type="checkbox" name="autoimageattributes_settings[under_score]" id="autoimageattributes_settings[under_score]" value="1"
		<?php if ( isset( $settings['under_score'] ) ) { checked( '1', $settings['under_score'] ); } ?>>
		<label for="autoimageattributes_settings[under_score]"><?php _e('Remove underscores ( _ ) from filename', 'auto_image_attributes') ?></label>
		<br>

	<?php
}

// Admin Interface Renderer
function autoimageattributes_admin_interface_render () {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	/* Commented out after moving menu location to Settings pages instead of Media as originally deisigned.
	// https://core.trac.wordpress.org/ticket/31000
	// Check if the user have submitted the settings
	// WordPress will add the "settings-updated" $_GET parameter to the url
	if ( isset( $_GET['settings-updated'] ) ) {
		// Add settings saved message with the class of "updated"
		add_settings_error( 'autoimageattributes_settings_saved_message', 'autoimageattributes_settings_saved_message', __( 'Settings are Saved', 'auto_image_attributes' ), 'updated' );
	}

	// Show Settings Saved Message
	settings_errors( 'autoimageattributes_settings_saved_message' ); */?>

	<div class="wrap">
		<h1>Auto Image Attributes From Filename With Bulk Updater</h1>

		<form action="options.php" method="post">
			<?php
			// Output nonce, action, and option_page fields for a settings page.
			settings_fields( 'autoimageattributes_settings_group' );

			// Prints out all settings sections added to a particular settings page.
			do_settings_sections( 'image-attributes-from-filename' );	// Page slug

			// Output save settings button
			submit_button( __('Save Settings', 'auto_image_attributes') );
			?>
		</form>

		<h2><?php _e('Update Existing Images In Media Library', 'auto_image_attributes') ?></h2>

		<p style="color:red"><?php _e('IMPORTANT: Please backup your database before running the bulk updater.', 'auto_image_attributes') ?></p>
		<p><?php _e('Run this bulk updater to update Image Title, Caption, Description and Alt Text from image filename for existing images in the media library.', 'auto_image_attributes') ?></p>
		<p><?php _e('If your image is named a-lot-like-love.jpg, your Image Title, Caption, Description and Alt Text will be: A Lot Like Love. ', 'auto_image_attributes') ?></p>
		<p><?php _e('Be Patient and do not close the browser while it\'s running. In case you do, you can always resume by returning to this page later.', 'auto_image_attributes') ?></p> <?php

		submit_button( __('Run Bulk Updater', 'auto_image_attributes'), 'autoimageattributes_run_bulk_updater_button' ); ?>

		<p><?php _e('To restart processing images from the beginning (the oldest upload first), reset the counter.', 'auto_image_attributes') ?></p> <?php
		submit_button( __('Reset Counter', 'auto_image_attributes'), 'autoimageattributes_reset_counter_button' ); ?>

		<p><?php _e('Number of Images Updated: ', 'auto_image_attributes') ?><span id="autoimageattributes_updated_counter"><?php autoimageattributes_number_of_images_updated(); ?></span></p>
		<p id="autoimageattributes_remaining_images_text" style="display: none;"><?php _e('Number of Images Remaining: ', 'auto_image_attributes') ?><span id="autoimageattributes_remaining_counter"><?php echo autoimageattributes_total_number_of_images(); ?></span></p>

		<span id="autoimageattributes_bulk_updater_results"></span>

	</div>
	<?php
}



/*--------------------------------------*/
/* Plugin Operations                    */
/*--------------------------------------*/


// Auto Add Image Attributes From Image Filename For New Uploads
function autoimageattributes_auto_image_attributes( $post_ID ) {

	// Default Values For Settings
	$defaults = array(
		'image_title' => '1',
		'image_caption' => '1',
		'image_description' => '1',
		'image_alttext' => '1',
		'hyphens' => '1',
		'under_score' => '1',
	);
	// Get Settings
	$settings = get_option('autoimageattributes_settings', $defaults);

	$attachment 		= get_post( $post_ID );

	// Extract the image name from the image url
	$image_extension = pathinfo($attachment->guid);
	$image_name = basename($attachment->guid, '.'.$image_extension['extension']);

	// Process the image name and neatify it
	if ( isset( $settings['hyphens'] ) && boolval($settings['hyphens']) ) {
		$attachment_title 	= str_replace( '-', ' ', $image_name );	// Hyphen Removal
	}
	if ( isset( $settings['under_score'] ) && boolval($settings['under_score']) ) {
		$attachment_title 	= str_replace( '_', ' ', $image_name );	// Underscore Removal
	}
	$attachment_title 	= ucwords( $attachment_title );					// Capitalize First Word

	$uploaded_image               	= array();
	$uploaded_image['ID']         	= $post_ID;

	if ( isset( $settings['image_title'] ) && boolval($settings['image_title']) ) {
		$uploaded_image['post_title'] 	= $image_name;	// Image Title
	}
	if ( isset( $settings['image_caption'] ) && boolval($settings['image_caption']) ) {
		$uploaded_image['post_excerpt'] = $image_name;	// Image Caption
	}
	if ( isset( $settings['image_description'] ) && boolval($settings['image_description']) ) {
		$uploaded_image['post_content'] = $image_name;	// Image Description
	}
	if ( isset( $settings['image_alttext'] ) && boolval($settings['image_alttext']) ) {
		update_post_meta( $post_ID, '_wp_attachment_image_alt', $image_name ); // Image Alt Text
	}

	wp_update_post( $uploaded_image );

}
add_action( 'add_attachment', 'autoimageattributes_auto_image_attributes' );


// Auto Add Image Attributes From Image Filename For Existing Uploads
function autoimageattributes_rename_old_image() {

	// Security Check
	check_ajax_referer( 'autoimageattributes_rename_old_image_nonce', 'security' );

	// Retrieve Counter
	$counter = get_option('autoimageattributes_bulk_updater_counter');
	$counter = intval ($counter);

	global $wpdb;
	$image = $wpdb->get_row("SELECT ID,guid FROM {$wpdb->prefix}posts WHERE post_type='attachment' ORDER BY post_date LIMIT 1 OFFSET {$counter}");

	// Die If No Image
	if ($image === NULL) {
		wp_die();
	}

	// Extract the image name from the image url
	$image_extension = pathinfo($image->guid);
	$image_name = basename($image->guid, '.'.$image_extension['extension']);

	// Process the image name and neatify it
	if ( isset( $settings['hyphens'] ) && boolval($settings['hyphens']) ) {
		$attachment_title 	= str_replace( '-', ' ', $attachment_title );	// Hyphen Removal
	}
	if ( isset( $settings['under_score'] ) && boolval($settings['under_score']) ) {
		$attachment_title 	= str_replace( '_', ' ', $attachment_title );	// Underscore Removal
	}
	$attachment_title 	= ucwords( $attachment_title );					// Capitalize First Word

	// Retrieve settings
	$settings = get_option('autoimageattributes_settings', $defaults);

	// Update the image Title, Caption and Description with the image name
	$updated_image = array(
	  'ID' => $image->ID,
	);
	if ( isset( $settings['image_title'] ) && boolval($settings['image_title']) ) {
		$updated_image['post_title'] 	= $image_name;	// Image Title
	}
	if ( isset( $settings['image_caption'] ) && boolval($settings['image_caption']) ) {
		$updated_image['post_excerpt'] = $image_name;	// Image Caption
	}
	if ( isset( $settings['image_description'] ) && boolval($settings['image_description']) ) {
		$updated_image['post_content'] = $image_name;	// Image Description
	}
	wp_update_post( $updated_image );

	// Update Image Alt Text (stored in post_meta table)
	if ( isset( $settings['image_alttext'] ) && boolval($settings['image_alttext']) ) {
		update_post_meta( $image->ID, '_wp_attachment_image_alt', $image_name ); // Image Alt Text
	}

	// Increment Counter And Update It
	$counter++;
	update_option( 'autoimageattributes_bulk_updater_counter', $counter );

	echo __('Image Attributes Updated For Image: ', 'auto_image_attributes') . $image->guid;
	wp_die();
}
add_action( 'wp_ajax_autoimageattributes_rename_old_image', 'autoimageattributes_rename_old_image' );


// Print Number Of Images Updated By The Bulk Updater
function autoimageattributes_number_of_images_updated() {

	$autoimageattributes_images_updated_counter = get_option('autoimageattributes_bulk_updater_counter');
	echo $autoimageattributes_images_updated_counter;
}


// Count Total Number Of Images In The Database
function autoimageattributes_total_number_of_images() {

	global $wpdb;
	$total_no_of_images = $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->prefix}posts WHERE post_type='attachment'");

	return $total_no_of_images;
}

// Print Remaining Number Of Images To Process
function autoimageattributes_count_remaining_images() {

	$total_no_of_images = autoimageattributes_total_number_of_images();

	$no_of_images_processed = get_option('autoimageattributes_bulk_updater_counter');
	$no_of_images_processed = intval ($no_of_images_processed);

	$reamining_images = $total_no_of_images - $no_of_images_processed;
	echo $reamining_images;

	wp_die();
}
add_action( 'wp_ajax_autoimageattributes_count_remaining_images', 'autoimageattributes_count_remaining_images' );


// Reset Counter To Zero So That Bulk Updating Starts From Scratch
function autoimageattributes_reset_bulk_updater_counter() {

	// Security Check
	check_ajax_referer( 'autoimageattributes_reset_counter_nonce', 'security' );

	update_option( 'autoimageattributes_bulk_updater_counter', '0' );
	echo __('Counter reset. The bulk updater will start from scratch in the next run.', 'auto_image_attributes');

	wp_die();
}
add_action( 'wp_ajax_autoimageattributes_reset_bulk_updater_counter', 'autoimageattributes_reset_bulk_updater_counter' );


// Bulk Updater Ajax
function autoimageattributes_image_bulk_updater() {

	// Load Ajax Only On Plugin Page
	$screen = get_current_screen();
	if ( $screen->id !== "settings_page_image-attributes-from-filename" ) {
		return;
	}?>

	<script type="text/javascript" >
	jQuery(document).ready(function($) {
		// Reset Bulk Updater Counter
		$('.autoimageattributes_reset_counter_button').click(function() {
			data = {
				action: 'autoimageattributes_reset_bulk_updater_counter',
				security: '<?php echo wp_create_nonce( "autoimageattributes_reset_counter_nonce" ); ?>'
			};

			$.post(ajaxurl, data, function (response) {
				alert(response);
				$('#autoimageattributes_updated_counter').text('0');
			});
		});

		// Run Bulk Updater
		$('.autoimageattributes_run_bulk_updater_button').click(function() {
			// Count Remaining Images To Process
			data = {
				action: 'autoimageattributes_count_remaining_images'
			};

			var remaining_images = null;

			var reamining_images_count = $.post(ajaxurl, data, function (response) {
				remaining_images = response;
				console.log(remaining_images);
			});

			// Loop For Each Image And Update Its Attributes
			reamining_images_count.done(function autoimageattributes_rename_image() {

				if(remaining_images > 0){

					$('#autoimageattributes_remaining_images_text').show();						// Show the text for remaining images

					data = {
						action: 'autoimageattributes_rename_old_image',
						security: '<?php echo wp_create_nonce( "autoimageattributes_rename_old_image_nonce" ); ?>'
					};

					var rename_image = $.post(ajaxurl, data, function (response) {
						$('#autoimageattributes_bulk_updater_results').append('<p>' + response + '</p>');
						var updated_counter = parseInt($('#autoimageattributes_updated_counter').text());
						$('#autoimageattributes_updated_counter').text(updated_counter+1);			// Update total number of images updated
						$('#autoimageattributes_remaining_counter').text(remaining_images-1);		// Update total number of images remaining
						console.log(response);
					});

					rename_image.done(function() {
						remaining_images--;
						autoimageattributes_rename_image();
					});
				}
				else {
					$('#autoimageattributes_bulk_updater_results').append('<p>All done!</p>')
					$('#autoimageattributes_remaining_counter').text('All done!')
				}
			});
		});
	});
	</script> <?php
}
add_action( 'admin_footer', 'autoimageattributes_image_bulk_updater' );

?>
