<?php 

class AWE_Videos {

    static private $servers;

	static public $countries = array(
		'ar' => 'Argentina',
		'au' => 'Australia',
		'at' => 'Austria',
		'be' => 'Belgium',
		'br' => 'Brazil',
		'bg' => 'Bulgaria',
		'ca' => 'Canada', 
		'cl' => 'Chile', 
		'cn' => 'China Mainland',
		'co' => 'Colombia',
		'cr' => 'Costa Rica', 
		'hr' => 'Croatia', 
		'cz' => 'Czech Republic',
		'dk' => 'Denmark',
		'ec' => 'Ecuador', 
		'ee' => 'Estonia', 
		'fi' => 'Finland', 
		'gr' => 'Greece',
		'gt' => 'Guatemala',
		'hk' => 'Hong Kong',
		'hu' => 'Hungary',
		'in' => 'India', 
		'ie' => 'Ireland',
		'il' => 'Israel',
		'it' => 'Italy', 
		'jp' => 'Japan',
		'lu' => 'Luxembourg',
		'mk' => 'Macedonia', 
		'my' => 'Malaysia',
		'mt' => 'Malta',
		'mx' => 'Mexico',
		'nl' => 'Netherlands', 
		'nz' => 'New Zealand',
		'no' => 'Norway', 
		'pe' => 'Peru', 
		'ph' => 'Philippines',
		'pl' => 'Poland',
		'pt' => 'Portugal',
		'pr' => 'Puerto Rico',
		'ro' => 'Romania', 
		'rs' => 'Serbia',
		'sg' => 'Singapore',
		'si' => 'Slovenia', 
		'za' => 'South Africa',
		'kr' => 'South Korea', 
		'es' => 'Spain', 
		'se' => 'Sweden', 
		'ch' => 'Switzerland',
		'tw' => 'Taiwan', 
		'th' => 'Thailand',
		'uk' => 'UK: England &amp; Wales',
		'scotland' => 'UK: Scotland',
		'us' => 'United States',
		'vn' => 'Vietnam'
	);


    public static function add_roles() {
        global $wp_roles;
		$roles = array('administrator', 'editor', 'author');
		foreach ( $roles as $role ) {
			$wp_roles->add_cap($role, 'publish_videos');
			$wp_roles->add_cap($role, 'edit_videos');
			$wp_roles->add_cap($role, 'edit_others_videos');
			$wp_roles->add_cap($role, 'delete_videos');
			$wp_roles->add_cap($role, 'delete_others_videos');
			$wp_roles->add_cap($role, 'read_private_videos');
			$wp_roles->add_cap($role, 'edit_video');
			$wp_roles->add_cap($role, 'delete_video');
			$wp_roles->add_cap($role, 'read_video');
		}
    }
	
