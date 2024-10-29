<?php
	global $copyright_links_footer_template;

	$messages = array();

	if ( $_POST ) {

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
			}

			if ( isset( $_POST['video_license_independent'] ) ) {
				$license['independent'] = true;
			} else {
				$license['independent'] = false;
			}

			$license['author'] = $_POST['video_license_author'];
			$license['author_url'] = $_POST['video_license_author_url'];

		    update_option('video_license', $license);
		}
	}

	$license = get_option('video_license', array(
		'independent' => true,
		'rights' => array( 'by' ),
		'title' => 'Attribution ShareAlike 3.0 International', 
		'jurisdiction' => '', 
		'url' => 'http://creativecommons.org/licenses/by/3.0/',
		'author' => get_bloginfo('name'),
		'author_url' => get_bloginfo('url')
	));

	?>
	<link rel="stylesheet" href="<?php bloginfo('plugin_directory'); ?>/style.css" type="text/css" />
	<div class="wrap">

        <h2>Video Settings</h2>

	<form method="post" action="">
	<?php wp_nonce_field('update-options'); ?>


	<h3>Creative Commons Licensing</h3>

		<?php

			if ( $license['independent'] ) {
				$checked = 'checked';
			} else {
				$checked = '';
			}

		?>

		<p><input type="checkbox" <?php print $checked; ?> name="video_license_independent" value="false"></input>
		All videos use the same license for consistency and clarity.</p>

		<input type="hidden" name="video_meta_box_nonce" value="', wp_create_nonce(basename(__FILE__)), '" />

		<?php

			if ( in_array('by', $license['rights'] ) ) {
				$checked = 'checked';
			} else {
				$checked = '';
			}

		?>

		<div class="cc_option_wrap"><div id="video_license_attribution"><input id="video_license_attribution" type="checkbox" name="video_license_attribution" <?php print $checked; ?>><br/>Attribution</input></div></div>

		<?php

			if ( in_array('sa', $license['rights'] ) ) {
				$checked = 'checked';
			} else {
				$checked = '';
			}

		?>
		<div class="cc_option_wrap"><div id="video_license_sharealike"><input type="checkbox" <?php print $checked; ?> name="video_license_sharealike"><br/>Share Alike</input></div></div>

		<?php

			if ( in_array('nc', $license['rights'] ) ) {
				$checked = 'checked';
			} else {
				$checked = '';
			}

		?>

		<div class="cc_option_wrap"><div id="video_license_noncommercial"><input type="checkbox" <?php print $checked; ?> name="video_license_noncommercial"><br/>Non Commercial</input></div></div>


		<?php

			if ( in_array('nd', $license['rights'] ) ) {
				$checked = 'checked';
			} else {
				$checked = '';
			}

		?>

		<div class="cc_option_wrap"><div id="video_license_noderiv"><input type="checkbox" <?php print $checked; ?> name="video_license_noderiv"><br/>No Deriv</input></div></div>

		<div class="cc_option_wrap">
		<div class="cc_option_wrap">
		<div id="video_license_author_wrap">

		<div id="video_license_author">Attribution Name <br/><input name="video_license_author" type="text" value="<?php print $license['author']; ?>"></input></div>
		<div id="video_license_author_url">Attribution URL <br/><input name="video_license_author_url" type="text" value="<?php print $license['author_url']; ?>"></input></div>

		</div>

		</div>

		<div class="cc_option_wrap"><div id="video_license_or">OR</div></div>

		<?php

			if ( !in_array('by', $license['rights'] ) ) {
				$checked = 'checked';
			} else {
				$checked = '';
			}

		?>

		<div class="cc_option_wrap"><div id="video_license_publicdomain"><input id="video_license_publicdomain" type="checkbox" <?php print $checked; ?> name="video_license_publicdomain"><br/>Public Domain</input></div></div>

		</div>

		<h4>Jurisdiction of your license</h4>
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

	<br style="clear:both;"/>

    <p><input type="submit" class="button" value="Save Changes" /></p>

	</div>

