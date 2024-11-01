<?php
/**
 * Crypt Class
 *
 * @author Christian Storm
 * @package vjoon\Adapter
 * @copyright 2022 vjoon GmbH
 */

namespace vjoon\Adapter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Function Class
 */
final class Crypt {

	/**
	 * Generate IV
	 *
	 * @return String iv.
	 */
	private static function iv() {
		$iv = App::$settings->get( 'crypt_key' );
		return $iv;
	}

	/**
	 * Generate Key
	 *
	 * @return String Key.
	 */
	private static function key() {
		return strrev( self::iv() );
	}

	/**
	 * Encrypt Data
	 *
	 * @param String $data String to encrypt.
	 * @return String Encypted string.
	 */
	public static function encrypt( $data ) {
		$cipher = new \phpseclib3\Crypt\AES( 'ctr' );
		$cipher->setIV( self::iv() );
		$cipher->setKey( self::key() );

		$ciphertext = base64_encode( $cipher->encrypt( $data ) );
		return $ciphertext;
	}

	/**
	 * Decrypt Data
	 *
	 * @param String $data String to decrypt.
	 * @return String Decrypted string.
	 */
	public static function decrypt( $data ) {
		$cipher = new \phpseclib3\Crypt\AES( 'ctr' );
		$cipher->setIV( self::iv() );
		$cipher->setKey( self::key() );

		$decrypt = $cipher->decrypt( base64_decode( $data ) );
		return $decrypt;
	}
}
