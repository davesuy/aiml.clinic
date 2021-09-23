<?php
/**
 * @var $nested_form
 * @var $entry
 * @var $modifiers
 * @var $format
 */

echo GFCommon::get_submitted_fields( $nested_form, $entry, false, false, $format, false, 'all_fields', $modifiers );