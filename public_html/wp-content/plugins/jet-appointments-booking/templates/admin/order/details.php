<?php
/**
 * Admin order details
 */
?>
<hr>
<h3><?php _e( 'Appointment Details', 'jet-appointment-booking' ); ?></h3>
<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">
	<?php
		foreach ( $details as $item ) {
			echo '<li>';
				if ( ! empty( $item['key'] ) ) {
					echo $item['key'] . ': ';
				}

				if ( ! empty( $item['is_html'] ) ) {
					echo $item['display'];
				} else {
					echo '<strong>' . $item['display'] . '</strong>';
				}

			echo '</li>';
		}
	?>
</ul>
