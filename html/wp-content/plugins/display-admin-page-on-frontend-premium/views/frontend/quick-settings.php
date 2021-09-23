<button class="vg-frontend-admin-quick-settings-toggle">x</button>
<form class="vg-frontend-admin-quick-settings">
	<a href='https://wpfrontendadmin.com/?utm_source=wp-admin&utm_campaign=logo&utm_medium=welcome-page' target="_blank" class="logo-wrapper">
		<img src="<?php
		echo esc_url(VG_Admin_To_Frontend_Obj()->args['logo']);
		?>" />
	</a>
    <div class="field inline-buttons">
		<a href="<?php echo esc_url(WPFA_Options_Obj()->get_settings_page_url()); ?>" target="_blank" class="inline-button"><?php _e('Global settings', VG_Admin_To_Frontend::$textname); ?></a>
		<a href="<?php echo esc_url($help_url); ?>" target="_blank" class="inline-button"><?php _e('I need help', VG_Admin_To_Frontend::$textname); ?></a>
		<a href="#" class="inline-button expand-common-errors"><?php _e('Solution for common errors', VG_Admin_To_Frontend::$textname); ?></a>
	</div>
    <div class="field common-errors">		
		<?php printf(__('<ol>
			<li>Current admin page URL: %s. If this is the wrong URL, you can <a href="%s" target="_blank">edit the page</a> and change the URL in the shortcode </li>
			<li class="required-capability-target">If you can not view the page after log in, you need to use one of these roles:</li>
			<li>You can get help instantly in the <a href="https://wpfrontendadmin.com/contact/?utm_source=wp-admin&utm_campaign=logo&utm_medium=welcome-page&utm_term=%s" target="_blank">live chat</a> on our website</li>
		</ol>', VG_Admin_To_Frontend::$textname), esc_url($page_url), admin_url('post.php?post=' . get_the_ID() . '&action=edit'), esc_url($help_url)); ?>
	</div>
	<hr>
    <div class="field">
        <label><?php _e('Page title:', VG_Admin_To_Frontend::$textname); ?> <input type="text" name="post_title" value="<?php echo esc_attr(get_the_title()); ?>"></label>
	</div>
    <div class="field">
        <label><?php _e('URL slug:', VG_Admin_To_Frontend::$textname); ?> <input type="text" name="post_name" value="<?php
			echo esc_attr($post->post_name);
			?>"></label>
	</div>
	<div class="field">
		<label>
			<?php _e('Page template:', VG_Admin_To_Frontend::$textname); ?> <a href="#"  title="<?php esc_attr_e('We recommend a full-width template.', VG_Admin_To_Frontend::$textname); ?>">(?)</a>
			<select name="page_template">
				<option value=""><?php _e('Default', VG_Admin_To_Frontend::$textname); ?></option>
				<?php
				foreach ($templates as $template_filename => $template_name) {
					?>
					<option value="<?php echo $template_filename; ?>" <?php selected($template_filename, $current_template); ?>><?php echo $template_name; ?></option>
					<?php
				}
				?>
			</select>
		</label>
	</div>
	<div class="field">
        <label><?php _e('Add page to this menu:', VG_Admin_To_Frontend::$textname); ?> 
			<select name="menu">
				<?php
				if (empty($menus)) {
					?>
					<option value=""><?php _e('No menus found', VG_Admin_To_Frontend::$textname); ?></option>
					<?php
				} else {
					?>
					<option value=""><?php _e('- Select -', VG_Admin_To_Frontend::$textname); ?></option>
					<?php
					foreach ($menus as $menu) {
						?>
						<option value="<?php echo $menu->term_id; ?>"><?php echo $menu->name; ?></option>
						<?php
					}
				}
				?>
			</select>
			<?php
			if (empty($menus)) {
				?>
				<a href="<?php echo esc_url(admin_url('nav-menus.php')); ?>" target="_blank"><?php _e('Create menu', VG_Admin_To_Frontend::$textname); ?> </a>
			<?php } ?>
		</label>
	</div>
	<hr>
	<div class="field">
		<button class="hide-elements-trigger"><?php _e('Hide element', VG_Admin_To_Frontend::$textname); ?></button><br/>
		<a class="show-elements-trigger" href="#"><?php _e('Show all elements', VG_Admin_To_Frontend::$textname); ?></a>		
		<input type="hidden" class="hide-elements-input" name="vgfa_hidden_elements" value="<?php echo esc_attr(get_post_meta(get_the_ID(), 'vgfa_hidden_elements', true)); ?>">
	</div>
	<hr>
	<div class="field hide-notices">
		<label>
			<input type="hidden" name="vgfa_hide_notices" value="">
			<input type="checkbox" name="vgfa_hide_notices" <?php checked((bool) VG_Admin_To_Frontend_Obj()->get_settings('disable_all_admin_notices')); ?>> <?php _e('Hide notices added by other plugins or themes?', VG_Admin_To_Frontend::$textname); ?> <a href="#" title="<?php esc_attr_e('For example, notices related to wp updates, messages from plugins asking for reviews, etc.', VG_Admin_To_Frontend::$textname); ?>">(?)</a>
		</label>

		<hr>
	</div>
	<?php do_action('wp_frontend_admin/quick_settings/after_fields', get_post(get_the_ID())); ?>
	<div class="field">
		<button class="vg-frontend-admin-save-button" data-saving-text="<?php _e('Saving...', VG_Admin_To_Frontend::$textname); ?>"><?php _e('Save', VG_Admin_To_Frontend::$textname); ?></button>
	</div>	
	<input type="hidden" name="action" value="vg_frontend_admin_save_quick_settings">
	<input type="hidden" name="ID" value="<?php echo get_the_ID(); ?>">
	<?php wp_nonce_field('vg_frontend_admin_save_quick_settings'); ?>
</form>