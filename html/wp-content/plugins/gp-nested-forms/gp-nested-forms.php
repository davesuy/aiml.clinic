<?php
/**
 * Plugin Name: GP Nested Forms
 * Plugin URI: http://gravitywiz.com/
 * Description: Create forms within forms for better management of complex forms. Formception!
 * Version: 1.0-beta-7.8
 * Author: Gravity Wiz
 * Author URI: http://gravitywiz.com
 * License: GPL2
 * Perk: True
 * Text Domain: gp-nested-forms
 * Domain Path: /languages
 */

define( 'GP_NESTED_FORMS_VERSION', '1.0-beta-7.8' );

require 'includes/class-gp-bootstrap.php';

$gp_nested_forms_bootstrap = new GP_Bootstrap( 'class-gp-nested-forms.php', __FILE__ );