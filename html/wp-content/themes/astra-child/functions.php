<?php
/**
 * Astra Child Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Astra Child
 * @since 1.0.0
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_ASTRA_CHILD_VERSION', '1.0.0' );

include_once( get_stylesheet_directory() .'/includes/gw-update-posts.php');
include_once( get_stylesheet_directory() .'/includes/gw-aiml-update-posts-func.php');
include_once( get_stylesheet_directory() .'/includes/custom-schedule.php');


/**
 * Enqueue styles
 */
function child_enqueue_styles_scripts() {

	wp_enqueue_style( 'astra-child-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_ASTRA_CHILD_VERSION, 'all' );

    wp_enqueue_script( 'jquery');

    wp_enqueue_script( 'jquery-ui-datepicker' );

    // You need styling for the datepicker. For simplicity I've linked to the jQuery UI CSS on a CDN.
    wp_register_style( 'jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css' );
    wp_enqueue_style( 'jquery-ui' );  
   
    wp_enqueue_script( 'astra-child-theme-js', get_stylesheet_directory_uri() . '/assets/js/custom_script.js', array('jquery','gform_gravityforms', 'gform_chosen' ), false, false);

    wp_enqueue_script( 'astra-child-theme-repeater-js', get_stylesheet_directory_uri() . '/assets/js/repeater.js',array('jquery'), false, true);

    wp_enqueue_style('gravityform', get_bloginfo('url'). '/wp-content/plugins/gravityforms/css/basic.min.css', array(), '1.0.0', 'all' );
    
    wp_enqueue_script('jquery-ui-aiml', 'https://code.jquery.com/ui/1.12.1/jquery-ui.js', array('jquery'), false, true );

    wp_enqueue_style('jquery-ui-css-aiml', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css',array(), '1.0.0', 'all');

}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles_scripts', 20 );

// Update CSS within in Admin
function admin_style() {

   // wp_enqueue_style('admin-styles', get_stylesheet_directory_uri() . '/assets/css/admin.css');

}

add_action('admin_enqueue_scripts', 'admin_style');


function aiml_init() {

}

add_action('init', 'aiml_init');


function relation_post_creation($post_id, $feed, $entry, $form) {


    if($entry['form_id'] == 1) {

        // $post_idx = '<pre>'.print_r($post_id, true).'</pre>';
        // $feedx = '<pre>'.print_r($feed, true).'</pre>';
        // $entryx = '<pre>'.print_r($entry, true).'</pre>';
        // $formx = '<pre>'.print_r($form, true).'</pre>';

        //$post_id = 1652;

        $meta_key = 'relation_a365fc2dd4b6a1395dac43506c62a189';
        $meta_values = $entry['17'];

        $arr_formatted = str_replace(str_split('\/:*?"<>|+-[]'), '',  $meta_values)."\n";

        $meta_values_formatted = explode(",",$arr_formatted);

        // foreach($meta_values_formatted as $meta_value) {

        //     add_post_meta(1674,  'ress' , 'xsx-'.$meta_value, false);

        // }

        $entry_email = rgar( $entry, '2' );
        $author_id_email = get_user_by( 'email', $entry_email  );
        $author_id_email_out = $author_id_email->ID;


        $existing_pms = get_post_meta( $post_id, $meta_key, true);


       if (is_array($meta_values_formatted) || is_object($meta_values_formatted))
       {

            //add_post_meta($post_id,  'reveal' , 'xx-'.$meta_value, false);

            delete_post_meta( $post_id, $meta_key);

            foreach($meta_values_formatted as $meta_value) {

               if (  $existing_pms !=  $meta_value ) {
                
                    add_post_meta($post_id,  $meta_key, $meta_value, false);

                }

            }


        } else {

            //add_post_meta($post_id,  'reveal' , 'zz-'.$meta_values, false);
        }

        /* Wp Front End */
        $bloginfo = get_bloginfo('url');
        $content = "[vg_display_admin_page page_url='".$bloginfo."/wp-admin/post.php?post=".$post_id."&action=edit']";
        $post_name = "expert-edit-page-".$post_id;
        $guid = get_bloginfo('url')."/expert-edit-page-".$post_id;
        $hide_elem = "#astra_settings_meta_box > div.postbox-header:nth-child(1) > h2.hndle.ui-sortable-handle:nth-child(1),  #pageparentdiv,  #edit-slug-box,  #elementor-switch-mode,  #misc-publishing-actions > div.misc-pub-section.curtime.misc-pub-curtime:nth-child(4),  #minor-publishing-actions,  #misc-publishing-actions > div.misc-pub-section.misc-pub-post-status:nth-child(1),  #revisionsdiv,  #authordiv,  #astra_settings_meta_box,  #wp-content-editor-tools,  #misc-publishing-actions > div.misc-pub-section.misc-pub-revisions:nth-child(3),  #visibility,  #delete-action > a.submitdelete.deletion:nth-child(1),  #wpbody-content > div.wrap:nth-child(3) > a.page-title-action:nth-child(2),  #wpbody-content > div.wrap:nth-child(3) > h1.wp-heading-inline:nth-child(1)";
        $taxonomy_id = array(24);
        $entry_title = rgar( $entry, '1.3' ).' '.rgar( $entry, '1.6' );
        $entry_email = rgar( $entry, '2' );
        $author_id_email = get_user_by( 'email', $entry_email  );
        $author_id_email_out = $author_id_email->ID;
     
        $data_wpfrnt = array(
            'post_content' => $content,
            'post_title' =>  $entry_title,
            'post_status' => 'publish',
            'post_name' => $post_name,
            'guid' => $guid,
            'post_type' => 'page',
            'meta_input' => array(
              'vgfa_show_own_posts' => array(),
              'vgfa_hidden_elements' => $hide_elem,
              'is_wpfa_page' => 1,
              'vgfa_text_changes' => '',
              'vgfa_disabled_columns' => array(),
              'edit-user-email' => $entry_email,
              'related-experts-post-id' => $post_id,
              
            )
        );
           
        $postIdEdit = wp_insert_post( $data_wpfrnt );

        //update_post_meta(1684, 'checkmeta', 'post1-'.$postIdEdit, false);

        if(!is_wp_error($postIdEdit)) {

            $pageIdEdit =  $postIdEdit;
       
            
            wp_set_object_terms( $pageIdEdit,   $taxonomy_id  , 'experts-edit-page' );

        }
           
  
    }

 

}


add_action('gform_advancedpostcreation_post_after_creation', 'relation_post_creation', 20, 4);


function assign_author_to_edit_page() {

    if ( is_user_logged_in() ) {

        $current_user = wp_get_current_user();


        $author_loggedin_email = get_user_by( 'email', $current_user->user_email, false );
      
        $args = array(
            'post_type' => 'page',
            'tax_query' => array(
                array(
                    'taxonomy' => 'experts-edit-page',
                    'field'    => 'slug',
                    'terms'    => 'experts-dashboard',
                ),
            ),
        );

        $query_experts_dashboard = new WP_Query( $args );

        // The Loop
        if ( $query_experts_dashboard->have_posts() ) {
        
            while ( $query_experts_dashboard->have_posts() ) {
                $query_experts_dashboard->the_post();

                global $post;

               

                $id_ep = get_the_ID();
                $experts_edit_page_meta_email = get_post_meta( $id_ep, 'edit-user-email', true);
                $related_experts_post_id = get_post_meta( $id_ep, 'related-experts-post-id', true);

               

                if( $experts_edit_page_meta_email ==  $current_user->user_email  ) {

                    $author_email_inst = get_user_by( 'email', $experts_edit_page_meta_email  );
                    $author_email_id =  $author_email_inst->ID;

                  

                    $argu = array(
                        'ID' =>  $id_ep,
                        'post_author' =>  $author_email_id 
                    );

                   // echo '<pre>'.print_r($id_ep, true).'</pre>';

                    if($post->post_author != $author_email_id) {

                        wp_update_post( $argu );

                        // Add User Related Experts Post

                        //echo '<pre>'.print_r($author_email_id, true).'</pre>';
                        //update_user_meta( $author_email_id, 'user-related-experts-post-id', $id_ep );

                        $expert_related_id = get_expert_id_logged_id($author_email_id);

                        update_user_meta( $author_email_id, 'user-related-experts-post-id', $expert_related_id);
                         
                  }             

                }

            }
         
        }


        

    }

}

add_action('init', 'assign_author_to_edit_page');


function selected_expertise_query() {

    ?>

        <script>
          
        if(window.location.href.indexOf("action") > -1) {       

            //alert(2);

        } else {

            //alert(1);

            jQuery(document).ready(function($) {

                  

                var relation = getUrlParameter('meta');
                var expertise_id = relation.substr(relation.indexOf(":") + 1);

                $(".elementor-widget-posts .elementor-post a").each(function() {
                    
                  var $this = $(this);       
                  var _href = $this.attr("href"); 
                 
                   
                  //$this.attr("href", _href + '?selected_expertise=' + this.value);
                  //alert(_href);
                  
                 $this.attr("href", _href + '?selected_expertise=' + expertise_id);
                   
                  // console.log(_href + '?selected_expertise=' + this.value);
                });

                   
                   



            });

            


        }

          
            
        </script>

        <?php

     

            if(isset($_SERVER["HTTP_REFERER"])) {

                $link = ($_SERVER["HTTP_REFERER"]);

                $parse_expertise_id = substr( $link, strrpos($link, ':' )+1);    

                ?>
                <script>

                if(window.location.href.indexOf("action") < 1) {     
                                  
                   jQuery( document ).ready(function($) {

                       $( "#onload-desc" ).remove();

                   });

                }



                </script>

                <?php


               $http_ref_url = $_SERVER["HTTP_REFERER"];

               $string_containts = 'jsf=epro-posts&meta';

                if (strpos( $http_ref_url,$string_containts) !== false) {
                    aiml_get_post_content_by_id($parse_expertise_id);
                }
    
              
               
               

            } else {


                if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
                    $link = "https";
                else
                    $link = "http";

                    // Here append the common URL characters.
                $link .= "://";
                  
                // Append the host(domain name, ip) to the URL.
                $link .= $_SERVER['HTTP_HOST'];
                  
                // Append the requested resource location to the URL
                $link .= $_SERVER['REQUEST_URI'];


                $parse_expertise_id = substr( $link, strrpos($link, ':' )+1);    

                echo '<div id="onload-desc">';

                  aiml_get_post_content_by_id($parse_expertise_id);

                echo '</div>';
            }


  
          
}




//add_action( 'elementor/element/posts/section_layout/before_section_end', 'selected_expertise_query');





function aiml_get_post_content_by_id($parse_expertise_id) {


     
        $content_post = get_post($parse_expertise_id);
        $content = $content_post->post_content;
        
        echo $content;

  
}


function get_post_meta_count($query_var, $value) {


     $args = array(
        'post_type' => 'experts',
        'meta_query' => array(
            array(
                'key'     => $query_var,
                'value'   => $value,
                'compare' => 'LIKE',
            )
        ),
        'post_status'   => 'publish',

    );


    $query = new WP_Query( $args );


    $count_posts = $query->post_count; 
    

    echo '<span class="jet-filters-counter-aiml">&nbsp;<span class="counter-prefix">(</span><span class="value">'.$count_posts.'</span><span class="counter-suffix">)</span></span>';

}


//add_action( 'gform_user_registered', 'wpc_gravity_registration_autologin',  10, 4 );
/**
 * Auto login after registration.
 */
function wpc_gravity_registration_autologin( $user_id, $user_config, $entry, $password ) {

    $user = get_userdata( $user_id );
    $user_login = $user->user_login;
    $user_password = $password;
    //$user->set_role(get_option('default_role', 'subscriber'));

    wp_signon( array(
        'user_login' => $user_login,
        'user_password' =>  $user_password,
        'remember' => false

    ) );

}

add_filter( 'gform_field_value_refurl', 'populate_referral_url');
 
function populate_referral_url( $form ){
    // Grab URL from HTTP Server Var and put it into a variable
    $refurl = $_SERVER['HTTP_REFERER'];
    GFCommon::log_debug( __METHOD__ . "(): HTTP_REFERER value returned by the server: {$refurl}" );
 
    // Return that value to the form
    return esc_url_raw($refurl);
}

add_action( 'wp', 'custom_maybe_activate_user', 9 );

function custom_maybe_activate_user() {

	$template_path    = STYLESHEETPATH . '/gfur-activate-template/activate.php';
	$is_activate_page = isset( $_GET['page'] ) && $_GET['page'] === 'gf_activation';
	$is_activate_page = $is_activate_page || isset( $_GET['gfur_activation'] ); // WP 5.5 Compatibility

	if ( ! file_exists( $template_path ) || ! $is_activate_page ) {
		return;
	}

	require_once( $template_path );

	exit();
}

function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

// The shortcode function
function aiml_appointment_date_func($atts, $content = null) { 

    $a = shortcode_atts( array(
        'start_words' => '',
        'end_words' => ''
    ), $atts );
 
    if(isset($_GET['appointment_date'])) {
        
        $appDate = $_GET['appointment_date'];
        $start = $a['start_words'];
        $end = $a['end_words'];

        if(!empty($start) && !empty($end)) {

            $parsed = get_string_between( $appDate, $start , $end);

            return $parsed;
        }

       // return $start.' '.$a['end_words'];
       
       
        
    }
     
}
    // Register shortcode
add_shortcode('aiml_appointment_date', 'aiml_appointment_date_func'); 

function gf_custom_field() {

    if (class_exists('GF_Field')) {

        class ExpertiseOptions extends GF_Field {


            public $type = 'expertise_options';
            private $meta_key = 'relation_a365fc2dd4b6a1395dac43506c62a189';
        
       
            private function get_post_id() {

                $user_id = get_current_user_id();

                $args = array(
                 'author'        =>   $user_id,
                 'orderby'       =>  'post_date',
                 'order'         =>  'ASC',
                 'posts_per_page' => 1,
                 'post_type' => 'experts'
                 );                      
             
              
                 $the_query = get_posts( $args );
                  
                if(!empty($the_query)) {
                    foreach(   $the_query  as   $the_quer ) { 
                        $post_id_aiml = $the_quer->ID;

                        return  $post_id_aiml;
                    }

                }
            

              
            }

            private function get_meta_key() {
                return $this->meta_key;
            }


     
            // The rest of the code is added here...

            public function get_form_editor_field_title() {
                return esc_attr__('Expertise Options', 'txtdomain');
            }

            public function get_form_editor_field_settings() {
                return [
                    'label_setting',
                    'choices_setting',
                    'description_setting',
                    'rules_setting',
                    'error_message_setting',
                    'css_class_setting',
                    'conditional_logic_field_setting',
                    'prepopulate_field_setting',
                    'placeholder_setting',
                    'label_placement_setting',
                    'error_message_setting',
                    'css_class_setting',
                    'size_setting',
                    'enable_enhanced_ui_setting'
                ];
            }

            public function is_value_submission_array() {
                return true;
            }

            public function get_field_input($form, $value = '', $entry = null) {
                

                // if ($this->is_form_editor()) {
                //     return '';
                // }

                $id = (int) $this->id;

              
                
                $multiselect = "";
                $current_user_expertise = get_post_meta( $this->get_post_id(),  $this->get_meta_key(), false);


                if(!empty($current_user_expertise)) {
                    $multiselect .= '<select multiple="multiple" data-placeholder="Click to select..." size="7" name="input_'.$id.'[]" id="input_6_' . $id . '" class="large gfield_select">';

                    foreach( $this->choices as $expertise) {

                        $c = array_intersect($current_user_expertise, $expertise);

                        $selected = '';

                        if (count($c) > 0) {
                            $selected = 'selected';
                        
                        } 


                        $multiselect .= '<option '. $selected .' value="'.$expertise['value'].'">'.$expertise['text'].'</option>';

                        
                

                    }

                
                
                    $multiselect .= '</select>';

                
    
                    return  $multiselect;
                }
              
            }



            public function get_value_save_entry($value, $form, $input_name, $lead_id, $lead) {
                
                if (empty($value)) {
                    $value = '...';
                    delete_post_meta( $this->get_post_id(), $this->get_meta_key());
                } else {
         

                delete_post_meta( $this->get_post_id(), $this->get_meta_key());

                foreach ($value as $expertise) {
                
                    $expertise_value[] = $expertise;

                    add_post_meta($this->get_post_id(), $this->get_meta_key(), $expertise, false);
                }
                
                $value = implode(", ",$expertise_value);
                   
                }
                return 'Expertise ID: '.$value;
            }

            public function get_value_entry_list($value, $entry, $field_id, $columns, $form) {
                return __('See Expertise Edit Entry details', 'txtdomain');
            }

            public function get_value_entry_detail($value, $currency = '', $use_text = false, $format = 'html', $media = 'screen') {

                $value = maybe_unserialize($value);	

                if (empty($value)) {

                    return '';
                }

                return $value;
               
            }


            public function is_value_submission_empty($form_id) {
                $value = rgpost('input_' . $this->id);
                foreach ($value as $input) {
                    if (strlen(trim($input)) > 0) {
                        return false;
                    }
                }
                return true;
            }

            

        }

        
        GF_Fields::register(new ExpertiseOptions());
    
    }


    
    
}

add_action('init', 'gf_custom_field');


function listing_grid_action($post_obj) {
    //echo '<pre>'.print_r($post_obj, true).'sd</pre>';

    
    ?>

        <script>
          
        if(window.location.href.indexOf("action") > -1) {       

            //alert(2);

        } else {

            //alert(1);

            jQuery(document).ready(function($) {

                  
                var relation = getUrlParameter('meta');
                var expertise_id = relation.substr(relation.indexOf(":") + 1);

                $(".jet-listing-dynamic-link a, .elementor-jet-button a, .jet-listing-dynamic-image a").each(function() {
                    
                  var $this = $(this);       
                  var _href = $this.attr("href"); 
                 
                   
                  //$this.attr("href", _href + '?selected_expertise=' + this.value);
                  //alert(_href);
                  
                 $this.attr("href", _href + '?selected_expertise=' + expertise_id);
                   
                  // console.log(_href + '?selected_expertise=' + this.value);
                });

                   
                   



            });

            


        }

          
            
        </script>

        <?php

     

            if(isset($_SERVER["HTTP_REFERER"])) {

                $link = ($_SERVER["HTTP_REFERER"]);

                $parse_expertise_id = substr( $link, strrpos($link, ':' )+1);    

                ?>
                <script>

                if(window.location.href.indexOf("action") < 1) {     
                                  
                   jQuery( document ).ready(function($) {

                       $( "#onload-desc" ).remove();

                   });

                }



                </script>

                <?php


               $http_ref_url = $_SERVER["HTTP_REFERER"];

               $string_containts = 'jsf=jet-engine&meta';

                if (strpos( $http_ref_url,$string_containts) !== false) {
                   aiml_get_post_content_by_id($parse_expertise_id);
                }
    
              
            
               

            } else {


                if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
                    $link = "https";
                else
                    $link = "http";

                    // Here append the common URL characters.
                $link .= "://";
                  
                // Append the host(domain name, ip) to the URL.
                $link .= $_SERVER['HTTP_HOST'];
                  
                // Append the requested resource location to the URL
                $link .= $_SERVER['REQUEST_URI'];


                $parse_expertise_id = substr( $link, strrpos($link, ':' )+1);    

                echo '<div id="onload-desc">';

                  aiml_get_post_content_by_id($parse_expertise_id);

                echo '</div>';
            }


}

add_action( 'jet-engine/listing/grid/before', 'listing_grid_action', 10, 2 );


function get_content_by_frontend_function() {

    global $current_user; 

    //if ( is_user_logged_in() ) {

        $related_experts_post_id = get_user_meta( $current_user->ID, 'user-related-experts-post-id' , true );

        // if(!empty($related_experts_post_id )) {
        //     $my_postid = $related_experts_post_id;//This is page id or post id
        //     $content_post = get_post($my_postid);
        //     $content = $content_post->post_content;
        //    // $content = str_replace(']]>', ']]&gt;', $content);
    
        //     return  $content;
        // }
       

    //}
        return "[vg_display_admin_page page_url='https://aiml.clinic/wp-admin/post.php?post=". $related_experts_post_id."&amp;action=edit']";

}

add_shortcode('get_content_by_frontend', 'get_content_by_frontend_function');

function my_dashboard_func() {

    global $current_user; 


    $related_experts_post_id = get_user_meta( $current_user->ID, 'user-related-experts-post-id' ,true);


    $home = get_bloginfo('url').'';
    $post_page_id =   $related_experts_post_id ;

    $user = wp_get_current_user();
    $allowed_roles = array('expert', 'administrator');
    $allowed_roles_sub = array('subscriber');


    if( array_intersect($allowed_roles, $user->roles ) ) {
     
        $content ='<div id="aiml_tabs">
        <ul>
            <li><a href="#tabs-1">MY DETAILS</a></li>
            <li><a href="#tabs-2">MY SCHEDULE</a></li>
            <li><a href="#tabs-3">MY APPOINTMENTS</a></li>
        </ul>
        <div id="tabs-1">';
        $content .= do_shortcode('[gravityform id="5" title="false" description="false"]');
        $content .= '</div><div id="tabs-2">';
        $content .= do_shortcode("[vg_display_admin_page page_url='".$home."/wp-admin/post.php?post=".$post_page_id."&amp;action=edit']");
        $content .= '</div><div id="tabs-3">';  
        $content .= do_shortcode('[elementor-template id="1675"]');
        $content .= '</div></div>';  
        
    } else {

        $content ='<div id="aiml_tabs-other">
        <ul>
            <li><a href="#tabs-other-1">MY DETAILS</a></li>
            <li><a href="#tabs-other-2">MY OTHER DETAILS</a></li>
            <li><a href="#tabs-other-3">MY APPOINTMENTS</a></li>
        </ul>
        <div id="tabs-other-1">';
        $content .= do_shortcode('[gravityform id="6" title="false" description="false"]');
        $content .= '</div><div id="tabs-other-2">';
        $content .= do_shortcode('[gravityform id="8" title="false" description="false"]');
        $content .= '</div><div id="tabs-other-3">';  
        $content .= do_shortcode('[elementor-template id="1675"]');
        $content .= '</div></div>';  


    }

    return $content;

 

}

add_shortcode('my_dashboard', 'my_dashboard_func');



function get_expert_id_logged_id($author_email_id) {

    $args = array(
        'author'        =>   $author_email_id,
        'orderby'       =>  'post_date',
        'order'         =>  'ASC',
        'posts_per_page' => 1,
        'post_type' => 'experts'
        );                      
    
    
        $the_query = get_posts( $args );
        
        if(!empty($the_query)) {
            foreach(   $the_query  as   $the_quer ) { 
                $post_id_aiml = $the_quer->ID;
                
                return $post_id_aiml;
            }
    
        }
    

}
