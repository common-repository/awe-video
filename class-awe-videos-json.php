<?php

  /* 
   The VideosJSON class does not rely upon WordPress global variables. 
   All state is passed through the only public function 'response'.

   Usage (an HTTP response):

   <?php VideosJSON::response(<arguments>); ?>

   $info = array('title' =>, 'description', 'keywords', )

  */

class CellarDoor_Videos_JSON {

    private static $out, $items, $info;

    static public function response($items, $author, $info, $url, $feed_url)
    {

        self::$items = $items;

        if ($info['explicit'] == 'on'){
            $explicit = 'yes';
        } else {
            $explicit = 'no';
        }
 
        self::$info = array('title' => $info['title'],
                            'url' => $url,
                            'feed_url' => $feed_url,
                            'name' => $info['title'], 
                            'author' => $author, 
                            'image' => $info['image'],
                            'charset' => 'UTF-8',
                            'keywords' => $info['keywords'],
                            'copyright' => str_replace(array('&copy;', '(c)', '(C)', chr(194) . chr(169), chr(169) ), '&#xA9;', $info['copyright']),
                            'description' => $info['description'],
                            'explicit' => $explicit,
                            'language' => $info['language'],
                            'update_period' => 'hourly',
                            'update_frequency' => '1',
                            );

        header('Content-Type: ' . 'application/json');// . '; charset=' . self::$info['charset'], true);
        //header('Content-Type: ' . 'text/javascript');// . '; charset=' . self::$info['charset'], true);

        print self::head();
        print '"items":[';
        $t = count($items);
        $i = 0;
        foreach($items as $item){
            print self::item($item);
            $i++;
            if ($i  < $t) { print ','; };
        }
        if ($i < $t){
            print '],';
        } else {
            print ']';
        }

        print self::foot();

    }

    static private function foot()
    {
        $f = '}}';
        return $f;
    }

    static private function last_modified(){
        return self::$items[0]->post_modified_gmt;
    }

    static private function head()
    {

        $h = '{';

        $h .= '"channel":{';
        $h .= '"title":"'.self::$info['title'].'",';
        $h .= '"feed_url":"'.self::$info['feed_url'].'",';
        $h .= '"link":"'.self::$info['url'].'",';
        $h .= '"description":"'.self::$info["description"].'",';
        $h .= '"modified":"'.mysql2date('D, d M Y H:i:s +0000', self::last_modified(), false).'",';
        $h .= '"language":"'.self::$info['language'].'",';
        $h .= '"update":"'.self::$info['update_period'].'",';
        $h .= '"frequency":"'.self::$info['update_frequency'].'",';
        $h .= '"author":"'.esc_html(self::$info['author']->display_name).'",';
        $h .= '"explicit":"'.self::$info['explicit'].'",';
        $h .= '"image":"' .self::$info['image'][0]. '",';
        $h .= '"email":"' .esc_html(self::$info['author']->display_name) . '",';
        $h .= '"editor":"'.esc_html(self::$info['author']->user_email .' ('. self::$info['author']->display_name.')') .'",';
        $h .= '"copyright":"'. esc_html(self::$info['copyright']) . '",';
	
        if( !empty(self::$info['subtitle']) ){
            $h .= '"subtitle"'.esc_html(self::$info['itunes_subtitle']).'",';
        }
	
        $h .= '"keywords":"'.esc_html(self::$info['keywords']).'",';
		
        return $h;

    }

    static private function item_title($post)
    {
        $title = isset($post->post_title) ? $post->post_title : '';
        $id = isset($post->ID) ? $post->ID : (int) $id;

        if ( !is_admin() ) {
            if ( !empty($post->post_password) ) {
                $protected_title_format = apply_filters('protected_title_format', __('Protected: %s'));
                $title = sprintf($protected_title_format, $title);
            } else if ( isset($post->post_status) && 'private' == $post->post_status ) {
                $private_title_format = apply_filters('private_title_format', __('Private: %s'));
                $title = sprintf($private_title_format, $title);
            }
        }

        $title = apply_filters('the_title', $title, $id);

        return apply_filters('the_title_rss', $title);
    }

    static private function item_permalink($post)
    {
        return esc_url(apply_filters('the_permalink_rss', get_permalink($post->ID)));
    }

    static private function item_comments_link($post)
    {
        return esc_url(get_permalink($post->ID) . '#comments');
    }

    static private function item_time($post)
    {
        return get_post_time('Y-m-d H:i:s', true, $post->ID);
    }

    static private function item_author($post)
    {
        $authordata = self::$info['author'];

        return esc_html(apply_filters('the_author', is_object($authordata) ? $authordata->display_name : null));
    }

    static private function item_author_permalink($post)
    {
        $authordata = self::$info['author'];

        return esc_html(apply_filters('the_author', is_object($authordata) ? '/'.$authordata->user_nicename.'/' : null));
    }

