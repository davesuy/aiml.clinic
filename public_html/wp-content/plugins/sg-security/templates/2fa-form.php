<?php if ( ! empty( $error ) ): ?>
	<div id="login_error"><strong><?php echo esc_html( $error ) ?></strong><br /></div>
<?php endif ?>

<form name="sgc2fa" id="loginform" action="<?php echo esc_url( add_query_arg( 'action', 'sgs2fa', wp_login_url() ) ); ?>" method="post">
	<p class="sg-2fa-title"><?php esc_html_e( 'In order to log in, please enter the verification code from you Authenticator app:', 'sg-security' ); ?></p>
	
	<?php if ( ! empty( $qr_url ) ) : ?>
		<div class="qr-section" style="text-align: center">
			</br>
			<img src="<?php echo esc_url( $qr_url ); ?>">
			<input type="hidden" name="sgs-2fa-setup" value="1" />
		</div>
	<?php endif ?>

	<p>
		</br>
		<label for="sgc2facode"><?php esc_html_e( 'Authentication Code:', 'sg-security' ); ?></label>
		<input name="sgc2facode" id="sgc2facode" class="input" value="" size="20" pattern="[0-9]*" autofocus />
	</p>

	<input type="hidden" name="sg-user-id"    id="sg-user-id"    value="<?php echo esc_attr( $user->ID ); ?>" />
	<input type="hidden" name="sg-2fa-nonce" id="sg-2fa-nonce" value="<?php echo esc_attr( $login_nonce ); ?>" />

	<?php if ( $interim_login ) : ?>
		<input type="hidden" name="interim-login" value="1" />
	<?php else : ?>
		<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>" />
	<?php endif; ?>

	<input type="hidden" name="rememberme" id="rememberme" value="<?php echo esc_attr( $rememberme ); ?>" />

	<?php submit_button( __( 'Authenticate', 'sg-security' ) ); ?>
</form>
