<?php
/**
 * SOFAV Photo Gallery
 *
 * @package           SOFAV Photo Gallery
 * @author            SOFAV (info@sofav.nl)
 * @copyright         2023 SOFAV
 *
 * @wordpress-plugin
 * Plugin Name:       SOFAV Photo Gallery
 * Plugin URI:        https://sofav.nl/wordpress-plugins/sofav-photo-gallery/
 * Description:       SOFAV Photo Gallery is a simple plugin to create a photo gallery.
 * Version:           0.1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Max van den Bosch (SOFAV)
 * Author URI:        https://sofav.nl/
 * Text Domain:       sofav-photo-gallery
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('SOFAV_PHOTO_GALLERY_VERSION', '0.1.0');
define('SOFAV_PHOTO_GALLERY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SOFAV_PHOTO_GALLERY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SOFAV_PHOTO_GALLERY_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('SOFAV_PHOTO_GALLERY_PLUGIN_FILE', __FILE__);

// Include the main SOFAV Photo Gallery class.
if (!class_exists('SOFAV_Photo_Gallery')) {
    include_once dirname(__FILE__) . '/lib/SOFAV_Photo_Gallery.php';
}

function sofav_photo_gallery_register_block(){
    register_block_type( __DIR__ );
}

// Instantiate the SOFAV Photo Gallery class.
$sofav_photo_gallery = new SOFAV_Photo_Gallery();

add_action('init', array($sofav_photo_gallery, 'init'));
add_action('init', 'sofav_photo_gallery_register_block');

// Register activation and deactivation hooks.
register_activation_hook(__FILE__, array($sofav_photo_gallery, 'activate'));
register_deactivation_hook(__FILE__, array($sofav_photo_gallery, 'deactivate'));

// Register uninstall hook.
register_uninstall_hook(__FILE__, array('SOFAV_Photo_Gallery', 'uninstall'));

// Register the plugin's activation notice.
add_action('admin_notices', array($sofav_photo_gallery, 'activation_notice'));