    public static function initialize() {

		$uploads = wp_upload_dir();

        self::$servers = array('local' => array(
			'base_path' => $uploads['basedir'].'/video/',
			'base_url' => $uploads['baseurl'].'/video/',
		));

		$labels = array(
			'name' => __('Video'),
			'singular_label' => __('Video'),
			'add_new' => __('Add New Video'),
			'add_new_item' => __('Add New Video'),
			'edit_item' => __('Edit Video'),
			'new_item' => __('New Video'),
			'view_item' => __('View Video'),
			'search_items' => __('Search Videos'),
			'not_found' => __('No videos found'),
			'not_found_in_trash' => ('No video found in Trash'),
			'parent_item_colon' => ''
		);

        register_post_type('video', array(
			'labels' => $labels,
			'singular_label' => __('Video'),
			'public' => true,
			'show_ui' => true,
			'_builtin' => false,
			'_edit_link' => 'post.php?post=%d',
			'capability_type' => 'post',
			'capabilities' => array(
				'publish_posts' => 'publish_videos',
				'edit_posts' => 'edit_videos',
				'edit_others_posts' => 'edit_others_videos',
				'delete_posts' => 'delete_videos',
				'delete_others_posts' => 'delete_others_videos',
				'read_private_posts' => 'read_private_videos',
				'edit_post' => 'edit_video',
				'delete_post' => 'delete_video',
				'read_post' => 'read_video',
			),
			'hierarchical' => false,
			'rewrite' => array("slug" => "video"),
			'menu_icon' => get_bloginfo('url') . '/wp-admin/images/media-button-video.gif',
			'supports' => array('title',
				'author', 
				'editor',
				'excerpt',
				/*'custom-fields',*/ 
				'description', 
				'thumbnail', 
				/*'comments',*/
			),
			'taxonomies' => array(),
		));

		$video_topic_labels = array(
					 'name' => _x('Video Topics', 'taxonomy general name'),
					 'singular_name' => _x('Video Topic', 'taxonomy singular name'),
					 'search_items' => __('Search Video Topics'),
					 'popular_items' => __('Popular Video Topics'),
					 'all_items' => __('All Video Topics'),
					 'edit_item' => __('Edit Video Topic'),
					 'update_item' => __('Update Video Topic'),
					 'add_new_item' => __('Add New Video Topic'),
					 'new_item_name' => __('New Video Topic'),
					 'add_or_remove_items' => __('Add or remove video topics'),
					 'choose_from_most_used' => __('Choose from most used video topics'),
					 'menu_name' => __('Topics')
					 );

		register_taxonomy('topic', 'video', 
			array( 
				'hierarchical' => false, 
				'labels' => $video_topic_labels,
				'public' => true, 
				'show_ui' => true,
				'query_var' => 'topic',
				'rewrite' => array('slug' => 'topic') 
			)
		);

		add_action( 'generate_rewrite_rules', 'AWE_Videos::rewrite_rules' );

		add_action('admin_init', 'AWE_Videos::add_roles', 10, 0);
		add_filter('map_meta_cap', 'AWE_Videos::map_meta_cap', 10, 4);
		add_action('admin_init', 'AWE_Videos::admin_init');
		add_action('admin_menu', 'AWE_Videos::remove_author_box');
		add_action('admin_menu', 'AWE_Videos::add_menus');
		add_action('post_submitbox_misc_actions', 'AWE_Videos::add_author_box');
		add_action('save_post', 'AWE_Videos::save_post');
		add_action('admin_footer', create_function('', "print  '<script type=\"text/javascript\">jQuery(\"#post\").attr(\"enctype\", \"multipart/form-data\");</script>';") );
		//add_action('admin_enqueue_scripts', 'AWE_Videos::plupload_header');
		//add_action('wp_ajax_photo_gallery_upload', 'AWE_Videos::video_upload');

		add_action('admin_print_styles', 'AWE_Videos::video_header', 10, 1);
		add_filter('query_vars', 'AWE_Videos::add_query_vars' );

		add_action('parse_query', 'AWE_Videos::parse_query');
		add_action('template_redirect', 'AWE_Videos::template_redirect', 0);

		wp_enqueue_script( 'videojs', get_bloginfo('template_directory').'/static/video-js/video.js' );
		wp_enqueue_style( 'videojs-css', get_bloginfo('template_directory').'/static/video-js/video-js.css' );

	}

	static function rewrite_rules(){
	    global $wp_rewrite;
	    $rules = array(
	        'topic/?$' => 'index.php?taxonomy_terms=topic',
		'feed/video/?$' => 'index.php?post_type=video&vfeed=webm',
		'feed/video/([^/]+)/?$' => 'index.php?post_type=video&vfeed='.$wp_rewrite->preg_index(1)
	    );
            $wp_rewrite->rules = array_merge( $rules, $wp_rewrite->rules );
	}

	static function template_redirect() {
		global $wp_query;

		if ( isset( $wp_query->query_vars['vfeed'] ) && 
		   isset( $wp_query->query_vars['post_type'] ) && 
		   $wp_query->query_vars['post_type'] == 'video' ) {

			$video_feed = $wp_query->query_vars['vfeed'];

		   	if ( $video_feed == 'mp4' || $video_feed == 'webm' ) {

			    global $posts; 
			    $admin = get_userdata(1);

			    $info = get_option('video_feed', true);
			    $info['image'] = get_option( 'video_feed_image', true);
			    $url = get_bloginfo('url');

			    if ( get_option('permalink_structure') != '' ) { 
				$feed_url = get_bloginfo('url') . '/feed/video/' . $video_feed;
			    } else {
				$feed_url = get_bloginfo('url') . '?post_type=video&vfeed=' . $video_feed;
			    }

			    AWE_Videos_Feed::response( $posts, $admin, $info, $url, $feed_url, $video_feed );
			    exit();
			   
			} else {

			    print 'Invalid video_feed';
			    exit();

			}
		}
	}

