<?php
/**
 * Class Endpoints Version 2
 *
 * Register Endpoints and Rewrite Rules Class for vjoon WordPress Adapter
 *
 * @author Christian Storm
 * @package vjoon\Adapter
 * @copyright 2022 vjoon GmbH
 */

namespace vjoon\Adapter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

final class EndpointsV2 {

	private static $namespace = 'vjoon/v';
	private static $base      = '/adapter';

	public static $route_v2;

	/**
	 * Register Endpoints via WP action 'init'
	 */
	public function __construct() {
		self::$route_v2 = self::$namespace . '2';

		add_action( 'plugins_loaded', array( __CLASS__, 'add_language_support' ), 10 );
		add_filter( 'load_textdomain_mofile', array( __CLASS__, 'app_language_fallback' ), 10, 2 );
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_api_v2' ), 1000 );
	}

	public static function add_language_support() {
		$basename      = \str_replace( WP_PLUGIN_DIR, '', App::$option->plugin->Dir );
		$language_path = $basename . App::$option->plugin->LanguageResourcePath;
		$rtn           = load_plugin_textdomain( 'vj-wp-adapter', false, $language_path );
	}

	public static function app_language_fallback( $mofile, $domain ) {
		return Functions::load_language_mofile( $mofile, $domain );
	}

