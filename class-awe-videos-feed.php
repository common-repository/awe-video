<?php

  /* 
   The VideosFeed class does not rely upon WordPress global variables. 
   All state is passed through the only public function 'response'.

   Usage (an HTTP response):

   <?php VideosFeed::response(<arguments>); ?>

   $info = array('title' =>, 'description', 'keywords', )

   Large majority of this code is based upon code from PowerPress and 
   from the WordPress core, all of which are also GPLv2 licensed.

  */

class AWE_Videos_Feed {

    private static $out, $items, $info;

    static public function response( $items, $author, $info, $url, $feed_url, $video_feed ) {

        self::$items = $items;

        if ($info['explicit'] == 'on') {
            $explicit = 'yes';
        } else {
            $explicit = 'no';
        }
 
        self::$info = array('title' => $info['title'],
                            'url' => $url,
			    'vfeed' => $video_feed,
                            'feed_url' => $feed_url,
                            'name' => $info['title'], 
                            'author' => $author, 
                            'image' => $info['image'],
                            'charset' => 'UTF-8',
                            'content_type' => feed_content_type('rss-http'), //todo don't use this function here
                            'keywords' => $info['keywords'],
                            'description' => $info['description'],
                            'explicit' => $explicit,
                            'itunes_categories' => $info['categories'],
                            'language' => $info['language'],
                            'update_period' => 'hourly',
                            'update_frequency' => '1',
                            );

        header('Content-Type: ' . self::$info['content_type'] . '; charset=' . self::$info['charset'], true);

        print self::head();

        foreach( $items as $item ) {
            print self::item($item);
        }

        print self::foot();

    }

    static private function foot() {
        $f = '</channel></rss>';
        return $f;
    }

    static private function last_modified() {
        return self::$items[0]->post_modified_gmt;
    }

    static private function head() {

        $h = '<?xml version="1.0" encoding="'.self::$info['charset'].'"?'.'>'.PHP_EOL;

        $h .= '<rss version="2.0"
	                           xmlns:content="http://purl.org/rss/1.0/modules/content/"
	                           xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	                           xmlns:dc="http://purl.org/dc/elements/1.1/"
	                           xmlns:atom="http://www.w3.org/2005/Atom"
	                           xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	                           xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
                               xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd">';

        $h .= '<channel>';
        $h .= '<title>'.self::$info['title'].'</title>';
        $h .= '<atom:link href="'.esc_url(self::$info['feed_url']).'" rel="self" type="application/rss+xml" />';
        $h .= '<link>'.esc_url(self::$info['url']).'</link>';
        $h .= '<description>'.self::$info["description"].'</description>';
        $h .= '<lastBuildDate>'.mysql2date('D, d M Y H:i:s +0000', self::last_modified(), false).'</lastBuildDate>';
        $h .= '<language>'.self::$info['language'].'</language>';
        $h .= '<sy:updatePeriod>'.self::$info['update_period'].'</sy:updatePeriod>';
        $h .= '<sy:updateFrequency>'.self::$info['update_frequency'].'</sy:updateFrequency>';

        $h .= '<itunes:summary>'.  self::format_itunes_value( self::$info['description'], 'summary' ) .'</itunes:summary>'.PHP_EOL;

        $h .= '<itunes:author>' . esc_html(self::$info['author']->display_name) . '</itunes:author>'.PHP_EOL;

        $h .= '<itunes:explicit>' . self::$info['explicit'] . '</itunes:explicit>'.PHP_EOL;
        
        $h .= '<itunes:image href="' . self::$info['image'][0]. '" />'.PHP_EOL;

        $h .= '<itunes:owner>'.PHP_EOL;
        $h .= '<itunes:name>' . esc_html(self::$info['author']->display_name) . '</itunes:name>'.PHP_EOL;
        $h .= '<itunes:email>' . esc_html(self::$info['author']->user_email) . '</itunes:email>'.PHP_EOL;
        $h .= '</itunes:owner>'.PHP_EOL;
        $h .= '<managingEditor>'. esc_html(self::$info['author']->user_email .' ('. self::$info['author']->display_name.')') .'</managingEditor>'.PHP_EOL;

        if( !empty(self::$info['subtitle']) ){
            $h .= '<itunes:subtitle>' . self::format_itunes_value(self::$info['itunes_subtitle'], 'subtitle') . '</itunes:subtitle>'.PHP_EOL;
        } else {
            $h .= '<itunes:subtitle>'.  self::format_itunes_value( self::$info['description'], 'subtitle') .'</itunes:subtitle>'.PHP_EOL;
        }
	
        $h .= '<itunes:keywords>' . self::format_itunes_value(self::$info['keywords'], 'keywords') . '</itunes:keywords>'.PHP_EOL;
		
        $h .= '<image>' .PHP_EOL;
        $h .= '<title>' . esc_html( self::$info['name'] ) . '</title>'.PHP_EOL;
        $h .= '<url>' . esc_html( str_replace(' ', '+', self::$info['image'][1])) . '</url>'.PHP_EOL;
        $h .= '<link>'. self::$info['url'] . '</link>' . PHP_EOL;
        $h .= '</image>' . PHP_EOL;

        //$h .= self::feed_itunes_categories();
        
        return $h;

    }

