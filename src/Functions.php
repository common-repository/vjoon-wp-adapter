<?php
/**
 * Class Functions
 *
 * MainClass for vjoon WordPress Adapter
 *
 * @author Christian Storm
 * @package vjoon\Adapter
 * @copyright 2022 vjoon GmbH
 */

namespace vjoon\Adapter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

final class Functions {

	private static $enq_scripts = array();
	private static $enq_styles  = array();
	private static $enq         = array();

	public static function get_file_content( $file ) {
		$fh       = fopen( $file, 'r' );
		$the_data = fread( $fh, filesize( $file ) );
		fclose( $fh );
		return $the_data;
	}

	/**
	 * Enqueue script via wp, but uses pluign option inline_style for inline scripts, set auto-handle name based on scriptfilename, uses minify option if set and set pluginVersion.
	 *
	 * @param String      $rel_src Relative source path, not absolute.
	 * @param Bool|String $ver Version.
	 * @param Bool        $in_footer In Footer.
	 * @param Array       $deps Dependecies.
	 * @param String      $handle Handle.
	 * @return void
	 */
	public static function enqueue_script_file( $rel_src, $ver = false, bool $in_footer = false, array $deps = array(), $handle = '' ) {
		$bn      = basename( $rel_src, '.js' );
		$rel_src = str_replace( $bn . '.js', $bn . App::$option->general->minified_jscss . '.js', $rel_src );
		$handle  = ! empty( $handle ) ? $handle : sanitize_title( App::$option->plugin->Name ) . '-' . $bn . '-script';
		$ver     = $ver == false ? App::$option->plugin->Version : $ver;
		if ( file_exists( App::$option->plugin->Dir . $rel_src ) ) {  // Only enqueue if file exists.
			if ( ! App::$option->general->inline_style ) { // Not inline.
				wp_register_script( $handle, App::$option->plugin->Url . $rel_src, $deps, $ver, $in_footer );
				wp_enqueue_script( $handle );
			} else { // Inline.
				wp_register_script( $handle, '', array(), $ver, $in_footer );
				$content = file_get_contents( App::$option->plugin->Dir . $rel_src );
				wp_enqueue_script( $handle );
				wp_add_inline_script( $handle, $content );
			}
			self::$enq_scripts[ $bn ] = array(
				'handle'    => $handle,
				'src'       => $rel_src,
				'in_footer' => $in_footer,
				'version'   => $ver,
			);
		} else {
			$notfound = 'File not found ' . App::$option->plugin->Dir . $rel_src;
			Debug::log( $notfound );
		}
	}

	/**
	 * Enqueue style via wp, but uses plugin option inline_style for inline style, set auto-handle name based on stylefilename, uses minify option if set and set pluginVersion.
	 *
	 * @param String      $rel_src Relative source path, not absolute.
	 * @param Bool|String $ver Version.
	 * @param String      $media Media.
	 * @param Array       $deps Dependecies.
	 * @param String      $handle Handle.
	 * @return void
	 */
	public static function enqueue_style_file( $rel_src, $ver = false, string $media = 'all', array $deps = array(), $handle = '' ) {
		$bn      = basename( $rel_src, '.css' );
		$rel_src = str_replace( $bn . '.css', $bn . App::$option->general->minified_jscss . '.css', $rel_src );
		$handle  = ! empty( $handle ) ? $handle : sanitize_title( App::$option->plugin->Name ) . '-' . $bn . '-style';
		$ver     = $ver == false ? App::$option->plugin->Version : $ver;
		if ( file_exists( App::$option->plugin->Dir . $rel_src ) ) {  // Only enqueue if file exists.
			if ( ! App::$option->general->inline_style ) { // Not inline.
				wp_register_style( $handle, App::$option->plugin->Url . $rel_src, $deps, $ver, $media );
				wp_enqueue_style( $handle );
			} else { // Inline.
				wp_register_style( $handle, false, $deps, $ver, $media );
				$content = file_get_contents( App::$option->plugin->Dir . $rel_src );
				wp_enqueue_style( $handle );
				wp_add_inline_style( $handle, $content );
			}
			self::$enq_styles[ $bn ] = array(
				'handle'  => $handle,
				'src'     => $rel_src,
				'version' => $ver,
				'media'   => $media,
			);
		} else {
			$notfound = 'File not found ' . App::$option->plugin->Dir . $rel_src;
			Debug::log( $notfound );
		}
	}

