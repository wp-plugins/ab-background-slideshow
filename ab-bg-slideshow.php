<?php
@ini_set('display_errors','Off');
/*
	Plugin Name: AB Background Slideshow
  	Plugin URI: http://wordpress.org/extend/plugins/ab-background-slideshow
  	Version: 1.3
 	Author: Aboobacker P Ummer
 	Author URI: http://aboobacker.com
 	Description: A beautiful slideshow on your website background.
 	Tags: Background slideshow, abooze slideshow, BG slideshow, bg slider
*/
ob_start();
// Link javascript & css files

//Note: This plugin conatin many future development codes; just NM them :)
function abinclude_bgfiles($lang) {
    wp_enqueue_style('bgstretcher', plugins_url('/bgstretcher.css', __FILE__));
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script('bgstretcher', plugins_url('/js/bgstretcher.js', __FILE__));
}
if(!is_admin())
	add_action('init', 'abinclude_bgfiles');

function ab_bg_show_script() {
?>
	<script type="text/javascript">
        jQuery(document).ready(function(){
            //  Initialize Backgound Stretcher	   
            jQuery('body').bgStretcher({
                imageWidth: 1024, 
                imageHeight: 768, 
                slideDirection: 'N',
                slideShowSpeed: 2000,
				nextSlideDelay: 6000,
                transitionEffect: 'fade',
				slideShow: true,
                sequenceMode: 'normal',
                buttonPrev: '#prev',
                buttonNext: '#next',
                pagination: '#nav',
                anchoring: 'left center',
                anchoringImg: 'left center',
                <?php ab_bg_show();?>
            });
        });
    </script>
<?php } 

add_action('wp_head', 'ab_bg_show_script');

//	pull the settings from the db
$wp_cycle_settings = get_option('wp_cycle_settings');
$wp_cycle_images = get_option('wp_cycle_images');
//	fallback
$wp_cycle_settings = wp_parse_args($wp_cycle_settings, $wp_cycle_defaults);
//	this function registers our settings in the db
add_action('admin_init', 'ab_show_register_settings');
function ab_show_register_settings() {
	register_setting('wp_cycle_images', 'wp_cycle_images', 'ab_bg_images_validate');
	register_setting('wp_cycle_settings', 'wp_cycle_settings', 'ab_bg_settings_validate1');
}
//	this function adds the settings page to the Appearance tab
add_action('admin_menu', 'add_ab_bg_menu');
function add_ab_bg_menu() {
	add_submenu_page('options-general.php', 'Background Slideshow Settings', '<strong style="color:#648F0A;">AB BG Slideshow</strong>', 'upload_files', 'ab-bg-slideshow', 'ab_show_admin_page');
}
//	add "Settings" link to plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__) , 'ab_show_plugin_action_links');
function ab_show_plugin_action_links($links) {
	$wp_cycle_settings_link = sprintf( '<a href="%s">%s</a>', admin_url( 'options-general.php?page=ab-bg-slideshow' ), __('Settings') );
	array_unshift($links, $wp_cycle_settings_link);
	return $links;
}
function ab_show_admin_page() {
	echo '<div class="wrap">';
		//	handle image upload, if necessary
		if($_REQUEST['action'] == 'wp_handle_upload')
			ab_bg_handle_upload();
		//	delete an image, if necessary
		if(isset($_REQUEST['delete']))
			ab_bg_delete_upload($_REQUEST['delete']);
		//	the image management form
		ab_bg_images_admin();
//	the settings management form - abooze
	echo '</div>';
}

function ab_bg_handle_upload() {
	global $wp_cycle_settings, $wp_cycle_images;
	//	upload the image
	$upload = wp_handle_upload($_FILES['wp_cycle'], 0);
	//	extract the $upload array
	extract($upload);
	//	the URL of the directory the file was loaded in
	$upload_dir_url = str_replace(basename($file), '', $url);
	//	get the image dimensions
	list($width, $height) = getimagesize($file);
	//	if the uploaded file is NOT an image
	if(strpos($type, 'image') === FALSE) {
		unlink($file); // delete the file
		echo '<div class="error" id="message"><p>Sorry, but the file you uploaded does not seem to be a valid image. Please try again.</p></div>';
		return;
	}

	//	make the thumbnail
	$thumb_height = round((100 * $wp_cycle_settings['img_height']) / $wp_cycle_settings['img_width']);
	if(isset($upload['file'])) {
		$thumbnail = image_resize($file, 100, $thumb_height, true, 'thumb');
		$thumbnail_url = $upload_dir_url . basename($thumbnail);
	}
	//	use the timestamp as the array key and id
	$time = date('YmdHis');
	//	add the image data to the array
	$wp_cycle_images[$time] = array(
		'id' => $time,
		'file' => $file,
		'file_url' => $url,
		'thumbnail' => $thumbnail,
		'thumbnail_url' => $thumbnail_url,
		'image_links_to' => ''
	);
	//	add the image information to the database
	$wp_cycle_images['update'] = 'Added';
	update_option('wp_cycle_images', $wp_cycle_images);
}
//	this function deletes the image,
//	and removes the image data from the db
function ab_bg_delete_upload($id) {
	global $wp_cycle_images;
	//	if the ID passed to this function is invalid,
	//	halt the process, and don't try to delete.
	if(!isset($wp_cycle_images[$id])) return;
	//	delete the image and thumbnail
	unlink($wp_cycle_images[$id]['file']);
	unlink($wp_cycle_images[$id]['thumbnail']);
	//	indicate that the image was deleted
	$wp_cycle_images['update'] = 'Deleted';
	//	remove the image data from the db
	unset($wp_cycle_images[$id]);
	update_option('wp_cycle_images', $wp_cycle_images);
}
function ab_bg_settings_update_check() {
	global $wp_cycle_settings;
	if(isset($wp_cycle_settings['update'])) {
		echo '<div class="updated fade" id="message"><p>Ab-Background Slideshow Settings <strong>'.$wp_cycle_settings['update'].'</strong></p></div>';
		unset($wp_cycle_settings['update']);
		update_option('wp_cycle_settings', $wp_cycle_settings);
	}
}
//	this function checks to see if we just added a new image
//	if so, it displays the "updated" message.
function ab_bg_images_update_check() {
	global $wp_cycle_images;
	if($wp_cycle_images['update'] == 'Added' || $wp_cycle_images['update'] == 'Deleted' || $wp_cycle_images['update'] == 'Updated') {
		echo '<div class="updated fade" id="message"><p>Image '.$wp_cycle_images['update'].' Successfully</p></div>';
		unset($wp_cycle_images['update']);
		update_option('wp_cycle_images', $wp_cycle_images);
	}
}

