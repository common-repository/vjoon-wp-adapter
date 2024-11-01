<?php

/**
 * Class Functions 
 * 
 * MediaClass for vjoon WordPress Adapter 
 * 
 * @author Christian Storm
 * @package vjoon\Adapter
 * @copyright 2019 vjoon GmbH
 * 
 */
namespace vjoon\Adapter;

if ( ! defined ( 'ABSPATH') ) exit; //exit if accessed directly

final class Media {

    private static $fileData;

    public function __construct($multipartfile){
        self::$fileData = $multipartfile;
    }

    /**
     * Upload Mediafile into MediaLibrary
     *
     * @return Boolean|WP_ERROR
     */
    public static function Upload() {
        $wp_upload_dir = wp_upload_dir();
        $filepath = $wp_upload_dir['path'].'/'.basename( self::$fileData['name'] );
        $filetype = wp_check_filetype( basename( $filepath ), null );

        //note: check if MediaFile exists as Attachment and delete, but save post_parent
        $has_post_parent = 0;
        $existIds =  self::exists( basename( $filepath ) );
        if ( $existIds ) {
            foreach($existIds as $Id) {
                $has_post_parent = self::getMedia_post_parent($Id);
                wp_delete_attachment($Id, true);
            }
        }
 
        move_uploaded_file(self::$fileData['tmp_name'],$filepath);

        $attachment = array(
            'guid'           => $wp_upload_dir['url'] . '/' . basename( $filepath ), 
            'post_mime_type' => $filetype['type'],
            'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filepath ) ),
            'post_content'   => '',
            'post_status'    => 'inherit',
            'post_parent'    => $has_post_parent,
        );

        $attach_id = wp_insert_attachment( $attachment, $filepath );

        if ( !is_wp_error($attach_id) ) {
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            $attach_data = wp_generate_attachment_metadata( $attach_id, $filepath );
            wp_update_attachment_metadata( $attach_id, $attach_data );

            $meta_id = add_post_meta($attach_id, 'mediaContentId', basename( $filepath ) , true);
            $meta_id = add_post_meta($attach_id, '_version', APP::$option->plugin->Version , true);
            $meta_id = add_post_meta($attach_id, '_pluginOrigin', APP::$option->plugin->Name , true);
            if ($meta_id) {
                return true;
            }
        } else {
            return $attach_id; //WP_ERROR
        }
    }

    /**
     * get post_id(s) as array of mediaContentId
     *
     * @param [string] $mediaContentId
     * @return Array|Boolean
     */
    public static function exists($mediaContentId) {
        $return = array();
        global $wpdb;
        $sql = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s and meta_value = %s";
        $sql = $wpdb->prepare($sql, 'mediaContentId', $mediaContentId);
        $result = $wpdb->get_results($sql);
        if ($result) {
            foreach($result as $v) {
                $return[]=$v->post_id;
            }
        } else {
            return false;
        }
        return $return;
    }

    /**
     * return post_parent of Attachment Post
     *
     * @param [integer] $id Attachement ID
     * @return integer
     */
    private static function getMedia_post_parent($id) {
        $return = 0;
        global $wpdb;
        $sql = "SELECT post_parent FROM $wpdb->posts WHERE ID = %d and 2 = %d";
        $sql = $wpdb->prepare($sql, $id, 2);
        $result = $wpdb->get_results($sql);
        if ($result) {
            foreach($result as $v) {
                $return = $v->post_parent; 
            }
        }
        return $return;
    }

    public static function getFileName() {
        return self::$fileData['name'];
    }

    public static function getFileType() {
        return self::$fileData['type'];
    }

    public static function getFileSize() {
        return self::$fileData['size'];
    }

    public static function getFileFullPath() {
        return self::$fileData['tmp_name'];
    }
}