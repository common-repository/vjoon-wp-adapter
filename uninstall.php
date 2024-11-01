<?php
/**
 *
 * uninstall.php
 *
 * Methods called on plugin uninstall
 * 
 * @author Christian Storm
 * @package vjoon\Adapter
 * @copyright 2022 vjoon GmbH
 */

namespace vjoon\Adapter;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit(); } else {

	require __DIR__ . '/vendor/autoload.php';

	Debug::log( 'App Uninstall process started...', '', false, false, true );

	delete_option( 'vjwpad_settings' );

	Debug::log( 'Uninstallprocess started', '', false, false, true );
	Functions::garbage_collector( time() );

	Provider::remove_user();
	delete_option( 'vjwpad_assignUser' );

	/**
	 * note:
	 * 1. via _pluginOrigin (APP::$option->plugin->Name) = vjoon WP Adapter -> alle POST IDs sammeln
	 * 2. alle o.g. Metafelder der gesammelten POST IDs löschen delete_post_meta()
	 */
	$_plugin_origin = 'vjoon WP Adapter';
	Debug::log( $_plugin_origin, 'PluginOrigin', false, false, true );

	global $wpdb;

	$result = $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta where meta_key = %s and meta_value = %s", '_pluginOrigin', $_plugin_origin ) );
	if ( $result ) {
		foreach ( $result as $row ) {
			Debug::log( $row->post_id, 'delete metadata from post_id', false, false, true );
			delete_post_meta( $row->post_id, 'author' );
			delete_post_meta( $row->post_id, 'contentId' );
			delete_post_meta( $row->post_id, '_origin' );
			delete_post_meta( $row->post_id, '_section' );
			delete_post_meta( $row->post_id, '_version' );
			delete_post_meta( $row->post_id, '_pluginOrigin' );
			delete_post_meta( $row->post_id, '_editor' );
			delete_post_meta( $row->post_id, '_k4exclusive' );
			delete_post_meta( $row->post_id, '_language' );
			delete_post_meta( $row->post_id, '_raw' );
			delete_post_meta( $row->post_id, '_preview' );
		}
	}

	Debug::log( 'Uninstallprocess done', '', false, false, true );
	}