	/**
	 * Add Schedule Process, Init Provider User, rewrite Rules.
	 *
	 * @return void
	 */
	public static function after_activation() {
		Provider::init_user();

		Endpoints::register_endpoints(); // note: Register only /_preview/.

		$is_network_activated = is_plugin_active_for_network( basename( dirname( $caller ) ) . '/Adapter.php' ); // is activated network wide (global under multisite)?
		if ( $is_network_activated ) {
			$option = empty( get_site_option( 'vjwpad_settings' ) ) ? (object) array() : get_site_option( 'vjwpad_settings' );
		} else {
			$option = empty( get_option( 'vjwpad_settings' ) ) ? (object) array() : get_option( 'vjwpad_settings' );
		}

		// note: set standard settings.
		$option->general                   = isset( $option->general ) ? $option->general : (object) array();
		$option->general->crypt_key        = isset( $option->general->crypt_key ) && ! empty( $option->general->crypt_key ) ? $option->general->crypt_key : substr( md5( time() ), -16 );
		$option->general->debug            = isset( $option->general->debug ) ? $option->general->debug : 0;
		$option->general->compression_ajax = isset( $option->general->compression_ajax ) ? $option->general->compression_ajax : 1;
		$option->general->minified_jscss   = isset( $option->general->minified_jscss ) ? $option->general->minified_jscss : 1;
		$option->general->inline_style     = isset( $option->general->inline_style ) ? $option->general->inline_style : 1;
		if ( $is_network_activated ) {
			update_site_option( 'vjwpad_settings', $option );
		} else {
			update_option( 'vjwpad_settings', $option );
		}

		flush_rewrite_rules(); // note: You should *NEVER EVER* do this on every page load.
		Debug::log( 'Function called after_activation' );
	}

	/**
	 * delete Schedule Process and rewrite Rules
	 *
	 * @return void
	 */
	public static function after_deactivation() {
		flush_rewrite_rules();
		Debug::log( 'Function called after_deactivation' );
	}

	/**
	 * Runs a daily event, via wp_cron process, to delete preview post or pages older than 24h.
	 *
	 * @param Time $time Time.
	 * @return Array Array with deleted Post Id's.
	 */
	public static function garbage_collector( $time = null ) {
		$time = isset( $time ) ? $time : ( time() - ( 24 * 60 * 60 ) );
		Debug::log( 'CleanUp process started...', '', false, false, true );
		$deleted = array();

		$args     = array(
			'post_type'   => 'any',
			'post_status' => 'draft',
			'meta_query'  => array(
				'content_clause' => array(
					'key'     => '_preview',
					'compare' => '<',
					'value'   => $time,
				),
			),
		);
		$previews = new \WP_Query( $args );
		Debug::log( $previews, 'Get Preview via WP_Query' );
		if ( ! is_wp_error( $previews ) ) {
			$ids = array(); // Array to put all expired previews.

			foreach ( $previews->posts as $post ) {
					$ids[] = $post->ID;
			}

			// note: delete all post_id related media.
			foreach ( $ids as $id ) {
				self::delete_related_posts( $id );
			}

			// note: delete posts.
			foreach ( $ids as $id ) {
				$res = wp_delete_post( $id, true );
				if ( $res ) {
					Debug::log( $id, 'delete PreviewPost with Post ID' );
					$deleted[] = $id;
				} else {
					Debug::log( $id, 'fail to delete PreviewPost with Post ID' );
				}
			}
		}

		Debug::log( 'CleanUp process done', '', false, false, true );
		return $deleted;
	}

	public static function delete_related_posts( $post_id ) {
		if ( self::is_adapter_api_post( $post_id ) ) {
			$args  = array(
				'post_parent' => $post_id,
				'post_type'   => 'any',
				'post_status' => 'any',
				'numberposts' => -1,
			); // note: no spec for post_type, delete all.
			$posts = get_posts( $args );
			Debug::log( $posts, 'Called Function to delete related posts from postId ' . $post_id );
			if ( ! empty( $posts ) ) {
				foreach ( $posts as $post ) {
					if ( is_attachment( $post->ID ) ) {
						Debug::log( $post->ID, 'delete related Attachment to Post with Post ID ' . $post_id );
						wp_delete_attachment( $post->ID, true );
					} else {
						Debug::log( $post->ID, 'delete related Post to Post with Post ID ' . $post_id );
						wp_delete_post( $post->ID, true );
					}
				}
			}
		}
	}

	/**
	 * Hide Adminbar on Frontend if User is the vjoonProvider User.
	 *
	 * @return Boolean is hidden.
	 */
	public static function hide_adminbar() {
		global $current_user;
		if ( $current_user->user_login == APP::$option->provider->User || $current_user->ID == 0 ) {
			remove_action( 'wp_head', '_admin_bar_bump_cb' );
			return false;
		}
		return true;
	}