function ab_bg_images_admin() { ?>
	<?php global $wp_cycle_images; ?>
	<?php ab_bg_images_update_check(); ?>
	<h2><?php _e('Background Images', 'wp_cycle'); ?></h2>
	<table class="form-table">
		<tr valign="top"><th scope="row">Upload New Image</th>
			<td>
			<form enctype="multipart/form-data" method="post" action="?page=ab-bg-slideshow">
				<input type="hidden" name="post_id" id="post_id" value="0" />
				<input type="hidden" name="action" id="action" value="wp_handle_upload" />
				<label for="wp_cycle">Select a File: </label>
				<input type="file" name="wp_cycle" id="wp_cycle" />
				<input type="submit" class="button-primary" name="html-upload" value="Upload" />
			</form>
			</td>
		</tr>
	</table><br />
	<?php if(!empty($wp_cycle_images)) : ?>
	<table class="widefat fixed" cellspacing="0">
		<thead>
			<tr>
				<th scope="col" class="column-slug">Image</th>

				<th scope="col" class="column-slug">Actions</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th scope="col" class="column-slug">Image</th>

				<th scope="col" class="column-slug">Actions</th>
			</tr>
		</tfoot>
		
		<tbody>
		<form method="post" action="options.php">
		<?php settings_fields('wp_cycle_images'); ?>
		<?php foreach((array)$wp_cycle_images as $image => $data) : ?>
			<tr>
				<input type="hidden" name="wp_cycle_images[<?php echo $image; ?>][id]" value="<?php echo $data['id']; ?>" />
				<input type="hidden" name="wp_cycle_images[<?php echo $image; ?>][file]" value="<?php echo $data['file']; ?>" />
				<input type="hidden" name="wp_cycle_images[<?php echo $image; ?>][file_url]" value="<?php echo $data['file_url']; ?>" />
				<input type="hidden" name="wp_cycle_images[<?php echo $image; ?>][thumbnail]" value="<?php echo $data['thumbnail']; ?>" />
				<input type="hidden" name="wp_cycle_images[<?php echo $image; ?>][thumbnail_url]" value="<?php echo $data['thumbnail_url']; ?>" />
				<th scope="row" class="column-slug"><img src="<?php echo $data['thumbnail_url']; ?>" /></th>
				<td class="column-slug"> <a href="?page=ab-bg-slideshow&amp;delete=<?php echo $image; ?>" class="button">Delete</a></td>
			</tr>
		<?php endforeach; ?>
		<input type="hidden" name="wp_cycle_images[update]" value="Updated" />
		</form>
		</tbody>
	</table>
	<?php endif; ?>

<?php
}

//////////////* Option to be added for chosing different diamensions start end*///////////////
//////////////////////////////////////////////////////////////////////////////////////////
function ab_bg_settings_validate1($input) {
	//$input['div'] = wp_filter_nohtml_kses($input['div']);
	$input['slide_delay'] = intval($input['slide_delay']);
	$input['slide_speed'] = intval($input['slide_speed']);
	return $input;
}

//	this function sanitizes our image data for storage
function ab_bg_images_validate($input) {
	foreach((array)$input as $key => $value) {
		if($key != 'update') {
			$input[$key]['file_url'] = clean_url($value['file_url']);
			$input[$key]['thumbnail_url'] = clean_url($value['thumbnail_url']);
			if($value['image_links_to'])
			$input[$key]['image_links_to'] = clean_url($value['image_links_to']);
		}
	}
	return $input;
}
function ab_bg_show($args = array(), $content = null) {
	global $wp_cycle_settings, $wp_cycle_images;
	// possible future use
	$args = wp_parse_args($args, $wp_cycle_settings);
	$newline = "\n"; // line break
	
	echo 'images: [';
	$count = 1;
	foreach((array)$wp_cycle_images as $image => $data) {
		$imgsCount = sizeof($wp_cycle_images);
		echo "'" . $data['file_url'] . "'";
		if($count != $imgsCount) echo ", ";
		$count++;		
	}
	echo "]";
}
?>