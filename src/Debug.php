<?php
/**
 * Class Functions
 *
 * DebugClass for vjoon WordPress Adapter
 *
 * @author Christian Storm
 * @package vjoon\Adapter
 * @copyright 2022 vjoon GmbH
 */

namespace vjoon\Adapter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

final class Debug {

	/**
	 * Debug Output Function
	 *
	 * @param Object  $obj - log object, can be any type: e.g. Array, String, Object...
	 * @param string  $title - output Title.
	 * @param boolean $output - log to debug.log (only if WP_DEBUG is set to true) if set to false or to Browser if set to true.
	 * @param boolean $die - exit after output if set to true.
	 * @param boolean $force - force output if set to true.
	 * @return void
	 */
	public static function log( $obj, $title = '', $output = false, $die = false, $force = false ) {
		self::print_debug( $obj, $output, $die, $force, $title );
	}

	/**
	 * print Debug information
	 *
	 * @param Object  $obj - log object, can be any type: e.g. Array, String, Object...
	 * @param boolean $output - log to debug.log (only if WP_DEBUG is set to true) if set to false or to Browser if set to true.
	 * @param boolean $die - exit after output if set to true.
	 * @param boolean $force - force output if set to true.
	 * @param string  $title - output Title.
	 * @return void
	 */
	private static function print_debug( $obj, $output, $die, $force, $title ) {
		if ( class_exists( 'vjoon\Adapter\App' ) ) {
			$_debug = App::$option->general->debug;
			$_name  = App::$option->plugin->Name;
		} else {
			$_debug = false;
			$_name  = '';
		}
		if ( $_debug || $force === true ) {
			if ( ! $output ) {
				error_log( $_name . ':' . PHP_EOL . str_repeat( '=-', 60 ) . 'B' . PHP_EOL . ( empty( $title ) ? ' ' : $title . ': ' . PHP_EOL ) . gettype( $obj ) . ': ' . self::print_var_name( $obj ) . ' - ' . print_r( $obj, true ) . PHP_EOL . str_repeat( '-=', 60 ) . 'E' . PHP_EOL ); }
			if ( $output ) {
				print( "<div class='debug'><div>" . ( empty( $title ) ? '' : $title . ': ' ) . gettype( $obj ) . ': ' . self::print_var_name( $obj ) . "</div><pre class='debug_info'>" . print_r( $obj, true ) . '</pre></div>' ); }
			if ( $die === true ) {
				exit(); }
		}
	}

	private static function print_var_name( $var ) {
		foreach ( $GLOBALS as $var_name => $value ) {
			if ( $value === $var ) {
				return $var_name;
			}
		}

		return '';
	}

}
