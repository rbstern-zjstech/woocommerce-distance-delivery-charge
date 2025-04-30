<?php

class zjs_Shipping_Method extends WC_Shipping_Method {

	public function __construct( $instance_id = 0 ) {
		$this->id                 = 'zjs_distance_shipping';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'Distance-Based Shipping', 'zjs' );
		$this->method_description = __( 'Shipping calculated per-mile using Google Maps.', 'zjs' );
		$this->supports           = array(
			'shipping-zones',
			'instance-settings',
		);

		$this->init();

		// Load settings
		$this->enabled = $this->get_option( 'enabled', 'yes' );
		$this->title   = $this->get_option( 'title', 'Distance-Based Delivery' );
		$this->per_mile_rate = $this->get_option( 'per_mile_rate', '2.75' );
		$this->minimum_delivery_charge = $this->get_option( 'minimum_delivery_charge', '15' );
	}

	public function init() {
		$this->init_form_fields();
		$this->init_settings();
	}

	public function init_form_fields() {
		$this->instance_form_fields = [
			'enabled' => [
				'title'   => __( 'Enable', 'zjs' ),
				'type'    => 'checkbox',
				'default' => 'yes'
			],
			'title' => [
				'title'       => __( 'Method Title', 'zjs' ),
				'type'        => 'text',
				'default'     => __( 'Delivery Charge', 'zjs' )
			],
			'base_location' => [
				'title'       => __( 'Ship From Address', 'zjs' ),
				'type'        => 'text',
				'description' => 'Full address of the mulch yard.'
			],
			'per_mile_rate' => [
				'title'       => __( 'Rate per Mile', 'zjs' ),
				'type'        => 'number',
				'description' => 'Shipping charge per mile.',
				'default'     => '3.00',
				'custom_attributes' => [
					'step' => '0.01',
					'min'  => '0',
				],
			],
			'minimum_delivery_charge' => [
				'title'       => __( 'Minimum Delivery Charge', 'zjs' ),
				'type'        => 'number',
				'description' => 'Minimum amount charged for delivery.',
				'default'     => '15.00',
				'custom_attributes' => [
					'step' => '0.01',
					'min'  => '0',
				],
			],
			'google_api_key' => [
				'title'       => __( 'Google Maps API Key', 'zjs' ),
				'type'        => 'text',
				'description' => 'An active Google Maps API key is required to calculate shipping charge per mile.',
			],
		];
	}

	public function calculate_shipping( $package = [] ) {
		if ( empty( $package['destination']['city'] ) || empty( $package['destination']['postcode'] ) ) {
			$this->add_rate([
				'id'    => $this->id,
				'label' => 'Click "Change Address" to enter your city and ZIP below for estimated delivery cost',
				'cost'  => 0,
			]);
			return;
		}

		$destination = sprintf(
			'%s, %s, %s %s',
			$package['destination']['address'],
			$package['destination']['city'],
			$package['destination']['state'],
			$package['destination']['postcode']
		);

		$base_address     = urlencode( $this->get_option('base_location') );
		$customer_address = urlencode( $destination );
		$api_key          = $this->get_option('google_api_key');

		$maps_url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins={$base_address}&destinations={$customer_address}&key={$api_key}";

		$response = wp_remote_get( $maps_url );

		if (is_wp_error($response)) {
			return;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset($data['rows'][0]['elements'][0]['distance']['value']) ) {
			$distance_meters = $data['rows'][0]['elements'][0]['distance']['value'];
			$distance_miles  = $distance_meters / 1609.344;

			$rate = floatval( $this->per_mile_rate );
			$minimum_charge = floatval( $this->minimum_delivery_charge );
			$cost = max( $distance_miles * $rate, $minimum_charge );

			$this->add_rate([
				'id'    => $this->id,
				'label' => sprintf('%s (%.2f miles)', $this->title, $distance_miles),
				'cost'  => round($cost, 2),
			]);
		} else {
			$this->add_rate([
				'id'    => $this->id,
				'label' => 'Unable to calculate shipping for the provided address.',
				'cost'  => 0,
			]);
		}
	}

}
