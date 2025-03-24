<?php
/**
 * Plugin Name: Just-In-Time Coupons
 * Description: Dynamically creates WooCommerce coupons from multiple reference template coupons when a user applies a matching code.
 * Version: 1.0
 * Author: Steven Wright
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'JIT_COUPONS_PATH', plugin_dir_path( __FILE__ ) );

// Includes
require_once JIT_COUPONS_PATH . 'includes/class-jit-coupons-admin.php';
require_once JIT_COUPONS_PATH . 'includes/class-jit-coupons-handler.php';

function jit_coupons_init() {
    new JIT_Coupons_Admin();
    new JIT_Coupons_Handler();
}
add_action( 'plugins_loaded', 'jit_coupons_init' );
