<?php
/**
 * Plugin Name: Woocommerce Distance-Based Shipping Calculator
 * Description: Distance-based shipping calculated via Google Maps API.
 * Version: 1.0
 * Author: R.Stern, ZJS Tech
 */

// Ensure WooCommerce is active and class loads after WooCommerce initializes
add_action('woocommerce_shipping_init', 'zjs_shipping_method_init');
function zjs_shipping_method_init() {
	if (!class_exists('WC_Shipping_Method')) {
		return; // Safety check.
	}

	// Now it's safe to include your class.
	require_once plugin_dir_path(__FILE__) . 'includes/class-zjs-shipping-method.php';
}

// Register the shipping method with WooCommerce.
add_filter('woocommerce_shipping_methods', 'zjs_register_shipping_method');
function zjs_register_shipping_method($methods) {
	$methods['zjs_distance_shipping'] = 'ZJS_Shipping_Method';
	return $methods;
}


// Optional: Check if WooCommerce is active.
add_action( 'plugins_loaded', 'zjs_check_woocommerce_active' );
function zjs_check_woocommerce_active() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function() {
			echo '<div class="error"><p><strong>Woocommerce Distance Shipping requires WooCommerce to be active.</strong></p></div>';
		} );
		return;
	}
}