	/**
	 * Additional action callback for the WordPress to add additional query_vars.
	 */
	static function add_query_vars( $query_vars ) {
		// Add our template query variable for WP_Query
		$query_vars[] = 'taxonomy_terms';
		$query_vars[] = 'vfeed';
		return $query_vars;
	}

	/**
	 * Additional action callback for the WordPress parse_query to translate query variables.
	 */
	static function parse_query( &$query ) {

		if ( isset( $query->query_vars['taxonomy_terms'] ) ) {
			global $terms, $taxonomy;
			$terms = get_terms( $query->query_vars['taxonomy_terms'], array( 'orderby' => 'count', 'order' => 'DESC' ) );
			$taxonomy = get_taxonomy( $query->query_vars['taxonomy_terms'] );
		}

		return $query;
	}

	static function license_html( &$post ) {
		global $post;
		$html = '<div class="video-license">';

		$license = get_post_meta( $post->ID, 'license', true );
		if ( !$license ) {
			$license = get_option( 'video_license' );
		}
		$base_url = get_bloginfo( 'template_directory' );

		$html .= '<span class="video-license-icons">';
		$html .= '<a href="'.$license['url'].'">';

		if ( in_array('by', $license['rights'] ) ) {
			$html .= '<img src="'.$base_url.'/static/cc-by-spacer.png" border="0" alt="Attribution" title="Attribution" class="cc-by-icon">';
		}
		if ( in_array('nc', $license['rights'] ) ) {
			$html .= '<img src="'.$base_url.'/static/cc-by-spacer.png" border="0" alt="NonCommercial" title="Noncommercial" class="cc-nc-icon">';
		}
		if ( in_array('sa', $license['rights'] ) ) {
			$html .= '<img src="'.$base_url.'/static/cc-by-spacer.png" border="0" alt="Share Alike" title="Share Alike" class="cc-sa-icon">';
		}
		if ( in_array('nd', $license['rights'] ) ) {
			$html .= '<img src="'.$base_url.'/static/cc-by-spacer.png" border="0" alt="NoDeriv" title="NoDeriv" class="cc-nd-icon">';
		}
		$html .= '</a></span> ';

		$html .= get_the_date( 'Y', '<span>', '</span>' );
		$html .= ' <a href="'.$license['author_url'].'" rel="license cc:author">'.$license['author'].'</a>.';
		$html .= ' <a href="'.$license['url'].'" rel="license cc:license">Some rights reserved.</a>';
		$html .= '</div>';
		return $html;
	}

	public static function topic_link(){
		if ( get_option('permalink_structure') != '' ) {
			return get_bloginfo('url').'/topic/';
		} else {
			return get_bloginfo('url').'/index.php?taxonomy_terms=topic';
		}
	}

	public static function video_header( $page ) {
		wp_enqueue_style( 'video-options-css', plugin_dir_url( __FILE__ ).'static/options.css');
	}

	public static function plupload_header( $page ) {
		if ( get_post_type() == 'video' ) {
			wp_enqueue_script('plupload-all');
		}
	}

	public static function video_upload() {
		check_ajax_referer('photo-upload');

		// you can use WP's wp_handle_upload() function:
		$status = wp_handle_upload($_FILES['async-upload'], array('test_form'=>true, 'action' => 'photo_gallery_upload'));

		// and output the results or something...
		echo $status['url'];

		exit();
	}

	public static function add_menus(){
		add_submenu_page( 'edit.php?post_type=video', 'Feed', 'Feed', 'manage_options', 'video_feed_options', 'AWE_Videos::feed_options_page');
		add_submenu_page( 'edit.php?post_type=video', 'Settings', 'Settings', 'manage_options', 'video_settings', 'AWE_Videos::settings_page');
	}

    public static function feed_options_page(){
        require_once dirname(__FILE__).'/options-feed.php';
    }

