<?php
/**
 * Class Response for vjoon WordPress Adapter
 *
 * @author Christian Storm
 * @package vjoon\Adapter
 * @copyright 2022 vjoon GmbH
 */

namespace vjoon\Adapter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // exit if accessed directly.
}

final class Response {

	/**
	 * Generate harmonized Response as Array ['code','message','data'].
	 *
	 * Internal Response-Codes:
	 * 1000 valid request
	 * 1001 no credentials given
	 * 1002 wrong credentials given
	 * 1003 not authorized
	 *
	 * 1100 upload invalid
	 *
	 * @param \WP_REST_Request $request WP Rest Request.
	 * @param Integer          $code Code.
	 * @param Integer          $internal Internal Code.
	 * @return Array|WP_Error
	 */
	public static function return( \WP_REST_Request $request, $code = 200, $internal = 1000 ) {
		$return_array = array();
		$code_array   = array();
		switch ( $internal ) {
			case 1000:
				$code_array['code']    = 'valid_request';
				$code_array['message'] = __( 'vj-wp-adapter.response.message.valid', 'vj-wp-adapter' );
				break;

			case 1001:
				$code_array['code']    = 'no_cred';
				$code_array['message'] = __( 'vj-wp-adapter.response.message.error.credentialsmissing', 'vj-wp-adapter' );
				break;

			case 1002:
				$code_array['code']    = 'wrong_cred';
				$code_array['message'] = __( 'vj-wp-adapter.response.message.error.credentialswrong', 'vj-wp-adapter' );
				break;

			case 1003:
				$code_array['code']    = 'no_auth';
				$code_array['message'] = __( 'vj-wp-adapter.response.message.error.noauth', 'vj-wp-adapter' );
				break;

			case 1004:
				$code_array['code']    = 'no_user';
				$code_array['message'] = __( 'vj-wp-adapter.response.message.error.nouser', 'vj-wp-adapter' );
				break;

			case 1005:
				$code_array['code']    = 'no_api_set';
				$code_array['message'] = __( 'vj-wp-adapter.response.message.error.noapi', 'vj-wp-adapter' );
				break;

			case 1100:
				$code_array['code']    = 'art_req_body';
				$code_array['message'] = __( 'vj-wp-adapter.response.message.error.bodymissing', 'vj-wp-adapter' );
				break;

			case 1101:
				$code_array['code']    = 'art_inv_body';
				$code_array['message'] = __( 'vj-wp-adapter.response.message.error.bodyinvalid', 'vj-wp-adapter' );
				break;

			case 1102:
				$code_array['code']    = 'art_err_create';
				$code_array['message'] = __( 'vj-wp-adapter.response.message.error.create', 'vj-wp-adapter' );
				break;

			case 1103:
				$code_array['code']    = 'art_err_publish';
				$code_array['message'] = __( 'vj-wp-adapter.response.message.error.publish', 'vj-wp-adapter' );
				break;

			case 1104:
				$code_array['code']    = 'art_err_unpublish';
				$code_array['message'] = __( 'vj-wp-adapter.response.message.error.unpublish', 'vj-wp-adapter' );
				break;

			case 1105:
				$code_array['code']    = 'art_err_preview';
				$code_array['message'] = __( 'vj-wp-adapter.response.message.error.preview', 'vj-wp-adapter' );
				break;

			case 1106:
				$code_array['code']    = 'art_err_delete';
				$code_array['message'] = __( 'vj-wp-adapter.response.message.error.delete', 'vj-wp-adapter' );
				break;

			case 1107:
				$code_array['code']    = 'art_err_cpt';
				$code_array['message'] = __( 'vj-wp-adapter.response.message.error.cpt', 'vj-wp-adapter' );
				break;

			case 1200:
				$code_array['code']    = 'md_req_file';
				$code_array['message'] = __( 'vj-wp-adapter.response.message.error.filemissing', 'vj-wp-adapter' );
				break;

			case 1201:
				$code_array['code']    = 'md_inv_file';
				$code_array['message'] = __( 'vj-wp-adapter.response.message.error.fileinvalid', 'vj-wp-adapter' );
				break;

			default:
				$code_array['code']    = 'unknown';
				$code_array['message'] = __( 'vj-wp-adapter.response.message.error.unknown', 'vj-wp-adapter' );
				break;

		}

		$error_array = array(
			'status'   => $code,
			'internal' => $internal,
		);
		if ( APP::$option->general->debug ) {
			$error_array['_debugInfo'] = self::add_debug_info( $request );
		}
		switch ( $code ) {
			case 200: // OK
				// note: nothing to do.
				break;

			default:
				$_return = new \WP_Error( $code_array['code'], $code_array['message'], $error_array );
				Debug::log( $request, 'THIS REQUEST RESULTS IN THE FOLLOWING WP_ERROR' );
				Debug::log( $_return, 'WP_ERROR' );
				_doing_it_wrong( 'RESTAPI', esc_attr( __( 'Route must be specified.' ) ), '5.3.0' );
				return $_return;
				break;
		}
		return $return_array;
	}


	/**
	 * Return DebugInformations as an Array.
	 *
	 * @param \WP_REST_Request $request WP Rest Request.
	 * @return Array with DebugInformations
	 */
	public static function add_debug_info( \WP_REST_Request $request ) {
		$nonce                        = wp_create_nonce( 'wp_rest' );
		$return_array                 = array();
		$return_array['hint']         = 'this is only visible in SupportMode';
		$return_array['nonce']        = $nonce;
		$return_array['nonceValid']   = wp_verify_nonce( $nonce, 'wp_rest' );
		$return_array['nonceTick']    = wp_nonce_tick();
		$return_array['wpToken']      = wp_get_session_token();
		$return_array['params']       = $request->get_params();
		$return_array['body']         = $request->get_body();
		$return_array['files']        = $request->get_file_params();
		$return_array['route']        = $request->get_route();
		$return_array['headers']      = $request->get_headers();
		$return_array['current_user'] = wp_get_current_user();
		$return_array['devTestHint']  = 'Use ' . site_url( '/wp-json/' ) . implode( '/', array_slice( explode( '/', $return_array['route'] ), 1, 3 ) ) . '/ping with Header X-WP-Nonce to test Authorization on API';
		return $return_array;

	}

}
