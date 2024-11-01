<?php
/**
 * Class Functions
 *
 * ArticleClass for vjoon WordPress Adapter
 *
 * @author Christian Storm
 * @package vjoon\Adapter
 * @copyright 2019 vjoon GmbH
 */

namespace vjoon\Adapter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // exit if accessed directly
}

final class Article {

	private static $artcile_data;
	private static $preview_url;

	public function __construct( $data ) {
		self::$artcile_data = $data;
	}

	/**
	 * Parse Data
	 *
	 * @param string $type Special Parse Type, use "article" to parse articleData, else only contentId is parsed
	 * @return Boolean|Object
	 */
	public static function Parse( $type = 'article' ) {
		$data  = self::$artcile_data;
		$_data = json_decode( $data, true ); // return as assoc array
		if ( $_data === false ) {
			return false;
		} elseif ( is_array( $_data ) ) {

			if ( ! isset( $_data['contentId'] ) || ! is_string( $_data['contentId'] ) ) {
				return false; }

			if ( $type == 'article' ) {
				if ( ! isset( $_data['contentType'] ) || ! is_string( $_data['contentType'] ) ) {
					return false; }
				if ( ! isset( $_data['contentType'] ) && $_data['contentType'] == 'custom' ) {
					if ( ! isset( $_data['customPostType'] ) || ! is_string( $_data['customPostType'] ) ) {
						return false; }
				}
				if ( ! isset( $_data['k4exclusive'] ) || ! is_bool( $_data['k4exclusive'] ) ) {
					return false; }
				if ( ! isset( $_data['editor'] ) || ! is_string( $_data['editor'] ) ) {
					return false; }
				if ( isset( $_data['editor'] ) && ( strtolower( $_data['editor'] ) != 'classic' && strtolower( $_data['editor'] ) != 'gutenberg' ) ) {
					Debug::log( $_data['editor'], 'Missing Parameter editor' );
					return false;
				}
				if ( ! isset( $_data['origin'] ) || ! is_string( $_data['origin'] ) ) {
					return false; }
				if ( ! isset( $_data['section'] ) || ! is_string( $_data['section'] ) ) {
					return false; }
				if ( ! isset( $_data['date'] ) || ! strtotime( $_data['date'] ) ) {
					return false; }
				if ( ! isset( $_data['author'] ) || ! is_string( $_data['author'] ) ) {
					return false; }
				if ( ! isset( $_data['content'] ) || ! is_string( $_data['content'] ) ) {
					return false; }
				if ( ! isset( $_data['title'] ) || ! is_string( $_data['title'] ) ) {
					return false; }
			}

			self::$artcile_data = json_decode( $data );
			return self::$artcile_data; // return as object!
		}
	}

