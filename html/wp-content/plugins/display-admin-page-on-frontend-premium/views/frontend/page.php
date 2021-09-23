
<style>
	.vgca-iframe-wrapper iframe {
		width: 100%;
		min-width: 100%;
		max-width: 100%;
		height: 100%;
		/*position: absolute;*/
		border: 0px;
		/* Fix. Some themes add loading icons on top of the iframe due to lazy loading*/
		background-image: none !important;
		background: transparent !important;
	}
	.vgca-iframe-wrapper {
		width: 100%;
		min-width: 100%;
		max-width: 100%;
		height: 100%;
		position: relative;	
		min-height: 80px;
	}
	.lds-ring {
		display: inline-block;
		position: relative;
		width: 64px;
		height: 64px;
	}
	.lds-ring div {
		box-sizing: border-box;
		display: block;
		position: absolute;
		width: 51px;
		height: 51px;
		margin: 6px;
		border: 6px solid #fff;
		border-radius: 50%;
		animation: lds-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
		border-color: #fff transparent transparent transparent;
	}
	.lds-ring div:nth-child(1) {
		animation-delay: -0.45s;
	}
	.lds-ring div:nth-child(2) {
		animation-delay: -0.3s;
	}
	.lds-ring div:nth-child(3) {
		animation-delay: -0.15s;
	}
	@keyframes lds-ring {
		0% {
			transform: rotate(0deg);
		}
		100% {
			transform: rotate(360deg);
		}
	}
	.vgca-loading-indicator.lds-ring {
		background: #00000030;
		border-radius: 50%;
	}
	.vgca-loading-indicator {
		display: block;
		position: absolute !important;
		z-index: 9;
		left: 50%;
		transform: translate(-50%, 0);
	}

	@media only screen and (max-width: 768px){
		.vgca-iframe-wrapper.desktop-as-mobile {
			overflow-x: scroll;
		}
		.vgca-iframe-wrapper.desktop-as-mobile iframe {
			width: 1300px !important;
			max-width: 1300px !important;
			min-width: 1300px !important;
		}
	}
	.vgca-iframe-wrapper iframe.vgfa-full-screen {
		position: fixed !important;
		top: 0 !important;
		left: 0 !important;
		height: 100% !important;
		width: 100% !important;
		/*Super high number to make sure it opens on top of the wp-admin bar*/
		z-index: 1000000000;
		/*The iframe shouldn't be transparent because if the admin page is transparent, it will look on top of the frontend header*/
		background-color: white;
	}
	.vgca-iframe-wrapper.vgfa-is-loading iframe {
		visibility: hidden;
	}
	<?php if (!empty($loading_animation_color)) { ?>
		:root {
			--sk-size: 60px !important;
			--sk-color: <?php echo sanitize_hex_color($loading_animation_color); ?> !important;
		}
	<?php } ?>
