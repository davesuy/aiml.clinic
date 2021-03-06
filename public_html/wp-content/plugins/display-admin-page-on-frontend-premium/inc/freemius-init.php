<?php

// Create a helper function for easy SDK access.

if ( !function_exists( 'dapof_fs' ) ) {
    function dapof_fs()
    {
        global  $dapof_fs ;
        
        if ( !isset( $dapof_fs ) ) {
            // Activate multisite network integration.
            if ( !defined( 'WP_FS__PRODUCT_1877_MULTISITE' ) ) {
                define( 'WP_FS__PRODUCT_1877_MULTISITE', true );
            }
          

		  // Include Freemius SDK.

		
        return $dapof_fs;
    }
    }
    // Init Freemius.
    dapof_fs();
    // Signal that SDK was initiated.
    do_action( 'dapof_fs_loaded' );
}