    public static function settings_page(){
        require_once dirname(__FILE__).'/options-settings.php';
    }

    public static function feed() {
        global $wp_query;

        $items = $wp_query->posts;
        $author = get_userdata($wp_query->query_vars['author']);
        
        $info = array(
                      'title' => $author->videofeed_title,
                      'description' => $author->videofeed_description,
                      'keywords' => $author->videofeed_keywords,
                      'image' => $author->videofeed_image,
                      'categories' => $author->videofeed_categories,
                      'copyright' => $author->videofeed_copyright,
                      'explicit' => $author->videofeed_explicit,
                      'language' => $author->videofeed_language,
                      );
        
        $url = get_bloginfo('url').'/'.$author->user_nicename;

        if ($wp_query->query_vars['format'] == 'rss2' && $wp_query->query_vars['post_type'] == 'video'){

            $feed_url = $url . '/videos/feed/';

            VideosFeed::response($items, $author, $info, $url, $feed_url);
            die();

        } else if ($wp_query->query_vars['format'] == 'json' && $wp_query->query_vars['post_type'] == 'video'){

            $feed_url = $url . '/videos/json/';

            VideosJSON::response($items, $author, $info, $url, $feed_url);
            die();

        } else if ($wp_query->query_vars['format'] == 'json' && $wp_query->query_vars['post_type'] == 'album'){

            $cat = get_term_by('slug', $wp_query->query_vars['catalog'], 'catalog');

            $info = array(
                          'title' => $cat->name,
                          'description' => $cat->description,
                          'keywords' => '',
                          'image' => '',
                          'categories' => '',
                          'copyright' => '',
                          'explicit' => '',
                          'language' => '',
                          );
            
            $feed_url = get_bloginfo('url').'/albums/catalog/'.$cat->slug.'/json/';

            AlbumsJSON::response($items, $author, $info, $url, $feed_url);
            die();

        }
    }

    public static function map_meta_cap($caps, $cap, $user_id, $args) {

        /* If editing, deleting, or reading a movie, get the post and post type object. */
        if ('edit_video' == $cap || 'delete_video' == $cap || 'read_video' == $cap) {
		    $post = get_post( $args[0] );
		    $post_type = get_post_type_object( $post->post_type );

		    /* Set an empty array for the caps. */
		    $caps = array();
		}

		if ('edit_video' == $cap) {
			/* If editing a movie, assign the required capability. */
	
		    if ( $user_id == $post->post_author ) {
		        $caps[] = $post_type->cap->edit_posts;
		    } else {
		        $caps[] = $post_type->cap->edit_others_posts;
			}

		} elseif ('delete_video' == $cap) {
	
			/* If deleting a movie, assign the required capability. */
		    if ( $user_id == $post->post_author ) {
			    $caps[] = $post_type->cap->delete_posts;
		    } else {
		        $caps[] = $post_type->cap->delete_others_posts;
			}


		} elseif ('read_video' == $cap) {
			/* If reading a private movie, assign the required capability. */

			if ('private' != $post->post_status) {
			    $caps[] = 'read';
			} elseif ( $user_id == $post->post_author ) {
			    $caps[] = 'read';
			} else {
			    $caps[] = $post_type->cap->read_private_posts;
			}
		}

		/* Return the capabilities required by the user. */
		return $caps;
    }

    public static function admin_init() {
        add_meta_box('video-release-file', 'Video File Sources', 'AWE_Videos::video_file_box', 'video', 'side', 'high');
		$license = get_option('video_license');
		if ( $license['independent'] == false ) {
	        add_meta_box('video-release-info', 'Copyright & Licensing', 'AWE_Videos::other_show_box', 'video', 'normal', 'high');
		}
    }