    static private function feed_itunes_categories() {
        $c = '';

        $categories = self::itunes_categories();

        $cat1 = self::$info['itunes_categories'][0];
        $cat2 = self::$info['itunes_categories'][1];
        $cat3 = self::$info['itunes_categories'][2];
        
        if( $cat1[0] ){
            $cat_description = $categories[$cat1[0].'-00'];
            $sub_cat_description = $categories[$cat1[0].'-'.$cat1[1]];
            if( $cat1[0] != $cat2[0] && $cat1[1] == '00' ){
                $c .= '<itunes:category text="'. esc_html($cat_description) .'" />'.PHP_EOL;
            } else {
                $c .= '<itunes:category text="'. esc_html($cat_description) .'">'.PHP_EOL;
                if( $cat1[1] != '00' )
                    $c .= '<itunes:category text="'. esc_html($sub_cat_description) .'" />'.PHP_EOL;
                
                // End this category set
                if( $cat1[0] != $cat2[0] )
                    $c .= '</itunes:category>'.PHP_EOL;
            }
        }
 
        if( $cat2[0] ){
            $cat_description = $categories[$cat2[0].'-00'];
            $sub_cat_description = $categories[$cat2[0].'-'.$cat2[1]];
            
            // It's a continuation of the last category...
            if( $cat1[0] == $cat2[0] ){
                if( $cat2[1] != '00' )
                    $c .= '<itunes:category text="'. esc_html($sub_cat_description) .'" />'.PHP_EOL;
                
                // End this category set
                if( $cat2[0] != $cat3[0] )
                    $c .= '</itunes:category>'.PHP_EOL;
            } else {
                // This is not a continuation, lets start a new category set
                if( $cat2[0] != $cat3[0] && $cat2[1] == '00' ){
                    $c .= '<itunes:category text="'. esc_html($cat_description) .'" />'.PHP_EOL;
                } else {
                    // We have nested values
                    if( $cat1[0] != $cat2[0] ) // Start a new category set
                        $c .= '<itunes:category text="'. esc_html($cat_description) .'">'.PHP_EOL;
                    if( $cat2[1] != '00' )
                        $c .= '<itunes:category text="'. esc_html($sub_cat_description) .'" />'.PHP_EOL;
                    if( $cat2[0] != $cat3[0] ) // End this category set
                        $c .= '</itunes:category>'.PHP_EOL;
                }
            }
        }
 
        if( $cat3[0] ){
            $cat_description = $categories[$cat3[0].'-00'];
            $sub_cat_description = $categories[$cat3[0].'-'.$cat3[1]];
	 
            // It's a continuation of the last category...
            if( $cat2[0] == $cat3[0] ){
                if( $cat3[1] != '00' )
                    $c .= '<itunes:category text="'. esc_html($sub_cat_description) .'" />'.PHP_EOL;
			
                // End this category set
                echo '</itunes:category>'.PHP_EOL;
            } else {
                // This is not a continuation, lets start a new category set
                if( $cat2[0] != $cat3[0] && $cat3[1] == '00' ){
                    $c .= '<itunes:category text="'. esc_html($cat_description) .'" />'.PHP_EOL;
                } else {
                    // We have nested values
                    if( $cat2[0] != $cat3[0] ) // Start a new category set
                        $c .= '<itunes:category text="'. esc_html($cat_description) .'">'.PHP_EOL;
                    if( $cat3[1] != '00' )
                        $c .= '<itunes:category text="'. esc_html($sub_cat_description) .'" />'.PHP_EOL;
                    // End this category set
                    $c .= '</itunes:category>'.PHP_EOL;
                }
            }
        }

        return $c;
    }

