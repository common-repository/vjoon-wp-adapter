<?php

/**
 * Class Classic 
 * 
 * Convert Classic Content in CKE Style to Classic Editor Style
 * Standard Blocks: https://gogutenberg.com/blocks/
 * 
 * @author Christian Storm
 * @package vjoon\Adapter
 * @copyright 2020 vjoon GmbH
 * 
 * 
 */

namespace vjoon\Adapter;

if ( ! defined ( 'ABSPATH') ) exit; //exit if accessed directly

final class Classic {

    /**
     * Convert Classic Content in CKE Style to Classic Editor Style
     *
     * @param [type] $content
     * @return void
     */
    public static function Doozy($content) {
        if ( !empty($content) ) {
            /*************************************** Convert with DOM Element ***************************************/
            $dom = new \DOMDocument(null, 'UTF-8');
            $dom->encoding = 'utf-8';
            libxml_use_internal_errors(true);
            $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
            $dom->removeChild($dom->doctype);
            $dom->encoding = 'utf-8';

            //dom img-Tag, note: be aware of that this element is not child of figure-Tag
            $img_array = $dom->getElementsByTagName('img');
            $img_src_alt_arr = [];
            foreach ($img_array as $img) {
                if ( isset($img->parentNode->nodeName) && $img->parentNode->nodeName != 'figure' ) { 
                    //parent node is figure-Tag, exclude this, only pure img-Tag
                    $src = $img->getAttribute('src');
                    $alt = $img->getAttribute('alt');
                    if (empty($img_src_alt_arr[$src])) {
                        $img_src_alt_arr[$src] = $alt;
                    }                        
                } else {
                    $src = $img->getAttribute('src');
                    $imgid = Functions::get_image_id($src);

                    //note: add wp-image class
                    $class = $img->getAttribute('class');
                    if(strpos($class,'wp-image') === false) {
                        $class = empty($class) ? "wp-image-".$imgid : $class." wp-image-".$imgid;
                    }
                    $img->setAttribute('class',$class);


                    $content = $dom->saveHTML();

                    $result = Functions::parse_resized_image($content, $img, $dom, $imgid); //note: handle image_resized
                    $content = $result['content'];
                    $is_resized = $result['is_resized'];
                    $img_width = $result['img_width'];
                    $img_height = $result['img_height'];
                    $img_percentage = empty($result['img_percent']) ? null : $result['img_percent'];

                    //parent node is figure-Tag
                    $class = $img->parentNode->getAttribute('class'); //as string, no matter if single or multiple classes
                    if ( strpos( $class, "image") !== false) { //only handle figure tag, if class has image

                        //check if align is available
                        $alignClass = "";
                        if ( strpos($class,"align-left") !== false) {
                            $alignClass = " alignleft";
                        } elseif (strpos($class,"align-right") !== false) {
                            $alignClass = " alignright";
                        } elseif (strpos($class,"align-center") !== false) {
                            $alignClass = " aligncenter";
                        } elseif (strpos($class,"style-side") !== false ) {
                            $alignClass = " alignright";
                        } else {
                            //$alignClass = "aligncenter";
                        }

                        //add attribute to figure tag
                        $img->parentNode->setAttribute('class',$alignClass.$is_resized);
                        if (!empty($img_percentage)) {$img->parentNode->setAttribute('style','max-width:'.$img_percentage.'%');} //add style attribute to figure-tag

                        $class = $img->getAttribute('class');
                        $img->setAttribute('class',(empty($class) ? $alignClass : $class.$alignClass ) );
                        if ( !empty($is_resized) ) {
                            $img->setAttribute('height',$img_height); //note: must be px
                            $img->setAttribute('width',$img_width); //note: must be px
                        }

                        $content = $dom->saveHTML();
                    }
            
                }

                //note: future: img tag without figure tag around (CK - K4 provides always a figure-tag around)
            }

        }
        return $content;
    }

}