    public static function video_file_box(){
        global $post;

		$video = AWE_Videos::retrieve($post->ID);

		print '<p><strong>WebM</strong> <em>(VP8/Vorbis)</em></p>';
		if( isset( $video['webm'] ) && $video['webm'] != '' ) {
			print '<strong style="color:green;">Currently:</strong>'.$video['webm'];
		}

		print '<p>Filename (Relative to Video Directory): <br/><input type="text" name="video_path_webm" id="video_path_webm"/></p>';

		print '<p><strong>Mp4</strong> <em>(H264/AAC)</em></p>';
		if( isset( $video['mp4'] ) && $video['mp4'] != '') {
			print '<strong style="color:green;">Currently:</strong>'.$video['mp4'];
		}
		print '<p>Filename (Relative to Video Directory): <br/><input type="text" name="video_path_mp4" id="video_path_mp4"/></p>';

		print '</p>';

		print '<p><strong>Aspect Ratio</strong>';
		print '<select name="video_file_aspect" id="video_file_aspect"/>';
		$selected = '';
		if ( $video['aspect'] == '0.5625' ) $selected = ' selected';
		print '<option value="0.5625"'.$selected.'>16:9</option>';
		$selected = '';
		if ( $video['aspect'] == '0.75' ) $selected = ' selected';
		print '<option value="0.75"'.$selected.'>4:3</option>';
		print '</select>';
		print '</p>';

    }

	public static function other_show_box(){
		global $post;

		$license = get_post_meta( $post->ID, 'license', true );
		if ( !$license ) {
			$license = get_option('video_license');
		}
	
		echo '<input type="hidden" name="video_meta_box_nonce" value="', wp_create_nonce(basename(__FILE__)), '" />';


		if ( in_array('by', $license['rights'] ) ) {
			$checked = 'checked';
		} else {
			$checked = '';
		}

		echo '<div class="cc_option_wrap"><div id="video_license_attribution"><input id="video_license_attribution" type="checkbox" name="video_license_attribution" '.$checked.'><br/>Attribution</input></div></div>';

		if ( in_array('sa', $license['rights'] ) ) {
			$checked = 'checked';
		} else {
			$checked = '';
		}

		echo '<div class="cc_option_wrap"><div id="video_license_sharealike"><input type="checkbox" name="video_license_sharealike" '.$checked.'><br/>Share Alike</input></div></div>';

		if ( in_array('nc', $license['rights'] ) ) {
			$checked = 'checked';
		} else {
			$checked = '';
		}

		echo '<div class="cc_option_wrap"><div id="video_license_noncommercial"><input type="checkbox" name="video_license_noncommercial" '.$checked.'><br/>Non Commercial</input></div></div>';

		if ( in_array('nd', $license['rights'] ) ) {
			$checked = 'checked';
		} else {
			$checked = '';
		}

		echo '<div class="cc_option_wrap"><div id="video_license_noderiv"><input type="checkbox" name="video_license_noderiv" '.$checked.'><br/>No Deriv</input></div></div>';

		echo '<div class="cc_option_wrap">';
		echo '<div class="cc_option_wrap">';
		echo '<div id="video_license_author_wrap">';

		print '<div id="video_license_author">Attribution Name <br/><input type="text" value="'.$license['author'].'" name="video_license_author"></input></div>';
		print '<div id="video_license_author_url">Attribution URL <br/><input type="text" value="'.$license['author_url'].'" name="video_license_author_url"></input></div>';

		echo '</div>';

		echo '</div>';

		echo '<div class="cc_option_wrap"><div id="video_license_or">OR</div></div>';

		if ( !in_array('by', $license['rights'] ) ) {
			$checked = 'checked';
		} else {
			$checked = '';
		}

		echo '<div class="cc_option_wrap"><div id="video_license_publicdomain"><input id="video_license_publicdomain" type="checkbox" name="video_license_publicdomain" '.$checked.'><br/>Public Domain</input></div></div>';

		echo '</div>';

	
		?>

		<div class="cc_option_wrap">

		<p>Jurisdiction of your license<p>
		<select name="video_license_jurisdiction">
			<?php 

			if ( $license['jurisdiction'] == '' ) {
				$selected = ' selected="selected"';
			} else {
				$selected = '';
			}
			print '<option  '.$selected.'value="">International</option>';

			foreach ( AWE_Videos::$countries as $code => $title ) {
				if ( $license['jurisdiction'] == $code ) {
					$selected = ' selected="selected"';
				} else {
					$selected = '';
				}
				print '<option '.$selected.' value="'.$code.'">'.$title.'</option>';
			}
			?>
		</select>

		</div>

		<?php


		echo '<br style="clear:both;">';

    }

