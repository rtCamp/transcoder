<?php
/**
 * Plugin Name: rtMedia Transcoder
 * Plugin URI: https://rtmedia.io/products/rtmedia-transcoder/
 * Description: This plugin converts your uploaded videos into the mp4 format. If you have rtMedia plugin installed on your website then this plugin will be helpful to convert your uploaded videos into mp4 format, it also generates the thumbnails for your video.
 * Version: 1.0
 * Text Domain: rtmedia-transcoder
 * Author: rtCamp
 * Author URI: http://rtcamp.com/?utm_source=dashboard&utm_medium=plugin&utm_campaign=rtmedia-transcoder
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package rtMediaTranscoder
 */

if ( ! defined( 'RTMEDIA_TRANSCODER_PATH' ) ) {
	/**
	 *  The server file system path to the plugin directory
	 */
	define( 'RTMEDIA_TRANSCODER_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'RTMEDIA_TRANSCODER_URL' ) ) {
	/**
	 * The url to the plugin directory
	 */
	define( 'RTMEDIA_TRANSCODER_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'RTMEDIA_TRANSCODER_BASE_NAME' ) ) {
	/**
	 * The base name of the plugin directory
	 */
	define( 'RTMEDIA_TRANSCODER_BASE_NAME', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'RTMEDIA_TRANSCODER_VERSION' ) ) {
	/**
	 * The version of the plugin
	 */
	define( 'RTMEDIA_TRANSCODER_VERSION', '1.0' );
}

if ( ! defined( 'EDD_RTMEDIA_TRANSCODER_STORE_URL' ) ) {
	// this is the URL our updater / license checker pings. This should be the URL of the site with EDD installed.
	define( 'EDD_RTMEDIA_TRANSCODER_STORE_URL', 'https://rtmedia.io/' );
}

if ( ! defined( 'EDD_RTMEDIA_TRANSCODER_ITEM_NAME' ) ) {
	// the name of your product. This should match the download name in EDD exactly.
	define( 'EDD_RTMEDIA_TRANSCODER_ITEM_NAME', 'rtMedia Transcoder' );
}

require_once RTMEDIA_TRANSCODER_PATH . 'admin/rtmedia-transcoder-functions.php';
require_once RTMEDIA_TRANSCODER_PATH . 'admin/rtmedia-transcoder-admin.php';

global $rtmedia_transcoder_admin;

$rtmedia_transcoder_admin = new RTMedia_Transcoder_Admin();
