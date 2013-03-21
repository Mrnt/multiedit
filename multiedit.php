<?php
/*
Plugin Name:	MultiEdit
Plugin URI:		https://github.com/Mrnt/multiedit.git
Description:	Multi-Editable Region Support for Page Templates.
Version:		0.1
Author:			joshua.strebel
Author URI:		http://www.maurentsoftware.com
*/

/*
 /--------------------------------------------------------------------\
|                                                                    |
| License: GPL                                                       |
|                                                                    |
| Page.ly MultiEdit- Adds editable Blocks to page templates in       |
| WordPress                                                          |
| Copyright (C) 2010, Joshua Strebel,                                |
| http://page.ly                                                     |
| All rights reserved.                                               |
|                                                                    |
| This program is free software; you can redistribute it and/or      |
| modify it under the terms of the GNU General Public License        |
| as published by the Free Software Foundation; either version 2     |
| of the License, or (at your option) any later version.             |
|                                                                    |
| This program is distributed in the hope that it will be useful,    |
| but WITHOUT ANY WARRANTY; without even the implied warranty of     |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the      |
| GNU General Public License for more details.                       |
|                                                                    |
| You should have received a copy of the GNU General Public License  |
| along with this program; if not, write to the                      |
| Free Software Foundation, Inc.                                     |
| 51 Franklin Street, Fifth Floor                                    |
| Boston, MA  02110-1301, USA                                        |
|                                                                    |
\--------------------------------------------------------------------/
*/
//error_reporting(E_ALL);
//ini_set("display_errors", 1);

define('ME_PLUGINASSETS',plugins_url( '' , __FILE__ ));

/**
 * Add options menu item
 */
function me_options() {
	add_options_page('Page.ly Multi-Edit Options', 'Multi-Edit', 'manage_options', 'multiedit-options', 'me_options_page');
	multieditAdminHeader();
}
add_action('admin_menu', 'me_options');

/**
 * Draw options page 
 */