	/**
	 * Remove Role View from Backend UserList.
	 *
	 * @param Array $views Views.
	 * @return Array Views.
	 */
	public static function override_view_users( $views ) {
		$users        = count_users();
		$all_num      = $users['total_users'] - 1;
		$class_all    = ( strpos( $views['all'], 'current' ) === false ) ? '' : 'current';
		$views['all'] = '<a href="users.php" class="' . $class_all . '">' . __( 'All' ) . ' <span class="count">(' . $all_num . ')</span></a>';
		if ( isset( $views[ APP::$option->provider->Role ] ) ) {
			unset( $views[ APP::$option->provider->Role ] ); }
		return $views;
	}

	/**
	 * Remove Role from Editable Rolelist.
	 *
	 * @param Array $roles Roles Array.
	 * @return Array Roles.
	 */
	public static function override_editable_roles( $roles ) {
		if ( isset( $roles[ APP::$option->provider->Role ] ) ) {
			unset( $roles[ APP::$option->provider->Role ] ); }
		return $roles;
	}

	/**
	 * Hide Provider User from Backend UserList.
	 *
	 * @param [type] $user_search User search.
	 * @return void
	 */
	public static function hide_provider_user( $user_search ) {
		if ( ! current_user_can( 'administrator' ) ) {
			global $wpdb;
			$user_search->query_where = str_replace( 'WHERE 1=1', "WHERE 1=1 AND {$wpdb->users}.user_login != '" . APP::$option->provider->User . "'", $user_search->query_where );
		}
	}

	/**
	 * Hide vjoon Provider _Preview Posts from List when User has no Admin Role
	 *
	 * @param [type] $query
	 * @return void
	 */
	public static function hide_provider_preview_posts( $query ) {
		if ( ! current_user_can( 'administrator' ) ) {
			$query->set(
				'meta_query',
				array(
					array(
						'key'     => '_preview',
						'compare' => 'NOT EXISTS',
						'value'   => '',
						'type'    => 'NUMERIC',
					),
				)
			);
		}
	}

	/**
	 * Decrement Tab View Counter for Hidden Provider _Preview Posts
	 *
	 * @param [type] $views
	 * @param string $post_type
	 * @return void
	 */
	public static function override_view_posts( $views, $post_type = 'post' ) {
		if ( ! current_user_can( 'administrator' ) ) {
			global $wpdb;
			$_preview_count = array();
			$result         = $wpdb->get_results( "SELECT * from $wpdb->posts INNER JOIN $wpdb->postmeta where $wpdb->posts.ID = $wpdb->postmeta.post_id and $wpdb->postmeta.meta_key = '_preview' and $wpdb->posts.post_type = '" . $post_type . "'" );
			if ( $result ) {
				$_preview_count['all'] = count( $result );
			} else {
				$_preview_count['all'] = 0; }
			$result = $wpdb->get_results( "SELECT * from $wpdb->posts INNER JOIN $wpdb->postmeta where $wpdb->posts.ID = $wpdb->postmeta.post_id and $wpdb->postmeta.meta_key = '_preview' and $wpdb->posts.post_status ='draft' and $wpdb->posts.post_type = '" . $post_type . "'" );
			if ( $result ) {
				$_preview_count['draft'] = count( $result );
			} else {
				$_preview_count['draft'] = 0; }
			$result = $wpdb->get_results( "SELECT * from $wpdb->posts INNER JOIN $wpdb->postmeta where $wpdb->posts.ID = $wpdb->postmeta.post_id and $wpdb->postmeta.meta_key = '_preview' and $wpdb->posts.post_status ='publish' and $wpdb->posts.post_type = '" . $post_type . "'" );
			if ( $result ) {
				$_preview_count['publish'] = count( $result );
			} else {
				$_preview_count['publish'] = 0; }

			$posts_count = (array) wp_count_posts( $post_type ); // available type post, page
			$_views      = array();
			foreach ( $views as $key => $val ) {
				$views[ $key ] = str_replace( '&#038;', '&', $views[ $key ] );
				$counter       = preg_replace( '/\D/', '', $views[ $key ] );
				// $counter = $posts_count[$key];
				$count = $counter - $_preview_count[ $key ];
				if ( ! empty( $count ) && $count > 0 ) {
					$class = ( strpos( $views[ $key ], 'current' ) === false ) ? '' : "class='current'";
					if ( $key == 'all' ) {
						$_views['all'] = "<a href='edit.php?post_type=post' $class aria-current='page'>All <span class='count'>(" . ( $count ) . ')</span></a>';
					} else {
						$_views[ $key ] = " <a href='edit.php?post_status=$key&post_type=post' $class>" . ucwords( $key ) . " <span class='count'>(" . ( $count ) . ')</span></a>';
					}
				}
			}
			Debug::log( $views, 'VIEWS' );
			Debug::log( $_views, '_VIEWS' );
			return $_views;
		}
		return $views;
	}

