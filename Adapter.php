<?php
/**
 * Plugin Name: vjoon WP Adapter
 * Plugin URI: https://vjoon.com/what-we-offer/integrate-automate-with-vjoonk4/#export-your-content-to-wordpress
 * Author: vjoon GmbH
 * Author URI: https://vjoon.com
 * Description: vjoon WordPress Adapter for vjoon K4
 * Version: 3.0.0
 * Text Domain: vj-wp-adapter
 * Domain Path: resources/LocalizedStrings
 **/

namespace vjoon\Adapter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require __DIR__ . '/vendor/autoload.php';

if ( ! class_exists( 'vjoon\Adapter\App' ) ) :

	final class App {
		private static $instance;
		public static $settings;
		public static $option;

		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof App ) ) {
				self::$instance = new App();
				self::$settings = new Settings( __FILE__ ); // Instance of Settings - Class.
				self::$option   = self::$settings->get_options(); // Holds options Application wide.

				register_activation_hook( __FILE__, array( self::$instance, 'app_activation' ) );
				register_deactivation_hook( __FILE__, array( self::$instance, 'app_deactivation' ) );

				$endpoints_v1 = new Endpoints(); // register Endpoints and write rewrite Rules.
				$endpoints_v2 = new EndpointsV2(); // register Endpoints and write rewrite Rules.

				if ( is_admin() ) { // add actions, filter only to backend.
					add_action( 'admin_menu', array( self::$instance, 'app_admin_menu' ) ); // add admin_menu_page.
					add_action( 'init', array( self::$instance, 'app_init_call' ) ); // use init for setting per_page option for WP List Table.

					add_action( 'wp_ajax_app_ajaxcall', array( self::$instance, 'app_ajaxcall' ) ); // ajax for logged on users.

					// enqueue Scripts/Styles Backend.
					add_action( 'admin_enqueue_scripts', array( self::$instance, 'app_enqueue_admin_assets' ) );

					// note: action to hide posts, pages from vj Provider User.
					add_action( 'pre_get_posts', array( self::$instance, 'app_hide_posts' ) );
					add_filter( 'views_edit-post', array( self::$instance, 'app_hide_posts_views' ) ); // note: hide Views Tabs over List.

					// note: hide ProviderUser from UserList.
					add_action( 'pre_user_query', array( self::$instance, 'app_hide_provider_user' ) );
					add_filter( 'views_users', array( self::$instance, 'app_view_users' ) );
					add_action( 'editable_roles', array( self::$instance, 'app_editable_roles' ) );

					// note: action before a post will be deleted -> delete all related posts to post.
					add_action( 'before_delete_post', array( self::$instance, 'app_before_delete_post' ), 10, 1 );

					// note: prevent plugin update via WordPress backend, only needed if plugin is hosted on WordPress plugin browser.
					if ( self::$option->general->debug ) {
						add_filter( 'site_transient_update_plugins', array( self::$instance, 'app_update_manager' ) );
					}

					// note: add settings to plugin overview page.
					$plugin = plugin_basename( __FILE__ );
					add_filter( 'plugin_action_links_' . $plugin, array( self::$instance, 'app_plugin_settings_link' ) );
					add_filter( 'plugin_row_meta', array( self::$instance, 'app_plugin_row_meta' ), 10, 4 );

					// note: add language support to app.
					add_action( 'plugins_loaded', array( self::$instance, 'app_add_language_support' ) );
					add_filter( 'load_textdomain_mofile', array( self::$instance, 'app_language_fallback' ), 10, 2 );

					// note: prevent post, page, cpt edit if k4exclusive=true.
					add_filter( 'user_has_cap', array( self::$instance, 'app_user_has_cap' ), 10, 3 );
					// note: add preview to exclusive posts.
					add_filter( 'post_row_actions', array( self::$instance, 'app_post_row_actions' ) );

					// note: add debug metabox to post edit.
					if ( self::$option->general->debug ) {
						add_action( 'add_meta_boxes', array( self::$instance, 'app_add_debug_metabox' ) ); }
				} else { // only on frontend.
					// note: action only called on frontend.
					add_action( 'wp_enqueue_scripts', array( self::$instance, 'app_enqueue_frontend_assets' ) );

					// note: remove adminbar if ProviderUser.
					add_filter( 'show_admin_bar', array( self::$instance, 'app_hide_adminbar' ) );

					// note: handle preview.
					add_filter( 'query_vars', array( self::$instance, 'app_handle_preview_query_vars' ) );
					add_action( 'template_redirect', array( self::$instance, 'app_template_redirect' ) );

				}

				// note: add cron job to delete.
				add_action( 'vjoon_adapter_garbage_collector', array( self::$instance, 'app_garbage_collector' ) );

				return self::$instance;
			}

		}

		public function app_admin_menu() {
			add_options_page( __( 'vj-wp-adapter.adapter.admin.page.title', 'vj-wp-adapter' ), __( 'vj-wp-adapter.adapter.admin.menu.title', 'vj-wp-adapter' ), 'manage_options', 'vj_wp_adapter_settings', array( self::$instance, 'app_settings' ) );
			if ( self::$option->general->debug ) {
				add_management_page( 'vjoon SupportMode', 'vjoon SupportMode', 'manage_options', 'vj_wp_adapter_debug', array( self::$instance, 'app_debug' ) ); }

		}

		public function app_init_call() {
			Endpoints::register_endpoints(); // note: Register only /_preview/.

		}

		public function app_admin() {

		}

		/**
		 * Show Settings Page
		 *
		 * @return void
		 */
		public function app_settings() {
			include 'lib/backend/settings.php';
		}

		/**
		 * Only Internal
		 *
		 * @return void
		 */
		public function app_debug() {

			global $wp_rewrite; // note: display RewriteRules.
			Debug::log( $wp_rewrite, 'REWRITE RULES', true );

			$nonce = wp_create_nonce( 'wp_rest' ); // note: display nonce.
			Debug::log( $nonce, 'NONCE', true );

			global $shortcode_tags; // note: display available shortcodes.
			Debug::log( $shortcode_tags, 'AVAILABLE SHORTCODES', true );

			$block_types = \WP_Block_Type_Registry::get_instance()->get_all_registered();
			Debug::log( $block_types, 'AVAILABLE BLOCKTYPES registered via PHP (no Clientside)', true );

			global $wp_embed; // same like $GLOBALS['wp_embed'].
			Debug::log( $wp_embed, 'WP_EMBED', true );

		}

		/**
		 * Output readme.md file
		 *
		 * @return void
		 */
		public function app_readme() {
			include 'lib/backend/readme.php';
		}

		public function app_enqueue_admin_assets( $page ) {
			if ( is_admin() ) {

				// main functions.
				Functions::enqueue_style_file( '/assets/css/app.css' );
				Functions::enqueue_script_file( '/assets/js/app.js' );
				Functions::enqueue_script_file( '/assets/js/get.js' );

				// REGISTER Theme DEBUG Style and Script only if debug is on.
				if ( self::$option->general->debug ) {
					Functions::enqueue_style_file( '/assets/css/debug.css' );
					Functions::enqueue_script_file( '/assets/js/debug.js' );
				}
			}
			if ( $page == 'plugins.php' ) {
				// messagebox.
				Functions::enqueue_style_file( '/assets/css/messagebox.css' );
				Functions::enqueue_script_file( '/assets/js/messagebox.js' );
				// add/override plugin deactivation confirm.
				Functions::enqueue_script_file( '/assets/js/plugins.js' );
			}
		}

		public function app_enqueue_frontend_assets() {

		}

		public function app_ajaxcall() {
			Functions::ajaxcall();
		}

		public function app_hide_adminbar() {
			return Functions::hide_adminbar();
		}

		public function app_hide_provider_user( $user_search ) {
			Functions::hide_provider_user( $user_search );
		}

		public function app_view_users( $views ) {
			return Functions::override_view_users( $views );

		}

		public function app_editable_roles( $roles ) {
			return Functions::override_editable_roles( $roles );
		}

		public function app_garbage_collector() {
			Functions::garbage_collector();
		}

		public function app_hide_posts( $query ) {
			Functions::hide_provider_preview_posts( $query );
		}

		public function app_hide_posts_views( $views ) {
			return Functions::override_view_posts( $views );
		}

		public function app_handle_preview_query_vars( $vars ) {
			$vars[] = 'content_id';
			$vars[] = 'vjoon';
			return $vars;
		}

		public function app_before_delete_post( $postid ) {
			Functions::delete_related_posts( $postid );
		}

		public function app_template_redirect() {

			// note: change the Date on Frontend, only if Preview.
			add_filter( 'the_time', array( self::$instance, 'app_change_preview_time' ), 10, 2 );
			add_filter( 'get_the_time', array( self::$instance, 'app_change_preview_time' ), 10, 3 );
			// available: the_modified_time, get_the_modified_time.

			add_filter( 'the_date', array( self::$instance, 'app_change_preview_date' ), 10, 4 );
			add_filter( 'get_the_date', array( self::$instance, 'app_change_preview_date' ), 10, 3 );
			// available: the_modified_date, get_the_modified_date.

			// note: change the Author on Frontend only for vjoon Provider User.
			add_filter( 'the_author', array( self::$instance, 'app_change_author' ) );

			// note: change the Author Link on Frontend only for vjoon Provider User.
			add_filter( 'author_link', array( self::$instance, 'app_change_author_link' ), 10, 3 );

			return Functions::override_wp_query_for_preview();
		}

		public function app_change_author( $author ) {
			return Functions::override_frontend_provider_author( $author );
		}

		public function app_change_preview_date( $the_date, $d, $post, $after = null ) {
			return Functions::override_frontend_preview_date( $the_date, $d, $post, $after );
		}

		public function app_change_preview_time( $the_time, $d, $post = null ) {
			return Functions::override_frontend_preview_time( $the_time, $d, $post );
		}

		public function app_change_author_link( $link, $author_id, $author_nicename ) {
			return Functions::override_frontend_author_link( $link, $author_id, $author_nicename );
		}

		public function app_manage_admin_columns( $columns ) {
			// future: columns könnten hier custom columns angehangen werden.
			unset( $columns['author'] );
			$columns['_author'] = __( 'vj-wp-adapter.adapter.admin.author.label', 'vj-wp-adapter' );
			Debug::log( $columns, 'MANAGE_POSTS_COLUMNS Filter' );
			return $columns;
		}

		public function app_manage_admin_columns_data( $column_name, $post_ID ) {
			// future: this is future.
			Debug::log(
				array(
					'column_name' => $column_name,
					'postId'      => $post_ID,
				),
				'manage_posts_custom_column action'
			);
			if ( $column_name == '_author' ) {
				$post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( $_GET['post_type'] ) : 'post';
				$author    = get_the_author();
				$id        = get_the_author_meta( 'ID' );
				if ( $author == APP::$option->provider->User ) { // note: only for vjoon Provider User.
					$name = Functions::get_provider_author( $post_ID );
					echo "<a href='?post_type=" . esc_attr( $post_type ) . '&author=' . esc_attr( $id ) . '&t=' . esc_attr( $author ) . '&n=' . esc_attr( $name ) . "'>" . esc_html( $name ) . '</a>';

				} else {
					echo "<a href='?post_type=" . esc_attr( $post_type ) . '&author=' . esc_attr( $id ) . "'>" . esc_html( $author ) . '</a>';

				}
			}

		}

		public function app_plugin_settings_link( $links ) {
			return Functions::add_plugin_link( $links, '<a href="options-general.php?page=vj_wp_adapter_settings">Settings</a>' );
		}

		public function app_plugin_row_meta( $links_array, $plugin_file_name, $plugin_data, $status ) {
			return Functions::add_plugin_row_meta( $links_array, $plugin_file_name, $plugin_data, $status );
		}

		public function app_add_language_support() {
			$basename      = basename( dirname( __FILE__ ) );
			$language_path = $basename . '/' . self::$option->plugin->LanguageResourcePath;
			$rtn           = load_plugin_textdomain( 'vj-wp-adapter', false, $language_path );
		}

		public function app_language_fallback( $mofile, $domain ) {
			return Functions::load_language_mofile( $mofile, $domain );
		}

		public function app_user_has_cap( $allcaps, $caps, $args ) {
			return Functions::exclude_posts( $allcaps, $caps, $args );
		}

		public function app_post_row_actions( $actions ) {
			return Functions::add_preview_exclude_posts( $actions );
		}

		public function app_use_gutenberg_for_post( $use_gb, $post ) {
			return ! Functions::is_classic_post( $post->ID );
		}

		public function app_extend_gutenberg_block() {
			Functions::enqueue_script_file( '/assets/js/gb-blocks/image.js', false, false, array( 'wp-blocks', 'wp-dom-ready', 'wp-edit-post' ) );

		}

		/**
		 * Add Metabox for Post Edit, only called if Debug is true.
		 *
		 * @param [Type] $post_type Post Type.
		 * @return void
		 */
		public function app_add_debug_metabox( $post_type ) {
			Functions::add_debug_metabox( $post_type );
		}

		/**
		 * On plugin activation: register endpoints and flush rewrite rules.
		 *
		 * @param Boolean $network_wide Network wide.
		 * @return void
		 */
		public function app_activation( $network_wide ) {
			if ( is_multisite() && $network_wide ) {
				// note: this plugin can not be activated network_wide.
				deactivate_plugins( plugin_basename( __FILE__ ), true, true );

				$args = array(
					'link_url'  => esc_url( network_admin_url( 'plugins.php' ) ),
					'link_text' => esc_html( __( 'vj-wp-adapter.adapter.admin.mu_activation.btn.back.label', 'vj-wp-adapter' ) ),
				);
				wp_die( esc_html( __( 'vj-wp-adapter.adapter.admin.mu_activation.label', 'vj-wp-adapter' ) ), esc_html( '' ), $args );

			}
			if ( ! wp_next_scheduled( 'vjoon_adapter_garbage_collector' ) ) {
				$today = strtotime( 'today 3:00' );// note: first run on time 03:00am.
				wp_schedule_event( $today, 'daily', 'vjoon_adapter_garbage_collector' );
			}

			Functions::after_activation();
			register_uninstall_hook( __FILE__, 'app_uninstall_dummy' );
		}

		/**
		 * On plugin deactivation: flush rewrite rules and remove Adapter Data.
		 *
		 * @return void
		 */
		public function app_deactivation() {
			wp_clear_scheduled_hook( 'vjoon_adapter_garbage_collector' );

			Functions::after_deactivation();
		}

		public function app_update_manager( $transient ) {
			if ( self::$option->general->debug ) {
				if ( $transient ) {
					unset( $transient->response[ plugin_basename( __FILE__ ) ] );
				}
			}
			return $transient;
		}

		/**
		 * Uninstall Action
		 *
		 * @return void
		 */
		public function app_uninstall_dummy() {
			// uninstall dummy function, uninstallation is done by uninstall.php.
		}

	}

endif;

function init_vjwpadapter() {
	return App::instance();
}

init_vjwpadapter();