function me_options_page() {
	if (!current_user_can('manage_options')) {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	$opts[] = array();
	$opts['shortcodes'] = get_option( 'pagely_multiedit_shortcodes' );
	!isset($opts['shortcodes']) ? update_option('pagely_multiedit_shortcodes',0) : '';

	// print_r($_POST);
	if( isset($_POST['opts']['shortcode'])  ) {
		// Read their posted value
		$opts['shortcodes'] = $_POST['opts']['shortcode'];
		// Save the posted value in the database
		update_option( 'pagely_multiedit_shortcodes', $opts['shortcodes'] );
		?>
		<div class="updated">
			<p>
				<strong><?php _e('Multi-Edit settings saved.'); ?> </strong>
			</p>
		</div>
		<?php
	}

	?>
	<div class="wrap">
		<h2><?php echo __( 'Page.ly Multi-Edit Options' );?></h2>
		<div id="pme_split">
		
			<div class="pme_left">
				<form name='pme' method='post' action=''>
					<table class='form-table'>
						<tr>
							<th><?php _e('Short Code Support:' ); ?></th>
							<td><input type="radio" name="opts[shortcode]" value="1"
							<?php echo $opts['shortcodes'] == 1 ?  'checked="checked"' : ''?> /> <label>Yes</label>&nbsp;&nbsp; <input
								type="radio" name="opts[shortcode]" value="0" <?php echo $opts['shortcodes'] == 0 ?  'checked="checked"' : ''?> />
								<label>No</label><br /> <span class="description"><?php _e('Enables Shortcode support for multi-edit regions. Caution this will enable all filters for the region like including social media buttons and other such nonsense in your output.' ); ?>
								</span>
							</td>
						</tr>
						<tr>
							<th></th>
							<td><p class="submit">
									<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
								</p></td>
						</tr>
					</table>
				</form>
			</div>
			<div class="pme_right">
				<a href="http://page.ly" target="_blank"><img src="<?php echo ME_PLUGINASSETS?>/pagely-plugin-ad.jpg" /> </a>
			</div>
		
			<br style="clear: both" />
		</div>
	</div>
	<?php
}

/**
 * Only hook in if we are on the admin pages for post or page editing 
 */
if (in_array(basename($_SERVER['PHP_SELF']), array('post.php','page.php')) && $_GET['action'] == 'edit' ) {
	add_action('init', 'multiedit');
}

/**
 * Hook into the footer so we can look at the template being used
 */
function multiedit() {
	add_action('admin_footer', 'doMultiMeta', 1);
}

$GLOBALS['multiEditDisplay'] = false;

/**
 * Call this from within a template to render a region
 * @param string $index		: name of region to be rendered
 * @param bool $return		: true to return the region as a string instead of outputting it
 * @return string
 */
function multieditDisplay($index, $return = false) {
	//if ($GLOBALS['multiEditDisplay'] === false) {
	$GLOBALS['multiEditDisplay'] = get_post_custom(0);
	//}
	$index = "multiedit_$index";

	if (isset($GLOBALS['multiEditDisplay'][$index])) {
		//check to apply filters is Shortcode support option is on.
		if( get_option('pagely_multiedit_shortcodes') == 1 ) {
			$me_str = apply_filters('the_content',$GLOBALS['multiEditDisplay'][$index][0]);
		} else {
			$me_str = $GLOBALS['multiEditDisplay'][$index][0];
		}
		// clean out some random mce charcters, this could be improved or we can better sanitize on the save side
		$me_str = preg_replace( '#mce_[a-z]+="[^"]+"#', '', $me_str );

		if ($return) {
			return $me_str;
		} else {
			echo $me_str;
		}
	}
}

/**
 * Add styling and JavaScript to admin page to support the edit tabs
 */
function multieditAdminHeader() {
	wp_enqueue_style('multiedit_style',  ME_PLUGINASSETS .'/multiedit.css');
	wp_enqueue_script('multiedit_js', ME_PLUGINASSETS .'/multiedit.js' );
}

/**
 * Draw the tabs for the different template regions in the admin page editor
 * @param array $meta				: page/post custom meta data
 * @param array $presentregions		: list of regions supported by the template 
 */
function drawMultieditHTML($meta, $presentregions) {
	global $post;
	echo '<h2 id="multiEditControl" class="nav-tab-wrapper"></h2>';
	echo '<div id="multiEditHidden"><a class="nav-tab multieditbutton nav-tab-active" id="default">Main Content</a>';
	
	// this adds the multiedit tabs that appear above the tinymce editor
	if (is_array($meta)) {
		foreach($meta as $item) {
			if (preg_match('/^multiedit_(.+)/',$item['meta_key'],$matches)) {
				// lets check regions defined in this template ($presentregions) against those in meta
				// so we can treat meta values that may be in $post, but not in this template differently
				//print_r($matches);
				$notactive = false;
				if (!array_key_exists($matches[1], $presentregions)) {
					$notactive = 'notactive';
					$fields[] = $matches[1];
				}
				$mkey = trim($item['meta_key']);
				$mval = trim($item['meta_value']);
				$mid = trim($item['meta_id']);
				$mclean = trim($matches[1]);
				// CamelCase => Camel Case:
				$mclean = preg_replace('/(?<=\\w)(?=[A-Z])/'," $1", $mclean);
				// Underscores to Spaces:
				$mclean = str_replace("_", " ", $mclean);

				echo '<a class="nav-tab multieditbutton '.$notactive.'" id="hs_'.$mkey.'" rel="'.$mid.'">'.$mclean.'</a><input type="hidden" id="hs_'.$mkey.'" name="'.$mkey.'" value="'.htmlspecialchars($mval).'" />';

			}
		}
		// show a message if needed
		if (!empty($fields)) {
			echo '<div id="nonactive" style="display:none"><p>'.implode(', ',$fields).' region(s) are not declared in the template.</p></div>';
		}
	}

	echo '<div id="multiEditFreezer" style="display:none">'. $post->post_content ."</div></div>\n";
}

/**
 * Check to see what template is being used and if it contains regions, if so add the necessary html to the page 
 * @return boolean
 */
function doMultiMeta() {

	global $post;
	$meta = has_meta($post->ID);

	// if default template.. assign var to page.php
	$post->page_template == 'default' ? $post->page_template = 'page.php' : '';

	$templatefile = locate_template(array($post->page_template));
	if(file_exists($templatefile)) {
		$template_data = implode('', array_slice(file($templatefile), 0, 10));
		$matches = '';
		//check for multiedit declaration in template
		if (preg_match( '|MultiEdit:(.*)$|mi', $template_data, $matches)) {
			$multi = explode(',',_cleanup_header_comment($matches[1]));
			// load scripts
			multieditAdminHeader();
			// WE have multiedit zones, load js and css load
			add_action('edit_page_form', 'multieditAdminEditor', 1);
			add_action('edit_form_advanced', 'multieditAdminEditor', 1);

			//simple var assigment
			foreach($meta as $k=>$v) {
				foreach($multi as $region) {
					if (in_array('multiedit_'.$region,$v)) {
						$present[$region] = true;
					}
				}
			}

			//draw html
			drawMultieditHTML($meta,$present);

			// if custom field is not declared yet, create one with update_post_meta
			foreach($multi as $region) {
				if(!isset($present[$region])) {
					update_post_meta($post->ID, 'multiedit_'.$region, '');
				}
			}
		} // end preg_match
	} else {
		// cant find a suitable template.. display nothing. Content is still in custom fields, so it is not lost.
		return false;
	}
}
?>