    static private function categories($post)
    {
        $categories = get_the_category($post->ID);
        $tags = get_the_tags($post->ID);
        $list = '';
        $cat_names = array();

        $filter = 'rss';

        if ( !empty($categories) ) {
            foreach ( (array) $categories as $category ) {
                $cat_names[] = sanitize_term_field('name', $category->name, $category->term_id, 'category', $filter);
            }
        }

        if ( !empty($tags) ) foreach ( (array) $tags as $tag ) {
            $cat_names[] = sanitize_term_field('name', $tag->name, $tag->term_id, 'post_tag', $filter);
        }

        $cat_names = array_unique($cat_names);

        foreach ( $cat_names as $cat_name ) {
                $list .= "<category><![CDATA[" . @html_entity_decode( $cat_name, ENT_COMPAT, get_option('blog_charset') ) . "]]></category>\n";
        }

        return apply_filters('the_category_rss', $list, 'rss2');
    }

    static private function item_guid($post)
    {
        return esc_url(apply_filters('get_the_guid', $post->guid));
    }

    static private function item_description($post, $type='rss2')
    {
        $d = get_post_meta($post->ID, 'video_description', true);
        $d = json_encode(apply_filters('video_description', $d, $post));
        return apply_filters('video_description_rss', $d, $post, $type);
    }

    static private function item_content($post, $type='rss2')
    {
        $c = '<ul>';
        $meta = get_post_custom($post->ID);
        if($meta['video_attribution']) $c .= '<li>Attribution: '.$meta['video_attribution'][0].'</li>';
        if($meta['video_copyright']) $c .= '<li>Copyright: '.$meta['video_copyright'][0].'</li>';
        $c .= '</ul>';

        $c = json_encode(apply_filters('video_content', $c, $post));
        return apply_filters('video_content_feed', $c, $post, $type);
    }

    static private function item_comments_feed_link($post, $type='rss2')
    {
        return esc_url(get_post_comments_feed_link($post->ID, $type));
    }

    static private function item_comments_count($post)
    {
        return get_comments_number($post->ID);
    }

    static private function item_duration($post)
    {
        $media_id3 = get_post_meta($post->ID, 'media_id3', true);
        if( $media_id3['playtime_string'] && preg_match('/^(\d{1,2}:){0,2}\d{1,2}$/i', ltrim($media_id3['playtime_string'], '0:'))){
            return ltrim($media_id3['playtime_string'], '0:');
        } else {
            return false;
        }
    }

    static private function item_label_id($post)
    {
        return get_post_meta($post->ID, 'video_label_id', true);
    }

    static private function item_images($post)
    {

        global $_wp_additional_image_sizes;

        $images = '{';
        $c = 0;
        $sizes = get_intermediate_image_sizes();
        $l = count($sizes);

        foreach ($sizes as $s){
            if ( isset($_wp_additional_image_sizes[$s]['width'])) {
                $width = intval( $_wp_additional_image_sizes[$s]['width']);
            } else {
                $width = get_option("{$s}_size_w");
            }

            if ( isset( $_wp_additional_image_sizes[$s]['height'])) {
                $height = intval($_wp_additional_image_sizes[$s]['height']);
            } else {
                $height = get_option("{$s}_size_h");
            }

            if ( isset( $_wp_additional_image_sizes[$s]['crop'])) {
                $crop = intval( $_wp_additional_image_sizes[$s]['crop']);
            } else {
                $crop = get_option("{$s}_crop");
            }

            $src = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), $s);

            $c = $c + 1;

            if($src){
                $images .= '"'.$s.'" : ["'.$src[0].'",'.$width.','.$height.']';
                if ($c < $l) { $images .= ','; }
            }

        }

        $images .= '}';

        if ($images == '{}') { $images = null; }

        return $images;
    }
    
    static private function item($post)
    {

        if(post_password_required($post)) return;

	$o = '{';
	$o .= '"id":"'.$post->ID.'",';
	$o .= '"release_id":"'.self::item_label_id($post).'",';
	$o .= '"title":"'.self::item_title($post).'",';
	$o .= '"link":"'.self::item_permalink($post).'",';
	$o .= '"comments":"'.self::item_comments_link($post).'",';
	$o .= '"pubdate":"'.mysql2date('D, d M Y H:i:s +0000', self::item_time($post), false).'",';
	$o .= '"author":"'.self::item_author($post).'",';
	$o .= '"author_permalink":"'.self::item_author_permalink($post).'",';
	$o .= self::categories($post);

	$o .= '"guid":"'.self::item_guid($post).'",';

        $images = self::item_images($post);
        if($images){
            $o .= '"images":'.$images.',';
        }

        $o .= '"description":'.self::item_description($post, 'rss2').',';

        $o .= '"content":'.self::item_content($post, 'rss2').',';

	$o .= '"comment-feed":"'.self::item_comments_feed_link($post).'",';
	$o .= '"comments":"'.self::item_comments_count($post).'",';

        $o .= self::enclosure($post);
            
        $o .= '"explicit":"'.self::$info['explicit'].'",';

        $duration = self::item_duration($post);
        $o .= '"duration":"'.$duration.'"';

        $o .= '}'; // todo make sure the last doesn't have a comma

        return $o;

    }

    static private function enclosure($post){

        $video = Videos::retrieve($post->ID);

        $e = '"enclosure":{"url":"'.trim($video['uri']).'", "type":"'.trim('video/mp4').'"},';

        return $e;
    }

}

?>
