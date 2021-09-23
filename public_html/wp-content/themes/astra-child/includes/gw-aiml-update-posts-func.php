<?php

if (class_exists('GW_Update_Posts')) {

	class GW_Update_Posts_extend extends GW_Update_Posts {

		public $form_id;
		public $post_id;
		public $featured_image_field_id;

	  
		function __construct($form_id, $post_id, $featured_image_field_id) {

		  	$this->form_id = $form_id;
			$this->post_id = $post_id;
			$this->featured_image_field_id = $featured_image_field_id;


			$this->GW_Update_Posts();

			add_action('init', array($this, 'featured_image'));

		}
	

		public function GW_Update_Posts() {

			new GW_Update_Posts( array(
				'form_id' => $this->form_id,
				'post_id' => 10,
				'title'   => 2,
				'content' => 8,
				'meta'    => array(
					'organization'         => 3,
					'approximately-how-much-time' => 6,
					'please-share-your-linkedin' => 7,
					'is-there-anything-else' => 8
				),
				'featured_image' => $this->featured_image_field_id,
			) );

		}

		public function featured_image() {

			if ( isset($_POST['is_submit_'.$this->form_id]) ) {
			
				
				if(!empty($_FILES['input_'.$this->featured_image_field_id ]['tmp_name']) && file_exists($_FILES['input_'.$this->featured_image_field_id]['tmp_name'])) {
			 
					$upload = wp_upload_bits($_FILES["input_".$this->featured_image_field_id]["name"], null, file_get_contents($_FILES["input_".$this->featured_image_field_id]["tmp_name"]));
			
					if ( ! $upload_file['error'] ) {
						$post_id = $this->post_id; //set post id to which you need to set featured image
						$filename = $upload['file'];
						$wp_filetype = wp_check_filetype($filename, null);
						$attachment = array(
							'post_mime_type' => $wp_filetype['type'],
							'post_title' => sanitize_file_name($filename),
							'post_content' => '',
							'post_status' => 'inherit'
						);
			
						$attachment_id = wp_insert_attachment( $attachment, $filename, $post_id );
			
						if ( ! is_wp_error( $attachment_id ) ) {
							require_once(ABSPATH . 'wp-admin/includes/image.php');
			
							$attachment_data = wp_generate_attachment_metadata( $attachment_id, $filename );
							wp_update_attachment_metadata( $attachment_id, $attachment_data );
							set_post_thumbnail( $this->post_id, $attachment_id );
						}
					}
		
				}
			}

		}

	

	}

	$form_id = 5;

	//$post_id = 1684;
	$post_id =  get_currenst_user_post_id();

	$featured_image_field_id = 9;

	$instance = new GW_Update_Posts_extend($form_id , $post_id , $featured_image_field_id);


}


function aiml_featured_image( ) { 


	//$post_id = 1684;
	$post_id = get_currenst_user_post_id();

 
    return  get_the_post_thumbnail( $post_id, 'thumbnail', array( 'class' => 'alignleft' ) );
 
	//return $post_id;
	 
}

add_shortcode('aiml_featured_image', 'aiml_featured_image'); 

function get_currenst_user_post_id() {

	$user_id = get_current_user_id();

	$args = array(
	 'author'        =>   $user_id,
	 'orderby'       =>  'post_date',
	 'order'         =>  'ASC',
	 'posts_per_page' => 1,
	 'post_type' => 'experts'
	 );                      
 
  
	 $the_query = get_posts( $args );
	
	 // The Loop
	 if(!empty($the_query)) {
		foreach(   $the_query  as   $the_quer ) { 
			$post_id_aiml = $the_quer->ID;

			return  $post_id_aiml;
		}

	 }



    //return 1684;
}