    public static function remove_author_box() {
        remove_meta_box('authordiv', 'video', 'normal');
    }

    public static function add_author_box() {
		global $post_ID;
		$post = get_post( $post_ID );
		if($post->post_type == 'video'){
			echo '<div class="misc-pub-section">Author: ';
			self::get_author_meta_box($post);
			echo '</div>';
        }
    }

    // Save data from meta box
    public static function save_post($post_id) {

		global $post;

		if ( isset( $post ) && $post->post_type == 'video' ) {

	        // verify nonce
	        if (!wp_verify_nonce($_POST['video_meta_box_nonce'], basename(__FILE__))){
			    return $post_id;
			}

			// check autosave
			if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			    return $post_id;
			}

			// check permissions
			if (!current_user_can('edit_videos', $post_id)) {
			    return $post_id;
			}

			if ( $_POST['video_file_aspect'] != '' ) {
				update_post_meta( $post_id, 'video_file_aspect', $_POST['video_file_aspect'] );
			}

			// handle uploaded file
			AWE_Videos::store($post_id, 'local', $_POST);

			$noncommercial = isset( $_POST['video_license_noncommercial'] ) ? $_POST['video_license_noncommercial'] : false;

			$sharealike = isset( $_POST['video_license_sharealike'] ) ? $_POST['video_license_sharealike'] : false;

			$attribution = isset( $_POST['video_license_attribution'] ) ? $_POST['video_license_attribution'] : false;

			$noderiv = isset( $_POST['video_license_noderiv'] ) ? $_POST['video_license_noderiv'] : false;

			$jurisdiction = $_POST['video_license_jurisdiction'];

			if ( $jurisdiction == '' ) {
				$country_title = 'International';
				$country_url = '';
			} else {
				$country_title = AWE_Videos::$countries[$jurisdiction];
				$country_url = $jurisdiction.'/';
			}

			if ( $noderiv && $sharealike ) {
		
				$messages[] = __('Licensing can not be both ShareAlike and NoDeriv', 'awevideo');
		
			} else {
		
				if ( !$noderiv && !$sharealike && !$attribution && !$noncommercial ) {
					$license = array(
						'rights' => array(),
						'title' => 'Public Domain', 
						'jurisdiction' => '', 
						'url' => 'http://creativecommons.org/publicdomain/mark/1.0/'
						);
				} else {
					if ( $noncommercial && $sharealike ) {
						// cc-by-nc-sa
						$license = array(
							'rights' => array('by', 'nc', 'sa'),
							'title' => 'Attribution NonCommercial ShareAlike 3.0 '.$country_title, 
							'jurisdiction' => $jurisdiction, 
							'url' => 'http://creativecommons.org/licenses/by-nc-sa/3.0/'.$country_url
						);
					} else if ( $noncommercial && $noderiv ) {
						// cc-by-nc-nd
						$license = array(
							'rights' => array('by', 'nc', 'nd'),
							'title' => 'Attribution NonCommercial NoDeriv 3.0 '.$country_title, 
							'jurisdiction' => $jurisdiction, 
							'url' => 'http://creativecommons.org/licenses/by-nc-nd/3.0/'.$country_url
						);
					} else if ( $noderiv ) {
						// cc-by-nd
						$license = array(
							'rights' => array('by', 'nd'),
							'title' => 'Attribution NoDeriv 3.0 '.$country_title, 
							'jurisdiction' => $jurisdiction, 
							'url' => 'http://creativecommons.org/licenses/by-nd/3.0/'.$country_url
						);
					} else if ( $noncommercial ) {
						// cc-by-nc
						$license = array(
							'rights' => array('by', 'nc'),
							'title' => 'Attribution NonCommercial 3.0 '.$country_title, 
							'jurisdiction' => $jurisdiction, 
							'url' => 'http://creativecommons.org/licenses/by-nc/3.0/'.$country_url
						);
					} else if ( $sharealike ) {
						// cc-by-sa
						$license = array(
							'rights' => array('by', 'sa'),
							'title' => 'Attribution ShareAlike 3.0 '.$country_title, 
							'jurisdiction' => $jurisdiction, 
							'url' => 'http://creativecommons.org/licenses/by-sa/3.0/'.$country_url
						);
					} else {
						// cc-by
						$license = array(
							'rights' => array('by'),
							'title' => 'Attribution 3.0 '.$country_title, 
							'jurisdiction' => $jurisdiction, 
							'url' => 'http://creativecommons.org/licenses/by/3.0/'.$country_url
						);
					}

					$license['author'] = $_POST['video_license_author'];
					$license['author_url'] = $_POST['video_license_author_url'];
		
		
					$old = get_post_meta( $post_id, 'license', true );

					update_post_meta( $post_id, 'license', $license,  $old );
				}
			}

		}

    }
	
    private static function get_author_meta_box( $post ) {
		global $user_ID;
		$authors = self::get_editable_user_ids( get_current_user_id(), true, $post->post_type);
		if ( $post->post_author && !in_array($post->post_author, $authors) ) {
			$authors[] = $post->post_author;
		}
		?>
		<label class="screen-reader-text" for="post_author_override"><?php _e('Artist'); ?></label><?php wp_dropdown_users( array('include' => $authors, 'name' => 'post_author_override', 'selected' => empty($post->ID) ? $user_ID : $post->post_author) ); ?>
		<?php
	}

    private static function get_editable_user_ids( $user_id, $exclude_zeros = true, $post_type = 'post' ) {
		global $wpdb, $wp_roles; 

		$user = new WP_User( $user_id );

		$post_type_obj = get_post_type_object($post_type);

		if ( ! $user->has_cap($post_type_obj->cap->edit_others_posts) ) {
			if ( $user->has_cap($post_type_obj->cap->edit_posts) || ! $exclude_zeros )
				return array($user->id);
			else
				return array();
			}

		$role_names = array_keys( $wp_roles->get_names() );
		$editable_roles = array();

		// Determine which roles have permission to edit posts for the current post type
		foreach ( $role_names as $role_name ) {
			$role = &get_role( $role_name );
			if ( $role->has_cap( $post_type_obj->cap->edit_others_posts ) || $role->has_cap( $post_type_obj->cap->edit_posts ) || ! $exclude_zeros )
				$editable_roles[] = $role_name;
			}

		if ( empty( $editable_roles ) )
			return array();

		// Find all users that have editable roles
		$likes = array();

		foreach ( $editable_roles as $role_name ) {
			$role_name = like_escape( $role_name );
			$likes[] = "meta_value LIKE '%%$role_name%%'";
		}

		$like = implode(' OR ', $likes);
		$meta_key = $wpdb->get_blog_prefix() . 'capabilities';  
		$sql = $wpdb->prepare("SELECT user_id FROM $wpdb->usermeta WHERE meta_key = %s AND ( $like )", $meta_key);
		return $wpdb->get_col( $sql );
    }

	public static function store( $post_id, $server_id, $data ) {

	       $server = self::$servers[$server_id];


	     if ( $data['video_path_webm'] != '' ) {
	     	update_post_meta( $post_id, 'media_path_webm', $data['video_path_webm'] );
		update_post_meta( $post_id, 'media_server_webm', $server_id );
	     }
	     if ( $data['video_path_mp4'] != '' ) {
	        update_post_meta( $post_id, 'media_path_mp4', $data['video_path_mp4'] );
		update_post_meta( $post_id, 'media_server_mp4', $server_id );
	     }


	}

	public static function retrieve( $post_id ) {
        // retrieve our data
        $post = get_post($post_id);
		$video_files = array( 'webm' => false, 'mp4' => false);

		foreach ( $video_files as $type => $file ) {

	        $path = get_post_meta($post_id, 'media_path_'.$type, true);
	        if($path != ''){
	            $post_meta = '';

	            $server_id = get_post_meta($post_id, 'media_server_'.$type, true);
	            $server = self::$servers[$server_id];
	            $uri = $server['base_url'].$path;

	            unset($server);
	            unset($path);
	            unset($server_id);

	            $video_files[$type] = $uri;
	        }
		}

		$video_files['aspect'] = get_post_meta($post_id, 'video_file_aspect', true);

		return $video_files;
    }
	
}

?>