    static private function item_title($post) {
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

    static private function item_permalink( $post ) {
        return esc_url(apply_filters('the_permalink_rss', get_permalink($post->ID)));
    }

    static private function item_comments_link( $post ) {
        return esc_url(get_permalink($post->ID) . '#comments');
    }

    static private function item_time( $post ) {
        return get_post_time('Y-m-d H:i:s', true, $post->ID);
    }

    static private function item_author( $post ) {
    	$license = get_post_meta( $post->ID, 'license', true );
	if ( is_array( $license ) ) {
            return esc_html( $license['author'] );
	} else {
	    return '';
	}
    }

    static private function categories( $post ) {
        $categories = get_the_category($post->ID);
        $tags = get_the_tags($post->ID);
        $list = '';
        $cat_names = array();

        $filter = 'rss';

        if ( !empty( $categories ) ) {
            foreach ( (array) $categories as $category ) {
                $cat_names[] = sanitize_term_field('name', $category->name, $category->term_id, 'category', $filter);
            }
        }

        if ( !empty( $tags ) ) foreach ( (array) $tags as $tag ) {
            $cat_names[] = sanitize_term_field( 'name', $tag->name, $tag->term_id, 'post_tag', $filter );
        }

        $cat_names = array_unique( $cat_names );

        foreach ( $cat_names as $cat_name ) {
                $list .= "<category><![CDATA[" . @html_entity_decode( $cat_name, ENT_COMPAT, get_option('blog_charset') ) . "]]></category>\n";
        }

        return apply_filters( 'the_category_rss', $list, 'rss2' );
    }

    static private function item_guid($post) {
        return esc_url(apply_filters('get_the_guid', $post->guid));
    }

    static private function item_description($post, $type='rss2') {
	$d = $post->post_excerpt;
        $d = str_replace(']]>', ']]&gt;', apply_filters('video_description', $d, $post));
        return apply_filters('video_description_rss', $d, $post, $type);
    }

    static private function item_content($post, $type='rss2') {
		$videos = AWE_Videos::retrieve( $post->ID );

		$video_self_hosted = false;

		$c = '';

		$c .= $post->post_content;
        $c = str_replace(']]>', ']]&gt;', apply_filters('video_content', $c, $post));
        return apply_filters('video_content_feed', $c, $post, $type);
    }

    static private function item_comments_feed_link($post, $type='rss2'){
        return esc_url(get_post_comments_feed_link($post->ID, $type));
    }

    static private function item_comments_count($post){
        return get_comments_number($post->ID);
    }

    static private function item_duration($post){
//        $media_meta = get_post_meta($post->ID, 'media_meta', true);
//        if( $media_meta['playtime'] && preg_match('/^(\d{1,2}:){0,2}\d{1,2}$/i', ltrim($media_meta['playtime'], '0:'))){
//            return ltrim($media_meta['playtime'], '0:');
//        } else {
//            return false;
//        }
    }
    
    static private function item($post) {

        if(post_password_required($post)) return;

        $o = '<item>';
		$o .= '<title>'.self::item_title($post).'</title>';
		$o .= '<link>'.self::item_permalink($post).'</link>';
		$o .= '<comments>'.self::item_comments_link($post).'</comments>';
		$o .= '<pubDate>'.mysql2date('D, d M Y H:i:s +0000', self::item_time($post), false).'</pubDate>';
		$o .= '<dc:creator>'.self::item_author($post).'</dc:creator>';
		$o .= self::categories($post);

		$o .= '<guid isPermaLink="false">'.self::item_guid($post).'</guid>';

        $o .= '<description><![CDATA['.self::item_description($post, 'rss2').']]></description>';

        $o .= '<content:encoded><![CDATA['.self::item_content($post, 'rss2').']]></content:encoded>';

		$o .= '<wfw:commentRss>'.self::item_comments_feed_link($post).'</wfw:commentRss>';
		$o .= '<slash:comments>'.self::item_comments_count($post).'</slash:comments>';

        $o .= self::enclosure($post);

//         // Get the post tags
//         if( !$keywords ){
//             // Lets try to use the page tags...
//             $tagobject = wp_get_post_tags( $post->ID );
//             if( count($tagobject) ){
//                 $tags = array();
//                 for($c = 0; $c < count($tagobject) && $c < 12; $c++) // iTunes only accepts up to 12 keywords
//                     $tags[] = $tagobject[$c]->name;
			
//                 if( count($tags) > 0 )
//                     $keywords = implode(",", $tags);
//             }
//         }
	
//         if( $keywords ){
//             $o .= "\t\t<itunes:keywords>" . self::format_itunes_value($keywords, 'keywords') . '</itunes:keywords>'.PHP_EOL;
//         }
	
//         if( $subtitle )
//             echo "\t\t<itunes:subtitle>". self::format_itunes_value($subtitle, 'subtitle') .'</itunes:subtitle>'.PHP_EOL;
//         else if( $excerpt_no_html )
//             echo "\t\t<itunes:subtitle>". self::format_itunes_value($excerpt_no_html, 'subtitle') .'</itunes:subtitle>'.PHP_EOL;
//         else	
//             echo "\t\t<itunes:subtitle>". self::format_itunes_value($content_no_html, 'subtitle') .'</itunes:subtitle>'.PHP_EOL;
	
        $o .= "<itunes:summary>". self::format_itunes_value(self::item_description($post), 'summary') .'</itunes:summary>'.PHP_EOL;
            
        $o .= "<itunes:author>" . self::item_author($post). '</itunes:author>'.PHP_EOL;

        $o .= "<itunes:explicit>" . self::$info['explicit'] . '</itunes:explicit>'.PHP_EOL;

        $duration = self::item_duration($post);
        if ($duration){
            $o .= "<itunes:duration>".$duration.'</itunes:duration>'.PHP_EOL;
        }

        $o .= '</item>';

        return $o;

    }

    static private function enclosure($post) {

        $videos = AWE_Videos::retrieve($post->ID);

	$e = '';

	if ( self::$info['vfeed'] == 'webm' ) {
		$e = '<enclosure url="'.$videos['webm'].'" length="'.''.'" type="video/webm"></enclosure>';
	}

	if ( self::$info['vfeed'] == 'mp4' ) { 
		$e = '<enclosure url="'.$videos['mp4'].'" length="'.''.'" type="video/mp4"></enclosure>';
	}

        return $e;
    }

    function format_itunes_value( $value, $tag ) {
        // Check if the string is UTF-8
        if( !defined('DB_CHARSET') || DB_CHARSET != 'utf8' ){
            // If it is not, convert to UTF-8 then decode it...
            $value = utf8_encode($value); 
        }

        $value = preg_replace("/\[(kml_(flash|swf)embed|audio\:)\b(.*?)(?:(\/))?(\]|$)/isu", '', $value);

        $value = @html_entity_decode($value, ENT_COMPAT, 'UTF-8'); 

        $value = preg_replace('/&amp;/ui' , '&', $value); 

        return esc_html( self::trim_itunes_value($value, $tag) );
    }

    function trim_itunes_value($value, $tag = 'summary') {
        // First we need to trim the string
        $value = trim($value); 
        $length = (function_exists('mb_strlen')?mb_strlen($value):strlen($value) );
        $trim_at = false;
        $remove_new_lines = false;

        switch($tag){
            case 'summary': {
                // 4000 character limit
                if( $length > 4000 )
                    $trim_at = 4000;
            }; break;
            case 'subtitle':
            case 'keywords':
            case 'author':
            case 'name':
            default: {
                $remove_new_lines = true;
                // 255 character limit
                if( $length > 255 )
                    $trim_at = 255;
            };
        }

        if($trim_at){
    
            // Start trimming
            $value = (function_exists('mb_substr')?mb_substr($value, 0, $trim_at):substr($value, 0, $trim_at) );
            $clean_break = false;
    
            // Pattern modifiers: case (i)nsensitive, entire (s)tring and (u)nicode
            if(preg_match('/(.*[,\n.\?!])[^,\n.\?!]/isu', $value, $matches)) {
                if( isset( $matches[1]) ) {
                    $detected_eof_pos = (function_exists('mb_strlen') ? mb_strlen($matches[1]) : strlen($matches[1]) );
    
                    // Look back at most 50 characters...
                    if( $detected_eof_pos > 3950 || ($detected_eof_pos > 205 && $detected_eof_pos < 255 )){
                        $value = $matches[1];
                        $clean_break = true;
                    }
    
                    // Otherwise we want to continue with the same value we started with...
                }
            }

            // Subtitle we want to add a ... at the end
            if($clean_break == false && $tag = 'subtitle') {
                $value = (function_exists('mb_substr') ? mb_substr($value, 0, 252) : substr($value, 0, 252) ) . '...';
            }
        }

        if($remove_new_lines){
            $value = str_replace( 
                                 array("\r\n\r\n", "\n", "\r", "\t","-  "), 
                                 array(' - ',' ', '', '  ', ''), 
                                 $value );
        }
        
        return $value;
    }

    function itunes_categories($prefix_sub_categories=false) {
        $temp = array();
        $temp['01-00'] = 'Arts';
            $temp['01-01'] = 'Design';
            $temp['01-02'] = 'Fashion & Beauty';
            $temp['01-03'] = 'Food';
            $temp['01-04'] = 'Literature';
            $temp['01-05'] = 'Performing Arts';
            $temp['01-06'] = 'Visual Arts';

        $temp['02-00'] = 'Business';
            $temp['02-01'] = 'Business News';
            $temp['02-02'] = 'Careers';
            $temp['02-03'] = 'Investing';
            $temp['02-04'] = 'Management & Marketing';
            $temp['02-05'] = 'Shopping';

        $temp['03-00'] = 'Comedy';

        $temp['04-00'] = 'Education';
            $temp['04-01'] = 'Education Technology';
            $temp['04-02'] = 'Higher Education';
            $temp['04-03'] = 'K-12';
            $temp['04-04'] = 'Language Courses';
            $temp['04-05'] = 'Training';

        $temp['05-00'] = 'Games & Hobbies';
            $temp['05-01'] = 'Automotive';
            $temp['05-02'] = 'Aviation';
            $temp['05-03'] = 'Hobbies';
            $temp['05-04'] = 'Other Games';
            $temp['05-05'] = 'Video Games';

        $temp['06-00'] = 'Government & Organizations';
            $temp['06-01'] = 'Local';
            $temp['06-02'] = 'National';
            $temp['06-03'] = 'Non-Profit';
            $temp['06-04'] = 'Regional';

        $temp['07-00'] = 'Health';
            $temp['07-01'] = 'Alternative Health';
            $temp['07-02'] = 'Fitness & Nutrition';
            $temp['07-03'] = 'Self-Help';
            $temp['07-04'] = 'Sexuality';

        $temp['08-00'] = 'Kids & Family';

        $temp['09-00'] = 'Music';

        $temp['10-00'] = 'News & Politics';

        $temp['11-00'] = 'Religion & Spirituality';
            $temp['11-01'] = 'Buddhism';
            $temp['11-02'] = 'Christianity';
            $temp['11-03'] = 'Hinduism';
            $temp['11-04'] = 'Islam';
            $temp['11-05'] = 'Judaism';
            $temp['11-06'] = 'Other';
            $temp['11-07'] = 'Spirituality';

        $temp['12-00'] = 'Science & Medicine';
            $temp['12-01'] = 'Medicine';
            $temp['12-02'] = 'Natural Sciences';
            $temp['12-03'] = 'Social Sciences';

        $temp['13-00'] = 'Society & Culture';
            $temp['13-01'] = 'History';
            $temp['13-02'] = 'Personal Journals';
            $temp['13-03'] = 'Philosophy';
            $temp['13-04'] = 'Places & Travel';

        $temp['14-00'] = 'Sports & Recreation';
            $temp['14-01'] = 'Amateur';
            $temp['14-02'] = 'College & High School';
            $temp['14-03'] = 'Outdoor';
            $temp['14-04'] = 'Professional';

        $temp['15-00'] = 'Technology';
            $temp['15-01'] = 'Gadgets';
            $temp['15-02'] = 'Tech News';
            $temp['15-03'] = 'Podcasting';
            $temp['15-04'] = 'Software How-To';

        $temp['16-00'] = 'TV & Film';

        if( $prefix_sub_categories ) {
            while( list($key,$val) = each($temp) ){
                $parts = explode('-', $key);
                $cat = $parts[0];
                $subcat = $parts[1];

                if( $subcat != '00' ) {
                    $temp[$key] = $temp[$cat.'-00'].' > '.$val;
                }
            }
            reset($temp);
        }

        return $temp;
    }

}

?>