	/**
	 * Upload Article with given Data
	 *
	 * @param Boolean $preview set to true if preview url requested
	 *
	 * @return Boolean
	 */
	public static function Upload( $preview = false ) {
		global $current_user;

		$oembed = 0;

		// note: check if contentId exists, delete if exists and save post_status
		$existIds           = self::exists( self::$artcile_data->contentId );
		$status             = 'draft';
		$upd_menu_nav_items = array();
		if ( $existIds ) {
			Debug::log( $existIds, 'UPLOADED CONTENTID EXISTS' );
			foreach ( $existIds as $Id ) {
				$_post  = get_post( $Id );
				$status = isset( $_post ) ? $_post->post_status : $status;

				// note: save nav_menu_item temporary associated with exiting post
				$_menu_item_ids = wp_get_associated_nav_menu_items( $Id );
				Debug::log( $_menu_item_ids, 'ASSOCIATED MENU ITEMS' );
				foreach ( (array) $_menu_item_ids as $menu_item_id ) {
					$upd_menu_nav_items[] = $menu_item_id; // wp_get_nav_menu_object($Id);

					update_post_meta( $menu_item_id, '_menu_item_object_id', 'update_nav_menu_item' );
					wp_update_post(
						array(
							'ID'        => $menu_item_id,
							'post_name' => 'update_nav_menu_item',
							'post_type' => 'saved_nav_menu_item',
						)
					);
				}
				Debug::log( $upd_menu_nav_items, 'SAVED MENU ITEMS FOR UPDATE' );

				wp_delete_post( $Id, true );
			}
		}

		// note: mandatory post meta data
		$meta = array(
			'author'        => self::$artcile_data->author,
			'contentId'     => self::$artcile_data->contentId,

			// hidden
			'_origin'       => self::$artcile_data->origin,
			'_section'      => self::$artcile_data->section,
			'_version'      => APP::$option->plugin->Version,
			'_pluginOrigin' => APP::$option->plugin->Name,
			'_editor'       => self::$artcile_data->editor,
			'_k4exclusive'  => self::$artcile_data->k4exclusive,

		);
		// note: optional post meta datas
		if ( isset( self::$artcile_data->language ) ) {
			$meta['_language'] = array( self::$artcile_data->language ); }

		if ( $preview ) {
			$meta['_preview'] = time(); }
		if ( APP::$option->general->debug ) {
			$meta['_raw'] = self::$artcile_data; }

		// note: content auf vjoonmedia:// parsen
		$content = base64_decode( self::$artcile_data->content );
		$parsed  = self::parseVjoonmedia( $content );
		if ( strtolower( self::$artcile_data->editor ) == 'classic' ) { // note: classic content
			$content = Classic::Doozy( $parsed['content'] );
		} elseif ( strtolower( self::$artcile_data->editor ) == 'gutenberg' ) { // note: convert content to Gutenberg Blocks
			$content = Gutenberg::Doozy( $parsed['content'] );
			$oembed  = $content['oembed']; // note: array mit eingebetteten wp:core-embed
			$content = $content['content'];
		}
		if ( strtolower( self::$artcile_data->contentType ) == 'custom' ) {
			self::$artcile_data->contentType = self::$artcile_data->customPostType;
			// note: check if CPT exists, return value with error code?
			if ( ! Functions::exists_cpt( self::$artcile_data->contentType ) ) {
				return array(
					'result' => false,
					'code'   => 1107,
				);
			}
		}

		$post_modified = gmdate( 'Y-m-d H:i:s', time() );

		// note: mandatory post datas
		$args = array(
			'post_author'    => $current_user->ID,
			'post_content'   => $content,
			'post_date'      => self::$artcile_data->date,
			'post_date_gmt'  => self::$artcile_data->date,
			'post_title'     => self::$artcile_data->title, // ($preview ? __('Preview: ', 'vj-wp-adapter') : '') . substr( self::_getDOMElement( $content ), 0, 128), //add Preview: to Title if $preview is set to true
			// 'post_excerpt'          => substr( self::_getDOMElement( $content, ['p'] ) ,0,512),
			'post_status'    => $status,
			'post_type'      => self::$artcile_data->contentType,
			'comment_status' => 'closed', // default = default_comment_status
			'ping_status'    => 'closed', // default = default_ping_status
			'meta_input'     => $meta,
		);

		// note: optional post datas
		/**
		 * AVAILABLE
		 * post_content_filterd'  => '',
		 * 'post_password'         => '',
		 * 'to_ping'               => '',
		 * 'pinged'                => '',
		 * 'post_parent'           => 0,
		 * 'menu_order'            => 0,
		 * 'guid'                  => '',
		 * 'import_id'             => 0,
		 * 'context'               => '',
		 * 'post_mime_type',
		 * 'ancestors',
		 * 'tags_input'            => Array(),
		 * 'tax_input'            => Array(),
		 * 'filter',
		 */

		$catId = 1;
		if ( isset( self::$artcile_data->category ) ) {
			require_once ABSPATH . 'wp-admin/includes/taxonomy.php';
			$catId = wp_create_category( self::$artcile_data->category );
			// $ids = wp_create_categories(self::$artcile_data->category); //note: wenn Array übergeben wird
			$args['post_category'] = array( $catId );
		} else {
			$args['post_category'] = array( $catId ); // note: 1 sollte meist Category Uncategorized sein
		}
		if ( isset( self::$artcile_data->tags ) && is_array( self::$artcile_data->tags ) ) {
			$args['tags_input'] = self::$artcile_data->tags; }

		$post_id = wp_insert_post( $args, true );

		if ( ! is_wp_error( $post_id ) ) {
			global $wpdb;
			$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET post_modified = %s, post_modified_gmt = %s  WHERE ID = %d", $post_modified, $post_modified, $post_id ) );

			// note: gefundene MediaDateien mit dem PostID verlinken!
			if (isset( $parsed['ids'] )) {
				foreach ( $parsed['ids'] as $id ) {
					wp_update_post(
						array(
							'ID'          => $id,
							'post_parent' => $post_id,
						)
					);
				}
			}

			// note: gespeicherte nav_menu_items mit neuer post_id versehen
			foreach ( $upd_menu_nav_items as $id ) {
				update_post_meta( $id, '_menu_item_object_id', $post_id, 'update_nav_menu_item' );
				wp_update_post(
					array(
						'ID'        => $id,
						'post_name' => $post_id,
						'post_type' => 'nav_menu_item',
					)
				);
				delete_post_meta( $id, '_wp_old_slug' );
				Debug::log( $id, 'UPDATE MENU ITEM' );
			}

			if ( isset( $parsed['featured_image'] ) && ! empty( $parsed['featured_image'] ) ) { // note: if content has featured_image parsed from vjoonmedia://
				set_post_thumbnail( $post_id, $parsed['featured_image'] );
			} else { // note: try to get featured image from content img-tag, this is maybe not possible because an external image can't be an featured image
				// $img = self::_getDOMElement($content, ['img']);
			}

			if ( strtolower( self::$artcile_data->editor ) == 'gutenberg' ) {
				// note: this block embed via API
				foreach ( $oembed as $oem ) {

					// set oembed to wp_postmeta table
					$attr = wp_parse_args( array(), wp_embed_defaults( $oem['url'] ) );
					$md5  = md5( $oem['url'] . serialize( $attr ) );
					add_post_meta( $post_id, '_oembed_' . $md5, $oem['data']->html ); // $_oembed
					add_post_meta( $post_id, '_oembed_time_' . $md5, time() );

				}
			}

			self::$preview_url = site_url( '' ) . '/_preview/' . self::$artcile_data->contentId;
			return array(
				'result' => true,
				'code'   => 0,
			);

		} else { // note: there was an error in the post insertion.
			Debug::log( $post_id, 'Error while insert post' );
			Debug::log( $args, 'INSERT POST ARGS ARRAY' );

			return array(
				'result' => false,
				'code'   => 1102,
			);

		}
	}