	/**
	 * Override WP_Query to get Posts displayed on Frontend
	 *
	 * @return void
	 */
	public static function override_wp_query_for_preview() {
		global $wp_query;

		$uuid    = get_query_var( 'content_id' );
		$preview = get_query_var( 'vjoon' );

		Debug::log(
			array(
				'uuid'    => $uuid,
				'preview' => $preview,
			),
			'GET_QUERY_VARS'
		);

		// note: fix to display full post on preview
		$args         = array(
			'post_status'    => 'any',
			'post_type'      => 'any',
			'posts_per_page' => 1,
			'meta_query'     => array(
				array(
					'key'   => 'contentId',
					'value' => $uuid,
				),
			),
		);
		$_custom_post = new \WP_Query( $args );
		$postID       = isset( $_custom_post->post->ID ) ? $_custom_post->post->ID : 0;

		if ( $postID > 0 ) {
			if ( ! empty( $preview ) && $preview == '_preview' ) {
				if ( ! empty( $uuid ) ) {
					$args     = array(
						// 'p' => $postID,
						'post__in'    => array( $postID ),
						'post_status' => 'any',
						'post_type'   => 'any',
					);
					$wp_query = new \WP_Query( $args );
					// $wp_query->is_singular = 1;
					$wp_query->is_single = 1; // this value enable featured image
					$wp_query->is_home   = 0; // this value fixes full view of post, set to 1 shows excerpt post
					Debug::log( $wp_query, 'CUSTOM_QUERY' );
					return $wp_query;
				}
			}
		} else {
			wp_reset_query();
			return $wp_query;
		}

	}

	public static function override_frontend_provider_author( $author ) {
		if ( $author ) {
			Debug::log( $author, 'THE_AUTHOR Filter' );
			if ( $author == APP::$option->provider->User ) {
				global $wp_query;
				// note: replace author
				// note: is_home oder is_single, is_home = posts als Übersichtseite, is_single = einzelner Post
				$postID = $wp_query->post->ID;
				return self::get_provider_author( $postID );
			} else {
				return $author;
			}
		} else {
			return $author;
		}
	}

	public static function override_frontend_preview_date( $the_date, $d, $post, $after ) {
		if ( isset( $post ) && $post->post_status == 'draft' && isset( $d ) && $d != 'c' ) { // c => ISODate -> 2004-02-12T15:19:21+00:00.
			$_date = date_format( date_create( $post->post_date_gmt ), 'F j, Y' );
			return $_date; // $the_date;
		} elseif ( isset( $post ) && $post->post_status == 'draft' && isset( $d ) && $d == 'c' ) {
			$_date = date_format( date_create( $post->post_date_gmt ), 'c' );
			return $_date;
		} else {
			return $the_date;
		}
	}

	public static function override_frontend_preview_time( $the_time, $d, $post ) {
		if ( isset( $post ) && $post->post_status == 'draft' && isset( $d ) && $d == '' ) {
			$_time = date_format( date_create( $post->post_date_gmt ), 'h:i a' );
			Debug::log(
				array(
					'the_time' => $the_time,
					'd'        => $d,
					'post'     => $post,
					'returned' => $_time,
				),
				'THE_TIME Filter'
			);
			return $_time;
		} else {
			return $the_time;
		}
	}

	public static function override_frontend_author_link( $link, $author_id, $author_nicename ) {
		// future: override author link on frontend.
		Debug::log(
			array(
				'link'            => $link,
				'author_id'       => $author_id,
				'author_nicename' => $author_nicename,
			),
			'AUTHOR_LINK Filter'
		);
		return $link;
	}

	/**
	 * used to add a link to plugins overview
	 *
	 * @param [type] $links Links.
	 * @param [type] $add Add.
	 * @return void
	 */
	public static function add_plugin_link( $links, $add ) {
		array_push( $links, $add );
		return $links;
	}

