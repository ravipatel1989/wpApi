<?php
/**
 * Twenty Nineteen functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WordPress
 * @subpackage Twenty_Nineteen
 * @since 1.0.0
 */
$dirpath = wp_upload_dir();
$path = $dirpath['basedir'].'/avatar/';
$url = $dirpath['baseurl'].'/avatar/';
$file_path = $dirpath['basedir'].'/files/';
$file_url = $dirpath['baseurl'].'/files/';
$pdf_path = $dirpath['basedir'].'/pdf/';
$pdf_url = $dirpath['baseurl'].'/pdf/';
define('SALT', 'ldkwidj57qizj2sfl');
define('USERROLE',["YP"=>"young_person","SW"=>"social_worker","PA"=>"personal_assistant"]);
define('AVATAR_PATH',$path);
define('AVATAR_URL',$url);
define('FILE_PATH',$file_path);
define('FILE_URL',$file_url);
define('PDF_PATH',$pdf_path);
define('PDF_URL',$pdf_url);
function cl_acf_set_language() {
    return acf_get_setting('default_language');
}

function get_global_option($name) {
    add_filter('acf/settings/current_language', 'cl_acf_set_language', 100);
    $option = get_field($name, 'option');
    remove_filter('acf/settings/current_language', 'cl_acf_set_language', 100);
    return $option;
}

add_action('after_setup_theme', 'remove_admin_bar');
function remove_admin_bar() {
    if (!current_user_can('administrator') && !is_admin()) {
        show_admin_bar(false);
    }
}

add_filter( 'redirect_canonical','sbt_disable_redirect_canonical' );
function sbt_disable_redirect_canonical( $redirect_url ) {
  return false;
}

require_once "class/class_common_functionality.php";
require_once "class/class_custom_tables.php";
require_once "class/class_custom_post_type.php";
require_once "class/class_json_api.php";
require_once "class/API/class_badges_api.php";
require_once "class/API/class_contact_api.php";
require_once "class/API/class_pathway_api.php";
require_once "class/API/class_tasks_api.php";
require_once "class/API/class_users_api.php";

// removes admin color scheme options
remove_action( 'admin_color_scheme_picker', 'admin_color_scheme_picker' );
//Removes the leftover 'Visual Editor', 'Keyboard Shortcuts' and 'Toolbar' options.
add_action( 'admin_head', function () {
	ob_start( function( $subject ) {
		$subject = preg_replace( '#<h[0-9]>'.__("Personal Options").'</h[0-9]>.+?/table>#s', '', $subject, 1 );
		$subject = preg_replace( '#<h[0-9]>'.__("About the user").'</h[0-9]>.+?/table>#s', '', $subject, 1 );
		return $subject;
	});
});
add_action( 'admin_footer', function(){
	ob_end_flush();
});  