	/**
	 * parses content for vjoonmedia://
	 *
	 * @param [type] $content
	 * @return array Array with parsed content, attachment ids and optional featured image
	 */
	private static function parseVjoonmedia( $content ) {
		$return = array();

		$pattern = '/vjoonmedia:\/\/(.*?)[\"\']/';

		$matches = null;
		preg_match_all( $pattern, $content, $matches );
		if ( ! empty( $matches ) ) {
			$values  = $matches[1];
			$matches = $matches[0];
		}

		Debug::log(
			array(
				'values'  => $values,
				'matches' => $matches,
			)
		);

		$cnt = 0;
		foreach ( $values as $mediaConentId ) {
			$res = Media::exists( $mediaConentId );
			if ( $res ) {
				foreach ( $res as $attachId ) {
					$return['ids'][] = $attachId;

					// note: handle featured image
					$fi_class = '';
					if ( ! isset( $return['featured_image'] ) || empty( $return['featured_image'] ) ) {
						// note: save featured image
						$return['featured_image'] = $attachId;
						// note: mark the image which is featured image from content via CSS
						// $fi_class= " class='featured_image'"; //note: remove due to Gutenberg Error: unexpected or invalid content, class was not needed

					}
					$url  = wp_get_attachment_url( $attachId );
					$sign = str_replace( 'vjoonmedia://' . $values[ $cnt ], '', $matches[ $cnt ] );

					Debug::log(
						array(
							'sign'        => $sign,
							'values_cnt'  => $values[ $cnt ],
							'matches_cnt' => $matches[ $cnt ],
							'attachId'    => $attachId,
						)
					);

					$content = str_replace( $matches[ $cnt ], $url . $sign . $fi_class, $content );
				}
			}
			$cnt++;
		}
		if ( empty( $return['content'] ) ) {
			$return['content'] = $content; }
		return $return;
	}

