
<p><b><?php _e('Note from WP Frontend Admin.', VG_Admin_To_Frontend::$textname); ?></b></p>
<p><?php printf(__('The shortcode has a broken URL. This URL does not exist: <a href="%s" target="_blank">%s</a> (click on this link to verify).', VG_Admin_To_Frontend::$textname), esc_url($final_url), esc_url($final_url)); ?></p>
<p><?php _e('Please edit the shortcode and use a valid URL.', VG_Admin_To_Frontend::$textname); ?></p>