	public static function add_plugin_row_meta( $links_array, $plugin_file_name, $plugin_data, $status ) {
		if ( $plugin_data['Name'] == APP::$option->plugin->Name ) {
			Debug::log(
				array(
					'links'    => $links_array,
					'plugin'   => $plugin_data,
					'Settings' => APP::$option->plugin,
				),
				'PLUGIN ROW META FILTER'
			);
			$links_array[0] = str_replace( '${VERSION_STRING}', APP::$option->plugin->Version, $links_array[0] );
			$row_meta       = array(
				'build' => '<a id="build" href="" title="get build info">' . __( 'vj-wp-adapter.functions.plugin.build.label', 'vj-wp-adapter' ) . '</a>',
			);
			$links_array    = array_merge( $links_array, $row_meta );
		}

		return $links_array;
	}

	/**
	 * return array with post_ids from Posts which are associated with _pluginOrigin
	 *
	 * @return array
	 */
	public static function get_plugin_origins() {
		$return = array();
		global $wpdb;
		$sql    = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s and meta_value = %s";
		$sql    = $wpdb->prepare( $sql, '_pluginOrigin', APP::$option->plugin->Name );
		$result = $wpdb->get_results( $sql );
		if ( $result ) {
			foreach ( $result as $v ) {
				$return[] = $v->post_id;
			}
		} else {
			return false;
		}
		return $return;
	}

	/**
	 * Ajaxcall from App.
	 *
	 * @return void
	 */
	public static function ajaxcall() {
		if ( isset( $_REQUEST['operation'] ) ) {
			$operation = sanitize_text_field( $_REQUEST['operation'] ); // note: mandatory.
			$data      = ( isset( $_REQUEST['data'] ) ? (array) $_REQUEST['data'] : array() ); // note: optional, if isset than as array.
			$data      = array_map( 'esc_attr', $data );

			$return_value = array();
			switch ( $operation ) {

				case 'initApp':
					self::after_activation(); // note: execute activation function.
					$return_value['result'] = 'initialized';
					break;

				case 'rcSecret':
					$return_value['result'] = wp_generate_password( 128, true, false );
					break;

				case 'rcKey':
					$return_value['result'] = self::uuid( 32 );
					break;

				case 'rcAPW':
					$_get     = App::$settings->get( 'uuid', 'api' ); // note: get apw uuid.
					$_uuid    = isset ( $_get ) && ! empty( $_get ) ? $_get : ''; 
					$_user_id = get_user_by( 'login', App::$option->provider->User );
					$deleted  = \WP_Application_Passwords::delete_application_password( $_user_id->ID, $_uuid ); // note: return Bool or WP_Error. If an old APW is store, than delete it.

					// note: reCreate Application Password.
					$_app_appendix = is_multisite() ? strval( ' for BlogId ' . get_current_blog_id() ) : '';
					$_app          = array(
						'name'   => Settings::$app_name . $_app_appendix,
						'app_id' => Settings::$app_id,
					);
					$app_password  = \WP_Application_Passwords::create_new_application_password( $_user_id->ID, $_app );
					if ( is_wp_error( $app_password ) ) {
						Debug::log( $app_password->get_error_message(), 'rcAPW Error' );
						$return_value['result'] = false;
					} else {
						Debug::log(
							array(
								'user_id' => $_user_id->ID,
								'app'     => $_app,
								'APW'     => $app_password,
							),
							'rcAPW'
						);
						// note: save application password UUID $app_password[1]['uuid'].
						$apw = isset( $app_password[0] ) ? \WP_Application_Passwords::chunk_password( $app_password[0] ) : '';
						if ( ! empty( $apw ) ) {
							$apw_uuid = isset( $app_password[1]['uuid'] ) ? $app_password[1]['uuid'] : '';
							App::$settings->set( 'apw', Crypt::encrypt( $apw ), 'api' );
							App::$settings->set( 'uuid', $apw_uuid, 'api' );
							App::$settings->save();
							$return_value['uuid'] = $apw_uuid;
						}
						$return_value['result'] = $apw;
					}
					break;

				case 'getVars':
					$return_value['result'] = self::get_vars();
					break;

				case 'garbageCollect':
					$deleted                = self::garbage_collector();
					$return_value['result'] = $deleted;
					break;

				case 'getUserlist':
					$users  = get_users();
					$_users = array();
					foreach ( $users as $user ) {
						$_users[ $user->ID ] = $user->display_name;

					}
					$return_value['result'] = (object) $_users;
					break;

				case 'assignUser':
					if ( empty( $data ) ) {
						$return_value['result'] = false;
					} else {
						$user = $data[0];
						update_option( 'vjwpad_assignUser', $user );
						if ( get_option( 'vjwpad_assignUser' ) == $user ) {
							$return_value['result'] = true;

						} else {
							$return_value['result'] = false;
						}
					}
					break;

				case 'getBuildInfo':
					$return_value['result'] = "Build: 182 (fe696b0)";
					break;

				default:
					$return_value['note'] = 'notKnownOrImplemented';
					break;
			}

			if ( App::$option->general->debug ) {
				$return_value['dbg_data'] = $data; }
			$return_value['return'] = $operation;

			if ( App::$option->general->compression_ajax ) {
				echo base64_encode( json_encode( $return_value ) );
			} else {
				echo json_encode( $return_value );
			}
			Debug::log( $return_value, 'ajaxcall response on action ' . $operation );
			wp_die();
		}
	}