	/**
	 * check if contentID exists
	 *
	 * @param [type] $contentId
	 * @return Array|Boolean
	 */
	private static function exists( $contentId ) {
		$return = array();
		global $wpdb;
		$sql    = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s and meta_value = %s";
		$sql    = $wpdb->prepare( $sql, 'contentId', $contentId );
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
	 * Publish Article
	 *
	 * @return Boolean
	 */
	public static function Publish() {
		$post_id = self::getPostId( self::$artcile_data->contentId );
		$args    = array(
			'ID'          => $post_id,
			'post_status' => 'publish',
		);
		Debug::log( $args, 'Call Update Post' );
		$result = wp_update_post( $args, true );
		Debug::log( $post_id, 'Article Published' );

		if ( ! is_wp_error( $result ) ) {
			return true;
		} else { // note: there was an error in the post insertion,
			Debug::log( $result->get_error_message(), 'Error while publish post' );
			return false;
		}
	}

	/**
	 * Unpublish Article
	 *
	 * @return Boolean
	 */
	public static function Unpublish() {
		$post_id = self::getPostId( self::$artcile_data->contentId );
		$args    = array(
			'ID'          => $post_id,
			'post_status' => 'draft',
		);
		Debug::log( $args, 'Call Update Post' );
		$result = wp_update_post( $args, true );
		Debug::log( $post_id, 'Article Unpublished' );

		if ( ! is_wp_error( $result ) ) {
			return true;
		} else { // note: there was an error in the post insertion,
			Debug::log( $result->get_error_message(), 'Error while unpublish post' );
			return false;
		}
	}

	public static function Delete() {
		$post_id = self::getPostId( self::$artcile_data->contentId );
		Debug::log( $post_id, 'Call Delete Article' );

		Functions::delete_related_posts( $post_id );

		$result = wp_delete_post( $post_id, true );

		if ( ! is_wp_error( $result ) ) {
			Debug::log( $result, 'Successful Call Delete Article' );
			return true;
		} else { // note: there was an error in the post insertion,
			Debug::log( $result->get_error_message(), 'Error while deleting post' );
			return false;
		}
	}

	/**
	 * get Preview Url from Uploaded Article in Previewmode
	 *
	 * @return string
	 */
	public static function getPreviewUrl() {
		return self::$preview_url;
	}

	/**
	 * get Post ID from Metadata with contentId
	 *
	 * @param [type] $contentId
	 * @return Integer|Boolean
	 */
	private static function getPostId( $contentId ) {
		$args = array(
			'post_type'   => 'any',
			'post_status' => 'any',
			'meta_query'  => array(
				'content_clause' => array(
					'key'   => 'contentId',
					'value' => $contentId,
				),
			),
		);
		$post = new \WP_Query( $args );
		if ( ! is_wp_error( $post ) ) {
			if ( isset( $post->post->ID ) ) {
					$post_id = $post->post->ID; // find it
					Debug::log( $post_id, 'getPostId from contentId ' . $contentId );
					return $post_id;
			} else {
				Debug::log( $post_id, 'PostId from contentId ' . $contentId . ' not found' );
				return false;
			}
		} else {
			Debug::log( $post->get_error_message(), 'Error while getting post id' );
			return false;
		}
	}

	/**
	 * get Element from HTML-DOM-CONTENT
	 *
	 * returns Tag Element if found, else full content
	 *
	 * @param string $content HTML Content
	 * @param array  $tags Element to get
	 *
	 * @return String
	 */
	private static function _getDOMElement( $content, array $tags = array( 'h1', 'h2', 'h3', 'h4' ) ) {
		$document = new \DOMDocument();
		$document->loadHTML( $content );

		$elems = array();
		foreach ( $tags as $tag ) {
			$elementList = $document->getElementsByTagName( $tag );
			foreach ( $elementList as $element ) {
				$elems[ $element->tagName ][] = $element->textContent;
			}
		}
		Debug::log( $elems, '_getDOMElement' );
		foreach ( $tags as $tag ) {
			if ( isset( $elems[ $tag ][0] ) ) {
				return Functions::fix_encoding( $elems[ $tag ][0] );}
		}
		return Functions::fix_encoding( $content );
	}

}
