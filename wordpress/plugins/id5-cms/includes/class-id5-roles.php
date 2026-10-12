<?php
/**
 * The "ผู้ดูแลเว็บไซต์" (Website Editor) role.
 *
 * Content administrators get everything they need to run the website and
 * nothing that can break the installation: no plugin, theme, user, core-update
 * or file-editing capability. Only one or two technical owners keep the native
 * Administrator role.
 *
 * @package ID5_CMS
 */

defined( 'ABSPATH' ) || exit;

class ID5_Roles {

	const ROLE = 'id5_editor';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'ensure_role' ), 20 );
	}

	/**
	 * Capabilities granted to a website editor.
	 */
	public static function capabilities() {
		return array(
			// Enough of the admin to work in.
			'read'                   => true,

			// Full control of content, including other people's.
			'edit_posts'             => true,
			'edit_others_posts'      => true,
			'edit_published_posts'   => true,
			'edit_private_posts'     => true,
			'publish_posts'          => true,
			'delete_posts'           => true,
			'delete_others_posts'    => true,
			'delete_published_posts' => true,
			'delete_private_posts'   => true,
			'read_private_posts'     => true,

			// Pages, in case plain pages are ever added.
			'edit_pages'             => true,
			'edit_others_pages'      => true,
			'edit_published_pages'   => true,
			'publish_pages'          => true,
			'delete_pages'           => true,

			// Media and categories.
			'upload_files'           => true,
			'manage_categories'      => true,

			// Menus and the Customizer panels, but not theme switching.
			'edit_theme_options'     => true,

			// Explicitly withheld: install_plugins, activate_plugins,
			// switch_themes, edit_themes, edit_plugins, manage_options,
			// list_users, create_users, edit_users, delete_users, update_core,
			// export, import, unfiltered_html.
		);
	}

	/**
	 * Create the role. Called on activation and kept idempotent so a capability
	 * added in a later version is picked up without a reinstall.
	 */
	public static function add_role() {
		remove_role( self::ROLE );
		add_role( self::ROLE, 'ผู้ดูแลเว็บไซต์ (Website Editor)', self::capabilities() );
		update_option( 'id5_roles_version', ID5_CMS_VERSION, false );
	}

	public static function ensure_role() {
		if ( ! get_role( self::ROLE ) || get_option( 'id5_roles_version' ) !== ID5_CMS_VERSION ) {
			self::add_role();
		}
	}
}
