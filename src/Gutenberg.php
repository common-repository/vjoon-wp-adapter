<?php

/**
 * Class Gutenberg 
 * 
 * Convert Classic Content to Gutenberg Blocks
 * Standard Blocks: https://gogutenberg.com/blocks/
 * 
 * @author Christian Storm
 * @package vjoon\Adapter
 * @copyright 2019 vjoon GmbH
 * 
 * 
 */

namespace vjoon\Adapter;

if ( ! defined ( 'ABSPATH') ) exit; //exit if accessed directly

final class Gutenberg {

    private static $oembeded = array();

    /**
     * Convert Classic Content To Gutenberg Blocks
     *
     * @param [type] $content
     * @return void
     */
    public static function Doozy($content) {
        if ( !empty($content) ) {
            Debug::Log($content,'CONTENT BEFORE CONVERTING TO GUTENBERG BLOCK');
            if (strpos($content, '<!-- wp:') === false) { //note: check if content is already GB

                /*************************************** Standard HTML Tags ***************************************/
                if (strpos($content, '<script') !== false) {
                    $content = str_replace('<script', '<!-- wp:html --> <script', $content);
                    $content = str_replace('</script>', '</script><!-- /wp:html -->', $content);
                }
                if (strpos($content, '<style') !== false) {
                    $content = str_replace('<style', '<!-- wp:html --> <style', $content);
                    $content = str_replace('</style>', '</style><!-- /wp:html -->', $content);
                }
                if (strpos($content, '<p') !== false) {
                    $content = self::convertHTMLTag($content, "p", "paragraph");
                    $content = preg_replace("/<\/p>/i", '</p><!-- /wp:paragraph -->', $content);
                }
                if (strpos($content, '<span') !== false) {
                    $content = preg_replace("/<span(|( [a-z][a-z0-9]*)[^>]*?(\/?))>/i", '<!-- wp:paragraph --> <p>', $content);
                    $content = preg_replace("/<\/span>/i", '</p><!-- /wp:paragraph -->', $content);
                }
                if (strpos($content, '<pre') !== false) {
                    $content = self::convertHTMLTag($content, "pre", "preformatted", "wp-block-preformatted");
                    $content = preg_replace("/<\/pre>/i", '</pre><!-- /wp:preformatted -->', $content);
                }
                if (strpos($content, '<h1') !== false) {
                    $content = self::convertHeadings($content, "h1", "1");
                    $content = preg_replace("/<\/h1>/i", '</h1><!-- /wp:heading -->', $content);
                }
                if (strpos($content, '<h2') !== false) {
                    $content = self::convertHeadings($content, "h2", "2");
                    $content = preg_replace("/<\/h2>/i", '</h2><!-- /wp:heading -->', $content);
                }
                if (strpos($content, '<h3') !== false) {
                    $content = self::convertHeadings($content, "h3", "3");
                    $content = preg_replace("/<\/h3>/i", '</h3><!-- /wp:heading -->', $content);
                }
                if (strpos($content, '<h4') !== false) {
                    $content = self::convertHeadings($content, "h4", "4");
                    $content = preg_replace("/<\/h4>/i", '</h4><!-- /wp:heading -->', $content);
                }
                if (strpos($content, '<h5') !== false) {
                    $content = self::convertHeadings($content, "h5", "5");
                    $content = preg_replace("/<\/h5>/i", '</h5><!-- /wp:heading -->', $content);
                }
                if (strpos($content, '<h6') !== false) {
                    $content = self::convertHeadings($content, "h6", "6");
                    $content = preg_replace("/<\/h6>/i", '</h6><!-- /wp:heading -->', $content);
                }
                if (strpos($content, '<ul') !== false) {
                    $content = self::convertHTMLTag($content, "ul", "list");
                    $content = preg_replace("/<\/ul>/i", '</ul><!-- /wp:list -->', $content);
                }
                if (strpos($content, '<ol') !== false) {
                    $content = self::convertHTMLTag($content, "ol", "list", "", ["reversed", "start"]);
                    $content = preg_replace("/<\/ol>/i", '</ol><!-- /wp:list -->', $content);
                }
                if (strpos($content, '<table') !== false) {
                    $content = self::convertHTMLTag($content, "table", "table");
                    $content = preg_replace("/<\/table>/i", '</table></figure><!-- /wp:table -->', $content);
                }
                if (strpos($content, '<hr') !== false) {
                    $content = preg_replace('/<hr[^>]*?(\/?)>/i', '<!-- wp:separator --><hr class="wp-block-separator"/><!-- /wp:separator -->', $content);
                }
                if (strpos($content, '<blockquote') !== false) {
                    $content = self::convertHTMLTag($content, "blockquote", "quote");
                    $content = preg_replace("/<blockquote>/i", '<blockquote class="wp-block-quote">', $content);
                    $content = preg_replace("/<\/blockquote>/i", '</blockquote><!-- /wp:quote -->', $content);
                }
                if (strpos($content, '[embed]') !== false) { 
                    $content = preg_replace("/\[embed\]/i", '<oembed url="', $content);
                    $content = preg_replace("/\[\/embed\]/i", '"> </oembed>', $content);
                }
                if (strpos($content, '<oembed') !== false) {
                    $content = self::convertHTMLTag($content, "oembed", "core-embed");
                    $content = preg_replace("/<oembed[^>]*?(\/?)>/i", '', $content);
                    $content = preg_replace("/<\/oembed>/i", '', $content);
                }

                /*************************************** Convert with DOM Element ***************************************/
                $dom = new \DOMDocument(null, 'UTF-8');
                $dom->encoding = 'utf-8';
                libxml_use_internal_errors(true);
                $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
                $dom->removeChild($dom->doctype);
                $dom->encoding = 'utf-8';

                $img_dnl = $dom->getElementsByTagName('img'); //returns DOMNodeList which is LIVE
                $img_array = self::array_copy( $img_dnl ); 
                $img_src_alt_arr = [];
                $uniqids = [];
                foreach($img_array as $i => $img) {
                    if ( isset($img->parentNode->nodeName) && $img->parentNode->nodeName != 'figure' ) {  //if parent node is figure-Tag, then exclude this img, only pure img-Tag
                        $src = $img->getAttribute('src');
                        $alt = $img->getAttribute('alt');
                        if (empty($img_src_alt_arr[$src])) {
                            $img_src_alt_arr[$src] = $alt;
                        }                        
                    } else {
                        $src = $img->getAttribute('src');
                        $imgid = Functions::get_image_id($src);
            
                        $class = $img->getAttribute('class'); //note: add wp-image class
                        if(strpos($class,'wp-image') === false) {
                            $class = empty($class) ? "wp-image-".$imgid : $class." wp-image-".$imgid;
                        }
                        $img->setAttribute('class',$class);
  
                        if ($img->hasAttributes()) { //note: remove all data-Attributes! Due to GB BLock Editor marks this as invalid, except Attributes are registered
                            $toremove = [];
                            foreach($img->attributes as $attr) {
                                if (strpos($attr->nodeName,'data') !== false) {
                                    $toremove[] = $attr->nodeName;
                                }
                            }
                            if (!empty($toremove)) {
                                foreach($toremove as $remove) {
                                    $img->removeAttribute($remove);
                                }
                            }
                        }

                        $content = $dom->saveHTML();

                        $result = Functions::parse_resized_image($content, $img, $dom, $imgid); //note: handle image_resized
                        $content = $result['content'];
                        $is_resized = $result['is_resized'];
                        $img_percentage = null;
                        if (!empty($is_resized)) {
                            $img_width = ',"width":'.$result['img_width']; //note: must be px
                            $img_height = ',"height":'.$result['img_height']; //note: must be px
                            $img_percentage = $result['img_percent'];
                        } else {
                            $img_width = "";
                            $img_height = "";
    
                        }

                        $class = isset($img->parentNode) ? $img->parentNode->getAttribute('class') : ''; //as string
                        if ( strpos( $class, "image") !== false) { //only handle figure tag, if figure-tag class has 'image'
                            $align = "";
                            $wpcore_class = 'wp-block-image';
                            if ( strpos($class,"align-left") !== false) {
                                $align = ',"align":"left"';
                                $wpcore_class = "alignleft";
                            } elseif ( strpos($class,"align-right") !== false ) {
                                $align = ',"align":"right"';
                                $wpcore_class = "alignright";
                            } elseif ( strpos($class,"align-center") !== false ) {
                                $align = ',"align":"center"';
                                $wpcore_class = "aligncenter";
                            } elseif ( strpos($class,"style-side") !== false ) {
                                $align = ',"align":"right"';
                                $wpcore_class = "alignright";
                            } else {
                                //$align = ',"align":"center"';
                                //$wpcore_class = "aligncenter";
                            }

                            //note: handle align
                            if (!empty($align)) {
                                //Handle img as figure Tag Child via DOM Element
                                $imgClone = $img->cloneNode();
                                $figcap = $img->parentNode->getElementsByTagName('figcaption');
                                $figcap = isset($figcap[0]) ? $figcap[0] : False;

                                $figClone = $img->parentNode->cloneNode(); //entweder true oder imgClone und figcap als child appenden
                                $figClone->appendChild($imgClone);
                                if ($figcap) { $figClone->appendChild($figcap); }
                                $figClone->setAttribute('class',$wpcore_class.' size-full'.$is_resized);

                                $uniqid = 'ghost'.uniqid();
                                $uniqids[] = '<'.$uniqid.'>';
                                $uniqids[] = '</'.$uniqid.'>';
                                $ghost = $dom->createElement($uniqid);

                                $div = $dom->createElement('div');
                                $div->setAttribute('class','wp-block-image'.(!empty($is_resized)? $is_resized.'-'.$imgid : ''));
                                $div->appendChild( $figClone );

                                $ghost->appendChild( $dom->createComment(' wp:image {"id":'.$imgid.',"sizeSlug":"full"'.$align.$img_width.$img_height.',"className":"'.(!empty($is_resized)? $is_resized.'-'.$imgid : '').'"} ') );
                                $ghost->appendChild( $div );
                                $ghost->appendChild( $dom->createComment(' /wp:image ') );

                                $img->parentNode->parentNode->replaceChild($ghost, $img->parentNode);

                            } else {
                                //add attribute to figure tag
                                $img->parentNode->setAttribute('class',$wpcore_class.(empty($class) ? '' : ' '.$class.$is_resized.(!empty($is_resized)? $is_resized.'-'.$imgid : '')));

                                //wp core block begin
                                $preContent = $dom->createComment(' wp:image {"id":'.$imgid.',"className":"'.$class.(!empty($is_resized)? $is_resized.'-'.$imgid : '').'"'.$align.$img_width.$img_height.'} ');
                                $img->parentNode->parentNode->insertBefore($preContent, $img->parentNode);

                                //wp core block end
                                $postContent = $dom->createComment(' /wp:image ');
                                $img->parentNode->parentNode->insertBefore($postContent, $img->parentNode->nextSibling);
                                
                            }
                            
                            $content = $dom->saveHTML();
                        }
                    }
                }
                
                if (strpos($content, '<img') !== false) {
                    $regex = '/src="([^"]*)"/';
                    preg_match_all($regex, $content, $matches);
                    $matches = array_reverse($matches);
                    $img_path = $matches[0];

                    $image_urls = array();
                    $image_urls = $img_path;

                    preg_match_all('/<img[^>]*?(\/?)>/i', $content, $results);

                    $a1 = $results[0];
                    $a2 = $image_urls;

                    $min = min(count($a1), count($a2));
                    $image_urls = array_combine(array_slice($a1, 0, $min), array_slice($a2, 0, $min));
                    foreach ($image_urls as $image_url => $value) {
                        if ( isset($img_src_alt_arr[$value]) ) {
                            $image_class = self::getClass($image_url);
                            $alt = !empty($img_src_alt_arr[$value]) ? $img_src_alt_arr[$value] : '';
                            $img_id = Functions::get_image_id($value);
                            $content = preg_replace($image_url, '!-- wp:image {"id":' . $img_id . '} --><figure class="wp-block-image ' . $image_class . '"><img src="' . esc_url($value) . '" alt="' . esc_attr($alt) . '" class="wp-image-' . esc_attr($img_id) . '"/></figure><!-- /wp:image --', $content);    
                        }
                    }
                }
                
                $content = str_replace(array('<html>','<body>','</html>','</body>'), '', $content);
                $content = str_replace($uniqids,'', $content);

                Debug::log($content,'RETURN CONTENT WITH CONVERTED GUTENBERG BLOCKS');
                return array('content' => $content,'oembed' => self::$oembeded);

            } else { //content is already GB, nothing to do

                return array('content' => $content,'oembed' => self::$oembeded);

            }
        } else { //content is empty, nothing to do

            return array('content' => $content,'oembed' => self::$oembeded);

        }

    }

