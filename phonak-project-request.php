<?php
/*
Plugin Name: Phonak Project Request
*/



/*
*
*
*
*
*
*  SCRIPTS
*
*
*
*
*
*/
add_shortcode( 'phonak_marketing_request_app', 'phonak_marketing_request_app' );
function phonak_marketing_request_app() {
	return '<div id="phonak_app"></div>';
}



function phonak_marketing_request_enqueue_style() {
	wp_enqueue_style( 'project-request', plugins_url().'/phonak-project-request/css/project-request.css', false ); 
}

function phonak_marketing_request_enqueue_script() {

}

add_action( 'wp_enqueue_scripts', 'phonak_marketing_request_enqueue_style',20 );
add_action( 'wp_enqueue_scripts', 'phonak_marketing_request_enqueue_script' );



/*
*
*
*
*
*
*  SHORT CODES
*
*
*
*
*
*/

function phonak_project(){
	$success = false;
	//PROCESS FORM DATA IF SUBBMITED 
	if(isset($_POST['submit'])){

		// Filter out any post items we don't need. Example: submit
		$filterOut = ['submit'];
		foreach($filterOut as $out){
			unset($_POST[$out]);
		}

		// define string replace array
		$replace = ['_'];
		$replaceWith = [' '];

		$description = '';

		foreach($_POST as $key => $val){
			$description .= '<p><strong>'.str_replace($replace, $replaceWith, $key).': </strong>';
				if(is_array($val)){
					$artworkArray = array();
					foreach($val as $subVal){
						$parts = explode('|', $subVal);
						$artworkArray[] = '<a href="'.$parts[1].'">'.$parts[0].'</a>';
					}
					$description .= implode(', ', $artworkArray);


				}else{
					$description .= $val;
				}
			$description .= '</p>';
		}


		// Hit API
		$username='netgain';
		$password='Phon@Pass2';
		$apikey = 'V81L-YXDN-U7M5-QOJ9-PWFM3UN-US7044';
		$URL='https://api.proworkflow.net/projectrequests?apikey='.$apikey;

		$projectTitle = 'phonakmarketing.ca Project Request | '.$_POST['clinic_name'].' | '.$_POST['project_type'];

		$projectData = [
			'title' => $projectTitle,
			'description' => $description
		];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$URL);
		curl_setopt($ch, CURLOPT_USERPWD, $username.":".$password);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $projectData);
		$result = curl_exec($ch);

		$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);   //get status code
		//echo '$status_code: '.$status_code.'<br/>';

		if($status_code == 201){
			$decodedResponse = json_decode($result);
			$projectId = $decodedResponse->details[0]->id;
			curl_close($ch);

			// deal with any attached files after we have the project ID.
			if(!empty($_FILES)){
				$description .= '<strong>File Attachments</strong>';
				foreach($_FILES as $key => $val){
					//$description .= '<p>'.$val['name'].': '.$val['tmp_name'].'</p>';
					//print_r($_FILES[$key]);
					//echo base64_encode($_FILES[$key]['tmp_name']);
					if($_FILES[$key]['tmp_name'] != ''){
						$fileData =  [
							'content' => base64_encode(file_get_contents($_FILES[$key]['tmp_name'])),
							'name' => $_FILES[$key]['name'],
							'projectid' => $projectId
						];

						//print_r($fileData);

						$URL='https://api.proworkflow.net/files?apikey='.$apikey;
						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL,$URL);
						curl_setopt($ch, CURLOPT_USERPWD, $username.":".$password);
						curl_setopt($ch, CURLOPT_TIMEOUT, 30); 
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);

						$result = curl_exec($ch);
						$decodedResponse = json_decode($result);

					}

				}

				$success = true;

			}else{
				echo 'no files to upload';
				print curl_error($ch);
				curl_close($ch);

				$success = false;
			}
		}

	}



	if($success){
		echo '<p style="color: #86bc24">Project successfully submitted.<p>';
	}else{ ?>

		<form method="post" action="#" enctype="multipart/form-data">

			<input type="hidden" name="project_type" value="<?php echo get_queried_object()->name; ?>">

			<?php
			// CHECK FOR CHILDREN
			$term_id = get_queried_object()->term_id;
			$term_meta = get_option( 'taxonomy_'.$term_id ); 

			$taxonomy_name = 'phonak-projects';
			$term_children = get_term_children( $term_id, $taxonomy_name );



			if(count($term_children) != 0){
				echo '<ul>';
				foreach ( $term_children as $child ) {
					$term = get_term_by( 'id', $child, $taxonomy_name );
					echo '<li><a href="' . get_term_link( $child, $taxonomy_name ) . '">' . $term->name . '</a></li>';
				}
				echo '</ul>';
			}

			// IF NO CHILDREN RENDER PROJECT ELEMENTS AND FORM
			if(count($term_children) == 0){
				if ( have_posts() ) :
					while ( have_posts() ) : the_post(); 
				?>
				<div>
					<label><input type="checkbox" name="project_element[]" value="<?php the_title(); ?>|<?php echo get_post_meta(get_the_ID(),'artwork_url',true); ?>"> <?php the_title(); ?></label>
				</div>
				<?php
					endwhile;
				endif;
				
				?>

				<div>
					<h3>Clinic Information</h3>
					<div>
						<label>Contact Name</label>
						<input type="text" name="contact_name" value="<?php if(isset($_POST['contact_name'])){ echo $_POST['contact_name']; } ?>" />
					</div>
					<div>
						<label>Email Address</label>
						<input type="email" name="email_address" value="<?php if(isset($_POST['email_address'])){ echo $_POST['email_address']; } ?>" />
					</div>
					<div>
						<label>Account Name</label>
						<input type="text" name="account_name" value="<?php if(isset($_POST['account_name'])){ echo $_POST['account_name']; } ?>" />
					</div>
					<div>
						<label>Phone</label>
						<input type="text" name="phone" value="<?php if(isset($_POST['phone'])){ echo $_POST['phone']; } ?>" />
					</div>
				</div>

				<?php 
				/**
				**
				** Newspaper Advertisement
				**
				**/
				if($term_meta['project_type'] == 'Print marketing - Newspaper Advertisement'){ ?>
				<div>
					<h3>Ad Information</h3>
					<div>
						<label>Have you booked an AD space in your publication?</label>
						<label><input type="radio" name="booked_ad_space" value="yes" <?php if(isset($_POST['booked_ad_space']) && $_POST['booked_ad_space'] == 'yes'){ echo 'SELECTED'; } ?> />Yes</label>
						<label><input type="radio" name="booked_ad_space" value="no" <?php if(isset($_POST['booked_ad_space']) && $_POST['booked_ad_space'] == 'no'){ echo 'SELECTED'; } ?> />No</label>
					</div>
					<div>
						<label>If yes, when is the deadline to submit the AD to your publication?</label>
						<input type="text" name="ad_space_deadline" value="<?php if(isset($_POST['ad_space_deadline'])){ echo $_POST['ad_space_deadline']; } ?>" />
					</div>
					<div>
						<label>Please provide your AD dimensions (in inches) as per your booking.</label>
						<input type="text" name="ad_dimensions_width" placeholder="Width" value="<?php if(isset($_POST['ad_dimensions_width'])){ echo $_POST['ad_dimensions_width']; } ?>" />
						<input type="text" name="ad_dimensions_height" placeholder="Height" value="<?php if(isset($_POST['ad_dimensions_height'])){ echo $_POST['ad_dimensions_height']; } ?>" />
					</div>
					<div>
						<label>Please provide the name of your publication?</label>
						<input type="text" name="publication_name" value="<?php if(isset($_POST['publication_name'])){ echo $_POST['publication_name']; } ?>" />
					</div>
				</div>
				<?php } ?>


				<?php 
				/**
				**
				** Direct Mail
				**
				**/
				if($term_meta['project_type'] == 'Print marketing - Direct Mail'){ ?>
				<div>
					<h3>Direct Mail Information</h3>
					<div>
						<label>When would you like to have your mailer distributed? (Please plan 4-6 weeks in advance in order to allow time for design, print and postage of all direct mail pieces.) </label>
						<input type="date" name="direct_mail_date" value="<?php if(isset($_POST['direct_mail_date'])){ echo $_POST['direct_mail_date']; } ?>">
					</div>
					<div>
						<label>Will your mailer be promoting a special event? i.e. Open House, etc.</label>
						<label><input type="radio" name="promoting_a_special_event" value="yes" <?php if(isset($_POST['promoting_a_special_event']) && $_POST['promoting_a_special_event'] == 'yes'){ echo 'SELECTED'; } ?> />Yes</label>
						<label><input type="radio" name="promoting_a_special_event" value="no" <?php if(isset($_POST['promoting_a_special_event']) && $_POST['promoting_a_special_event'] == 'no'){ echo 'SELECTED'; } ?> />No</label>
					</div>
					<div>
						<label>If yes, please provide the date and name of the event?</label>
						<input type="text" name="special_event_name" placeholder="Event Name" value="<?php if(isset($_POST['special_event_date'])){ echo $_POST['special_event_date']; } ?>" />
						<input type="text" name="special_event_date" placeholder="Event Date" value="<?php if(isset($_POST['special_event_date'])){ echo $_POST['special_event_date']; } ?>" />
					</div>
					<div>
						<label>Would you like Phonak to arrange printing and delivery of your mailer? (Alternatively, clinics can arrange printing with a preferred printer and mailing through Canada Post)</label>
						<label><input type="radio" name="phonak_to_arrange_printing_and_delivery_of_your_mailer" value="yes" <?php if(isset($_POST['phonak_to_arrange_printing_and_delivery_of_your_mailer']) && $_POST['phonak_to_arrange_printing_and_delivery_of_your_mailer'] == 'yes'){ echo 'SELECTED'; } ?> />Yes</label>
						<label><input type="radio" name="phonak_to_arrange_printing_and_delivery_of_your_mailer" value="no" <?php if(isset($_POST['phonak_to_arrange_printing_and_delivery_of_your_mailer']) && $_POST['phonak_to_arrange_printing_and_delivery_of_your_mailer'] == 'no'){ echo 'SELECTED'; } ?> />No</label>
					</div>
				</div>
				<?php } ?>

				<?php 
				/**
				**
				** Newspaper Insert
				**
				**/
				if($term_meta['project_type'] == 'Print marketing - Newspaper Insert'){ ?>
				<div>
					<h3>Newspaper Insert Information</h3>
					<div>
						<label>When would you like to have your insert distributed? (Please plan 6-8 weeks in advance in order to allow time for customization, print distribution of inserts) </label>
						<input type="date" name="distribution_date" value="<?php if(isset($_POST['distribution_date'])){ echo $_POST['distribution_date']; } ?>" />
					</div>
					<div>
						<label>Have you booked an insertion with your publication?</label>
						<label><input type="radio" name="have_you_booked_an_insertion_with_your_publication" value="yes" <?php if(isset($_POST['have_you_booked_an_insertion_with_your_publication']) && $_POST['have_you_booked_an_insertion_with_your_publication'] == 'yes'){ echo 'SELECTED'; } ?> />Yes</label>
						<label><input type="radio" name="have_you_booked_an_insertion_with_your_publication" value="no" <?php if(isset($_POST['have_you_booked_an_insertion_with_your_publication']) && $_POST['have_you_booked_an_insertion_with_your_publication'] == 'no'){ echo 'SELECTED'; } ?> />No</label>
					</div>
					<div>
						<label>If yes, when is the deadline to submit the insert creative to your publication?</label>
						<input type="date" name="insert_creative_deadline" value="<?php if(isset($_POST['insert_creative_deadline'])){ echo $_POST['insert_creative_deadline']; } ?>" />
					</div>
					<div>
						<label>Would you like Phonak to arrange printing and delivery of your newspaper insert? (Alternatively clinics can arrange printing with a preferred printer and distribution through their local newspapers)</label>
						<label><input type="radio" name="phonak_to_arrange_printing_and_delivery_of_your_newspaper_insert" value="yes" <?php if(isset($_POST['phonak_to_arrange_printing_and_delivery_of_your_newspaper_insert']) && $_POST['phonak_to_arrange_printing_and_delivery_of_your_newspaper_insert'] == 'yes'){ echo 'SELECTED'; } ?> />Yes</label>
						<label><input type="radio" name="phonak_to_arrange_printing_and_delivery_of_your_newspaper_insert" value="no" <?php if(isset($_POST['phonak_to_arrange_printing_and_delivery_of_your_newspaper_insert']) && $_POST['phonak_to_arrange_printing_and_delivery_of_your_newspaper_insert'] == 'no'){ echo 'SELECTED'; } ?> />No</label>
					</div>
				</div>
				<?php } ?>


				<?php 
				/**
				**
				** Database marking
				**
				**/
				if($term_meta['project_type'] == 'Database marking'){ ?>
				<div>
					<h3>Database marking Information</h3>
					<div>
						<label>Date you would like to send? </label>
						<input type="date" name="date_to_send" value="<?php if(isset($_POST['date_to_send'])){ echo $_POST['date_to_send']; } ?>" />
					</div>
					<div>
						<label>Do you have a Database client list?</label>
						<label><input type="radio" name="database_client_list" value="yes" <?php if(isset($_POST['database_client_list']) && $_POST['database_client_list'] == 'yes'){ echo 'SELECTED'; } ?> />Yes</label>
						<label><input type="radio" name="database_client_list" value="no" <?php if(isset($_POST['database_client_list']) && $_POST['database_client_list'] == 'no'){ echo 'SELECTED'; } ?> />No</label>
					</div>
				</div>
				<?php } ?>


				<?php 
				/**
				**
				** Digital marketing
				**
				**/
				if($term_meta['project_type'] == 'Digital marketing'){ ?>
				<div>
					<h3>Digital marketing Information</h3>
					<div>
						<label>When would you like to have your banner/eletter sent? </label>
						<input type="date" name="date_to_send" value="<?php if(isset($_POST['date_to_send'])){ echo $_POST['date_to_send']; } ?>" />
					</div>
				</div>
				<?php } ?>

				<div>
					<h3>Clinic Content for Advertisement</h3>
					<div>
						<label>Clinic Name</label>
						<input type="text" name="clinic_name" value="<?php if(isset($_POST['clinic_name'])){ echo $_POST['clinic_name']; } ?>" />
					</div>
					<div>
						<label>Address</label>
						<input type="text" name="clinic_address" value="<?php if(isset($_POST['clinic_address'])){ echo $_POST['clinic_address']; } ?>" />
					</div>
					<div>
						<label>Phone Number</label>
						<input type="text" name="clinic_phone" value="<?php if(isset($_POST['clinic_phone'])){ echo $_POST['clinic_phone']; } ?>" />
					</div>
					<div>
						<label>Website</label>
						<input type="text" name="clinic_Website" value="<?php if(isset($_POST['clinic_Website'])){ echo $_POST['clinic_Website']; } ?>" />
					</div>
					<div>
						<label>Please upload your logo if you would like it included in the ad:</label>
						<input type="file" name="clinic_logo" value="<?php if(isset($_POST['clinic_logo'])){ echo $_POST['clinic_logo']; } ?>" />
					</div>
					<div>
						<label>Would you like to include a map?</label>
						<input type="radio" name="clinic_include_map" value="yes" <?php if(isset($_POST['clinic_include_map']) && $_POST['clinic_include_map'] == 'yes'){ echo 'SELECTED'; } ?> />
						<input type="radio" name="clinic_include_map" value="no" <?php if(isset($_POST['clinic_include_map']) && $_POST['clinic_include_map'] == 'no'){ echo 'SELECTED'; } ?> />
					</div>
					<div>
					<label>Other Details</label>
					<textarea name="other_details"><?php if(isset($_POST['other_details'])){ echo $_POST['other_details']; } ?></textarea>
					</div>
				</div>


				<input type="submit" name="submit">
			<?php } ?>
		</form>
	<?php
	}




}

