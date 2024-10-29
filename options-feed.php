<?php

if($_POST){

    if( !empty($_POST['videofeed_categories']) ){
	list($cat1, $sub_cat1) = explode('-', $_POST['videofeed_categories']);
	$cats = array(array($cat1, $sub_cat1));
    } else {
	$cats = '';
    }


    $info = array(
    	  'title' => $_POST['videofeed_title'],
	  'description' => $_POST['videofeed_description'],
	  'keywords' => $_POST['videofeed_keywords'],
	  'categories' => $cats,
	  'language' => $_POST['videofeed_language'],
	  'explicit' => $_POST['videofeed_explicit'],
    );

    $old = get_option( 'video_feed', true );
    update_option( 'video_feed', $info, $old );

    // Valid file types for upload.
    $valid_file_types = array(
			      "image/jpeg" => true,
			      "image/pjpeg" => true,
			      "image/gif" => true,
			      "image/png" => true,
			      "image/x-png" => true
			      );

    // Upload the image
    if(isset($_FILES['videofeed_image']) && @$_FILES['videofeed_image']['name']){
	// Something uploaded?
	if($_FILES['videofeed_image']['error']) {

	    // Any errors?
	    $error = 'Upload error.';

	} else if (@$valid_file_types[$_FILES['videofeed_image']['type']]){

	    // Valid types?
	    $upload_dir = wp_upload_dir();
	    $path = $upload_dir['basedir'] . '/avatars/';
	    $file = $_FILES['videofeed_image']['name'];

	    // Directory exists?
	    if(!file_exists($path) && @!mkdir($path, 0777)) {
		$error = 'Upload directory <b>"'.$path.'"</b> does not exist.';
	    } else {
		// Get a unique filename.
		// First, if already there, include the User's ID; this should be enough.
		if(file_exists($path . $file)) {
		    $parts = pathinfo($file);
		    $file = basename($parts['basename'], '.' . $parts['extension']) . '-' . $user_id . '.' . $parts['extension'];
		}

		$tries = 10;

		// Second, if required loop to create a unique file name.
		$i = 0;
		while(file_exists($path . $file) && $i < $tries) {
		    $i++;
		    $parts = pathinfo($file);
		    $file = substr(basename($parts['basename'], '.' . $parts['extension']), 0, strlen(basename($parts['basename'], '.' . $parts['extension'])) - ($i > 1 ? 2 : 0)) . '-' . $i . '.' . $parts['extension'];
		}

		if($i >= $tries) {
		    $error = "Too many tries to find non-existent file";
		} else {
		    $file = strtolower($file);

		    // Copy uploaded file.
		    if(!move_uploaded_file($_FILES['videofeed_image']['tmp_name'], $path . $file)) {
			$error = "File upload failed.";
		    } else {
			chmod($path . $file, 0644);

			// Resize 
			$scaled_size_itunes = '600';
			$scaled_size_rss = '144';

			$resized_file_itunes = image_resize($path . $file, $scaled_size_itunes, $scaled_size_itunes, true, $scaled_size_itunes.'x'.$scaled_size_itunes);
			$resized_file_rss = image_resize($path . $file, $scaled_size_rss, $scaled_size_rss, true, $scaled_size_rss.'x'.$scaled_size_rss);

			if(!is_wp_error($resized_file_itunes) && !is_wp_error($resized_file_rss)){
			    $parts = pathinfo($file);
			    $file_itunes = basename($resized_file_itunes, '.' . $parts['extension']) . '.' . $parts['extension'];
			    $file_rss = basename($resized_file_rss, '.' . $parts['extension']) . '.' . $parts['extension'];
			} else {
			    $error = 'Unable to resize image: '.$path . $file . ' Please also make sure the image is 600px square or larger.';
			}
		    }
		}
	    }
	} else {
	    $error = 'Wrong type.';
	}

	// Save the new local avatar for this user.
	if ( empty( $error ) ) {
	    update_option( 'video_feed_image', array( $upload_dir['baseurl'] .'/avatars/'. $file_itunes, $upload_dir['baseurl'] .'/avatars/'.$file_rss ) );
	} else {
	    update_option( 'video_feed_error', $error );
	}
    }

}

$error = get_option('video_feed_error');
delete_option('video_feed_error');

$info = get_option('video_feed', true);
$image =  get_option( 'video_feed_image', true);

?>

<div class="wrap">
    <h2>Videos Feed</h2>

    <?php if ($error){ ?>

    <div id='message' class='error fade'> <?php echo $error; ?></div>
														   <?php } ?>

    <form action="" method="post" enctype="multipart/form-data" />

    <table class="form-table">
       <tbody>
       <tr>
       <td><label for="videofeed_title">Title</label></td>
       <td><input type="text" name="videofeed_title" id="videofeed_title" value="<?php print $info['title']; ?>" size="40"/></td>
       </tr>
       <tr>
       <td><label for="videofeed_description">Description</label></td>
       <td><textarea cols="30" rows="5" id="videofeed_description" name="videofeed_description"><?php print $info['description']; ?></textarea></td>
       </tr>
       <tr>
       <td><label for="videofeed_keywords">Keywords<small>(comma seperated)</small></label></td>
       <td><input type="text" name="videofeed_keywords" id="videofeed_keywords" value="<?php print $info['keywords']; ?>" size="40"/></td>
       </tr>
       <tr>
       <td><label for="videofeed_image">Image</label></td>
       <td>
    <?php 
	if( $image && is_array($image) ){
	    foreach( $image as $src ){
			print '<img src="'.$src.'">';
	    }
	}
    ?>
    <br/><br/>
    <p>Select image file: <input type="file" name="videofeed_image" id="videofeed_image" /></p>
    </td>
    </tr>
    <tr>
    <td><label for="videofeed_categories">Categories</label></td>
    <td><input type="hidden" name="videofeed_categories" id="videofeed_categories" value="10-00"/><strong>News & Politics</strong></td>
    </tr>
    <tr>
    <td>Language</td>
    <td>
    <?php 
	if($info['explicit'] == 'on') {
	    $checked = 'checked'; 
	} else {
	    $checked = ''; 
	}
    ?>
    <input type="checkbox" name="videofeed_explicit" id="videofeed_explicit" <?php print $checked; ?>/>Explicit
    <input type="hidden" name="videofeed_language" id="videofeed_language" value="en" />
    </td>
    </tr>
    <tr>
    <td><input type="submit" value="Save Options"/></td>
    </tr>
    </tbody>
    </table>
    <div class="clear"></div>
    </form>
</div>

