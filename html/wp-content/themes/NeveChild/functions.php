<?php
/*This file is part of NeveChild, neve child theme.

All functions of this file will be loaded before of parent theme functions.
Learn more at https://codex.wordpress.org/Child_Themes.

Note: this function loads the parent stylesheet before, then child theme stylesheet
(leave it in place unless you know what you are doing.)
*/

if ( ! function_exists( 'suffice_child_enqueue_child_styles' ) ) {
	function NeveChild_enqueue_child_styles() {
	    // loading parent style
	    wp_register_style(
	      'parente2-style',
	      get_template_directory_uri() . '/style.css'
	    );

	    wp_enqueue_style( 'parente2-style' );
	    // loading child style
	    wp_register_style(
	      'childe2-style',
	      get_stylesheet_directory_uri() . '/style.css'
	    );
	    wp_enqueue_style( 'childe2-style');
	 }
}
add_action( 'wp_enqueue_scripts', 'NeveChild_enqueue_child_styles' );

/*Write here your own functions */

add_action('after_setup_theme','my_add_role_function');

function my_add_role_function(){
    $roles_set = get_option('my_roles_are_set');
    if(!$roles_set){
        add_role('expert', 'Expert', array(
            'read' => true, // True allows that capability, False specifically removes it.
            'edit_posts' => true,
            'delete_posts' => true,
            'upload_files' => true 
        ));
        update_option('my_roles_are_set',true);
    }
}