add_shortcode('phonak_project','phonak_project');




/*
*
*
*
*
*
*  POST TYPE / TAXONOMIES 
*
*
*
*
*
*/

//BUILD Project 
add_action( 'init', 'phonak_project_post_type' );
function phonak_project_post_type() {
    register_post_type( 'project-elements',
        array(
            'labels' => array(
                'name' => 'Phonak Project Elements',
                'singular_name' => 'Phonak Project Element',
                'add_new' => 'Add New',
                'add_new_item' => 'Add New Phonak Project Element',
                'edit' => 'Edit',
                'edit_item' => 'Edit Phonak Project Element',
                'new_item' => 'New Phonak Project Element',
                'view' => 'View',
                'view_item' => 'View Phonak Project Element',
                'search_items' => 'Search Phonak Project Elements',
                'not_found' => 'No Phonak Project Elements found',
                'not_found_in_trash' => 'No Phonak Project Elements found in Trash'
            ),

            'public' => true,
            'supports' => array( 'title', 'thumbnail', 'page-attributes'),
            'has_archive' => true,
            'rewrite' => array( 'slug' => 'project-elements', 'with_front' => false ),
        )
    );

    register_taxonomy(
        'phonak-projects',
        'project-elements',
        array(
            'labels' => array(
                'name' => 'Projects',
                'add_new_item' => 'Add New Phonak Project',
                'new_item_name' => "New Phonak Project"
            ),
            'show_ui' => true,
            'show_tagcloud' => false,
            'hierarchical' => true,
            'rewrite' => array( 'slug' => 'phonak-project', 'with_front' => false )
        )
    );

}


