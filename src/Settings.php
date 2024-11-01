<?php
/**
 * Class Settings
 *
 * SettingsClass for vjoon WordPress Adapter
 *
 * @author Christian Storm
 * @package vjoon\Adapter
 * @copyright 2022 vjoon GmbH
 */

namespace vjoon\Adapter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

final class Settings {

	public static $provider_rolename = 'vjoon_wp_provider';
	public static $provider_username = 'vj_wp_provider';
	public static $app_id            = '17b7d145-e0cc-49ab-916f-dd477120d4d5';
	public static $app_name          = 'vjoon WordPress Adapter';

	private static $option;

	public function __construct( $caller ) {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		} //note: needed to get plugin data.

		$is_network_activated = is_plugin_active_for_network( basename( dirname( $caller ) ) . '/Adapter.php' ); // is activated network wide (global under multisite)?

		// note: Switch get options, depending on is network activated or Blog activated.
		$temp = (object) array();
		if ( $is_network_activated ) {
			$temp = empty( get_site_option( 'vjwpad_settings' ) ) ? (object) array(
				'general' => (object) array(),
				'api'     => (object) array(),
			) : get_site_option( 'vjwpad_settings' );
		} else {
			$temp = empty( get_option( 'vjwpad_settings' ) ) ? (object) array(
				'general' => (object) array(),
				'api'     => (object) array(),
			) : get_option( 'vjwpad_settings' );
		}
		self::$option = $temp;

		// note: Standard plugin settings, always be rewritten.
		self::$option->plugin               = (object) array();
		self::$option->plugin->Is_Multisite = is_multisite();
		self::$option->plugin->Is_Network   = empty( $is_network_activated ) ? 0 : $is_network_activated;
		self::$option->plugin->Url          = plugins_url( '/', $caller );
		self::$option->plugin->Dir          = plugin_dir_path( $caller );
		self::$option->plugin->Version      = get_plugin_data( $caller, false, false )['Version'];
		if ( self::$option->plugin->Version == '${VERSION_STRING}' ) {
			self::$option->plugin->Version = str_replace( PHP_EOL, '', Functions::get_file_content( self::$option->plugin->Dir . '/version.txt' ) );
		}
		self::$option->plugin->Name                 = get_plugin_data( $caller, false, false )['Name'];
		self::$option->plugin->LanguageResourcePath = 'resources/LocalizedStrings/';

		self::$option->provider       = (object) array();
		self::$option->provider->User = self::$provider_username;
		self::$option->provider->Role = self::$provider_rolename;

		// note: configurable via settings page, except crypt_key
		self::$option->general                   = isset( $temp->general ) ? $temp->general : (object) array();
		self::$option->general->crypt_key        = isset( $temp->general->crypt_key ) && ! empty( $temp->general->crypt_key ) ? $temp->general->crypt_key : substr( md5( time() ), -16 );
		self::$option->general->minified_jscss   = isset( $temp->general->minified_jscss ) && ! empty( $temp->general->minified_jscss ) ? '.min' : '';
		self::$option->general->compression_ajax = isset( $temp->general->compression_ajax ) && ! empty( $temp->general->compression_ajax ) ? 1 : 0;
		self::$option->general->inline_style     = isset( $temp->general->inline_style ) && ! empty( $temp->general->inline_style ) ? 1 : 0;
		self::$option->general->debug            = isset( $temp->general->debug ) && ! empty( $temp->general->debug ) ? $temp->general->debug : 0;

		self::$option->api = isset( $temp->api ) ? $temp->api : (object) array();

	}

	public function get_options() {
		return self::$option;
	}

	public function set_options( $option ) {
		self::$option = $option;
	}

	/**
	 * Set Option key with value.
	 *
	 * @param String $key Option key.
	 * @param Mixed  $value Option value.
	 * @param String $type Option type.
	 * @return void
	 */
	public function set( $key, $value, $type = 'general' ) {
		self::$option->{$type}->{$key} = $value;
		Debug::log( 'Set Setting Key ' . $key . ' of type ' . $type . ' to value ' . $value );
	}

	public function get( $key, $type = 'general' ) {
		$val = isset( self::$option->{$type}->{$key} ) ? self::$option->{$type}->{$key} : null;
		Debug::log( 'Get Setting Key ' . $key . ' of type ' . $type . ' with value ' . $val );
		return $val;
	}

	/**
	 * Save Settings.
	 *
	 * @return Boolean
	 */
	public function save() {
		if ( self::$option->plugin->Is_Network ) {
			return update_site_option( 'vjwpad_settings', self::$option );
		} else {
			return update_option( 'vjwpad_settings', self::$option );
		}
	}

}