	/**
	 * Register rest API Endpoints.
	 *
	 * Available routes:
	 * authorize - Authorization on System
	 * deauthorize - Deauthorization on System
	 * article/_upload - Article Upload with Data (if new as status draft, if update than previous status)
	 * article/_preview - Article Preview with Data, returns previewURL, deleted by WP Cron (ScheduleEvent) after 24h
	 * article/_publish - set existing Article to Publish
	 * article/_unpublish - set existing Article to Unpublish
	 * article/_delete - delete existing Article from System
	 * media - Media Upload with Data (e.g. Image, PDF)
	 * ping - Pingtest, useful to test Authorization
	 *
	 * @return void
	 */
	public static function register_rest_api_v2() {
		// note: Auth.
		register_rest_route(
			self::$route_v2,
			self::$base . '/authorize',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_auth_v2' ),
				'permission_callback' => array( __CLASS__, 'prem_rest_auth_v2' ),
				'args'                => array(),
			)
		);
		// note: Deauth.
		register_rest_route(
			self::$route_v2,
			self::$base . '/deauthorize',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_deauth_v2' ),
				'permission_callback' => array( __CLASS__, 'perm_rest_deauth_v2' ),
				'args'                => array(),
			)
		);
		// note: Upload.
		register_rest_route(
			self::$route_v2,
			self::$base . '/article/_upload',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_article_upload_v2' ),
				'permission_callback' => array( __CLASS__, 'perm_rest_article_upload_v2' ),
				'args'                => array(),
			)
		);
		// note: Preview.
		register_rest_route(
			self::$route_v2,
			self::$base . '/article/_preview',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_article_preview_v2' ),
				'permission_callback' => array( __CLASS__, 'perm_rest_article_preview_v2' ),
				'args'                => array(),
			)
		);
		// note: Publish.
		register_rest_route(
			self::$route_v2,
			self::$base . '/article/_publish',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_article_publish_v2' ),
				'permission_callback' => array( __CLASS__, 'perm_rest_article_publish_v2' ),
				'args'                => array(),
			)
		);
		// note: Unpublish.
		register_rest_route(
			self::$route_v2,
			self::$base . '/article/_unpublish',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_article_unpublish_v2' ),
				'permission_callback' => array( __CLASS__, 'perm_rest_article_unpublish_v2' ),
				'args'                => array(),
			)
		);
		// note: Delete.
		register_rest_route(
			self::$route_v2,
			self::$base . '/article/_delete',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_article_delete_v2' ),
				'permission_callback' => array( __CLASS__, 'perm_rest_article_delete_v2' ),
				'args'                => array(),
			)
		);
		// note: Media upload.
		register_rest_route(
			self::$route_v2,
			self::$base . '/media',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_media_v2' ),
				'permission_callback' => array( __CLASS__, 'perm_rest_media_v2' ),
				'args'                => array(),
			)
		);

		// note: Ping.
		register_rest_route(
			self::$route_v2,
			self::$base . '/ping',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_ping_v2' ),
				'permission_callback' => array( __CLASS__, 'perm_rest_ping_v2' ),
				'args'                => array(),
			)
		);

	}

	// ********************* REST AUTH *********************//
	/**
	 * Authorize Request with Key, Secret
	 * if Key, Secret are correct, than internal Provider Users is logged on to system
	 * return WP_REST_Response with Nonce for further requests or Error on Failure
	 *
	 * @param \WP_REST_Request $request WP Rest Request.
	 * @return WP_Error|WP_REST_Response
	 */
	public static function rest_auth_v2( \WP_REST_Request $request ) {
		$valid = self::validate_request_user( $request );

		if ( $valid == null ) { // note: missing creds.
			return Response::return( $request, 405, 1001 );

		} elseif ( $valid ) {
			$response_array = array();
			$response_array = Response::return( $request );
			if ( APP::$option->general->debug ) {
				$response_array['_debugMode'] = Response::add_debug_info( $request );}
			$response_array['nonce'] = ! is_wp_error( $valid );
			$response                = new \WP_REST_Response( $response_array, 200 );
			return $response;

		} else {
			return Response::return( $request, 403, 1002 );

		}

	}

	public static function prem_rest_auth_v2( \WP_REST_Request $request ) {
		return true;
	}

	// ********************* REST DEAUTH *********************//
	/**
	 * Deauthorization Request, logoff vjoon Provider User
	 *
	 * @param \WP_REST_Request $request WP Rest Request.
	 * @return @return WP_Error|WP_REST_Response
	 */
	public static function rest_deauth_v2( \WP_REST_Request $request ) {
		$valid = self::validate_request_user( $request );

		if ( $valid == null ) { // note: missing creds.
			return Response::return( $request, 405, 1001 );

		} elseif ( $valid ) {
			$response_array = array();
			$response_array = Response::return( $request );
	
			if ( APP::$option->general->debug ) {
				$response_array['_debugMode'] = Response::add_debug_info( $request );
			}
	
			$response_array['deauth'] = true;
			$response                 = new \WP_REST_Response( $response_array, 200 );
			return $response;

		} else {
			return Response::return( $request, 403, 1002 );

		}

	}
	public static function perm_rest_deauth_v2( \WP_REST_Request $request ) {
		return true;
	}

	// ********************* REST _UPLOAD *********************//
	/**
	 * Upload Article
	 *
	 * @param \WP_REST_Request $request WP Rest Request.
	 * @return WP_Error|WP_REST_Response
	 */
	public static function rest_article_upload_v2( \WP_REST_Request $request ) {
		$valid = self::validate_request_user( $request );

		if ( $valid == null ) { // note: missing creds.
			return Response::return( $request, 405, 1001 );

		} elseif ( $valid ) {
			$response_array = array();
			$response_array = Response::return( $request );
			if ( APP::$option->general->debug ) {
				$response_array['_debugMode'] = Response::add_debug_info( $request );}

			$body = $request->get_body();
			if ( $body ) {
				$article       = new Article( $body );
				$article_valid = $article->Parse();

				if ( $article_valid ) { // json data for article upload are valid.
					$result = $article->Upload();
					if ( $result['result'] ) { // upload successfully.
						$response = new \WP_REST_Response( $response_array, 200 );
						return $response;
					} else { // error on upload.
						return Response::return( $request, 405, $result['code'] );
					}
				} else {
					return Response::return( $request, 405, 1101 );
				}
			} else {
				return Response::return( $request, 405, 1100 );
			}
		} else {
			return Response::return( $request, 403, 1002 );
		}
	}

	public static function perm_rest_article_upload_v2( \WP_REST_Request $request ) {
		return true;
	}

	// ********************* REST _PREVIEW *********************//
	/**
	 * Preview Article
	 *
	 * @param \WP_REST_Request $request WP Rest Request.
	 * @return WP_Error|WP_REST_Response
	 */
	public static function rest_article_preview_v2( \WP_REST_Request $request ) {
		$valid = self::validate_request_user( $request );

		if ( $valid == null ) { // note: missing creds.
			return Response::return( $request, 405, 1001 );

		} elseif ( $valid ) {
			$response_array = array();
			$response_array = Response::return( $request );
			if ( APP::$option->general->debug ) {
				$response_array['_debugMode'] = Response::add_debug_info( $request );}

			$body = $request->get_body();
			if ( $body ) {
				$article       = new Article( $body );
				$article_valid = $article->Parse();

				if ( $article_valid ) { // json data for article upload are valid.
					$result = $article->Upload( true );
					if ( $result['result'] ) { // preview successfully.
						$response_array['previewUrl'] = $article->getPreviewUrl();
						$response                     = new \WP_REST_Response( $response_array, 200 );
						return $response;
					} else { // error on preview.
						$code = ( $result['code'] == 1107 ) ? 1107 : 1105;
						return Response::return( $request, 405, $code );
					}
				} else {
					return Response::return( $request, 405, 1101 );
				}
			} else {
				return Response::return( $request, 405, 1100 );
			}
		} else {
			return Response::return( $request, 403, 1002 );
		}
	}
	public static function perm_rest_article_preview_v2( \WP_REST_Request $request ) {
		return true;
	}

	// ********************* REST _PUBLISH *********************//
	/**
	 * Publish Article
	 *
	 * @param \WP_REST_Request $request WP Rest Request.
	 * @return WP_Error|WP_REST_Response
	 */
	public static function rest_article_publish_v2( \WP_REST_Request $request ) {
		$valid = self::validate_request_user( $request );

		if ( $valid == null ) { // note: missing creds.
			return Response::return( $request, 405, 1001 );

		} elseif ( $valid ) {
			$response_array = array();
			$response_array = Response::return( $request );
			if ( APP::$option->general->debug ) {
				$response_array['_debugMode'] = Response::add_debug_info( $request );}

			$body = $request->get_body();
			if ( $body ) {
				$article       = new Article( $body );
				$article_valid = $article->Parse( 'contentId' );

				if ( $article_valid ) { // json data for article upload are valid.
					$result = $article->Publish();
					if ( $result ) { // publish successfully.
						$response = new \WP_REST_Response( $response_array, 200 );
						return $response;
					} else { // error on publishing.
						return Response::return( $request, 405, 1103 );
					}
				} else {
					return Response::return( $request, 405, 1101 );
				}
			} else {
				return Response::return( $request, 405, 1100 );
			}
		} else {
			return Response::return( $request, 403, 1002 );
		}
	}
	public static function perm_rest_article_publish_v2( \WP_REST_Request $request ) {
		return true;
	}

	// ********************* REST _UNPUBLISH *********************//
	/**
	 * Unpublish Article
	 *
	 * @param \WP_REST_Request $request WP Rest Request.
	 * @return WP_Error|WP_REST_Response
	 */
	public static function rest_article_unpublish_v2( \WP_REST_Request $request ) {
		$valid = self::validate_request_user( $request );

		if ( $valid == null ) { // note: missing creds.
			return Response::return( $request, 405, 1001 );

		} elseif ( $valid ) {
			$response_array = array();
			$response_array = Response::return( $request );
			if ( APP::$option->general->debug ) {
				$response_array['_debugMode'] = Response::add_debug_info( $request );}

			$body = $request->get_body();
			if ( $body ) {
				$article       = new Article( $body );
				$article_valid = $article->Parse( 'contentId' );

				if ( $article_valid ) { // json data for article upload are valid.
					$result = $article->Unpublish();
					if ( $result ) { // unpublish successfully.
						$response = new \WP_REST_Response( $response_array, 200 );
						return $response;
					} else { // error on unpublishing.
						return Response::return( $request, 405, 1104 );
					}
				} else {
					return Response::return( $request, 405, 1101 );
				}
			} else {
				return Response::return( $request, 405, 1100 );
			}
		} else {
			return Response::return( $request, 403, 1002 );

		}
	}

	public static function perm_rest_article_unpublish_v2( \WP_REST_Request $request ) {
		return true;
	}

	// ********************* REST _DELETE *********************//
	/**
	 * Undocumented function
	 *
	 * @param \WP_REST_Request $request WP Rest Request.
	 * @return WP_Error|WP_REST_Response
	 */
	public static function rest_article_delete_v2( \WP_REST_Request $request ) {
		$valid = self::validate_request_user( $request );

		if ( $valid == null ) { // note: missing creds.
			return Response::return( $request, 405, 1001 );

		} elseif ( $valid ) {
			$response_array = array();
			$response_array = Response::return( $request );
			if ( APP::$option->general->debug ) {
				$response_array['_debugMode'] = Response::add_debug_info( $request );}

			$body = $request->get_body();
			if ( $body ) {
				$article       = new Article( $body );
				$article_valid = $article->Parse( 'contentId' );

				if ( $article_valid ) { // json data for article upload are valid.
					$result = $article->Delete();
					if ( $result ) { // deleting successfully.
						$response = new \WP_REST_Response( $response_array, 200 );
						return $response;
					} else { // error on deleting.
						return Response::return( $request, 405, 1106 );
					}
				} else {
					return Response::return( $request, 405, 1101 );
				}
			} else {
				return Response::return( $request, 405, 1100 );
			}
		} else {
			return Response::return( $request, 403, 1002 );
		}
	}

	public static function perm_rest_article_delete_v2( \WP_REST_Request $request ) {
		return true;
	}

	// ********************* REST MEDIA *********************//
	/**
	 * Undocumented function
	 *
	 * @param \WP_REST_Request $request WP Rest Request.
	 * @return WP_Error|WP_REST_Response
	 */
	public static function rest_media_v2( \WP_REST_Request $request ) {
		$valid = self::validate_request_user( $request );

		if ( $valid == null ) { // note: missing creds.
			return Response::return( $request, 405, 1001 );

		} elseif ( $valid ) {
			$response_array = array();
			$response_array = Response::return( $request );
			if ( APP::$option->general->debug ) {
				$response_array['_debugMode'] = Response::add_debug_info( $request );}

			$files = $request->get_file_params();
			if ( $files ) {
				$result = true;
				foreach ( $files as $file ) {
					$media_file = new Media( $file );
					$_result    = $media_file->Upload();
					$result     = is_wp_error( $result ) ? $result : $_result;
				}

				if ( $result ) {
					$response = new \WP_REST_Response( $response_array, 200 );
					return $response;
				} else {
					return Response::return( $request, 405, 1201 );
				}
			} else {
				return Response::return( $request, 405, 1200 );
			}
		} else {
			return Response::return( $request, 403, 1002 );
		}
	}

	public static function perm_rest_media_v2( \WP_REST_Request $request ) {
		return true;
	}

	// ********************* REST PING (AUTH-TEST) *********************//
	/**
	 * Test Rest API
	 *
	 * @param \WP_REST_Request $request WP Rest Request.
	 * @return WP_Error|WP_REST_Response
	 */
	public static function rest_ping_v2( \WP_REST_Request $request ) {
		$valid = self::validate_request_user( $request );

		if ( $valid == null ) { // note: missing creds.
			return Response::return( $request, 405, 1001 );

		} elseif ( $valid ) {
			$response_array = array();
			$response_array = Response::return( $request );
			if ( APP::$option->general->debug ) {
				$response_array['_debugMode'] = Response::add_debug_info( $request );
			}
			$response_array['authorized'] = ! is_wp_error( $valid );
			$response                     = new \WP_REST_Response( $response_array, 200 );
			return $response;

		} else {
			return Response::return( $request, 403, 1002 );

		}
	}

	public static function perm_rest_ping_v2( \WP_REST_Request $request ) {
		return true;
	}

	private static function validate_request_user( \WP_REST_Request $request ) {
		$valid         = false;
		$provider_user = get_user_by( 'login', APP::$option->provider->User );
		$headers       = $request->get_headers();
		$apw           = explode( ' ', $headers['authorization'][0] );

		if ( is_array( $apw ) && isset( $apw[1] ) ) {
			$apw = explode( ':', \base64_decode( $apw[1] ) );
		} else {
			return null;
		}

		if ( is_array( $apw ) && isset( $apw[0] ) && isset( $apw[1] ) ) {
			$valid = wp_authenticate_application_password( $provider_user, $apw[0], $apw[1] );
		} else {
			return null;
		}

		Debug::log( $valid, 'VALIDATE_REQUEST_USER' );

		return ! is_wp_error( $valid );
	}

	// ********************* REWRITE_RULES for ENDPOINTS *********************//
	/**
	 * Register Endpoints
	 */
	public static function register_endpoints() {
		// Debug::log( 'REGISTER PREVIEW ENDPOINT V2' );
		// PREVIEW.
		add_rewrite_rule(
			'^_preview/([^/]*)/?',
			'index.php?vjoon=_preview&content_id=$matches[1]',
			'top'
		);

	}

}
