<?php
/**
 * Class Provider for vjoon WordPress Adapter
 *
 * @author Christian Storm
 * @package vjoon\Adapter
 * @copyright 2022 vjoon GmbH
 */

namespace vjoon\Adapter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // exit if accessed directly.
}

final class Provider {

	/**
	 * Create or recreate a user with roles and caps for the vjoon WordPress provider.
	 *
	 * @return void
	 */
	public static function init_user() {
		// Makes sure the plugin is defined before trying to use it.
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		// recreate roles.
		remove_role( App::$option->provider->Role );
		add_role(
			App::$option->provider->Role,
			'vjoon WordPress Provider',
			array(
				'upload_files' => true,
				'edit_posts'   => true,
				'edit_pages'   => true,
			)
		);

		// create vjoon Provider User if not exists.
		if ( ! username_exists( App::$option->provider->User ) ) {
			$user_id = wp_create_user( App::$option->provider->User, wp_generate_password( 24, true, true ), '' );
			$user    = get_user_by( 'ID', $user_id );
			$user->set_role( App::$option->provider->Role );

		} else { // reset role and password.
			$_user    = get_user_by( 'login', App::$option->provider->User );
			$_user_id = $_user->ID;
			$_user->set_role( App::$option->provider->Role );
			$_new_pass = wp_generate_password( 24, true, true );
			\wp_set_password( $_new_pass, $_user_id );

		}
	}

	public static function remove_user() {
		$old_user = get_user_by( 'login', Settings::$provider_username . $mu_user_appendix );
		$new_user = get_option( 'vjwpad_assignUser' );
		$result   = self::_wp_delete_user( $old_user->ID, empty( $new_user ) ? null : $new_user );
		Debug::log( $result, 'Remove internal user with ID ' . $old_user->ID );
		remove_role( Settings::$provider_rolename );
	}

	/**
	 * From WordPress class user.php to override wp_delete_user().
	 *
	 * @param [type]  $id Id.
	 * @param [type]  $reassign Reassign.
	 * @param Boolean $delete_on_reassign_null Delete if null on reassign.
	 * @return Boolean
	 */
	private static function _wp_delete_user( $id, $reassign = null, $delete_on_reassign_null = true ) {
		global $wpdb;

		if ( ! is_numeric( $id ) ) {
			return false;
		}

		$id   = (int) $id;
		$user = new \WP_User( $id );

		if ( ! $user->exists() ) {
			return false;
		}

		// Normalize $reassign to null or a user ID. 'novalue' was an older default.
		if ( 'novalue' === $reassign ) {
			$reassign = null;
		} elseif ( null !== $reassign ) {
			$reassign = (int) $reassign;
		}

		/**
		 * Fires immediately before a user is deleted from the database.
		 *
		 * @since 2.0.0
		 * @since 5.5.0 Added the `$user` parameter.
		 *
		 * @param int      $id       ID of the user to delete.
		 * @param int|null $reassign ID of the user to reassign posts and links to.
		 *                           Default null, for no reassignment.
		 * @param WP_User  $user     WP_User object of the user to delete.
		 */
		do_action( 'delete_user', $id, $reassign, $user );

		if ( null === $reassign ) {
			if ( $delete_on_reassign_null ) {
				$post_types_to_delete = array();
				foreach ( get_post_types( array(), 'objects' ) as $post_type ) {
					if ( $post_type->delete_with_user ) {
						$post_types_to_delete[] = $post_type->name;
					} elseif ( null === $post_type->delete_with_user && post_type_supports( $post_type->name, 'author' ) ) {
						$post_types_to_delete[] = $post_type->name;
					}
				}

				/**
				 * Filters the list of post types to delete with a user.
				 *
				 * @since 3.4.0
				 *
				 * @param string[] $post_types_to_delete Array of post types to delete.
				 * @param int      $id                   User ID.
				 */
				$post_types_to_delete = apply_filters( 'post_types_to_delete_with_user', $post_types_to_delete, $id );
				$post_types_to_delete = implode( "', '", $post_types_to_delete );
				$post_ids             = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_author = %d AND post_type IN ('$post_types_to_delete')", $id ) );
				if ( $post_ids ) {
					foreach ( $post_ids as $post_id ) {
						wp_delete_post( $post_id, true );
					}
				}

				// Clean links.
				$link_ids = $wpdb->get_col( $wpdb->prepare( "SELECT link_id FROM $wpdb->links WHERE link_owner = %d", $id ) );

				if ( $link_ids ) {
					foreach ( $link_ids as $link_id ) {
						wp_delete_link( $link_id );
					}
				}
			}
		} else {
			$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_author = %d", $id ) );
			$wpdb->update( $wpdb->posts, array( 'post_author' => $reassign ), array( 'post_author' => $id ) );
			if ( ! empty( $post_ids ) ) {
				foreach ( $post_ids as $post_id ) {
					clean_post_cache( $post_id );
				}
			}
			$link_ids = $wpdb->get_col( $wpdb->prepare( "SELECT link_id FROM $wpdb->links WHERE link_owner = %d", $id ) );
			$wpdb->update( $wpdb->links, array( 'link_owner' => $reassign ), array( 'link_owner' => $id ) );
			if ( ! empty( $link_ids ) ) {
				foreach ( $link_ids as $link_id ) {
					clean_bookmark_cache( $link_id );
				}
			}
		}

		// FINALLY, delete user.
		if ( is_multisite() ) {
			remove_user_from_blog( $id, get_current_blog_id() );
		} else {
			$meta = $wpdb->get_col( $wpdb->prepare( "SELECT umeta_id FROM $wpdb->usermeta WHERE user_id = %d", $id ) );
			foreach ( $meta as $mid ) {
				delete_metadata_by_mid( 'user', $mid );
			}

			$wpdb->delete( $wpdb->users, array( 'ID' => $id ) );
		}

		clean_user_cache( $user );

		/**
		 * Fires immediately after a user is deleted from the database.
		 *
		 * @since 2.9.0
		 * @since 5.5.0 Added the `$user` parameter.
		 *
		 * @param int      $id       ID of the deleted user.
		 * @param int|null $reassign ID of the user to reassign posts and links to.
		 *                           Default null, for no reassignment.
		 * @param WP_User  $user     WP_User object of the deleted user.
		 */
		do_action( 'deleted_user', $id, $reassign, $user );

		return true;
	}
}