//CAROUSEL META BOX
function phonak_project_meta() {
    add_meta_box( 'phonak_project_meta_box',
        'Project Details',
        'display_phonak_project_meta_box',
        'project-elements', 'advanced', 'high'
    );
}

add_action( 'admin_init', 'phonak_project_meta' );

function display_phonak_project_meta_box( $project ) {
    global $post;
    $artwork_url = esc_html( get_post_meta( $project->ID, 'artwork_url', true ) );
    ?>
    <table style="width: 100%;">
        <tr>
            <td style="width: 100%">Artwork URL</td>
        </tr>
        <tr>
            <td><input type="text" style="width: 100%" name="artwork_url" value="<?php echo $artwork_url; ?>" /></td>
        </tr>
        <input type="hidden" name="phonak_project_flag" value="true" />
    </table>
<?php
}


add_action( 'save_post', 'custom_fields_phonak_project_update', 10, 2 );

function custom_fields_phonak_project_update($post_id, $post ){
    if ( $post->post_type == 'project-elements' ) {
        if (isset($_POST['phonak_project_flag'])) {
            if ( isset( $_POST['artwork_url'] ) && $_POST['artwork_url'] != '' ) {
                update_post_meta( $post_id, 'artwork_url', $_POST['artwork_url'] );
            }else{
                update_post_meta( $post_id, 'artwork_url', '');
            }
        }
    }
}