</style>
<div  class="vgfa-is-loading vgca-iframe-wrapper <?php if ($use_desktop_in_mobile) echo 'desktop-as-mobile'; ?>">
	<!--Loading indicator-->
	<?php
	if (empty($loading_animation_style)) {
		?>
		<div class="lds-ring vgca-loading-indicator"><div></div><div></div><div></div><div></div></div>
	<?php } elseif ($loading_animation_style === 'plane') { ?>
		<div class="sk-plane vgca-loading-indicator"></div>
	<?php } elseif ($loading_animation_style === 'chase') { ?>
		<div class="sk-chase vgca-loading-indicator">
			<div class="sk-chase-dot"></div>
			<div class="sk-chase-dot"></div>
			<div class="sk-chase-dot"></div>
			<div class="sk-chase-dot"></div>
			<div class="sk-chase-dot"></div>
			<div class="sk-chase-dot"></div>
		</div>
	<?php } elseif ($loading_animation_style === 'bounce') { ?>
		<div class="sk-bounce vgca-loading-indicator">
			<div class="sk-bounce-dot"></div>
			<div class="sk-bounce-dot"></div>
		</div>
	<?php } elseif ($loading_animation_style === 'wave') { ?>
		<div class="sk-wave vgca-loading-indicator">
			<div class="sk-wave-rect"></div>
			<div class="sk-wave-rect"></div>
			<div class="sk-wave-rect"></div>
			<div class="sk-wave-rect"></div>
			<div class="sk-wave-rect"></div>
		</div>
	<?php } elseif ($loading_animation_style === 'pulse') { ?>
		<div class="sk-pulse vgca-loading-indicator"></div>
	<?php } elseif ($loading_animation_style === 'flow') { ?>
		<div class="sk-flow vgca-loading-indicator">
			<div class="sk-flow-dot"></div>
			<div class="sk-flow-dot"></div>
			<div class="sk-flow-dot"></div>
		</div>
	<?php } elseif ($loading_animation_style === 'swing') { ?>
		<div class="sk-swing vgca-loading-indicator">
			<div class="sk-swing-dot"></div>
			<div class="sk-swing-dot"></div>
		</div>
	<?php } elseif ($loading_animation_style === 'circle') { ?>
		<div class="sk-circle vgca-loading-indicator">
			<div class="sk-circle-dot"></div>
			<div class="sk-circle-dot"></div>
			<div class="sk-circle-dot"></div>
			<div class="sk-circle-dot"></div>
			<div class="sk-circle-dot"></div>
			<div class="sk-circle-dot"></div>
			<div class="sk-circle-dot"></div>
			<div class="sk-circle-dot"></div>
			<div class="sk-circle-dot"></div>
			<div class="sk-circle-dot"></div>
			<div class="sk-circle-dot"></div>
			<div class="sk-circle-dot"></div>
		</div>
	<?php } elseif ($loading_animation_style === 'circle-fade') { ?>
		<div class="sk-circle-fade vgca-loading-indicator">
			<div class="sk-circle-fade-dot"></div>
			<div class="sk-circle-fade-dot"></div>
			<div class="sk-circle-fade-dot"></div>
			<div class="sk-circle-fade-dot"></div>
			<div class="sk-circle-fade-dot"></div>
			<div class="sk-circle-fade-dot"></div>
			<div class="sk-circle-fade-dot"></div>
			<div class="sk-circle-fade-dot"></div>
			<div class="sk-circle-fade-dot"></div>
			<div class="sk-circle-fade-dot"></div>
			<div class="sk-circle-fade-dot"></div>
			<div class="sk-circle-fade-dot"></div>
		</div>
	<?php } elseif ($loading_animation_style === 'grid') { ?>
		<div class="sk-grid vgca-loading-indicator">
			<div class="sk-grid-cube"></div>
			<div class="sk-grid-cube"></div>
			<div class="sk-grid-cube"></div>
			<div class="sk-grid-cube"></div>
			<div class="sk-grid-cube"></div>
			<div class="sk-grid-cube"></div>
			<div class="sk-grid-cube"></div>
			<div class="sk-grid-cube"></div>
			<div class="sk-grid-cube"></div>
		</div>
	<?php } elseif ($loading_animation_style === 'fold') { ?>
		<div class="sk-fold vgca-loading-indicator">
			<div class="sk-fold-cube"></div>
			<div class="sk-fold-cube"></div>
			<div class="sk-fold-cube"></div>
			<div class="sk-fold-cube"></div>
		</div>
	<?php } elseif ($loading_animation_style === 'wander') { ?>
		<div class="sk-wander vgca-loading-indicator">
			<div class="sk-wander-cube"></div>
			<div class="sk-wander-cube"></div>
			<div class="sk-wander-cube"></div>
		</div>
	<?php } elseif ($loading_animation_style === 'none') { ?>
		<div class="vgca-loading-indicator"></div>
	<?php } ?>
	<iframe id="vgca-iframe-<?php echo crc32($page_url) . uniqid(); ?>" data-minimum-height="<?php echo preg_replace('/[^0-9]/', '', $minimum_height); ?>" data-lazy-load="<?php echo (bool) $lazy_load; ?>" data-forward-parameters="<?php echo (bool) $forward_parameters; ?>"  data-wpfa="<?php echo esc_url($final_url); ?>" src="<?php echo $lazy_load ? '' : esc_url($final_url); ?>" class="skip-lazy no-lazyload" data-no-lazy="1"></iframe>

	<p class="wpfa-loading-too-long-message" style="display: none;"><?php
		printf(__('Tip from WP Frontend Admin: The page has taken too long to load (more than 40 seconds). There is probably an error<br>You can get help in the <a href="%s" target="_blank">live chat on our website</a> or open a support ticket via email. We will help you with the setup for free.', VG_Admin_To_Frontend::$textname), 'https://wpfrontendadmin.com/contact/?utm_source=wp-admin&utm_campaign=wrong-permissions-help&utm_medium=' . (empty(VG_Admin_To_Frontend_Obj()->allowed_urls) ? 'free' : 'pro') . '-plugin');
		?></p>
</div>