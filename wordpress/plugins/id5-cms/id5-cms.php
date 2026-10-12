<?php
/**
 * Plugin Name:       ID5 CMS
 * Plugin URI:        https://github.com/immigration-division-5/id5-thailand
 * Description:       Turns this WordPress install into the editing layer for the ID5 @ Thailand 5.0 public website. Registers the site's content types with Thai labels, adds the Customizer panels for site-wide text, and pushes every change to the public website.
 * Version:           1.7.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Immigration Division 5
 * Text Domain:       id5-cms
 *
 * @package ID5_CMS
 */

defined( 'ABSPATH' ) || exit;

define( 'ID5_CMS_VERSION', '1.7.0' );
define( 'ID5_CMS_FILE', __FILE__ );
define( 'ID5_CMS_DIR', plugin_dir_path( __FILE__ ) );
define( 'ID5_CMS_URL', plugin_dir_url( __FILE__ ) );

require_once ID5_CMS_DIR . 'includes/class-id5-schema.php';
require_once ID5_CMS_DIR . 'includes/class-id5-post-types.php';
require_once ID5_CMS_DIR . 'includes/class-id5-fields.php';
require_once ID5_CMS_DIR . 'includes/class-id5-customizer.php';
require_once ID5_CMS_DIR . 'includes/class-id5-serializer.php';
require_once ID5_CMS_DIR . 'includes/class-id5-rest.php';
require_once ID5_CMS_DIR . 'includes/class-id5-import.php';
require_once ID5_CMS_DIR . 'includes/class-id5-webhook.php';
require_once ID5_CMS_DIR . 'includes/class-id5-roles.php';
require_once ID5_CMS_DIR . 'includes/class-id5-admin.php';
require_once ID5_CMS_DIR . 'includes/class-id5-site-editor.php';
require_once ID5_CMS_DIR . 'includes/class-id5-texts.php';
require_once ID5_CMS_DIR . 'includes/class-id5-numbering.php';
require_once ID5_CMS_DIR . 'includes/class-id5-uploads-store.php';

/**
 * Boot every subsystem once WordPress is ready.
 */
function id5_cms_boot() {
	ID5_Post_Types::init();
	ID5_Fields::init();
	ID5_Customizer::init();
	ID5_REST::init();
	ID5_Import::init();
	ID5_Webhook::init();
	ID5_Roles::init();
	ID5_Admin::init();
	ID5_Site_Editor::init();
	ID5_Texts::init();
	ID5_Numbering::init();
	ID5_Uploads_Store::init();
}
add_action( 'plugins_loaded', 'id5_cms_boot' );

/**
 * On activation: register post types once so their rewrite rules exist, create
 * the Website Editor role, and seed the fixed-set content that administrators
 * may edit but must not create or delete.
 */
function id5_cms_activate() {
	ID5_Post_Types::register();
	ID5_Roles::add_role();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'id5_cms_activate' );

/**
 * On deactivation: drop the rewrite rules but keep all content and the role, so
 * reactivating is lossless and deactivating never destroys anything.
 */
function id5_cms_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'id5_cms_deactivate' );