    /**
     * Convert Headings H1, H2, H3, H4, H5, H6 to Gutenberg Block
     *
     * @param string $content
     * @param string $tag
     * @param string $level
     * @return void
     */
    private static function convertHeadings($content = '', $tag = '', $level = ''){
        if ($tag !== '') {
            $dom = new \DOMDocument(null, 'UTF-8');
            $dom->encoding = 'utf-8';
            libxml_use_internal_errors(true);
            $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
            $dom->removeChild($dom->doctype);
            $tags = $dom->getElementsByTagName($tag);
            foreach ($tags as $tag) {
                $class = $tag->getAttribute('class');
                $id = $tag->getAttribute('id');
                $attributes = $tag->attributes;

                while ($attributes->length) {
                    $tag->removeAttribute($attributes->item(0)->name);
                }

                if ($id !== '') {
                    $tag->setAttribute('id', $id);
                }
                if ($class !== '') {
                    $tag->setAttribute('class', $class);
                }
                if ($class !== '') {
                    $preContent = $dom->createComment(' wp:heading {"level":' . $level . ', "className": "' . $class . '"} ');
                } else {
                    $preContent = $dom->createComment(' wp:heading {"level":' . $level . '} ');
                }
                $tag->parentNode->insertBefore($preContent, $tag);

            }
            return $dom->saveHTML();
        }
    }

