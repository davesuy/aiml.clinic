<?php
/**
 * The header for Astra Theme.
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Astra
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?><!DOCTYPE html>
<?php astra_html_before(); ?>
<html <?php language_attributes(); ?>>
<head>
<?php astra_head_top(); ?>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="https://gmpg.org/xfn/11">

<?php wp_head(); ?>
<?php astra_head_bottom(); ?>
</head>

<body <?php astra_schema_body(); ?> <?php body_class(); ?>>
<?php astra_body_top(); ?>
<?php wp_body_open(); ?>
<div 
<?php
	echo astra_attr(
		'site',
		array(
			'id'    => 'page',
			'class' => 'hfeed site',
		)
	);
	?>
>
	<a class="skip-link screen-reader-text" href="#content"><?php echo esc_html( astra_default_strings( 'string-header-skip-link', false ) ); ?></a>
	<?php 
	astra_header_before(); 

	astra_header(); 

	astra_header_after();

	astra_content_before(); 
	?>
	<div id="content" class="site-content">
		<div class="ast-container">
		<?php astra_content_top(); ?>
<?php

$postid = 1684; 
$jet_apb_post_meta = 'jet_apb_post_meta';
$custom_schedules = get_post_meta($postid,'jet_apb_post_meta', true);
$days = 'monday';
$val_fr = '05:00';
$val_to = '17:00';


function workingHoursfindandReplace($array, $days, $from, $to) {

	if(!empty($array)) {

		$value_out = array();

		foreach($array as $key => $value)
		{ 
					
			
			if(!empty($value['working_hours'])) {

				$ax[] =  array('from' => $from, 'to' => $to);
				$get_days = $value['working_hours'][$days];
				
				//echo '<pre>'.print_r($get_days, true).'</pre>';

				//echo '<pre>'.print_r($ax , true).'</pre>';

				//$value['working_hours'][$days][] = array();

				if($ax !== $get_days) {

					$slice = $value['working_hours'][$days];
					// array_pop($slice);

					//echo '<pre>'.print_r( count($slice), true).'</pre>';

					//for($x = 1; $x <= count($slice); $x++) {
						$value['working_hours'][$days][0] = array('from' => $from, 'to' => $to);
					//}

					

				
					
				}
				
				$value_out = $value;
			
		
			}

		
		}

	
		return $value_out;
	}

	
	
	//echo '<pre>'.print_r($value_out, true).'</pre>';
}


$whf_arr['custom_schedule'] = workingHoursfindandReplace($custom_schedules, $days, $val_fr, $val_to );
$post_id_arr = array('ID' => $postid);

$whf_postid_merge = array_merge($whf_arr,$post_id_arr );



if($custom_schedules !== $whf_postid_merge) {
	//delete_post_meta($postid, $jet_apb_post_meta, $whf_postid_merge);

	//update_post_meta($postid, $jet_apb_post_meta, $whf_postid_merge);

}

/* Jquery functionality validation for duplicates schedule */


//echo '<pre>'.print_r($whf_postid_merge, true).'</pre>';

//echo '<pre>'.print_r($custom_schedules, true).'</pre>';