// cateogry "Type"
// Add term page
function phonak_projects_taxonomy_add_new_meta_field() {
	// this will add the custom meta field to the add new term page
	?>
	<div class="form-field">
		<label for="term_meta[project_type]">Project Type</label>
		<select name="term_meta[project_type]" id="term_meta[project_type]">
			<option>Print marketing</option>
			<option>Database marking</option>
			<option>Digital marketing</option>
			<option>Events marketing</option>
		</select>
	</div>
<?php
}

function phonak_projects_taxonomy_edit_new_meta_field($term) {
	// this will add the custom meta field to the add new term page
	$t_id = $term->term_id;
	$term_meta = get_option( 'taxonomy_'.$t_id ); 
	?>

	<tr class="form-field">
	<th scope="row" valign="top"><label for="term_meta[custom_term_meta]">Project Type</label></th>
		<td>
			<select name="term_meta[project_type]" id="term_meta[project_type]">
				<option <?php if($term_meta['project_type'] == 'Print marketing'){ echo 'SELECTED'; } ?>>Print marketing</option>
				<option <?php if($term_meta['project_type'] == 'Print marketing - Newspaper Advertisement'){ echo 'SELECTED'; } ?>>Print marketing - Newspaper Advertisement</option>
				<option <?php if($term_meta['project_type'] == 'Print marketing - Direct Mail'){ echo 'SELECTED'; } ?>>Print marketing - Direct Mail</option>
				<option <?php if($term_meta['project_type'] == 'Print marketing - Newspaper Insert'){ echo 'SELECTED'; } ?>>Print marketing - Newspaper Insert</option>
				<option <?php if($term_meta['project_type'] == 'Database marking'){ echo 'SELECTED'; } ?>>Database marking</option>
				<option <?php if($term_meta['project_type'] == 'Digital marketing'){ echo 'SELECTED'; } ?>>Digital marketing</option>
				<option <?php if($term_meta['project_type'] == 'Events marketing'){ echo 'SELECTED'; } ?>>Events marketing</option>
			</select>
		</td>
	</tr>

<?php
}
add_action( 'phonak-projects_add_form_fields', 'phonak_projects_taxonomy_edit_new_meta_field', 10, 2 );
add_action( 'phonak-projects_edit_form_fields', 'phonak_projects_taxonomy_edit_new_meta_field', 10, 2 );



function save_phonak_projects_custom_meta( $term_id ) {
	if ( isset( $_POST['term_meta'] ) ) {
		$t_id = $term_id;
		

		$term_meta = get_option( 'taxonomy_'.$t_id );
		$term_meta['project_type'] = $_POST['term_meta']['project_type'];

		
		// Save the option array.
		update_option( 'taxonomy_'.$t_id, $term_meta );
	}
}  
add_action( 'edited_phonak-projects', 'save_phonak_projects_custom_meta', 10, 2 );  
add_action( 'create_phonak-projects', 'save_phonak_projects_custom_meta', 10, 2 );