    /**
     * Convert HTML Tag to Gutenberg Block
     *
     * @param string $content
     * @param string $htmlTag
     * @param string $namespace
     * @param string $preClass
	 * @param string array $allowedAttributeNames
     * @return void
     */
    private static function convertHTMLTag($content = '', $htmlTag = '', $namespace = '', $preClass = '', $allowedAttributeNames = []){
        if ( $htmlTag !== '' ) {
            $dom = new \DOMDocument(null, 'UTF-8');
            $dom->encoding = 'utf-8';
            libxml_use_internal_errors(true);
            $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
            $dom->removeChild($dom->doctype);
            $tags = $dom->getElementsByTagName($htmlTag);
            $tags_array = self::array_copy( $tags ); 
            $uniqids = [];
            foreach($tags_array as $i => $tag) {
                if ($tag->tagName == "blockquote") { //note: special handle p tags in blockquote, which have wp:paragraph
                    $_chs = $tag->childNodes;
                    $_arr = array();
                    foreach($_chs as $_ch) {
                        if ($_ch->nodeName == '#comment' ) {
                            $_arr[] = $_ch;
                        }
                    }
                    foreach($_arr as $ar) {
                        $ar->parentNode->removeChild($ar);
                    }
                }

                if ( isset($tag->parentNode->nodeName) && $tag->parentNode->nodeName == "figure") { //note: wenn parent ein figure-Tag ist, dann diesen parent löschen und $tag einfügen
                    $tag->parentNode->parentNode->replaceChild($tag, $tag->parentNode);
                }

                $class = $tag->getAttribute('class');
                $url = $tag->getAttribute('url');
				
				$allowedAttributes = [];
				foreach($allowedAttributeNames as $i => $attributeName) {
					if ($tag->hasAttribute($attributeName)) {
						$attribute = $tag->getAttribute($attributeName);
						$allowedAttributes[] = [$attributeName, $attribute];
					} 
				}
				
                $attributes = $tag->attributes;
                while ($attributes->length) {
                    $tag->removeAttribute($attributes->item(0)->name);
                }

                if ( $preClass !== '' && $class !== '' ) {
                    $tag->setAttribute('class', $preClass . ' ' . $class);
                } elseif ( $preClass !== '' ) {
                    $tag->setAttribute('class', $preClass);
                } elseif ( $class !== '' ) {
                    $tag->setAttribute('class', $class);
                }
				
				foreach($allowedAttributes as $i) {
					$tag->setAttribute($i[0], $i[1]);
				}

                switch ($htmlTag) {
                    case 'ol':
                        $preContent = $dom->createComment(self::buildCommentString($namespace, $class, $allowedAttributes, '"ordered":true'));
						$tag->parentNode->insertBefore($preContent, $tag); //insert <!-- wp: hidden tag -->
                        break;

                    case 'oembed':
                        $oem = self::cacheEmbedObject($url);
                        self::$oembeded[] = array('url'=>$url,'data'=>$oem);
                        $addProviderClass = (strtolower($oem->provider_name) == 'youtube') ? (($class !=='' ) ? $class. ' wp-embed-aspect-16-9 wp-has-aspect-ratio' : 'wp-embed-aspect-16-9 wp-has-aspect-ratio')  : $class;

                        //note: add additional figure tag
                        //Youtube <figure class="wp-block-embed-youtube wp-block-embed is-type-video is-provider-youtube wp-embed-aspect-16-9 wp-has-aspect-ratio">
                        //Twitter <figure class="wp-block-embed-twitter wp-block-embed is-type-rich is-provider-twitter">
                        //Instagram <figure class="wp-block-embed-instagram wp-block-embed is-type-rich is-provider-instagram">
                        
                        $uniqid = 'ghost'.uniqid();
                        $uniqids[] = '<'.$uniqid.'>';
                        $uniqids[] = '</'.$uniqid.'>';
                        $ghost = $dom->createElement($uniqid);

                        $div = $dom->createElement('div');
                        $div->setAttribute('class','wp-block-embed__wrapper');
                        $div->appendChild ( $dom->createTextNode(PHP_EOL.$url.PHP_EOL) );

                        $fig = $dom->createElement('figure');
                        $fig->setAttribute('class','wp-block-embed-'.strtolower($oem->provider_name).' wp-block-embed is-type-'.$oem->type.' is-provider-'.strtolower($oem->provider_name).''.( ($addProviderClass!=='') ? ' '.$addProviderClass : '') );
                        $fig->appendChild( $div );

                        $ghost->appendChild( $dom->createComment(' wp:' . $namespace.'/'.strtolower($oem->provider_name). ' {"url":"'.$url.'","type":"'.$oem->type.'","providerNameSlug":"'.strtolower($oem->provider_name).'", "className":' .( ($addProviderClass!=='') ? '"' . $addProviderClass . '"' : '""' ). '} ') );
                        $ghost->appendChild( $fig );
                        $ghost->appendChild( $dom->createComment(' /wp:' . $namespace.'/'.strtolower($oem->provider_name).' ') );

                        $tag->parentNode->replaceChild($ghost, $tag);
                        break;

                    default:
                        $preContent = $dom->createComment(self::buildCommentString($namespace, $class, $allowedAttributes));						
                        $tag->parentNode->insertBefore($preContent, $tag); //insert <!-- wp: hidden tag -->
                        break;

                }

                //note: additional insert needed Gutenberg Block Tags with moved Class
                if ( $htmlTag === 'table' ) {
                    $tableClone = $tag->cloneNode(true); //full Node
                    $tableClone->removeAttribute('class');
                    $elem = $dom->createElement('figure');
                    $elem->setAttribute('class','wp-block-table'.( ($class!=='') ? ' '.$class : ''));
                    $elem->appendChild($tableClone);
                    $tag->parentNode->replaceChild($elem, $tag);
                }
 
            }
 
            $content = $dom->saveHTML();
            $content = str_replace($uniqids,'', $content);

            //Debug::log($content,'RETURNED BY CONVERTHTMLTAG');
            return $content;
        }
    }
	