	public static function check_application_password() {
		$_user_id = get_user_by( 'login', App::$option->provider->User );
		$_get     = App::$settings->get( 'uuid', 'api' ); // note: get apw uuid.
		$_uuid    = isset ( $_get ) && ! empty( $_get ) ? $_get : ''; 
		$res = \WP_Application_Passwords::get_user_application_password( $_user_id->ID, $_uuid );
		Debug::log( $res, 'CHECK_APPLICATION_PASSWORD' );
		return $res;
	}

	/**
	 * Get APP Vars.
	 *
	 * @param boolean $nopriv None Priviliged.
	 * @return Array Array with vars.
	 */
	private static function get_vars( $nopriv = false ) {
		$ret          = null;
		$host         = ( isset( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . "://$_SERVER[HTTP_HOST]";
		$url          = home_url( '/' );
		$url          = str_replace( $host, '', $url );
		$msgboxlabels = array(
			'questionLabel'         => __( 'vj-wp-adapter.functions.plugin.deactivate.msg', 'vj-wp-adapter' ),
			'deleteContentLabel'    => __( 'vj-wp-adapter.functions.plugin.delete.optionlabel', 'vj-wp-adapter' ),
			'attributeContentLabel' => __( 'vj-wp-adapter.functions.plugin.attribute.optionlabel', 'vj-wp-adapter' ),
			'errorMessage'          => __( 'vj-wp-adapter.functions.plugin.error.msg', 'vj-wp-adapter' ),
			'btnCancel'             => __( 'vj-wp-adapter.functions.plugin.btn.cancel.label', 'vj-wp-adapter' ),
			'btnOK'                 => __( 'vj-wp-adapter.functions.plugin.btn.ok.label', 'vj-wp-adapter' ),
		);
		if ( $nopriv === false || App::$option->general->debug ) {
			$ret = array(
				'debug'            => App::$option->general->debug,
				'compression_ajax' => App::$option->general->compression_ajax,
				'minified_jscss'   => App::$option->general->minified_jscss,
				'inline_style'     => App::$option->general->inline_style,
				'version'          => App::$option->plugin->Version,
				'path'             => App::$option->plugin->Dir,
				'site_url'         => $url,
				'script_url'       => App::$option->plugin->Url,
				// note: add language depending labels for messagebox.
				'msgboxlabels'     => $msgboxlabels,

			);
		} else { // not priv = true.
			$ret = array(
				'version'          => App::$option->plugin->Version,
				'compression_ajax' => App::$option->general->compression_ajax,
				'minified_jscss'   => App::$option->general->minified_jscss,
				'inline_style'     => App::$option->general->inline_style,
				'script_url'       => App::$option->plugin->Url,
				// note: add language depending labels for messagebox.
				'msgboxlabels'     => $msgboxlabels,

			);

		}
		return $ret;
	}

	/**
	 * Create a uuid.
	 *
	 * @param Int $len Length.
	 * @return String with uuid.
	 */
	public static function uuid( $len = 13 ) {
		if ( function_exists( 'random_bytes' ) ) {
			$bytes = random_bytes( ceil( $len / 2 ) );
		} elseif ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
			$bytes = openssl_random_pseudo_bytes( ceil( $len / 2 ) );
		} else {
			throw new \Exception( 'no cryptographically secure random function available' );
		}
		return substr( bin2hex( $bytes ), 0, $len );
	}

	/**
	 * Fix Encoding.
	 *
	 * @param String $string String to fix Encode.
	 * @param String $encOut Encoding.
	 * @return void
	 */
	public static function fix_encoding( $string, $encOut = 'UTF-8' ) {
		return iconv( mb_detect_encoding( $string, mb_detect_order(), true ), $encOut, $string );
	}

	/**
	 * get Image ID (attachment) from a url.
	 *
	 * @param String $image_url Url of image.
	 * @return Int|Boolean
	 */
	public static function get_image_id( $image_url ) {
		$attachment = self::get_image_id_helper( $image_url );
		if ( $attachment ) {
			return isset( $attachment[0] ) ? $attachment[0] : false;
		} else {
			// not found, try image_url without  -scaled.
			if ( strpos( $image_url, '-scaled.' ) !== false ) {
				// fixing -scaled. information
				$image_url  = str_replace( '-scaled.', '.', $image_url );
				$attachment = self::get_image_id_helper( $image_url );
				if ( $attachment ) {
					return isset( $attachment[0] ) ? $attachment[0] : false;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
	}

	private static function get_image_id_helper( $image_url ) {
		global $wpdb;
		$attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid='%s' and 2 = %d", $image_url, 2 ) );
		return $attachment;
	}

	/**
	 * Get provided Author from Post.
	 *
	 * @param Int $post_id Post Id.
	 * @return Array
	 */
	public static function get_provider_author( $post_id ) {
		return get_post_meta( $post_id, 'author', true );
	}

	/**
	 * Get exclusive Parameter from Post.
	 *
	 * @param Int $post_id Post Id.
	 * @return Array
	 */
	public static function is_post_exclusive( $post_id ) {
		return get_post_meta( $post_id, '_k4exclusive', true );
	}

	/**
	 * Remove edit and delete rights for posts which are exclusive, except for admins.
	 *
	 * @param Object $allcaps AllCaps.
	 * @param [type] $caps Caps.
	 * @param Array  $args Args
	 * @return void
	 */
	public static function exclude_posts( $allcaps, $caps, $args ) {
		// note: postId = $args[2]
		if ( ( strpos( $args[0], 'edit_' ) !== false || strpos( $args[0], 'delete_' ) !== false ) && isset( $args[2] ) ) {
			$is_exclusive = self::is_post_exclusive( $args[2] );

			if ( $is_exclusive && ! current_user_can( 'administrator' ) ) {
				$_caps = (object) $allcaps;
				foreach ( $_caps as $cap => $val ) {
					if ( strpos( $cap, 'edit_' ) !== false || strpos( $cap, 'delete_' ) !== false ) {
						unset( $allcaps[ $cap ] );
					}
				}
			}
		}
		return $allcaps;
	}

	/**
	 * Add Preview ability to exclude Posts
	 *
	 * @param [type] $actions
	 * @return void
	 */
	public static function add_preview_exclude_posts( $actions ) {
		global $post;
		$is_exclusive = self::is_post_exclusive( $post->ID );
		if ( $is_exclusive && ! current_user_can( 'administator' ) ) {
			$title           = _draft_or_post_title();
			$actions['view'] = '<a href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ) ) . '" title="' . esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;' ), $title ) ) . '" rel="permalink">' . __( 'Preview' ) . '</a>';
		}
		return $actions;
	}

	/**
	 * get is Post "Classic" from metavalue
	 *
	 * @param Int $post_id Post Id.
	 * @return boolean
	 */
	public static function is_classic_post( $post_id ) {
		$editor = get_post_meta( $post_id, '_editor', true );
		return ( $editor == 'classic' ) ? true : false;
	}

	/**
	 * get if given Post by post_id is an Adapter API Post
	 *
	 * @param Int $post_id Post Id.
	 * @return boolean
	 */
	public static function is_adapter_api_post( $post_id ) {
		$editor = get_post_meta( $post_id, '_pluginOrigin', true );
		return empty( $editor ) ? false : true;

	}

	/**
	 * Add Metabox to Posts Edit
	 *
	 * @param [type] $post_type
	 * @return void
	 */
	public static function add_debug_metabox( $post_type ) {
		add_meta_box( 'debug_metabox', 'vjoon SupportMode', array( __CLASS__, 'add_debug_metabox_callback' ), $post_type, 'advanced', 'high' );

	}

	/**
	 * Callback Function for Add Metabox.
	 *
	 * @param Object $post Post Object.
	 * @return void
	 */
	public static function add_debug_metabox_callback( $post ) {
		// Use get_post_meta to retrieve an existing value from the database.
		$value = get_post_meta( $post->ID, '_raw', true );
		Debug::log( $value, 'METABOX_CALLBACK' );

		if ( ! empty( $value ) ) {
			// Display the form, using the current value.
			?>
			<a href="data:application/octet-stream;charset=utf-8;base64,<?php echo base64_encode( json_encode( $value ) ); ?>" download="<?php echo sanitize_title( $post->post_title ); ?>.json">Download SupportMode File</a>
			<?php
		} else {
			?>
			<span>No SupportMode File available. Make sure this posts is uploaded by WordPress Adapter API or reupload this post!</span>
			<?php
		}
	}

	/**
	 * Parses a html style tag attribute element to array.
	 *
	 * @param [type] $content Content.
	 * @return Array
	 */
	public static function parse_html_style_tag( $content ) {
		$styles = explode( ';', $content );
		$return = array();
		foreach ( $styles as $style ) {
			if ( ! empty( $style ) ) {
				$_split = explode( ':', $style );
				if ( ! empty( $_split ) ) {
					$return[ $_split[0] ] = $_split[1];
				}
			}
		}
		return $return;
	}

	/**
	 * Undocumented function.
	 *
	 * @param [type] $content Content.
	 * @param [type] $img DOM Object.
	 * @param [type] $dom DOM.
	 * @param [type] $imgid ImageId.
	 * @return array
	 */
	public static function parse_resized_image( $content, $img, $dom, $imgid ) {
		$class       = isset( $img->parentNode ) ? $img->parentNode->getAttribute( 'class' ) : '';
		$is_resized  = '';
		$img_height  = '';
		$img_width   = '';
		$img_percent = null;
		if ( strpos( $class, 'image_resized' ) !== false ) {
			// class image_resized aus DOM Element entfernen.
			$class = str_replace( 'image_resized', '', $class );
			$img->parentNode->setAttribute( 'class', $class );

			$img_size = wp_get_attachment_image_src( $imgid, 'full' ); // note: default: thumbnail,  available thumbnail,full,medium, large.
			$w        = $img->parentNode->getAttribute( 'style' );
			$styles   = self::parse_html_style_tag( $w );

			Debug::log( $img_size, 'IMAGE_SIZE' );
			Debug::log( $styles, 'PARSED STYLES' );

			// note: remove width or height from attr style.
			$_restyle    = array();
			$img_h       = null;
			$img_w       = null;
			$img_percent = null;
			foreach ( $styles as $key => $value ) {
				Debug::log(
					array(
						'key'   => $key,
						'value' => $value,
					),
					'FOREACHED'
				);
				if ( $key == 'width' ) {
					if ( strpos( $value, '%' ) !== false ) {
						$p           = floatval( $value ) / 100.00; // percenttage.
						$img_w       = $img_size[1] * $p;
						$img_h       = $img_size[2] * $p;
						$img_percent = floatval( $value );
					} elseif ( strpos( $value, 'px' ) !== false ) {
						$img_w = str_replace( 'px', '', $value );
					}
					$is_resized = ' is-resized';
				} elseif ( $key == 'height' ) {
					if ( strpos( $value, '%' ) !== false ) {
						$p           = floatval( $value ) / 100.00; // percenttage.
						$img_w       = $img_size[1] * $p;
						$img_h       = $img_size[2] * $p;
						$img_percent = floatval( $value );
					} elseif ( strpos( $value, 'px' ) !== false ) {
						$img_h = str_replace( 'px', '', $value );
					}
					$is_resized = ' is-resized';
				} else {
					$_restyle = $key . ':' . $value;
				}
			}
			$img->parentNode->setAttribute( 'style', implode( ';', $_restyle ) );
			if ( ! empty( $img_h ) ) {
				$img_height = $img_h;
				$img->setAttribute( 'height', $img_h );
			} else {
				$img_height = '';
			}
			if ( ! empty( $img_w ) ) {
				$img_width = $img_w;
				$img->setAttribute( 'width', $img_w );
			} else {
				$img_width = '';
			}

			$content = $dom->saveHTML();
		}
		return array(
			'content'     => $content,
			'is_resized'  => $is_resized,
			'img_width'   => $img_width,
			'img_height'  => $img_height,
			'img_percent' => $img_percent,
		);

	}

	public static function exists_cpt( $cpt ) {
		return post_type_exists( $cpt );
	}

	public static function load_language_mofile( $mofile, $domain ) {
		if ( $domain == 'vj-wp-adapter' ) {
			$mofile = App::$option->plugin->Dir . App::$option->plugin->LanguageResourcePath . 'vj-wp-adapter-' . get_locale() . '.mo';
			if ( ! file_exists( $mofile ) ) {
				$locale  = explode( '_', get_locale() );
				$locale2 = $locale[0];

				switch ( $locale2 ) {
					case 'de':
						$mofile = App::$option->plugin->Dir . App::$option->plugin->LanguageResourcePath . 'vj-wp-adapter-de_DE.mo';
						break;

					case 'en':
						$mofile = App::$option->plugin->Dir . App::$option->plugin->LanguageResourcePath . 'vj-wp-adapter-en_US.mo';
						break;

					default:
						$mofile = App::$option->plugin->Dir . App::$option->plugin->LanguageResourcePath . 'vj-wp-adapter-en_US.mo';
						break;
				}

				Debug::log( $mofile, 'Load Language Fallback File' );
			}
		}
		return $mofile;
	}
}
