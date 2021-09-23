<?php
/**
 * Filter label template
 */

$show_label = ! empty( $settings['show_label'] ) ? filter_var( $settings['show_label'], FILTER_VALIDATE_BOOLEAN ) : false;

if ( ! $show_label ) {
	return;
}

$filter_label = get_post_meta( $filter_id, '_filter_label', true );

if ( empty( $filter_label ) ) {
	return;
}

?>
<div class="jet-filter-label"><?php echo $filter_label; ?></div>