	/**
     * builds comment string for a tag
     *
     * @param string $namespace
	 * @param string $class
	 * @param string[] $allowedAttributes
     * @param string $customAttribute
	 * @return string
     */
    private static function buildCommentString($namespace = '', $class = '', $allowedAttributes = [], $customAttribute = ''){
        $preContent = ' wp:' . $namespace;
		$classOrCustomAttribute =  $class !== '' || $customAttribute !== '';
		if ($classOrCustomAttribute || count($allowedAttributes)) {
			$preContent .= ' {' . $customAttribute . ( ($class!=='') ? ', "className": "' . $class . '"' : '' ) ;		
			$preContent .= $classOrCustomAttribute ? ', ' : '';
			$preContent .= implode(', ', array_map(function($el){ return  "\"$el[0]\":\"$el[1]\""; }, $allowedAttributes));
			$preContent .= '}';
		}
        return $preContent;
    }


    /**
     * getClass from content
     *
     * @param string $string
     * @return void
     */
    private static function getClass($string = ''){
        preg_match('/class="(.+?)"/', $string, $input);
        return isset($input[1]) ? $input[1] : '';
    }

    /**
     * internal WP REST API Request to oembed provider
     *
     * @param [string] $url
     * @return object data
     */
    private static function cacheEmbedObject($url) {
        global $content_width;
        //note: get oembed via Internal REST API CALL
        $request = new \WP_REST_Request('GET', '/oembed/1.0/proxy');
        $request->set_query_params(array('url' => $url,'_locale' => 'user', 'maxwidth' => $content_width));
        Debug::log($request->get_params(),'PARAMS FOR INTERNAL WP REST REQUEST');
        $response = rest_do_request($request); //future: im resonse opbject -> data -> provider_name = 'instagram' und -> type = 'rich'
        $server = rest_get_server();
        $data = $server->response_to_data( $response, false);

        return $data;
    }


    private static function array_copy($arr) {
        $newArray = array();
        foreach($arr as $key => $value) {
            if ( is_array($value) ) {
                $newArray[$key] = self::array_copy($value);
            } else if ( is_object($value) ) {
                 $newArray[$key] = $value; //clone $value;
            } else {
                $newArray[$key] = $value;
            }
        }
        return $newArray;
    }

}